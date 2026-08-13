<?php

namespace App\Http\Controllers;

use App\Models\InvoiceScan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Invoice\ExtractedInvoice;
use App\Services\Invoice\InvoiceExtractionException;
use App\Services\Invoice\InvoiceExtractor;
use App\Services\Invoice\ProductMatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceScanController extends Controller
{
    public function index(): View
    {
        return view('invoice-scans.index', [
            'imbasan' => InvoiceScan::query()
                ->with('pembuka')
                ->withCount('items')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('invoice-scans.create', [
            'adaKunci' => filled(config('anthropic.api_key')),
            'saizMaksKb' => (int) config('anthropic.saiz_maks_kb'),
        ]);
    }

    public function store(Request $request, InvoiceExtractor $extractor, ProductMatcher $matcher): RedirectResponse
    {
        $data = $request->validate([
            'invois' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:'.(int) config('anthropic.saiz_maks_kb')],
            'catatan' => ['nullable', 'string'],
            'tindakan' => ['nullable', 'in:baca,simpan'],
        ]);

        $fail = $data['invois'];
        $simpanSahaja = ($data['tindakan'] ?? 'baca') === 'simpan';

        // Gambar disimpan dahulu supaya ia tidak hilang jika bacaan AI gagal;
        // imbasan yang tersimpan boleh dicuba baca semula bila-bila masa.
        $imbasan = InvoiceScan::create([
            'kod' => $this->kodSeterusnya(),
            'status' => 'draf',
            'laluan_fail' => $fail->store('invois', 'local'),
            'nama_fail_asal' => $fail->getClientOriginalName(),
            'jenis_mime' => $fail->getMimeType(),
            'dibuka_oleh' => $request->user()?->id,
            'catatan' => $data['catatan'] ?? null,
        ]);

        if ($simpanSahaja) {
            return redirect()->route('invoice-scans.show', $imbasan)
                ->with('status', __('wky.flash.imbas_disimpan_sahaja', ['kod' => $imbasan->kod]));
        }

        return $this->bacaImbasan($imbasan, $extractor, $matcher);
    }

    /** Membaca gambar yang sudah tersimpan dengan AI, untuk imbasan Simpan Sahaja. */
    public function read(InvoiceScan $invoiceScan, InvoiceExtractor $extractor, ProductMatcher $matcher): RedirectResponse
    {
        $this->pastikanDraf($invoiceScan);

        if (! $invoiceScan->belumDibaca()) {
            return back()->with('ralat', __('wky.imbas.ralat_sudah_dibaca'));
        }

        return $this->bacaImbasan($invoiceScan, $extractor, $matcher);
    }

    /**
     * Menghantar fail imbasan kepada AI dan menyimpan baris yang dibacanya.
     *
     * Kegagalan mengembalikan pengguna dengan mesej dan membiarkan imbasan
     * kekal belum dibaca, supaya ia boleh dicuba semula tanpa memuat naik
     * gambar yang sama sekali lagi.
     */
    private function bacaImbasan(InvoiceScan $imbasan, InvoiceExtractor $extractor, ProductMatcher $matcher): RedirectResponse
    {
        if (blank(config('anthropic.api_key'))) {
            return redirect()->route('invoice-scans.show', $imbasan)
                ->with('ralat', __('wky.imbas.ralat_tiada_kunci'));
        }

        // Panggilan penglihatan boleh mengambil masa lebih lama daripada had
        // pelaksanaan PHP yang lalai, jadi had itu dinaikkan untuk permintaan ini.
        @set_time_limit((int) config('anthropic.timeout') + 30);

        try {
            $hasil = $extractor->extract(
                Storage::disk('local')->get($imbasan->laluan_fail),
                $imbasan->jenis_mime,
            );
        } catch (InvoiceExtractionException $e) {
            return redirect()->route('invoice-scans.show', $imbasan)->with('ralat', $e->getMessage());
        }

        if ($hasil->barang === []) {
            return redirect()->route('invoice-scans.show', $imbasan)
                ->with('ralat', __('wky.imbas.ralat_tiada_baris'));
        }

        DB::transaction(function () use ($imbasan, $hasil, $matcher) {
            $imbasan->update([
                'no_invois' => $hasil->noInvois,
                'tarikh_invois' => $hasil->tarikhInvois,
                'nama_pembekal' => $hasil->namaPembekal,
                'supplier_id' => $this->tekaPembekal($hasil),
                'dibaca_pada' => now(),
            ]);

            foreach ($hasil->barang as $baris) {
                $padanan = $matcher->match($baris);

                $imbasan->items()->create([
                    'product_id' => $padanan['product']?->id,
                    'sku_invois' => $baris->sku,
                    'nama_invois' => $baris->nama,
                    'kuantiti' => $baris->kuantiti,
                    'harga_unit' => $baris->hargaUnit,
                    'kaedah_padanan' => $padanan['kaedah'],
                ]);
            }
        });

        return redirect()->route('invoice-scans.show', $imbasan)->with('status', __('wky.flash.imbas_dibaca', [
            'bil' => $imbasan->items()->count(),
            'tiada' => $imbasan->items()->whereNull('product_id')->count(),
        ]));
    }

    public function show(InvoiceScan $invoiceScan): View
    {
        $invoiceScan->load(['items.product', 'pembuka', 'pengesah', 'supplier']);

        return view('invoice-scans.show', [
            'imbasan' => $invoiceScan,
            'products' => Product::where('aktif', true)->orderBy('nama')->get(),
            'suppliers' => Supplier::orderBy('nama')->get(),
        ]);
    }

    /** Menstrim fail invois asal supaya pengguna boleh membandingkannya dengan padanan. */
    public function file(InvoiceScan $invoiceScan): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($invoiceScan->laluan_fail), 404);

        return Storage::disk('local')->response(
            $invoiceScan->laluan_fail,
            $invoiceScan->nama_fail_asal,
            ['Content-Type' => $invoiceScan->jenis_mime],
        );
    }

    /** Menyimpan pembetulan pengguna tanpa menyentuh stok. */
    public function update(Request $request, InvoiceScan $invoiceScan): RedirectResponse
    {
        $this->pastikanDraf($invoiceScan);

        $data = $request->validate([
            'no_invois' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'catatan' => ['nullable', 'string'],
            'baris' => ['array'],
            'baris.*.product_id' => ['nullable', Rule::exists('products', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'baris.*.kuantiti' => ['nullable', 'integer', 'min:1'],
            'baris.*.dilangkau' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($invoiceScan, $data, $request) {
            $invoiceScan->update([
                'no_invois' => $data['no_invois'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($invoiceScan->items as $item) {
                if (! array_key_exists($item->id, $data['baris'] ?? [])) {
                    continue;
                }

                $baris = $data['baris'][$item->id];
                $produkBaharu = $baris['product_id'] ?? null;

                $item->update([
                    'product_id' => $produkBaharu,
                    'kuantiti' => $baris['kuantiti'] ?? $item->kuantiti,
                    'dilangkau' => (bool) ($baris['dilangkau'] ?? false),
                    // Padanan yang ditukar oleh pengguna ditanda 'manual' supaya
                    // jelas mana satu datang daripada AI dan mana satu daripada orang.
                    'kaedah_padanan' => $this->kaedahPadanan($item->getOriginal('product_id'), $produkBaharu, $item->kaedah_padanan),
                ]);
            }
        });

        return back()->with('status', __('wky.flash.imbas_disimpan'));
    }

    /** Mengesahkan imbasan: setiap baris yang dipadankan menjana pergerakan stok masuk. */
    public function confirm(Request $request, InvoiceScan $invoiceScan): RedirectResponse
    {
        $this->pastikanDraf($invoiceScan);

        if ($invoiceScan->belumDibaca()) {
            return back()->with('ralat', __('wky.imbas.ralat_belum_dibaca'));
        }

        $bolehDiproses = $invoiceScan->items->filter->bolehDiproses();

        if ($bolehDiproses->isEmpty()) {
            return back()->with('ralat', __('wky.imbas.ralat_tiada_padanan'));
        }

        $direkod = 0;

        DB::transaction(function () use ($invoiceScan, $bolehDiproses, $request, &$direkod) {
            foreach ($bolehDiproses as $item) {
                // Baki dibaca semula dengan kunci di sini kerana stok mungkin
                // berubah antara imbasan dibuat dan disahkan.
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product === null) {
                    continue;
                }

                $sebelum = $product->stok;
                $selepas = $sebelum + $item->kuantiti;

                $product->update(['stok' => $selepas]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()?->id,
                    'jenis' => 'masuk',
                    'kuantiti' => $item->kuantiti,
                    'stok_sebelum' => $sebelum,
                    'stok_selepas' => $selepas,
                    'rujukan' => $invoiceScan->rujukanStok(),
                    'catatan' => __('wky.imbas.catatan_pergerakan', ['kod' => $invoiceScan->kod]),
                ]);

                $direkod++;
            }

            $invoiceScan->update([
                'status' => 'selesai',
                'disahkan_oleh' => $request->user()?->id,
                'disahkan_pada' => now(),
            ]);
        });

        return redirect()->route('invoice-scans.show', $invoiceScan)
            ->with('status', __('wky.flash.imbas_disahkan', ['kod' => $invoiceScan->kod, 'bil' => $direkod]));
    }

    /**
     * Memadam imbasan berserta gambarnya.
     *
     * Hanya draf boleh dipadam. Imbasan yang telah disahkan sudah menjana
     * pergerakan stok yang merujuk kodnya, dan membuangnya akan meninggalkan
     * pergerakan yang menunjuk kepada imbasan yang tidak lagi wujud —
     * pastikanDraf() menahannya di sini, bukan hanya menyembunyikan butang.
     */
    public function destroy(InvoiceScan $invoiceScan): RedirectResponse
    {
        $this->pastikanDraf($invoiceScan);

        // Kod dibaca dahulu kerana ia diperlukan untuk mesej selepas rekod hilang.
        $kod = $invoiceScan->kod;

        // Fail dibuang sebelum rekod kerana storan tidak boleh digulung semula:
        // rekod tanpa fail masih boleh dipadam, tetapi fail tanpa rekod menjadi
        // sampah yang tiada sesiapa boleh capai. Baris barangnya dibuang melalui
        // cascade pada kunci asing invoice_scan_items.
        Storage::disk('local')->delete($invoiceScan->laluan_fail);

        $invoiceScan->delete();

        return redirect()->route('invoice-scans.index')
            ->with('status', __('wky.flash.imbas_dipadam', ['kod' => $kod]));
    }

    private function kaedahPadanan(?int $asal, ?int $baharu, string $semasa): string
    {
        if ($baharu === null) {
            return 'tiada';
        }

        return $asal === $baharu ? $semasa : 'manual';
    }

    private function tekaPembekal(ExtractedInvoice $hasil): ?int
    {
        if ($hasil->namaPembekal === null) {
            return null;
        }

        return Supplier::whereRaw('LOWER(nama) = ?', [mb_strtolower($hasil->namaPembekal)])->value('id');
    }

    private function pastikanDraf(InvoiceScan $invoiceScan): void
    {
        if (! $invoiceScan->isDraf()) {
            throw ValidationException::withMessages([
                'status' => __('wky.flash.imbas_terkunci', [
                    'kod' => $invoiceScan->kod,
                    'status' => mb_strtolower($invoiceScan->labelStatus()),
                ]),
            ]);
        }
    }

    private function kodSeterusnya(): string
    {
        $tahun = now()->format('Y');
        $bil = InvoiceScan::where('kod', 'like', "SCAN-{$tahun}-%")->count() + 1;

        return sprintf('SCAN-%s-%03d', $tahun, $bil);
    }
}
