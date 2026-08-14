<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Permohonan pembelian, kelulusan, dan penerimaan barang. */
class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    /*
     | firstOrCreate dan bukan create: banyak ujian di bawah memanggil ini lebih
     | daripada sekali dalam satu ujian, dan emel pengguna unik merentas seluruh
     | sistem.
     */
    private function pengguna(string $peranan = 'admin'): User
    {
        return User::firstOrCreate(
            ['email' => $peranan.'@ujian.test'],
            [
                'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
                'name' => ucfirst($peranan).' Ujian',
                'peranan' => $peranan,
                'password' => 'password123',
            ],
        );
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
            'stok' => 0,
            'stok_minimum' => 5,
        ], $atribut));
    }

    /** Draf yang sudah diluluskan dan sedia untuk diterima. */
    private function diluluskan(Product $produk, int $kuantiti = 10, float $kos = 8): PurchaseOrder
    {
        $admin = $this->pengguna();

        $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => $kuantiti, 'kos_seunit' => $kos]],
        ]);

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();

        $this->actingAs($admin)->post(route('purchase-orders.submit', $pesanan));
        $this->actingAs($admin)->post(route('purchase-orders.decide', $pesanan), ['keputusan' => 'diluluskan']);

        return $pesanan->fresh();
    }

    public function test_permohonan_bermula_sebagai_draf_dengan_kod_berjujukan(): void
    {
        $produk = $this->produk();
        $pembekal = Supplier::create([
            'workspace_id' => $produk->workspace_id,
            'kod' => 'PMB-1',
            'nama' => 'Pembekal Ujian',
        ]);

        $this->actingAs($this->pengguna())->post(route('purchase-orders.store'), [
            'supplier_id' => $pembekal->id,
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5, 'kos_seunit' => 12]],
        ])->assertSessionHasNoErrors();

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();

        $this->assertSame('draf', $pesanan->status);
        $this->assertStringStartsWith('PO-', $pesanan->kod);
        $this->assertSame('12.00', $pesanan->items->first()->kos_seunit);
    }

    public function test_baris_tanpa_kos_mengambil_harga_kos_produk(): void
    {
        $produk = $this->produk(['harga_kos' => 6.5]);

        $this->actingAs($this->pengguna())->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 2]],
        ]);

        $this->assertSame('6.50', PurchaseOrder::latest('id')->first()->items->first()->kos_seunit);
    }

    /*
     | Selepas dihantar, isi PO ialah apa yang orang lain baca dan luluskan.
     | Menyuntingnya di belakang mereka menjadikan kelulusan itu tidak bermakna.
     */
    public function test_pesanan_yang_sudah_dihantar_tidak_boleh_disunting(): void
    {
        $produk = $this->produk();
        $admin = $this->pengguna();

        $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ]);

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();
        $this->actingAs($admin)->post(route('purchase-orders.submit', $pesanan));

        $this->actingAs($admin)->get(route('purchase-orders.edit', $pesanan))->assertForbidden();

        $this->actingAs($admin)->put(route('purchase-orders.update', $pesanan), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 999]],
        ])->assertForbidden();

        $this->assertSame(5, $pesanan->fresh()->items->first()->kuantiti);
    }

    public function test_staf_boleh_memohon_tetapi_tidak_boleh_meluluskan(): void
    {
        $produk = $this->produk();
        $staf = $this->pengguna('staf');

        $this->actingAs($staf)->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ])->assertSessionHasNoErrors();

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();
        $this->actingAs($staf)->post(route('purchase-orders.submit', $pesanan));

        $this->actingAs($staf)
            ->post(route('purchase-orders.decide', $pesanan), ['keputusan' => 'diluluskan'])
            ->assertForbidden();

        $this->assertSame('menunggu', $pesanan->fresh()->status);
    }

    public function test_admin_meluluskan_dan_keputusannya_direkod(): void
    {
        $produk = $this->produk();
        $admin = $this->pengguna();

        $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ]);

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();
        $this->actingAs($admin)->post(route('purchase-orders.submit', $pesanan));
        $this->actingAs($admin)->post(route('purchase-orders.decide', $pesanan), ['keputusan' => 'diluluskan']);

        $pesanan->refresh();

        $this->assertSame('diluluskan', $pesanan->status);
        $this->assertSame($admin->id, $pesanan->diputuskan_oleh);
        $this->assertNotNull($pesanan->diputuskan_pada);
    }

    public function test_menolak_menyimpan_sebabnya(): void
    {
        $produk = $this->produk();
        $admin = $this->pengguna();

        $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ]);

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();
        $this->actingAs($admin)->post(route('purchase-orders.submit', $pesanan));
        $this->actingAs($admin)->post(route('purchase-orders.decide', $pesanan), [
            'keputusan' => 'ditolak',
            'sebab_tolak' => 'Bajet tidak mencukupi.',
        ]);

        $pesanan->refresh();

        $this->assertSame('ditolak', $pesanan->status);
        $this->assertSame('Bajet tidak mencukupi.', $pesanan->sebab_tolak);
    }

    public function test_pesanan_belum_diluluskan_tidak_boleh_diterima(): void
    {
        $produk = $this->produk();
        $admin = $this->pengguna();

        $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ]);

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('purchase-orders.receive', $pesanan), ['terima' => [1 => 5]])
            ->assertForbidden();

        $this->assertSame(0, $produk->fresh()->stok);
    }

    /*
     | Inilah sambungan kepada aliran kos: harga yang diluluskan pada PO ialah
     | harga yang masuk ke dalam kira-kira, bukan harga kos produk yang mungkin
     | sudah berubah antara kelulusan dan penghantaran.
     */
    public function test_penerimaan_penuh_menaikkan_stok_dan_mencap_kos_po(): void
    {
        $produk = $this->produk(['harga_kos' => 10]);
        $pesanan = $this->diluluskan($produk, 10, 8);

        // Harga kos produk berubah selepas kelulusan; kos PO yang patut menang.
        $produk->update(['harga_kos' => 99]);

        $item = $pesanan->items->first();

        $this->actingAs($this->pengguna())
            ->post(route('purchase-orders.receive', $pesanan), ['terima' => [$item->id => 10]])
            ->assertSessionHasNoErrors();

        $this->assertSame(10, $produk->fresh()->stok);
        $this->assertSame('selesai', $pesanan->fresh()->status);

        $pergerakan = StockMovement::latest('id')->first();

        $this->assertSame('masuk', $pergerakan->jenis);
        $this->assertSame('pembelian', $pergerakan->sebab);
        $this->assertSame('8.00', $pergerakan->kos_seunit);
        $this->assertSame($pesanan->kod, $pergerakan->rujukan);
    }

    public function test_penerimaan_separa_mengekalkan_status_diluluskan(): void
    {
        $produk = $this->produk();
        $pesanan = $this->diluluskan($produk, 10);
        $item = $pesanan->items->first();

        $this->actingAs($this->pengguna())
            ->post(route('purchase-orders.receive', $pesanan), ['terima' => [$item->id => 4]]);

        $pesanan->refresh()->load('items');

        $this->assertSame('diluluskan', $pesanan->status);
        $this->assertSame(4, $pesanan->items->first()->kuantiti_diterima);
        $this->assertTrue($pesanan->diterimaSepara());
        $this->assertSame(4, $produk->fresh()->stok);

        // Baki menyusul, dan pesanan menutup dirinya sendiri.
        $this->actingAs($this->pengguna())
            ->post(route('purchase-orders.receive', $pesanan), ['terima' => [$item->id => 6]]);

        $this->assertSame('selesai', $pesanan->fresh()->status);
        $this->assertSame(10, $produk->fresh()->stok);
    }

    /*
     | Menerima lebih daripada yang dipesan ialah percanggahan dengan dokumen
     | yang diluluskan, bukan kelonggaran yang memudahkan.
     */
    public function test_menerima_lebih_daripada_baki_ditolak(): void
    {
        $produk = $this->produk();
        $pesanan = $this->diluluskan($produk, 10);
        $item = $pesanan->items->first();

        $this->actingAs($this->pengguna())
            ->post(route('purchase-orders.receive', $pesanan), ['terima' => [$item->id => 11]])
            ->assertSessionHas('ralat');

        $this->assertSame(0, $produk->fresh()->stok);
        $this->assertSame(0, $item->fresh()->kuantiti_diterima);
    }

    public function test_pesanan_yang_sudah_menerima_tidak_boleh_dibatalkan(): void
    {
        $produk = $this->produk();
        $pesanan = $this->diluluskan($produk, 10);
        $item = $pesanan->items->first();

        $this->actingAs($this->pengguna())
            ->post(route('purchase-orders.receive', $pesanan), ['terima' => [$item->id => 3]]);

        $this->actingAs($this->pengguna())
            ->post(route('purchase-orders.cancel', $pesanan))
            ->assertSessionHas('ralat');

        $this->assertSame('diluluskan', $pesanan->fresh()->status);
    }

    /*
     | Status akhir tiada laluan keluar. PO yang sudah diputuskan tidak boleh
     | dihidupkan semula, kerana rekod yang boleh berubah selepas diluluskan
     | bukan lagi kelulusan.
     */
    public function test_pesanan_yang_diluluskan_tidak_boleh_dihantar_semula(): void
    {
        $produk = $this->produk();
        $pesanan = $this->diluluskan($produk);

        $this->actingAs($this->pengguna())
            ->post(route('purchase-orders.submit', $pesanan))
            ->assertForbidden();
    }

    public function test_hanya_draf_boleh_dipadam(): void
    {
        $produk = $this->produk();
        $pesanan = $this->diluluskan($produk);

        $this->actingAs($this->pengguna())
            ->delete(route('purchase-orders.destroy', $pesanan))
            ->assertForbidden();

        $this->assertDatabaseHas('purchase_orders', ['id' => $pesanan->id]);
    }

    public function test_produk_berulang_digabungkan_menjadi_satu_baris(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->pengguna())->post(route('purchase-orders.store'), [
            'baris' => [
                ['product_id' => $produk->id, 'kuantiti' => 3, 'kos_seunit' => 5],
                ['product_id' => $produk->id, 'kuantiti' => 4, 'kos_seunit' => 5],
            ],
        ])->assertSessionHasNoErrors();

        $pesanan = PurchaseOrder::latest('id')->firstOrFail();

        $this->assertCount(1, $pesanan->items);
        $this->assertSame(7, $pesanan->items->first()->kuantiti);
    }

    public function test_permohonan_tanpa_baris_ditolak(): void
    {
        $this->actingAs($this->pengguna())->post(route('purchase-orders.store'), [
            'baris' => [['product_id' => '', 'kuantiti' => '']],
        ])->assertSessionHas('ralat');

        $this->assertSame(0, PurchaseOrder::count());
    }
}
