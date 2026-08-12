<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Ujian',
            'email' => 'admin@ujian.test',
            'peranan' => 'admin',
            'password' => 'password123',
        ]);
    }

    private function staf(): User
    {
        return User::create([
            'name' => 'Staf Ujian',
            'email' => 'staf@ujian.test',
            'peranan' => 'staf',
            'password' => 'password123',
        ]);
    }

    private function produk(array $atribut = []): Product
    {
        $bil = Product::count() + 1;

        return Product::create(array_merge([
            'sku' => "UJI-{$bil}",
            'nama' => 'Produk Ujian',
            'category_id' => Category::create(['kod' => "UJI{$bil}", 'nama' => 'Kategori Ujian'])->id,
            'supplier_id' => Supplier::create(['kod' => "SUP{$bil}", 'nama' => 'Pembekal Ujian'])->id,
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => 50,
            'stok_minimum' => 10,
        ], $atribut));
    }

    public function test_tetamu_dialih_ke_halaman_log_masuk(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_pengguna_boleh_log_masuk(): void
    {
        $this->admin();

        $this->post('/login', ['email' => 'admin@ujian.test', 'password' => 'password123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_kata_laluan_salah_ditolak(): void
    {
        $this->admin();

        $this->from('/login')
            ->post('/login', ['email' => 'admin@ujian.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_semua_halaman_utama_boleh_dipapar(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $laluan = [
            '/dashboard',
            '/products', '/products/create', "/products/{$produk->id}", "/products/{$produk->id}/edit",
            '/categories', '/categories/create', "/categories/{$produk->category_id}/edit",
            '/suppliers', '/suppliers/create', "/suppliers/{$produk->supplier_id}", "/suppliers/{$produk->supplier_id}/edit",
            '/stock', '/stock/create',
            '/kiraan-stok', '/kiraan-stok/create',
            '/imbas-invois', '/imbas-invois/create',
            '/laporan/bulanan', '/laporan/bulanan?bulan=' . now()->format('Y-m'),
            '/users', '/users/create', "/users/{$admin->id}/edit",
        ];

        foreach ($laluan as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_staf_tidak_boleh_akses_pengurusan_pengguna(): void
    {
        $this->actingAs($this->staf())->get('/users')->assertForbidden();
    }

    public function test_stok_masuk_menambah_baki_dan_merekod_jejak(): void
    {
        $produk = $this->produk(['stok' => 50]);

        $this->actingAs($this->admin())
            ->post('/stock', ['product_id' => $produk->id, 'jenis' => 'masuk', 'kuantiti' => 25])
            ->assertRedirect('/stock');

        $this->assertSame(75, $produk->fresh()->stok);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produk->id,
            'jenis' => 'masuk',
            'stok_sebelum' => 50,
            'stok_selepas' => 75,
        ]);
    }

    public function test_stok_keluar_menolak_baki(): void
    {
        $produk = $this->produk(['stok' => 50]);

        $this->actingAs($this->admin())
            ->post('/stock', ['product_id' => $produk->id, 'jenis' => 'keluar', 'kuantiti' => 20]);

        $this->assertSame(30, $produk->fresh()->stok);
    }

    public function test_pelarasan_menetapkan_baki_tepat(): void
    {
        $produk = $this->produk(['stok' => 50]);

        $this->actingAs($this->admin())
            ->post('/stock', ['product_id' => $produk->id, 'jenis' => 'pelarasan', 'kuantiti' => 12]);

        $this->assertSame(12, $produk->fresh()->stok);
    }

    public function test_stok_keluar_melebihi_baki_ditolak(): void
    {
        $produk = $this->produk(['stok' => 5]);

        $this->actingAs($this->admin())
            ->from('/stock/create')
            ->post('/stock', ['product_id' => $produk->id, 'jenis' => 'keluar', 'kuantiti' => 10])
            ->assertRedirect('/stock/create')
            ->assertSessionHas('ralat');

        $this->assertSame(5, $produk->fresh()->stok);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_kategori_yang_masih_digunakan_tidak_boleh_dipadam(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())
            ->delete("/categories/{$produk->category_id}")
            ->assertSessionHas('ralat');

        $this->assertDatabaseHas('categories', ['id' => $produk->category_id]);
    }

    public function test_stok_produk_tidak_boleh_diubah_terus_melalui_borang_produk(): void
    {
        $produk = $this->produk(['stok' => 50]);

        $this->actingAs($this->admin())->put("/products/{$produk->id}", [
            'sku' => $produk->sku,
            'nama' => 'Nama Dikemas Kini',
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok_minimum' => 10,
            'stok' => 9999,
        ])->assertRedirect('/products');

        $this->assertSame('Nama Dikemas Kini', $produk->fresh()->nama);
        $this->assertSame(50, $produk->fresh()->stok);
    }

    public function test_borang_stok_pantas_kembali_ke_dashboard(): void
    {
        $produk = $this->produk(['stok' => 10]);

        $this->actingAs($this->admin())
            ->post('/stock', ['product_id' => $produk->id, 'jenis' => 'masuk', 'kuantiti' => 5, 'sumber' => 'pantas'])
            ->assertRedirect('/dashboard');

        $this->assertSame(15, $produk->fresh()->stok);
    }

    public function test_laporan_bulanan_mengira_masuk_keluar_bulan_semasa(): void
    {
        $produk = $this->produk(['stok' => 100]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', ['product_id' => $produk->id, 'jenis' => 'masuk', 'kuantiti' => 30]);
        $this->actingAs($admin)->post('/stock', ['product_id' => $produk->id, 'jenis' => 'keluar', 'kuantiti' => 12]);

        $this->actingAs($admin)
            ->get('/laporan/bulanan')
            ->assertOk()
            ->assertViewHas('jumlahMasuk', 30)
            ->assertViewHas('jumlahKeluar', 12)
            ->assertViewHas('jumlahTransaksi', 2);
    }

    public function test_laporan_bulanan_menolak_format_bulan_tidak_sah(): void
    {
        $this->actingAs($this->admin())
            ->get('/laporan/bulanan?bulan=bukan-tarikh')
            ->assertSessionHasErrors('bulan');
    }

    public function test_carta_ringkasan_bulanan_ada_enam_titik_data(): void
    {
        $this->produk();

        $respons = $this->actingAs($this->admin())->get('/dashboard')->assertOk();
        $ringkasan = $respons->viewData('ringkasanBulanan');

        $this->assertCount(6, $ringkasan['label']);
        $this->assertCount(6, $ringkasan['masuk']);
        $this->assertCount(6, $ringkasan['keluar']);
    }

    public function test_skop_stok_rendah_menapis_dengan_betul(): void
    {
        $this->produk(['sku' => 'RENDAH', 'stok' => 3, 'stok_minimum' => 10]);
        $this->produk(['sku' => 'CUKUP', 'stok' => 80, 'stok_minimum' => 10]);

        $this->assertSame(['RENDAH'], Product::stokRendah()->pluck('sku')->all());
    }
}
