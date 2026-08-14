<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Produk yang ada sejarah diarkibkan, bukan dipadam.
 *
 * Setiap kunci asing produk ditanda cascadeOnDelete, jadi memadam satu produk
 * memadam baris daripada tujuh jadual sekali gus — termasuk stock_movements,
 * iaitu jejak audit itu sendiri. Sistem sudah pun menghalang kategori dan
 * pembekal daripada dipadam semasa masih digunakan; jejak audit berhak mendapat
 * sekurang-kurangnya perlindungan yang sama.
 */
class ArkibProdukTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(string $peranan = 'admin'): User
    {
        return User::firstOrCreate(
            ['email' => $peranan.'@ujian.test'],
            [
                'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
                'name' => 'Ujian',
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
            'harga_kos' => 5,
            'harga_jual' => 10,
            'stok' => 100,
            'stok_minimum' => 1,
        ], $atribut));
    }

    /*
     | Ini ujian yang paling penting dalam fail ini: sebelum pembetulan, satu
     | permintaan DELETE memusnahkan setiap pergerakan stok produk itu.
     */
    public function test_padam_produk_tidak_memusnahkan_jejak_auditnya(): void
    {
        $admin = $this->pengguna();
        $produk = $this->produk(['stok' => 0]);

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 10,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, StockMovement::count());

        $this->actingAs($admin)->delete(route('products.destroy', $produk));

        // Produk masih ada, pergerakannya masih ada, cuma tidak lagi aktif.
        $this->assertDatabaseHas('products', ['id' => $produk->id]);
        $this->assertSame(1, StockMovement::count());
        $this->assertFalse((bool) $produk->fresh()->aktif);
    }

    public function test_produk_diarkib_hilang_daripada_borang_stok(): void
    {
        $admin = $this->pengguna();
        $produk = $this->produk(['stok' => 0]);

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 5,
        ]);

        $this->actingAs($admin)->delete(route('products.destroy', $produk));

        $this->actingAs($admin)->get(route('stock.create'))
            ->assertOk()
            ->assertDontSee($produk->sku);
    }

    /*
     | Produk yang belum pernah menyentuh apa-apa masih dipadam terus. Tiada
     | sejarah untuk dilindungi, dan memaksanya kekal sebagai baris arkib hanya
     | mengotorkan senarai dengan kesilapan menaip.
     */
    public function test_produk_tanpa_sejarah_masih_dipadam_terus(): void
    {
        $admin = $this->pengguna();
        $produk = $this->produk();

        $this->actingAs($admin)->delete(route('products.destroy', $produk));

        $this->assertDatabaseMissing('products', ['id' => $produk->id]);
    }

    public function test_staf_tidak_boleh_memadam_atau_mengarkib(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->pengguna('staf'))
            ->delete(route('products.destroy', $produk))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $produk->id]);
    }

    public function test_staf_tidak_ditawarkan_butang_padam(): void
    {
        $this->produk();

        $this->actingAs($this->pengguna('staf'))
            ->get(route('products.index'))
            ->assertOk()
            ->assertDontSee(__('wky.produk.arkib'));
    }

    /*
     | Soalan pengesahan mesti menyebut bilangan rekod yang terlibat, supaya
     | "arkib" tidak dibaca sebagai "buang sahaja".
     */
    public function test_pengesahan_menyebut_bilangan_rekod(): void
    {
        $admin = $this->pengguna();
        $produk = $this->produk(['stok' => 0]);

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 5,
        ]);

        $this->actingAs($admin)->get(route('products.index'))
            ->assertOk()
            ->assertSee(__('wky.produk.arkib'));
    }

    public function test_produk_diarkib_boleh_diaktifkan_semula(): void
    {
        $admin = $this->pengguna();
        $produk = $this->produk(['stok' => 0]);

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 5,
        ]);

        $this->actingAs($admin)->delete(route('products.destroy', $produk));
        $this->assertFalse((bool) $produk->fresh()->aktif);

        $this->actingAs($admin)->put(route('products.update', $produk), [
            'sku' => $produk->sku,
            'nama' => $produk->nama,
            'unit' => 'unit',
            'harga_kos' => 5,
            'harga_jual' => 10,
            'stok_minimum' => 1,
            'aktif' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertTrue((bool) $produk->fresh()->aktif);
    }

    public function test_kiraan_sejarah_menghitung_setiap_jenis_rekod(): void
    {
        $admin = $this->pengguna();
        $produk = $this->produk(['stok' => 0]);

        $this->assertSame([], $produk->kiraanSejarah());
        $this->assertFalse($produk->adaSejarah());

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 5,
        ]);

        $sejarah = $produk->fresh()->kiraanSejarah();

        $this->assertArrayHasKey('pergerakan', $sejarah);
        $this->assertSame(1, $sejarah['pergerakan']);
        $this->assertTrue($produk->fresh()->adaSejarah());
    }
}
