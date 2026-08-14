<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Butang tambah produk pada borang stok dan modal stok pantas.
|
| Pemasangan baharu bermula tanpa satu produk pun, jadi borang stok yang
| pertama dilihat pengguna ialah borang yang tidak boleh dihantar. Butang ini
| memberinya jalan keluar tanpa perlu mencari halaman Produk sendiri.
*/
class TambahProdukDariStokTest extends TestCase
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

    /** @return array<string, mixed> */
    private function medanProduk(array $tambahan = []): array
    {
        return array_merge([
            'sku' => 'BRG-BAHARU',
            'nama' => 'Barang Baharu',
            'unit' => 'unit',
            'harga_kos' => 5,
            'harga_jual' => 10,
            'stok_minimum' => 1,
            'aktif' => 1,
        ], $tambahan);
    }

    public function test_modal_stok_pantas_memaparkan_butang_tambah_produk(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertSee(route('products.create', ['kembali' => 'dashboard']), false);
    }

    public function test_borang_stok_memaparkan_butang_tambah_produk(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('stock.create'))
            ->assertSee(route('products.create', ['kembali' => 'stok']), false);
    }

    public function test_simpan_dari_dashboard_pulang_ke_dashboard(): void
    {
        $this->actingAs($this->pengguna())
            ->post(route('products.store'), $this->medanProduk(['kembali' => 'dashboard']))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('products', ['sku' => 'BRG-BAHARU']);
    }

    /*
     | Pulang ke borang stok sahaja tidak cukup: pengguna datang ke sana untuk
     | merekod stok produk itu, jadi produk baharu itu mesti sudah terpilih.
     */
    public function test_simpan_dari_borang_stok_pulang_dengan_produk_terpilih(): void
    {
        $respons = $this->actingAs($this->pengguna())
            ->post(route('products.store'), $this->medanProduk(['kembali' => 'stok']));

        $produk = Product::where('sku', 'BRG-BAHARU')->firstOrFail();

        $respons->assertRedirect(route('stock.create', ['product_id' => $produk->id]));
    }

    public function test_simpan_dari_halaman_produk_kekal_pulang_ke_senarai(): void
    {
        $this->actingAs($this->pengguna())
            ->post(route('products.store'), $this->medanProduk())
            ->assertRedirect(route('products.index'));
    }

    /*
     | "kembali" datang daripada permintaan, jadi ia boleh membawa apa sahaja.
     | Ia kata kunci dan bukan URL; nilai yang tidak dikenali mesti jatuh
     | kembali kepada senarai produk dan bukan mengalihkan pengguna ke luar.
     */
    public function test_nilai_kembali_yang_tidak_dikenali_tidak_mengalihkan_ke_tempat_lain(): void
    {
        $this->actingAs($this->pengguna())
            ->post(route('products.store'), $this->medanProduk(['kembali' => 'https://contoh-jahat.test']))
            ->assertRedirect(route('products.index'));
    }

    public function test_butang_batal_pulang_ke_tempat_yang_sama(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('products.create', ['kembali' => 'stok']))
            ->assertSee(route('stock.create'), false)
            ->assertSee('name="kembali" value="stok"', false);
    }

    public function test_borang_produk_biasa_tiada_medan_kembali(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('products.create'))
            ->assertDontSee('name="kembali"', false);
    }
}
