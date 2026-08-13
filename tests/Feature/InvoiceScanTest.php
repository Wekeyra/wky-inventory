<?php

namespace Tests\Feature;

use App\Models\InvoiceScan;
use App\Models\InvoiceScanItem;
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

    /**
     * Matlamat aliran ini: client hanya mengambil gambar dan menekan Confirm.
     * Produk yang belum wujud dicipta terus supaya imbasan sampai ke skrin
     * semakan dalam keadaan sedia direkod.
     */
    public function test_produk_yang_belum_wujud_dicipta_dan_dipadankan_semasa_imbasan(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('142601 010105', 'ABBA Pink Flat File', 20, 2.20),
        ]));

        $this->muatNaik($admin);

        $produk = Product::where('sku', '142601 010105')->firstOrFail();
        $this->assertSame('ABBA Pink Flat File', $produk->nama);
        $this->assertSame('2.20', $produk->harga_kos);

        // Stok hanya bergerak melalui pengesahan, sama seperti kemasukan lain.
        $this->assertSame(0, $produk->stok);

        $baris = InvoiceScanItem::firstOrFail();
        $this->assertSame($produk->id, $baris->product_id);
        $this->assertSame('auto', $baris->kaedah_padanan, 'Baris yang belum pernah dilihat sesiapa mesti boleh dibezakan daripada pilihan manusia.');
    }

    /** Selepas dicipta sekali, invois berikutnya padan mengikut SKU seperti biasa. */
    public function test_imbasan_kedua_padan_dengan_produk_yang_dicipta_imbasan_pertama(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('142601 010105', 'ABBA Pink Flat File', 20, 2.20),
        ]));
        $this->muatNaik($admin);

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('142601 010105', 'ABBA Pink Flat File', 30, 2.20),
        ]));
        $this->muatNaik($admin);

        $this->assertSame(1, Product::count(), 'Imbasan kedua tidak boleh mencipta produk berganda.');

        $barisKedua = InvoiceScanItem::latest('id')->firstOrFail();
        $this->assertSame('sku', $barisKedua->kaedah_padanan);
    }

    /**
     * Indeks padanan dimuatkan sekali sahaja untuk satu imbasan, jadi tanpa
     * pendaftaran produk baharu ke dalam indeks itu, baris kedua akan cuba
     * mencipta SKU yang sama dan melanggar keunikan di tengah imbasan.
     */
    public function test_dua_baris_berkod_sama_dalam_satu_invois_hanya_mencipta_satu_produk(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('142601 010105', 'ABBA Pink Flat File', 20, 2.20),
            new ExtractedLine('142601 010105', 'ABBA Pink Flat File', 5, 2.20),
        ]));

        $this->muatNaik($admin)->assertRedirect();

        $this->assertSame(1, Product::count());
        $this->assertSame(2, InvoiceScanItem::count());
        $this->assertSame(0, InvoiceScanItem::whereNull('product_id')->count());
    }

    /** Baris tanpa kod pembekal tetap perlu SKU, kerana medan itu wajib. */
    public function test_baris_tanpa_kod_pembekal_mendapat_sku_jana(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine(null, 'Barang Tanpa Kod', 4, null),
        ]));

        $this->muatNaik($admin);

        $produk = Product::firstOrFail();
        $this->assertSame('AUTO-0001', $produk->sku);
        $this->assertSame('0.00', $produk->harga_kos, 'Invois tanpa harga unit bermula pada 0.');
        $this->assertSame($produk->id, InvoiceScanItem::firstOrFail()->product_id);
    }

    /**
     * Aliran penuh yang diminta: ambil gambar, kemudian satu klik Confirm.
     * Tiada langkah memilih produk di antaranya.
     */
    public function test_client_boleh_terus_sahkan_selepas_imbasan_tanpa_memilih_produk(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('142601 010105', 'ABBA Pink Flat File', 20, 2.20),
            new ExtractedLine('192503 020300', 'A4 Paper', 20, 11.70),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->actingAs($admin)->post("/imbas-invois/{$imbasan->id}/sahkan")
            ->assertRedirect("/imbas-invois/{$imbasan->id}");

        $this->assertSame('selesai', $imbasan->fresh()->status);
        $this->assertSame(20, Product::where('sku', '142601 010105')->value('stok'));
        $this->assertSame(20, Product::where('sku', '192503 020300')->value('stok'));
        $this->assertSame(2, StockMovement::count());
    }

    /**
     * Imbasan sentiasa memadankan setiap baris sekarang, tetapi pengguna boleh
     * mengosongkan padanan itu semula — contohnya apabila produk yang dicipta
     * automatik itu sebenarnya salah. Jalan itu masih perlu membawa pengguna ke
     * borang produk yang sudah terisi.
     */
    public function test_borang_produk_boleh_dimulakan_daripada_baris_yang_dikosongkan(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('142601 010105', 'ABBA Pink Flat File', 20, 2.20),
        ]));

        $this->muatNaik($admin);
        $baris = InvoiceScanItem::firstOrFail();

        $this->kosongkanPadanan($admin, $baris);

        $this->actingAs($admin)->get("/products/create?baris_imbasan={$baris->id}")
            ->assertOk()
            ->assertSee('value="142601 010105"', false)
            ->assertSee('value="ABBA Pink Flat File"', false)
            ->assertSee('value="2.20"', false)
            ->assertSee('name="baris_imbasan" value="'.$baris->id.'"', false);
    }

    /** Mengosongkan padanan sesuatu baris melalui borang pembetulan. */
    private function kosongkanPadanan(User $pengguna, InvoiceScanItem $baris): void
    {
        $this->actingAs($pengguna)->put("/imbas-invois/{$baris->invoice_scan_id}", [
            'baris' => [$baris->id => ['product_id' => null, 'kuantiti' => $baris->kuantiti]],
        ]);

        $baris->refresh();
        $this->assertNull($baris->product_id, 'Prasyarat ujian: baris ini sepatutnya sudah kosong.');
    }

    /**
     * Padanan berlaku sekali sahaja semasa AI membaca invois, jadi produk yang
     * baharu dicipta tidak akan dipadankan sendiri. Tanpa pemautan ini pengguna
     * terpaksa memilih semula produk yang baru sahaja dia cipta.
     */
    public function test_produk_yang_dicipta_dari_baris_terus_dipautkan_kepada_baris_itu(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('142601 010105', 'ABBA Pink Flat File', 20, 2.20),
        ]));

        $this->muatNaik($admin);
        $baris = InvoiceScanItem::firstOrFail();

        $this->kosongkanPadanan($admin, $baris);

        $this->actingAs($admin)->post('/products', [
            'sku' => 'BETUL-1',
            'nama' => 'Nama Yang Betul',
            'unit' => 'unit',
            'harga_kos' => 2.20,
            'harga_jual' => 3.50,
            'stok_minimum' => 5,
            'baris_imbasan' => $baris->id,
        ])->assertRedirect("/imbas-invois/{$baris->invoice_scan_id}");

        $produk = Product::where('sku', 'BETUL-1')->firstOrFail();

        $baris->refresh();
        $this->assertSame($produk->id, $baris->product_id);
        $this->assertSame('manual', $baris->kaedah_padanan, 'Padanan daripada orang ditanda manual, bukan datang daripada AI.');
    }

    /**
     * Id baris yang disuap terus ke dalam borang tidak boleh menyentuh imbasan
     * syarikat lain. Produk tetap dicipta dalam ruang kerja pencipta, tetapi
     * baris syarikat lain itu kekal tidak berubah.
     */
    public function test_baris_imbasan_syarikat_lain_tidak_boleh_dipautkan(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('X-1', 'Barang Syarikat A', 3, 1.00),
        ]));

        $this->muatNaik($admin);
        $barisSyarikatA = InvoiceScanItem::firstOrFail();
        $produkAsal = $barisSyarikatA->product_id;

        $syarikatB = Workspace::create(['nama' => 'Syarikat B']);
        $adminB = User::create([
            'workspace_id' => $syarikatB->id,
            'name' => 'Admin B',
            'email' => 'admin@syarikat-b.test',
            'peranan' => 'admin',
            'password' => 'password123',
        ]);

        $this->actingAs($adminB)->post('/products', [
            'sku' => 'CUBA-1',
            'nama' => 'Cuba Curi',
            'unit' => 'unit',
            'harga_kos' => 1,
            'harga_jual' => 2,
            'stok_minimum' => 0,
            'baris_imbasan' => $barisSyarikatA->id,
        ])->assertRedirect('/products');

        $this->assertSame($produkAsal, $barisSyarikatA->fresh()->product_id, 'Baris syarikat lain mesti kekal tidak disentuh.');
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

    /**
     * Barang asing tidak lagi ditinggalkan tanpa padanan: ia menjadi produk
     * baharu. Teks asal invois tetap disimpan supaya baris itu masih boleh
     * dibandingkan dengan dokumen asal.
     */
    public function test_barang_yang_tidak_dikenali_menjadi_produk_baharu(): void
    {
        $this->produk('ELK-001', 'Papan Kekunci');
        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('XYZ-999', 'Kabel HDMI 2 meter', 3, null),
        ]));

        $this->muatNaik($this->admin());

        $item = InvoiceScan::latest('id')->firstOrFail()->items()->first();
        $this->assertSame('auto', $item->kaedah_padanan);
        $this->assertSame('Kabel HDMI 2 meter', $item->nama_invois);
        $this->assertSame('XYZ-999', $item->product->sku);
        $this->assertSame(2, Product::count(), 'Produk sedia ada tidak diganggu.');
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

    /**
     * Langkau ialah satu-satunya cara mengecualikan baris sekarang, kerana
     * setiap baris sudah pasti mempunyai produk selepas imbasan.
     */
    public function test_baris_dilangkau_tidak_diproses_walaupun_ia_berpadanan(): void
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

        // Barang asing itu kini produk baharu, jadi ia turut direkodkan.
        $this->assertSame(3, Product::where('sku', 'TIADA')->value('stok'));
        $this->assertSame(2, StockMovement::count());
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

    /**
     * Selepas produk dicipta sendiri, satu-satunya cara sampai ke keadaan
     * "tiada apa untuk direkod" ialah dengan melangkau setiap baris.
     */
    public function test_pengesahan_ditolak_apabila_setiap_baris_dilangkau(): void
    {
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('TIADA', 'Barang asing', 3, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();
        $item = $imbasan->items()->firstOrFail();

        $this->actingAs($admin)->put("/imbas-invois/{$imbasan->id}", [
            'baris' => [$item->id => ['product_id' => $item->product_id, 'kuantiti' => 3, 'dilangkau' => 1]],
        ]);

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

    public function test_memadam_imbasan_membuang_rekod_gambar_dan_barisnya(): void
    {
        $produk = $this->produk('A', 'Produk A', stok: 10);
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 5, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();
        $laluanFail = $imbasan->laluan_fail;

        Storage::disk('local')->assertExists($laluanFail);
        $this->assertSame(1, $imbasan->items()->count());

        $this->actingAs($admin)
            ->delete("/imbas-invois/{$imbasan->id}")
            ->assertRedirect('/imbas-invois');

        $this->assertDatabaseMissing('invoice_scans', ['id' => $imbasan->id]);

        // Baris barang dibuang melalui cascade, bukan oleh controller.
        $this->assertDatabaseMissing('invoice_scan_items', ['invoice_scan_id' => $imbasan->id]);

        // Fail yang tertinggal tanpa rekod menjadi sampah yang tiada sesiapa
        // boleh capai, jadi ia mesti hilang bersama rekodnya.
        Storage::disk('local')->assertMissing($laluanFail);

        $this->assertSame(10, $produk->fresh()->stok, 'Memadam imbasan tidak menyentuh stok.');
        $this->assertSame(0, StockMovement::count());
    }

    /**
     * Imbasan yang telah disahkan menjana pergerakan stok yang merujuk kodnya.
     * Memadamnya akan meninggalkan pergerakan yang menunjuk kepada imbasan yang
     * tidak lagi wujud, jadi sekatan itu mesti berada pada controller dan bukan
     * hanya pada butang yang disembunyikan.
     */
    public function test_imbasan_yang_disahkan_tidak_boleh_dipadam(): void
    {
        $this->produk('A', 'Produk A', stok: 10);
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 5, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->actingAs($admin)->post("/imbas-invois/{$imbasan->id}/sahkan");
        $this->assertSame('selesai', $imbasan->fresh()->status);

        $laluanFail = $imbasan->laluan_fail;

        $this->actingAs($admin)
            ->from("/imbas-invois/{$imbasan->id}")
            ->delete("/imbas-invois/{$imbasan->id}")
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('invoice_scans', ['id' => $imbasan->id]);
        Storage::disk('local')->assertExists($laluanFail);
    }

    public function test_butang_padam_hanya_muncul_pada_imbasan_draf(): void
    {
        $this->produk('A', 'Produk A', stok: 10);
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 5, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        // @method('DELETE') menghasilkan medan tersembunyi, bukan atribut method,
        // jadi inilah tanda sebenar kewujudan borang padam pada halaman itu.
        $medanPadam = '<input type="hidden" name="_method" value="DELETE">';

        $this->actingAs($admin)->get('/imbas-invois')
            ->assertOk()
            ->assertSee($medanPadam, false);

        $this->actingAs($admin)->post("/imbas-invois/{$imbasan->id}/sahkan");

        // Selepas disahkan, lajur Tindakan tinggal butang lihat sahaja.
        $this->actingAs($admin)->get('/imbas-invois')
            ->assertOk()
            ->assertDontSee($medanPadam, false);
    }

    /**
     * Gambar disimpan sebelum AI dipanggil, jadi kegagalan bacaan tidak
     * membuang kerja pengguna — imbasan kekal belum dibaca dan boleh dicuba
     * semula tanpa memuat naik gambar yang sama sekali lagi.
     */
    public function test_ralat_pembacaan_mengekalkan_imbasan_sebagai_belum_dibaca(): void
    {
        $this->produk('A', 'Produk A');
        $this->palsukanRalat('Perkhidmatan AI sedang sibuk.');

        $this->muatNaik($this->admin())->assertSessionHas('ralat', 'Perkhidmatan AI sedang sibuk.');

        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->assertTrue($imbasan->belumDibaca());
        $this->assertSame(0, $imbasan->items()->count());
    }

    public function test_invois_tanpa_baris_ditolak(): void
    {
        $this->produk('A', 'Produk A');
        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, []));

        $this->muatNaik($this->admin())->assertSessionHas('ralat');

        $this->assertTrue(InvoiceScan::latest('id')->firstOrFail()->belumDibaca());
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

        $this->assertTrue(InvoiceScan::latest('id')->firstOrFail()->belumDibaca());
    }

    public function test_simpan_sahaja_menyimpan_gambar_tanpa_memanggil_ai(): void
    {
        $this->produk('A', 'Produk A');
        $admin = $this->admin();

        // Jika AI dipanggil, pengekstrak ini melempar dan ujian gagal.
        $this->palsukanRalat('API tidak sepatutnya dipanggil.');

        $this->actingAs($admin)->post('/imbas-invois', [
            'invois' => UploadedFile::fake()->image('invois.jpg'),
            'tindakan' => 'simpan',
        ])->assertRedirect()->assertSessionHas('status');

        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->assertTrue($imbasan->belumDibaca());
        $this->assertSame(0, $imbasan->items()->count());
        $this->assertSame('draf', $imbasan->status);
    }

    public function test_simpan_sahaja_berfungsi_tanpa_kunci_api(): void
    {
        config(['anthropic.api_key' => null]);
        $this->produk('A', 'Produk A');

        $this->actingAs($this->admin())->post('/imbas-invois', [
            'invois' => UploadedFile::fake()->image('invois.jpg'),
            'tindakan' => 'simpan',
        ])->assertSessionHas('status')->assertSessionMissing('ralat');

        $this->assertSame(1, InvoiceScan::count());
    }

    public function test_imbasan_tersimpan_boleh_dibaca_kemudian(): void
    {
        $produk = $this->produk('A', 'Produk A', 10);
        $admin = $this->admin();

        $this->palsukanRalat('API tidak sepatutnya dipanggil.');

        $this->actingAs($admin)->post('/imbas-invois', [
            'invois' => UploadedFile::fake()->image('invois.jpg'),
            'tindakan' => 'simpan',
        ]);

        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->palsukanBacaan(new ExtractedInvoice('INV-9', null, 'Tech Supply', [
            new ExtractedLine('A', 'Produk A', 4, null),
        ]));

        $this->actingAs($admin)
            ->post("/imbas-invois/{$imbasan->id}/baca")
            ->assertRedirect(route('invoice-scans.show', $imbasan))
            ->assertSessionHas('status');

        $imbasan->refresh();

        $this->assertFalse($imbasan->belumDibaca());
        $this->assertSame('INV-9', $imbasan->no_invois);
        $this->assertSame(1, $imbasan->items()->count());
        $this->assertSame($produk->id, $imbasan->items()->first()->product_id);
    }

    public function test_imbasan_yang_sudah_dibaca_tidak_boleh_dibaca_semula(): void
    {
        $this->produk('A', 'Produk A');
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 2, null),
        ]));

        $this->muatNaik($admin);
        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->post("/imbas-invois/{$imbasan->id}/baca")
            ->assertSessionHas('ralat', __('wky.imbas.ralat_sudah_dibaca'));

        $this->assertSame(1, $imbasan->items()->count());
    }

    public function test_imbasan_belum_dibaca_tidak_boleh_disahkan(): void
    {
        $produk = $this->produk('A', 'Produk A', 10);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/imbas-invois', [
            'invois' => UploadedFile::fake()->image('invois.jpg'),
            'tindakan' => 'simpan',
        ]);

        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->post("/imbas-invois/{$imbasan->id}/sahkan")
            ->assertSessionHas('ralat');

        $this->assertSame(10, $produk->fresh()->stok);
        $this->assertSame(0, StockMovement::count());
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

    public function test_halaman_muat_naik_menawarkan_kamera(): void
    {
        $this->actingAs($this->admin())
            ->get('/imbas-invois/create')
            ->assertOk()
            ->assertSee(__('wky.imbas.ambil_gambar'))
            ->assertSee('id="modal-kamera"', false)
            ->assertSee('id="kameraVideo"', false)
            // Input sandaran untuk peranti tanpa kamera dalam halaman.
            ->assertSee('capture="environment"', false);
    }

    public function test_gambar_kamera_diterima_sebagai_invois(): void
    {
        $this->produk('A', 'Produk A');
        $admin = $this->admin();

        $this->palsukanBacaan(new ExtractedInvoice(null, null, null, [
            new ExtractedLine('A', 'Produk A', 3, null),
        ]));

        // Kamera menghantar JPEG yang dibina dalam pelayar, bukan fail yang dipilih.
        $this->actingAs($admin)->post('/imbas-invois', [
            'invois' => UploadedFile::fake()->image('invois-20260812-101500.jpg', 1600, 1200),
        ])->assertRedirect();

        $imbasan = InvoiceScan::latest('id')->firstOrFail();

        $this->assertSame('invois-20260812-101500.jpg', $imbasan->nama_fail_asal);
        $this->assertSame(1, $imbasan->items()->count());
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
