<?php

namespace Tests\Feature;

use App\Models\InvoiceScan;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Padanan imbasan invois dengan pesanan belian, dan halaman Analitik.
 *
 * Dua kepingan terakhir aliran perolehan: invois yang memberitahu pesanan
 * bahawa barangnya sudah sampai, dan analitik yang memberitahu apa yang patut
 * dipesan seterusnya.
 */
class AnalitikPadananPoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@ujian.test'],
            [
                'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
                'name' => 'Admin Ujian',
                'peranan' => 'admin',
                'password' => 'password123',
            ],
        );
    }

    private function produk(array $atribut = []): Product
    {
        return Product::create(array_merge([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'sku' => 'BRG-'.(Product::count() + 1),
            'nama' => 'Barang Ujian '.(Product::count() + 1),
            'unit' => 'unit',
            'harga_kos' => 5,
            'harga_jual' => 12,
            'stok' => 100,
            'stok_minimum' => 5,
        ], $atribut));
    }

    private function pesananDiluluskan(Product $produk, int $kuantiti): PurchaseOrder
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => $kuantiti, 'kos_seunit' => 5]],
        ]);

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();

        $this->actingAs($admin)->post(route('purchase-orders.submit', $pesanan));
        $this->actingAs($admin)->post(route('purchase-orders.decide', $pesanan), ['keputusan' => 'diluluskan']);

        return $pesanan->fresh();
    }

    /** Imbasan yang sudah dibaca dan sedia untuk disahkan. */
    private function imbasanSedia(Product $produk, int $kuantiti, ?PurchaseOrder $pesanan = null): InvoiceScan
    {
        $imbasan = InvoiceScan::create([
            'workspace_id' => $produk->workspace_id,
            'kod' => 'SCAN-UJIAN-'.(InvoiceScan::count() + 1),
            'status' => 'draf',
            'no_invois' => 'INV-1',
            'purchase_order_id' => $pesanan?->id,
            'laluan_fail' => 'ujian.jpg',
            'nama_fail_asal' => 'ujian.jpg',
            'jenis_mime' => 'image/jpeg',
            'dibuka_oleh' => $this->admin()->id,
            'dibaca_pada' => now(),
        ]);

        $imbasan->items()->create([
            'product_id' => $produk->id,
            'sku_invois' => $produk->sku,
            'nama_invois' => $produk->nama,
            'kuantiti' => $kuantiti,
            'harga_unit' => 5,
            'kaedah_padanan' => 'manual',
        ]);

        return $imbasan->fresh();
    }

    public function test_mengesahkan_imbasan_memajukan_pesanan_yang_dipautkan(): void
    {
        $produk = $this->produk(['stok' => 0]);
        $pesanan = $this->pesananDiluluskan($produk, 10);
        $imbasan = $this->imbasanSedia($produk, 10, $pesanan);

        $this->actingAs($this->admin())
            ->post(route('invoice-scans.confirm', $imbasan))
            ->assertSessionHasNoErrors();

        $pesanan->refresh()->load('items');

        $this->assertSame(10, $pesanan->items->first()->kuantiti_diterima);
        $this->assertSame('selesai', $pesanan->status);
        $this->assertSame(10, $produk->fresh()->stok);
    }

    public function test_imbasan_separa_mengekalkan_pesanan_terbuka(): void
    {
        $produk = $this->produk(['stok' => 0]);
        $pesanan = $this->pesananDiluluskan($produk, 10);
        $imbasan = $this->imbasanSedia($produk, 4, $pesanan);

        $this->actingAs($this->admin())->post(route('invoice-scans.confirm', $imbasan));

        $pesanan->refresh()->load('items');

        $this->assertSame(4, $pesanan->items->first()->kuantiti_diterima);
        $this->assertSame('diluluskan', $pesanan->status);
        $this->assertTrue($pesanan->diterimaSepara());
    }

    /*
     | Pembekal menghantar lebih daripada yang dipesan. Barang itu memang
     | sampai, jadi ia mesti masuk ke stok sepenuhnya — yang dihadkan hanyalah
     | berapa banyak daripadanya dikira terhadap pesanan.
     */
    public function test_lebihan_masuk_ke_stok_tetapi_tidak_melebihi_pesanan(): void
    {
        $produk = $this->produk(['stok' => 0]);
        $pesanan = $this->pesananDiluluskan($produk, 10);
        $imbasan = $this->imbasanSedia($produk, 15, $pesanan);

        $this->actingAs($this->admin())->post(route('invoice-scans.confirm', $imbasan));

        $this->assertSame(15, $produk->fresh()->stok);
        $this->assertSame(10, $pesanan->refresh()->load('items')->items->first()->kuantiti_diterima);
        $this->assertSame('selesai', $pesanan->status);
    }

    public function test_imbasan_tanpa_pesanan_kekal_seperti_biasa(): void
    {
        $produk = $this->produk(['stok' => 0]);
        $pesanan = $this->pesananDiluluskan($produk, 10);
        $imbasan = $this->imbasanSedia($produk, 10);

        $this->actingAs($this->admin())->post(route('invoice-scans.confirm', $imbasan));

        $this->assertSame(10, $produk->fresh()->stok);
        $this->assertSame(0, $pesanan->refresh()->load('items')->items->first()->kuantiti_diterima);
        $this->assertSame('diluluskan', $pesanan->status);
    }

    public function test_pesanan_draf_tidak_boleh_dipautkan(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ]);

        $draf = PurchaseOrder::latest('id')->firstOrFail();
        $imbasan = $this->imbasanSedia($produk, 5);

        $this->actingAs($admin)
            ->put(route('invoice-scans.update', $imbasan), ['purchase_order_id' => $draf->id])
            ->assertSessionHasErrors('purchase_order_id');
    }

    public function test_halaman_analitik_menyenaraikan_produk_stok_rendah(): void
    {
        $rendah = $this->produk(['nama' => 'Barang Hampir Habis', 'stok' => 2, 'stok_minimum' => 10]);
        $cukup = $this->produk(['nama' => 'Barang Cukup', 'stok' => 500, 'stok_minimum' => 10]);

        $respons = $this->actingAs($this->admin())->get(route('analytics.index'))->assertOk();

        // Kotak pilihan reorder yang diperiksa, bukan nama produk: produk yang
        // stoknya cukup tetap muncul di halaman ini melalui jadual Stok Mati,
        // jadi mencari namanya sahaja akan sentiasa berjaya.
        $respons->assertSee('name="produk['.$rendah->id.']"', false)
            ->assertDontSee('name="produk['.$cukup->id.']"', false);
    }

    /*
     | Cadangan kuantiti mesti mengambil kira kadar penggunaan, bukan jurang
     | kepada paras minimum sahaja — kalau tidak, dua produk yang bergerak pada
     | kelajuan berbeza akan dicadangkan dalam kuantiti yang sama.
     */
    public function test_cadangan_reorder_mengambil_kira_penggunaan(): void
    {
        // Bermula dengan stok yang cukup untuk pergerakan keluar itu; menolak
        // 30 daripada baki 2 akan ditolak, dan pergerakan yang tidak wujud
        // menjadikan kadar penggunaan sifar.
        $produk = $this->produk(['stok' => 100, 'stok_minimum' => 10]);
        $admin = $this->admin();

        // 30 unit keluar dalam tempoh; jurang ke paras minimum ialah 8.
        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan', 'kuantiti' => 30,
        ])->assertSessionHasNoErrors();

        // Baki diturunkan supaya produk ini masuk ke senarai reorder.
        $produk->update(['stok' => 2]);

        $respons = $this->actingAs($admin)->get(route('analytics.index', ['hari' => 30]));

        $respons->assertOk();

        // Dua atribut disemak berasingan kerana templat memisahkannya dengan
        // baris baharu; hanya satu produk berada dalam senarai reorder di sini,
        // jadi kuantiti itu tidak boleh datang daripada baris lain.
        // 30 unit / 30 hari x 30 = 30 sebulan, campur jurang 8 = 38.
        $respons->assertSee('name="produk['.$produk->id.']"', false)
            ->assertSee('value="38"', false);
    }

    public function test_borang_pesanan_menerima_cadangan_reorder(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())
            ->get(route('purchase-orders.create', ['produk' => [$produk->id => 25]]))
            ->assertOk()
            ->assertSee('value="25"', false);
    }

    public function test_stok_mati_menyenaraikan_produk_yang_tidak_pernah_bergerak(): void
    {
        $produk = $this->produk(['nama' => 'Barang Tersadai', 'stok' => 40, 'stok_minimum' => 1]);

        $this->actingAs($this->admin())
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee($produk->nama)
            ->assertSee(__('wky.analitik.tidak_pernah'));
    }

    public function test_produk_terlaris_disusun_mengikut_untung(): void
    {
        $untungTinggi = $this->produk(['nama' => 'Barang Untung', 'harga_kos' => 1, 'stok' => 100]);
        $untungRendah = $this->produk(['nama' => 'Barang Murah', 'harga_kos' => 9, 'stok' => 100]);
        $admin = $this->admin();

        // Barang Murah menjual lebih banyak unit tetapi menyumbang untung lebih kecil.
        $this->actingAs($admin)->post(route('sales.store'), [
            'baris' => [
                ['product_id' => $untungTinggi->id, 'kuantiti' => 10, 'harga_jual' => 20],
                ['product_id' => $untungRendah->id, 'kuantiti' => 50, 'harga_jual' => 10],
            ],
        ])->assertSessionHasNoErrors();

        $html = $this->actingAs($admin)->get(route('analytics.index'))->getContent();

        $this->assertLessThan(
            strpos($html, 'Barang Murah'),
            strpos($html, 'Barang Untung'),
            'Produk dengan untung lebih besar sepatutnya berada lebih tinggi dalam senarai.',
        );
    }

    public function test_tempoh_yang_tidak_dikenali_jatuh_kepada_lalai(): void
    {
        $this->produk();

        $this->actingAs($this->admin())
            ->get(route('analytics.index', ['hari' => 9999]))
            ->assertOk()
            ->assertSee(__('wky.analitik.tempoh_hari', ['hari' => 90]));
    }

    public function test_pembekal_dipaparkan_pada_cadangan_reorder(): void
    {
        $ruang = Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id;

        $pembekal = Supplier::create(['workspace_id' => $ruang, 'kod' => 'PMB-1', 'nama' => 'Pembekal Utama']);
        $this->produk(['stok' => 1, 'stok_minimum' => 10, 'supplier_id' => $pembekal->id]);

        $this->actingAs($this->admin())
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Pembekal Utama');
    }
}
