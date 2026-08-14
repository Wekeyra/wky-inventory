<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Stok\NilaiStok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kos seunit yang dibekukan pada setiap pergerakan stok.
 *
 * Perkara yang diuji di sini ialah satu idea: kos pada masa sesuatu pergerakan
 * berlaku mesti kekal seperti pada masa itu, walaupun harga kos produk berubah
 * selepasnya. Tanpa itu, laporan bulan lepas berubah nilainya setiap kali
 * pembekal menaikkan harga.
 */
class KosPergerakanTest extends TestCase
{
    use RefreshDatabase;

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

    private function produk(array $atribut = []): Product
    {
        return Product::create(array_merge([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'sku' => 'BRG-'.(Product::count() + 1),
            'nama' => 'Barang Ujian',
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => 100,
            'stok_minimum' => 5,
        ], $atribut));
    }

    public function test_stok_masuk_membekukan_kos_yang_ditaip(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'kos_seunit' => 12.50,
        ])->assertSessionHasNoErrors();

        $this->assertSame('12.50', StockMovement::latest('id')->first()->kos_seunit);
    }

    /*
     | Inilah sebab sebenar ciri ini wujud. Sebelum ini nilai pergerakan dikira
     | daripada harga kos produk semasa laporan dibuka, jadi menaikkan harga
     | pembekal turut menukar nilai pergerakan yang sudah lama berlaku.
     */
    public function test_kos_tidak_berubah_apabila_harga_kos_produk_dinaikkan(): void
    {
        $produk = $this->produk(['harga_kos' => 10]);

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 10,
        ]);

        $pergerakan = StockMovement::latest('id')->first();
        $this->assertSame('10.00', $pergerakan->kos_seunit);

        $produk->update(['harga_kos' => 99]);

        $this->assertSame('10.00', $pergerakan->fresh()->kos_seunit);
    }

    public function test_kos_kosong_jatuh_kepada_harga_kos_produk(): void
    {
        $produk = $this->produk(['harga_kos' => 7.25]);

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 3,
        ]);

        $this->assertSame('7.25', StockMovement::latest('id')->first()->kos_seunit);
    }

    /*
     | harga_kos lalainya 0 dan wajib diisi pada borang produk, jadi sifar
     | bermakna "tidak pernah ditetapkan". Merekodkannya sebagai 0 akan
     | mendakwa barang itu percuma, dan dakwaan itu mengalir ke setiap laporan
     | yang menjumlahkannya.
     */
    public function test_produk_tanpa_harga_kos_meninggalkan_kos_tidak_direkod(): void
    {
        $produk = $this->produk(['harga_kos' => 0]);

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 4,
        ]);

        $this->assertNull(StockMovement::latest('id')->first()->kos_seunit);
    }

    /* Sifar yang ditaip sendiri ialah satu kenyataan, bukan medan yang terlepas. */
    public function test_sifar_yang_ditaip_direkod_sebagai_sifar(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 2, 'kos_seunit' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame('0.00', StockMovement::latest('id')->first()->kos_seunit);
    }

    /*
     | Stok keluar tidak boleh memilih kosnya sendiri. Kos barang yang keluar
     | ialah kos barang itu semasa ia masuk, dan itu sudah direkod.
     */
    public function test_stok_keluar_menolak_kos_yang_dihantar(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan',
            'kuantiti' => 5, 'kos_seunit' => 999,
        ])->assertSessionHasErrors('kos_seunit');
    }

    public function test_stok_keluar_daripada_lot_membawa_kos_lot_itu(): void
    {
        $produk = $this->produk(['jejak_batch' => true, 'stok' => 0, 'harga_kos' => 10]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'kos_seunit' => 8.40, 'no_batch' => 'LOT-A',
        ])->assertSessionHasNoErrors();

        $lot = ProductBatch::where('no_batch', 'LOT-A')->firstOrFail();
        $this->assertSame('8.40', $lot->kos_seunit);

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan',
            'kuantiti' => 3, 'product_batch_id' => $lot->id,
        ])->assertSessionHasNoErrors();

        // Kos lot, bukan harga kos produk (10.00).
        $this->assertSame('8.40', StockMovement::latest('id')->first()->kos_seunit);
    }

    /*
     | Nombor lot unik bagi setiap produk, jadi kemasukan kedua bagi lot yang
     | sama tidak boleh dipecahkan menjadi dua kos. Lot itu memang mengandungi
     | unit daripada kedua-dua kemasukan, bercampur.
     */
    public function test_kemasukan_kedua_memuratakan_kos_lot_secara_berwajaran(): void
    {
        $produk = $this->produk(['jejak_batch' => true, 'stok' => 0]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'kos_seunit' => 10, 'no_batch' => 'LOT-B',
        ]);

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 30, 'kos_seunit' => 20, 'no_batch' => 'LOT-B',
        ]);

        // (10 x 10 + 30 x 20) / 40 = 17.50
        $this->assertSame('17.50', ProductBatch::where('no_batch', 'LOT-B')->first()->kos_seunit);
    }

    public function test_nilai_stok_produk_berbatch_dikira_daripada_kos_lot(): void
    {
        $produk = $this->produk(['jejak_batch' => true, 'stok' => 0, 'harga_kos' => 10]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'kos_seunit' => 3, 'no_batch' => 'LOT-C',
        ]);

        // 10 unit pada kos lot 3.00 = 30.00, bukan 10 x harga_kos produk (100.00).
        $this->actingAs($admin);
        $this->assertSame(30.0, NilaiStok::kini());
    }

    /*
     | Stok yang wujud sebelum kos mula direkod tidak boleh lenyap daripada
     | nilai stok, kerana barang itu masih di rak.
     */
    public function test_lot_tanpa_kos_jatuh_kepada_harga_kos_produk(): void
    {
        $produk = $this->produk(['jejak_batch' => true, 'stok' => 5, 'harga_kos' => 4]);

        ProductBatch::create([
            'workspace_id' => $produk->workspace_id,
            'product_id' => $produk->id,
            'no_batch' => 'LOT-LAMA',
            'kuantiti' => 5,
        ]);

        $this->actingAs($this->admin());

        $this->assertSame(20.0, NilaiStok::kini());
    }

    public function test_produk_tanpa_batch_kekal_dinilai_pada_harga_kos(): void
    {
        $this->produk(['jejak_batch' => false, 'stok' => 6, 'harga_kos' => 2.5]);

        $this->actingAs($this->admin());

        $this->assertSame(15.0, NilaiStok::kini());
    }
}
