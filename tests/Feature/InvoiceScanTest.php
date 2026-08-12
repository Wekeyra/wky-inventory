<?php

namespace Tests\Feature;

use App\Models\InvoiceScan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Invoice\ExtractedInvoice;
use App\Services\Invoice\ExtractedLine;
use App\Services\Invoice\InvoiceExtractionException;
use App\Services\Invoice\InvoiceExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['anthropic.api_key' => 'sk-ant-ujian']);
    }

    private function admin(): User
    {
        return User::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'name' => 'Admin Ujian',
            'email' => 'admin@ujian.test',
            'peranan' => 'admin',
            'password' => 'password123',
        ]);
    }

    private function produk(string $sku, string $nama, int $stok = 0): Product
    {
        return Product::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'sku' => $sku,
            'nama' => $nama,
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => $stok,
            'stok_minimum' => 5,
            'aktif' => true,
        ]);
    }

    /** Menggantikan pemanggil API sebenar dengan hasil tetap. */
    private function palsukanBacaan(ExtractedInvoice $hasil): void
    {
        $this->swap(InvoiceExtractor::class, new class($hasil) implements InvoiceExtractor
        {
            public function __construct(private readonly ExtractedInvoice $hasil) {}

            public function extract(string $kandungan, string $jenisMime): ExtractedInvoice
            {
                return $this->hasil;
            }
        });
    }

    private function palsukanRalat(string $mesej): void
    {
        $this->swap(InvoiceExtractor::class, new class($mesej) implements InvoiceExtractor
        {
            public function __construct(private readonly string $mesej) {}

            public function extract(string $kandungan, string $jenisMime): ExtractedInvoice
            {
                throw new InvoiceExtractionException($this->mesej);
            }
        });
    }

    private function muatNaik(User $pengguna): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($pengguna)->post('/imbas-invois', [
            'invois' => UploadedFile::fake()->image('invois.jpg'),
        ]);
    }

    public function test_baris_dipadankan_mengikut_sku(): void
    {
        $produk = $this->produk('ELK-001', 'Papan Kekunci');
        $this->palsukanBacaan(new ExtractedInvoice('INV-9', '2026-08-01', null, [
            new ExtractedLine('elk 001', 'Nama berbeza sepenuhnya', 12, 99.50),
        ]));

        $this->muatNaik($this->admin());

        $item = InvoiceScan::latest('id')->firstOrFail()->items()->first();
        $this->assertSame($produk->id, $item->product_id);
        $this->assertSame('sku', $item->kaedah_padanan);
        $this->assertSame(12, $item->kuantiti);
        $this->assertSame('99.50', $item->harga_unit);
    }

    public function test_baris_dipadankan_mengikut_nama_apabila_sku_tiada(): void
    {
        $produk = $this->produk('ELK-002', 'Tetikus Tanpa Wayar');
        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine(null, 'tetikus  tanpa wayar', 5, null),
        ]));

        $this->muatNaik($this->admin());

        $item = InvoiceScan::latest('id')->firstOrFail()->items()->first();
        $this->assertSame($produk->id, $item->product_id);
        $this->assertSame('nama', $item->kaedah_padanan);
    }

    public function test_baris_tanpa_padanan_ditinggalkan_untuk_pengguna(): void
    {
        $this->produk('ELK-001', 'Papan Kekunci');
        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('XYZ-999', 'Kabel HDMI 2 meter', 3, null),
        ]));

        $this->muatNaik($this->admin());

        $item = InvoiceScan::latest('id')->firstOrFail()->items()->first();
        $this->assertNull($item->product_id);
        $this->assertSame('tiada', $item->kaedah_padanan);
        $this->assertSame('Kabel HDMI 2 meter', $item->nama_invois);
    }

    public function test_pembekal_diteka_daripada_nama_pada_invois(): void
    {
        $pembekal = Supplier::create(['workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id, 'kod' => 'SUP1', 'nama' => 'Tech Supply Sdn Bhd']);
        $this->produk('A', 'Produk A');
        $this->palsukanBacaan(new ExtractedInvoice(null, null, 'tech supply sdn bhd', [
            new ExtractedLine('A', 'Produk A', 1, null),
        ]));

        $this->muatNaik($this->admin());

        $this->assertSame($pembekal->id, InvoiceScan::latest('id')->firstOrFail()->supplier_id);
    }

    public function test_muat_naik_tidak_mengubah_stok(): void
    {
        $produk = $this->produk('A', 'Produk A', stok: 40);
        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 10, null),
        ]));

        $this->muatNaik($this->admin());

        $this->assertSame(40, $produk->fresh()->stok, 'Stok tidak boleh berubah sebelum pengesahan.');
        $this->assertSame(0, StockMovement::count());
        $this->assertSame('draf', InvoiceScan::latest('id')->firstOrFail()->status);
    }

    public function test_pengesahan_menambah_stok_dan_menjana_pergerakan(): void
    {
        $produk = $this->produk('A', 'Produk A', stok: 40);
        $admin = $this->admin();
        $this->palsukanBacaan(new ExtractedInvoice('INV-2026-77', null, null, [
            new ExtractedLine('A', 'Produk A', 25, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->post("/imbas-invois/{$imbasan->id}/sahkan")
            ->assertRedirect("/imbas-invois/{$imbasan->id}");

        $this->assertSame(65, $produk->fresh()->stok);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produk->id,
            'jenis' => 'masuk',
            'kuantiti' => 25,
            'stok_sebelum' => 40,
            'stok_selepas' => 65,
            'rujukan' => 'INV-2026-77',
        ]);

        $imbasan->refresh();
        $this->assertSame('selesai', $imbasan->status);
        $this->assertSame($admin->id, $imbasan->disahkan_oleh);
    }

    public function test_baris_tanpa_padanan_dan_baris_dilangkau_tidak_diproses(): void
    {
        $dipadan = $this->produk('A', 'Produk A', stok: 10);
        $dilangkau = $this->produk('B', 'Produk B', stok: 20);
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 5, null),
            new ExtractedLine('B', 'Produk B', 7, null),
            new ExtractedLine('TIADA', 'Barang asing', 3, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();
        $item = $imbasan->items()->with('product')->get()->keyBy('nama_invois');

        $this->actingAs($admin)->put("/imbas-invois/{$imbasan->id}", [
            'baris' => [
                $item['Produk B']->id => ['product_id' => $dilangkau->id, 'kuantiti' => 7, 'dilangkau' => 1],
            ],
        ]);

        $this->actingAs($admin)->post("/imbas-invois/{$imbasan->id}/sahkan");

        $this->assertSame(15, $dipadan->fresh()->stok);
        $this->assertSame(20, $dilangkau->fresh()->stok, 'Baris yang dilangkau tidak boleh menyentuh stok.');
        $this->assertSame(1, StockMovement::count());
    }

    public function test_pengguna_boleh_memilih_produk_untuk_baris_tak_padan(): void
    {
        $produk = $this->produk('A', 'Produk A', stok: 0);
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('KOD-ASING', 'Nama asing', 9, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();
        $item = $imbasan->items()->first();

        $this->actingAs($admin)->put("/imbas-invois/{$imbasan->id}", [
            'baris' => [$item->id => ['product_id' => $produk->id, 'kuantiti' => 9]],
        ])->assertSessionHas('status');

        $item->refresh();
        $this->assertSame($produk->id, $item->product_id);
        $this->assertSame('manual', $item->kaedah_padanan, 'Pilihan pengguna mesti dibezakan daripada padanan AI.');

        $this->actingAs($admin)->post("/imbas-invois/{$imbasan->id}/sahkan");
        $this->assertSame(9, $produk->fresh()->stok);
    }

    public function test_pengesahan_tanpa_sebarang_padanan_ditolak(): void
    {
        $this->produk('A', 'Produk A', stok: 10);
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('TIADA', 'Barang asing', 3, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->from("/imbas-invois/{$imbasan->id}")
            ->post("/imbas-invois/{$imbasan->id}/sahkan")
            ->assertSessionHas('ralat');

        $this->assertSame('draf', $imbasan->fresh()->status);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_imbasan_selesai_tidak_boleh_diubah_lagi(): void
    {
        $produk = $this->produk('A', 'Produk A', stok: 0);
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 4, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();
        $this->actingAs($admin)->post("/imbas-invois/{$imbasan->id}/sahkan");

        $this->actingAs($admin)
            ->put("/imbas-invois/{$imbasan->id}", ['baris' => []])
            ->assertSessionHasErrors('status');

        $this->assertSame(4, $produk->fresh()->stok, 'Stok kekal seperti selepas pengesahan pertama.');
    }

    public function test_membatalkan_imbasan_tidak_merekod_stok(): void
    {
        $produk = $this->produk('A', 'Produk A', stok: 10);
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 5, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->delete("/imbas-invois/{$imbasan->id}")
            ->assertRedirect('/imbas-invois');

        $this->assertSame('dibatalkan', $imbasan->fresh()->status);
        $this->assertSame(10, $produk->fresh()->stok);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_ralat_pembacaan_tidak_mencipta_imbasan(): void
    {
        $this->produk('A', 'Produk A');
        $this->palsukanRalat('Perkhidmatan AI sedang sibuk.');

        $this->muatNaik($this->admin())->assertSessionHas('ralat', 'Perkhidmatan AI sedang sibuk.');

        $this->assertSame(0, InvoiceScan::count());
    }

    public function test_invois_tanpa_baris_ditolak(): void
    {
        $this->produk('A', 'Produk A');
        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, []));

        $this->muatNaik($this->admin())->assertSessionHas('ralat');

        $this->assertSame(0, InvoiceScan::count());
    }

    public function test_tanpa_kunci_api_permintaan_tidak_dihantar(): void
    {
        config(['anthropic.api_key' => null]);
        $this->produk('A', 'Produk A');

        // Pengekstrak sengaja dibiarkan melempar: jika ia dipanggil, ujian gagal
        // dengan mesej yang salah dan bukan mesej "tiada kunci".
        $this->palsukanRalat('API tidak sepatutnya dipanggil.');

        $this->muatNaik($this->admin())
            ->assertSessionHas('ralat', __('wky.imbas.ralat_tiada_kunci'));

        $this->assertSame(0, InvoiceScan::count());
    }

    public function test_jenis_fail_tidak_disokong_ditolak(): void
    {
        $this->produk('A', 'Produk A');

        $this->actingAs($this->admin())
            ->from('/imbas-invois/create')
            ->post('/imbas-invois', ['invois' => UploadedFile::fake()->create('data.zip', 10)])
            ->assertSessionHasErrors('invois');

        $this->assertSame(0, InvoiceScan::count());
    }

    public function test_halaman_imbas_invois_boleh_dipapar(): void
    {
        $this->produk('A', 'Produk A');
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 2, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        foreach (['/imbas-invois', '/imbas-invois/create', "/imbas-invois/{$imbasan->id}"] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get("/imbas-invois/{$imbasan->id}/fail")->assertOk();
    }

    /**
     * Menguji pengekstrak sebenar dan bukan yang palsu. Penolakan jenis fail
     * berlaku sebelum sebarang panggilan rangkaian, jadi ujian ini melindungi
     * pendawaian SDK tanpa memerlukan kunci API.
     */
    public function test_pengekstrak_sebenar_menolak_jenis_fail_tidak_disokong(): void
    {
        $extractor = $this->app->make(\App\Services\Invoice\ClaudeInvoiceExtractor::class);

        $this->expectException(InvoiceExtractionException::class);
        $this->expectExceptionMessage(__('wky.imbas.ralat_jenis_fail'));

        $extractor->extract('kandungan', 'application/zip');
    }

    public function test_kod_imbasan_dijana_mengikut_turutan(): void
    {
        $this->produk('A', 'Produk A');
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 1, null),
        ]));

        $this->muatNaik($admin);
        $this->muatNaik($admin);

        $tahun = now()->format('Y');
        $this->assertSame(
            ["SCAN-{$tahun}-001", "SCAN-{$tahun}-002"],
            InvoiceScan::orderBy('id')->pluck('kod')->all(),
        );
    }
}
