<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Services\Stok\BakiLokasi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $movements = StockMovement::query()
            ->with(['product', 'user', 'batch', 'location', 'tujuan'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->string('jenis')->toString()))
            ->when($request->filled('sebab'), fn ($q) => $q->where('sebab', $request->string('sebab')->toString()))
            // Pemindahan menyentuh dua gudang, jadi tapisan lokasi menangkapnya
            // dari kedua-dua belah — senarai gudang tujuan sepatutnya
            // menunjukkan barang yang masuk ke situ juga.
            ->when($request->filled('location_id'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('location_id', $request->integer('location_id'))
                ->orWhere('location_tujuan_id', $request->integer('location_id'))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('stock.index', [
            'movements' => $movements,
            'products' => Product::orderBy('nama')->get(),
            'locations' => Location::orderByDesc('lalai')->orderBy('nama')->get(),
            'sebabPilihan' => self::sebabPilihan(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('stock.form', [
            // Batch disertakan supaya borang boleh menunjukkan lot mana yang
            // masih ada baki sebaik sahaja produk dipilih, tanpa satu lagi
            // pusingan ke pelayan.
            'products' => Product::where('aktif', true)
                ->with([
                    'batches' => fn ($q) => $q->adaBaki()->orderByRaw('tarikh_luput is null, tarikh_luput'),
                    'balances',
                ])
                ->orderBy('nama')
                ->get(),
            'terpilih' => $request->integer('product_id') ?: null,
            'locations' => Location::aktif()->orderByDesc('lalai')->orderBy('nama')->get(),
            'lokasiTerpilih' => $request->integer('location_id') ?: Location::lalai()?->id,
            'sebabPilihan' => self::sebabPilihan(),
        ]);
    }

    /**
     * Sebab yang dibenarkan, berpasangan dengan labelnya, dikumpulkan mengikut jenis.
     *
     * Borang membina pilihannya daripada senarai yang sama seperti yang
     * disemak oleh pengesahan, jadi pilihan yang dipaparkan tidak boleh
     * menjadi pilihan yang ditolak.
     *
     * @return array<string, array<string, string>>
     */
    public static function sebabPilihan(): array
    {
        return array_map(
            fn (array $senarai) => array_combine(
                $senarai,
                array_map(fn (string $sebab) => __('wky.sebab.' . $sebab), $senarai),
            ),
            StockMovement::SEBAB,
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::find($request->integer('product_id'));
        $jenis = $request->string('jenis')->toString();

        // Medan batch hanya wajib apabila produk itu memang dijejak batchnya,
        // dan hanya pada arah yang menyentuh lot: pelarasan menetapkan baki
        // keseluruhan produk dan tidak menyebut lot mana yang berubah.
        $perluBatch = $product?->jejak_batch && in_array($jenis, ['masuk', 'keluar'], true);

        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            // Borang sentiasa menghantar lokasi, tetapi ia tidak wajib di sini:
            // permintaan tanpa lokasi mendarat di gudang lalai. Syarikat satu
            // premis tidak pernah perlu memikirkan modul gudang langsung, dan
            // aliran lama tidak terhenti kerana satu medan baharu.
            'location_id' => ['nullable', Rule::exists('locations', 'id')
                ->where('workspace_id', $request->user()->workspace_id)
                ->where('aktif', true)],
            // 'pindah' tiada di sini: ia melalui modul Pemindahan Stok, kerana
            // ia menyentuh dua gudang dan mempunyai peringkat dalam perjalanan.
            'jenis' => ['required', 'in:masuk,keluar,pelarasan'],
            // Senarai sebab dikunci mengikut jenis supaya gabungan yang tidak
            // masuk akal tidak boleh disuap terus ke dalam borang.
            'sebab' => ['required', Rule::in(StockMovement::SEBAB[$jenis] ?? [])],
            'kuantiti' => ['required', 'integer', 'min:1'],
            'no_batch' => [$perluBatch && $jenis === 'masuk' ? 'required' : 'nullable', 'string', 'max:100'],
            'no_siri' => ['nullable', 'string', 'max:100'],
            'tarikh_luput' => ['nullable', 'date'],
            'product_batch_id' => [$perluBatch && $jenis === 'keluar' ? 'required' : 'nullable',
                Rule::exists('product_batches', 'id')
                    ->where('workspace_id', $request->user()->workspace_id)
                    ->where('product_id', $request->integer('product_id'))],
            'rujukan' => ['nullable', 'string', 'max:100'],
            'penerima' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
        ]);

        try {
            $pergerakan = DB::transaction(function () use ($data, $request, $perluBatch) {
                // lockForUpdate menghalang dua pengguna mengemas kini stok yang sama serentak.
                $product = Product::lockForUpdate()->findOrFail($data['product_id']);

                $lokasi = (int) ($data['location_id'] ?? Location::lalai()?->id);
                $sebelum = $product->stok;
                $selepas = $this->laraskanBaki($product, $lokasi, $data);

                $batch = $perluBatch ? $this->laraskanBatch($product, $data) : null;

                $product->update(['stok' => $selepas]);

                return StockMovement::create([
                    'product_id' => $product->id,
                    'product_batch_id' => $batch?->id,
                    'location_id' => $lokasi,
                    'user_id' => $request->user()?->id,
                    'jenis' => $data['jenis'],
                    'sebab' => $data['sebab'],
                    'kuantiti' => $data['kuantiti'],
                    'stok_sebelum' => $sebelum,
                    'stok_selepas' => $selepas,
                    'rujukan' => $data['rujukan'] ?? null,
                    // Nombor DO dijana dalam transaksi yang sama seperti
                    // pergerakan, jadi dua permintaan serentak tidak boleh
                    // berkongsi nombor dokumen yang sama.
                    'no_do' => $data['jenis'] === 'keluar' ? $this->noDoSeterusnya() : null,
                    'penerima' => $data['jenis'] === 'keluar' ? ($data['penerima'] ?? null) : null,
                    'catatan' => $data['catatan'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('ralat', $e->getMessage());
        }

        // Borang pantas dipanggil dari dashboard, jadi pengguna dikembalikan ke sana.
        $destinasi = $request->input('sumber') === 'pantas' ? 'dashboard' : 'stock.index';

        return redirect()->route($destinasi)->with('status', __(
            $pergerakan->adaDeliveryOrder() ? 'wky.flash.stok_direkod_do' : 'wky.flash.stok_direkod',
            ['no_do' => $pergerakan->no_do],
        ));
    }

    /**
     * Delivery Order untuk satu pergerakan stok keluar.
     *
     * Ia dijana daripada rekod pergerakan itu sendiri dan bukan disimpan sebagai
     * dokumen berasingan, jadi apa yang dicetak sentiasa sepadan dengan apa yang
     * benar-benar keluar daripada stok.
     */
    public function deliveryOrder(StockMovement $movement): View
    {
        abort_unless($movement->adaDeliveryOrder(), 404);

        $movement->load(['product', 'user', 'batch']);

        return view('stock.do', ['pergerakan' => $movement]);
    }

    /**
     * Melaraskan baki gudang yang terlibat, dan memulangkan jumlah stok baharu.
     *
     * Masuk dan keluar menggerakkan kedua-dua nombor sebanyak kuantiti yang
     * sama. Pelarasan pula menetapkan baki **gudang itu** kepada nilai yang
     * dimasukkan, kemudian jumlah produk dikira semula daripada semua gudang
     * berserta stok dalam perjalanan — kerana pelarasan bermaksud "inilah yang
     * sebenarnya ada di sini", dan jumlah keseluruhan ialah hasil tambahnya,
     * bukan nombor yang berdiri sendiri.
     *
     * @param  array<string, mixed>  $data
     */
    private function laraskanBaki(Product $product, int $lokasi, array $data): int
    {
        $kuantiti = (int) $data['kuantiti'];

        if ($data['jenis'] === 'pelarasan') {
            BakiLokasi::tetapkan($product, $lokasi, $kuantiti);

            return (int) $product->balances()->sum('kuantiti') + $product->dalamPerjalanan();
        }

        $delta = $data['jenis'] === 'masuk' ? $kuantiti : -$kuantiti;
        $selepas = $product->stok + $delta;

        if ($selepas < 0) {
            throw new RuntimeException(
                __('wky.flash.stok_tidak_cukup', ['baki' => $product->stok, 'unit' => $product->unit])
            );
        }

        // Baki gudang disemak oleh BakiLokasi, yang menolak penolakan melebihi
        // baki gudang itu — satu gudang tidak boleh menghantar barang yang
        // sebenarnya berada di gudang lain.
        BakiLokasi::laraskan($product, $lokasi, $delta);

        return $selepas;
    }

    /**
     * Menambah atau menolak baki lot yang terlibat.
     *
     * Stok masuk mencari batch dengan nombor yang sama sebelum menciptanya,
     * kerana kemasukan kedua bagi lot yang sama ialah tambahan kepada lot itu
     * dan bukan lot baharu yang kebetulan sama nombornya.
     */
    private function laraskanBatch(Product $product, array $data): ProductBatch
    {
        if ($data['jenis'] === 'masuk') {
            $batch = ProductBatch::lockForUpdate()->firstOrNew([
                'product_id' => $product->id,
                'no_batch' => $data['no_batch'],
            ]);

            // Tarikh luput dan nombor siri hanya ditulis apabila diisi, supaya
            // kemasukan susulan yang dibiarkan kosong tidak memadam maklumat
            // yang sudah ada pada lot itu.
            $batch->fill(array_filter([
                'no_siri' => $data['no_siri'] ?? null,
                'tarikh_luput' => $data['tarikh_luput'] ?? null,
            ]));

            $batch->kuantiti = ($batch->kuantiti ?? 0) + $data['kuantiti'];
            $batch->save();

            return $batch;
        }

        $batch = ProductBatch::lockForUpdate()->findOrFail($data['product_batch_id']);

        if ($batch->kuantiti < $data['kuantiti']) {
            throw new \RuntimeException(__('wky.flash.batch_tidak_cukup', [
                'batch' => $batch->no_batch,
                'baki' => $batch->kuantiti,
                'unit' => $product->unit,
            ]));
        }

        $batch->decrement('kuantiti', $data['kuantiti']);

        return $batch;
    }

    private function noDoSeterusnya(): string
    {
        $tahun = now()->format('Y');
        $bil = StockMovement::where('no_do', 'like', "DO-{$tahun}-%")->count() + 1;

        return sprintf('DO-%s-%03d', $tahun, $bil);
    }
}
