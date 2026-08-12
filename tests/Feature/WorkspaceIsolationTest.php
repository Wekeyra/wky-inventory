<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data setiap syarikat mesti kekal terasing. Ujian di sini membina dua ruang
 * kerja lengkap dan memastikan tiada satu pun laluan membocorkan data antara
 * keduanya.
 */
class WorkspaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Product} */
    private function syarikat(string $nama, string $emel, string $sku): array
    {
        $ruangKerja = Workspace::create(['nama' => $nama]);

        $pengguna = User::create([
            'workspace_id' => $ruangKerja->id,
            'name' => "Admin {$nama}",
            'email' => $emel,
            'peranan' => 'admin',
            'password' => 'password123',
        ]);

        $kategori = Category::create([
            'workspace_id' => $ruangKerja->id,
            'kod' => 'KAT',
            'nama' => "Kategori {$nama}",
        ]);

        $pembekal = Supplier::create([
            'workspace_id' => $ruangKerja->id,
            'kod' => 'SUP',
            'nama' => "Pembekal {$nama}",
        ]);

        $produk = Product::create([
            'workspace_id' => $ruangKerja->id,
            'sku' => $sku,
            'nama' => "Produk {$nama}",
            'category_id' => $kategori->id,
            'supplier_id' => $pembekal->id,
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => 50,
            'stok_minimum' => 5,
        ]);

        StockMovement::create([
            'workspace_id' => $ruangKerja->id,
            'product_id' => $produk->id,
            'user_id' => $pengguna->id,
            'jenis' => 'masuk',
            'kuantiti' => 50,
            'stok_sebelum' => 0,
            'stok_selepas' => 50,
        ]);

        return [$pengguna, $produk];
    }

    public function test_pengguna_hanya_nampak_produk_syarikat_sendiri(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        $this->syarikat('Beta', 'beta@ujian.test', 'B-1');

        $this->actingAs($satu)->get('/products')
            ->assertOk()
            ->assertSee('Produk Alfa')
            ->assertDontSee('Produk Beta');
    }

    public function test_kategori_dan_pembekal_juga_terasing(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        $this->syarikat('Beta', 'beta@ujian.test', 'B-1');

        $this->actingAs($satu)->get('/categories')->assertOk()
            ->assertSee('Kategori Alfa')->assertDontSee('Kategori Beta');

        $this->actingAs($satu)->get('/suppliers')->assertOk()
            ->assertSee('Pembekal Alfa')->assertDontSee('Pembekal Beta');
    }

    public function test_pergerakan_stok_terasing(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        $this->syarikat('Beta', 'beta@ujian.test', 'B-1');

        $this->actingAs($satu)->get('/stock')->assertOk()
            ->assertSee('Produk Alfa')->assertDontSee('Produk Beta');
    }

    public function test_dashboard_hanya_mengira_data_sendiri(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        $this->syarikat('Beta', 'beta@ujian.test', 'B-1');

        $this->actingAs($satu)->get('/dashboard')->assertOk()->assertDontSee('Produk Beta');

        $this->assertSame(1, Product::withoutGlobalScopes()->where('sku', 'A-1')->count());
        $this->assertSame(2, Product::withoutGlobalScopes()->count());
    }

    public function test_produk_syarikat_lain_memulangkan_404(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        [, $produkBeta] = $this->syarikat('Beta', 'beta@ujian.test', 'B-1');

        $this->actingAs($satu)->get("/products/{$produkBeta->id}")->assertNotFound();
        $this->actingAs($satu)->get("/products/{$produkBeta->id}/edit")->assertNotFound();
        $this->actingAs($satu)->delete("/products/{$produkBeta->id}")->assertNotFound();
    }

    public function test_pengguna_syarikat_lain_tidak_boleh_disunting(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        [$dua] = $this->syarikat('Beta', 'beta@ujian.test', 'B-1');

        $this->actingAs($satu)->get("/users/{$dua->id}/edit")->assertNotFound();
        $this->actingAs($satu)->delete("/users/{$dua->id}")->assertNotFound();

        $this->assertNotNull($dua->fresh());
    }

    public function test_senarai_pengguna_hanya_menunjukkan_ruang_kerja_sendiri(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        $this->syarikat('Beta', 'beta@ujian.test', 'B-1');

        $this->actingAs($satu)->get('/users')
            ->assertOk()
            ->assertSee('Admin Alfa')
            ->assertDontSee('Admin Beta');
    }

    public function test_stok_tidak_boleh_direkod_untuk_produk_syarikat_lain(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        [, $produkBeta] = $this->syarikat('Beta', 'beta@ujian.test', 'B-1');

        $this->actingAs($satu)->post('/stock', [
            'product_id' => $produkBeta->id,
            'jenis' => 'masuk',
            'kuantiti' => 5,
        ])->assertSessionHasErrors('product_id');

        $this->assertSame(50, $produkBeta->fresh()->stok);
    }

    public function test_dua_syarikat_boleh_menggunakan_sku_yang_sama(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');
        $this->syarikat('Beta', 'beta@ujian.test', 'SAMA');

        $this->actingAs($satu)->post('/products', [
            'sku' => 'SAMA',
            'nama' => 'Produk SKU Sama',
            'unit' => 'unit',
            'harga_kos' => 5,
            'harga_jual' => 9,
            'stok' => 0,
            'stok_minimum' => 1,
        ])->assertRedirect(route('products.index'));

        $this->assertSame(2, Product::withoutGlobalScopes()->where('sku', 'SAMA')->count());
    }

    public function test_sku_masih_tidak_boleh_berulang_dalam_syarikat_yang_sama(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');

        $this->actingAs($satu)->post('/products', [
            'sku' => 'A-1',
            'nama' => 'Produk Pendua',
            'unit' => 'unit',
            'harga_kos' => 5,
            'harga_jual' => 9,
            'stok' => 0,
            'stok_minimum' => 1,
        ])->assertSessionHasErrors('sku');
    }

    public function test_produk_baharu_menerima_ruang_kerja_penciptanya(): void
    {
        [$satu] = $this->syarikat('Alfa', 'alfa@ujian.test', 'A-1');

        $this->actingAs($satu)->post('/products', [
            'sku' => 'A-2',
            'nama' => 'Produk Kedua',
            'unit' => 'unit',
            'harga_kos' => 5,
            'harga_jual' => 9,
            'stok' => 0,
            'stok_minimum' => 1,
        ]);

        $baharu = Product::withoutGlobalScopes()->where('sku', 'A-2')->firstOrFail();

        $this->assertSame($satu->workspace_id, $baharu->workspace_id);
    }
}
