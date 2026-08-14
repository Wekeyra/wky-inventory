<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jualan dan kos barang dijual.
 *
 * Idea yang diuji: untung kasar mesti dikira daripada dua harga yang dibekukan
 * pada masa jualan — harga jual dan kos — dan bukan daripada harga produk yang
 * dibaca semula kemudian, kerana kedua-duanya boleh berubah selepas itu.
 */
class JualanCogsTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(): User
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
            'nama' => 'Barang Ujian',
            'unit' => 'unit',
            'harga_kos' => 6,
            'harga_jual' => 10,
            'stok' => 100,
            'stok_minimum' => 5,
        ], $atribut));
    }

    public function test_jualan_menolak_stok_dan_merekod_pergerakan(): void
    {
        $produk = $this->produk(['stok' => 100]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'pelanggan' => 'Kedai Ali',
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 4, 'harga_jual' => 15]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(96, $produk->fresh()->stok);

        $pergerakan = StockMovement::latest('id')->first();

        $this->assertSame('keluar', $pergerakan->jenis);
        $this->assertSame('jualan', $pergerakan->sebab);
        $this->assertSame(Sale::latest('id')->first()->kod, $pergerakan->rujukan);
    }

    public function test_untung_kasar_dikira_daripada_harga_dan_kos_yang_dibekukan(): void
    {
        $produk = $this->produk(['harga_kos' => 6]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 10, 'harga_jual' => 15]],
        ]);

        $jualan = Sale::latest('id')->first();

        $this->assertSame(150.0, $jualan->jumlahJualan());
        $this->assertSame(60.0, $jualan->kosBarangDijual());
        $this->assertSame(90.0, $jualan->untungKasar());
    }

    /*
     | Inilah sebab kedua-dua harga dibekukan. Kalau untung kasar dikira
     | daripada produk semasa laporan dibuka, menukar harga selepas jualan akan
     | menulis semula keuntungan yang sudah berlaku.
     */
    public function test_menukar_harga_produk_tidak_mengubah_jualan_lama(): void
    {
        $produk = $this->produk(['harga_kos' => 6, 'harga_jual' => 10]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ]);

        $jualan = Sale::latest('id')->first();
        $this->assertSame(20.0, $jualan->untungKasar());

        $produk->update(['harga_kos' => 99, 'harga_jual' => 200]);

        $this->assertSame(20.0, $jualan->fresh()->load('items')->untungKasar());
    }

    public function test_harga_jual_kosong_mengambil_harga_produk(): void
    {
        $produk = $this->produk(['harga_jual' => 12.5]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 2, 'harga_jual' => '']],
        ]);

        $this->assertSame('12.50', Sale::latest('id')->first()->items->first()->harga_jual);
    }

    /*
     | COGS sifar menghasilkan untung kasar yang menyamai keseluruhan jualan,
     | jadi produk tanpa harga kos meninggalkan kos sebagai tidak diketahui dan
     | jualan itu ditandakan.
     */
    public function test_produk_tanpa_harga_kos_menandakan_jualan_sebagai_tidak_lengkap(): void
    {
        $produk = $this->produk(['harga_kos' => 0]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 3, 'harga_jual' => 10]],
        ]);

        $jualan = Sale::latest('id')->first();

        $this->assertNull($jualan->items->first()->kos_seunit);
        $this->assertFalse($jualan->kosPenuh());
    }

    public function test_jualan_daripada_lot_mengambil_kos_lot_itu(): void
    {
        $produk = $this->produk(['jejak_batch' => true, 'stok' => 0, 'harga_kos' => 6]);
        $admin = $this->pengguna();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 20, 'kos_seunit' => 4.25, 'no_batch' => 'LOT-A',
        ]);

        $lot = ProductBatch::where('no_batch', 'LOT-A')->firstOrFail();

        $this->actingAs($admin)->post(route('sales.store'), [
            'baris' => [[
                'product_id' => $produk->id, 'kuantiti' => 5,
                'harga_jual' => 10, 'product_batch_id' => $lot->id,
            ]],
        ])->assertSessionHasNoErrors();

        // Kos lot (4.25), bukan harga kos produk (6.00).
        $this->assertSame('4.25', Sale::latest('id')->first()->items->first()->kos_seunit);
        $this->assertSame(15, $lot->fresh()->kuantiti);
    }

    public function test_produk_berbatch_mesti_menyebut_lot(): void
    {
        $produk = $this->produk(['jejak_batch' => true, 'stok' => 10]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 1, 'harga_jual' => 10]],
        ])->assertSessionHas('ralat');

        $this->assertSame(0, Sale::count());
        $this->assertSame(10, $produk->fresh()->stok);
    }

    public function test_menjual_melebihi_baki_ditolak_dan_tiada_apa_disimpan(): void
    {
        $produk = $this->produk(['stok' => 3]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5, 'harga_jual' => 10]],
        ])->assertSessionHas('ralat');

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(3, $produk->fresh()->stok);
    }

    /*
     | Satu baris yang gagal mesti menggulung keseluruhan jualan. Jualan yang
     | separuh tersimpan meninggalkan stok yang sudah ditolak untuk barang yang
     | tiada pada dokumen.
     */
    public function test_satu_baris_gagal_menggulung_keseluruhan_jualan(): void
    {
        $cukup = $this->produk(['stok' => 100]);
        $tidak = $this->produk(['stok' => 1]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [
                ['product_id' => $cukup->id, 'kuantiti' => 2, 'harga_jual' => 10],
                ['product_id' => $tidak->id, 'kuantiti' => 5, 'harga_jual' => 10],
            ],
        ])->assertSessionHas('ralat');

        $this->assertSame(0, Sale::count());
        $this->assertSame(100, $cukup->fresh()->stok);
        $this->assertSame(1, $tidak->fresh()->stok);
    }

    /*
     | Produk berulang tidak digabungkan: diskaun pada sebahagian kuantiti ialah
     | jualan yang sah, dan menggabungkannya akan membuang salah satu harga.
     */
    public function test_produk_berulang_kekal_sebagai_dua_baris(): void
    {
        $produk = $this->produk(['stok' => 100]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [
                ['product_id' => $produk->id, 'kuantiti' => 2, 'harga_jual' => 10],
                ['product_id' => $produk->id, 'kuantiti' => 3, 'harga_jual' => 7],
            ],
        ])->assertSessionHasNoErrors();

        $jualan = Sale::latest('id')->first();

        $this->assertCount(2, $jualan->items);
        $this->assertSame(41.0, $jualan->jumlahJualan());
    }

    public function test_laporan_bulanan_memaparkan_untung_kasar(): void
    {
        $produk = $this->produk(['harga_kos' => 4]);

        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 10, 'harga_jual' => 10]],
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('reports.monthly'))
            ->assertOk()
            ->assertSee(__('wky.jual.untung_kasar'))
            // 100.00 jualan − 40.00 kos = 60.00
            ->assertSee('60.00');
    }

    public function test_jualan_tanpa_baris_ditolak(): void
    {
        $this->actingAs($this->pengguna())->post(route('sales.store'), [
            'baris' => [['product_id' => '', 'kuantiti' => '']],
        ])->assertSessionHas('ralat');

        $this->assertSame(0, Sale::count());
    }
}
