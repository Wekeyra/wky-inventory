<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Penapis kategori pada halaman Produk membawa butang menciptanya sendiri.
| Borang kategori itu perlu tahu dari mana pengguna datang, supaya Simpan dan
| Batal kedua-duanya memulangkannya ke halaman Produk dan bukan ke senarai
| kategori yang bukan tempat kerjanya.
*/
class TambahKategoriDariProdukTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(): User
    {
        return User::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'name' => 'Ujian',
            'email' => 'ujian@ujian.test',
            'peranan' => 'admin',
            'password' => 'password123',
        ]);
    }

    public function test_halaman_produk_memaparkan_butang_tambah_kategori(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('products.index'))
            ->assertSee(route('categories.create', ['kembali' => 'produk']), false);
    }

    public function test_simpan_dari_halaman_produk_pulang_ke_halaman_produk(): void
    {
        $this->actingAs($this->pengguna())
            ->post(route('categories.store'), [
                'kod' => 'BARU',
                'nama' => 'Kategori Baharu',
                'kembali' => 'produk',
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('categories', ['kod' => 'BARU']);
    }

    public function test_simpan_dari_senarai_kategori_kekal_pulang_ke_senarai(): void
    {
        $this->actingAs($this->pengguna())
            ->post(route('categories.store'), [
                'kod' => 'BIASA',
                'nama' => 'Kategori Biasa',
            ])
            ->assertRedirect(route('categories.index'));
    }

    /*
     | "kembali" datang daripada permintaan, jadi ia boleh membawa apa sahaja —
     | termasuk URL ke tapak lain. Ia kata kunci dan bukan URL, dan apa-apa
     | selain kata kunci yang dikenali mesti jatuh kembali kepada senarai
     | kategori.
     */
    public function test_nilai_kembali_yang_tidak_dikenali_tidak_mengalihkan_ke_tempat_lain(): void
    {
        $this->actingAs($this->pengguna())
            ->post(route('categories.store'), [
                'kod' => 'JAHAT',
                'nama' => 'Kategori Jahat',
                'kembali' => 'https://contoh-jahat.test',
            ])
            ->assertRedirect(route('categories.index'));
    }

    public function test_butang_batal_pulang_ke_halaman_produk(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('categories.create', ['kembali' => 'produk']))
            ->assertSee(route('products.index'), false)
            ->assertSee('name="kembali" value="produk"', false);
    }

    /*
     | Borang kategori biasa tidak boleh membawa medan tersembunyi itu, kerana
     | kehadirannya sahaja yang menentukan ke mana Simpan pulang.
     */
    public function test_borang_kategori_biasa_tiada_medan_kembali(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('categories.create'))
            ->assertDontSee('name="kembali"', false);
    }

    public function test_menyunting_kategori_sedia_ada_tiada_medan_kembali(): void
    {
        $pengguna = $this->pengguna();

        $kategori = Category::create([
            'workspace_id' => $pengguna->workspace_id,
            'kod' => 'SEDIA',
            'nama' => 'Kategori Sedia Ada',
        ]);

        $this->actingAs($pengguna)
            ->get(route('categories.edit', $kategori))
            ->assertDontSee('name="kembali"', false);
    }
}
