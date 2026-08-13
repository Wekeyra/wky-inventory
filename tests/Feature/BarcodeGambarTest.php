<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Barcode produk dan gambar produk. */
class BarcodeGambarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $syarikat = 'Syarikat Ujian', string $emel = 'admin@ujian.test'): User
    {
        return User::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => $syarikat])->id,
            'name' => 'Admin Ujian',
            'email' => $emel,
            'peranan' => 'admin',
            'password' => 'password123',
        ]);
    }

    /** @param array<string, mixed> $atribut */
    private function borang(array $atribut = []): array
    {
        return array_merge([
            'sku' => 'ELK-001',
            'nama' => 'Kabel Elektrik',
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok_minimum' => 5,
            'aktif' => 1,
        ], $atribut);
    }

    public function test_produk_disimpan_dengan_barcode(): void
    {
        $this->actingAs($this->admin())
            ->post('/products', $this->borang(['barcode' => '9556001234567']))
            ->assertRedirect('/products');

        $this->assertDatabaseHas('products', ['sku' => 'ELK-001', 'barcode' => '9556001234567']);
    }

    public function test_carian_produk_menemui_barcode(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/products', $this->borang(['barcode' => '9556001234567']));
        $this->actingAs($admin)->post('/products', $this->borang(['sku' => 'LAIN-1', 'nama' => 'Produk Lain']));

        $this->actingAs($admin)->get('/products?cari=9556001234567')
            ->assertOk()
            ->assertSee('Kabel Elektrik')
            ->assertDontSee('Produk Lain');
    }

    public function test_barcode_mesti_unik_dalam_ruang_kerja(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/products', $this->borang(['barcode' => '9556001234567']));

        $this->actingAs($admin)
            ->post('/products', $this->borang(['sku' => 'ELK-002', 'barcode' => '9556001234567']))
            ->assertSessionHasErrors('barcode');

        $this->assertSame(1, Product::count());
    }

    /**
     * Barcode yang sama boleh wujud dalam dua syarikat, sama seperti SKU:
     * dua kedai yang menjual barang yang sama memang akan mengimbas kod yang sama.
     */
    public function test_dua_ruang_kerja_boleh_berkongsi_barcode(): void
    {
        $this->actingAs($this->admin())
            ->post('/products', $this->borang(['barcode' => '9556001234567']));

        $this->actingAs($this->admin('Syarikat Kedua', 'kedua@ujian.test'))
            ->post('/products', $this->borang(['barcode' => '9556001234567']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Product::withoutGlobalScopes()->count());
    }

    public function test_gambar_dimuat_naik_dan_dihidangkan(): void
    {
        Storage::fake('local');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/products', $this->borang(['gambar' => UploadedFile::fake()->image('kabel.jpg')]))
            ->assertRedirect('/products');

        $produk = Product::withoutGlobalScopes()->first();

        $this->assertNotNull($produk->laluan_gambar);
        Storage::disk('local')->assertExists($produk->laluan_gambar);

        $this->actingAs($admin)->get("/products/{$produk->id}/gambar")->assertOk();
    }

    /** Gambar berada pada cakera peribadi, jadi ia terikat pada ruang kerja seperti rekod lain. */
    public function test_gambar_produk_syarikat_lain_memulangkan_404(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
            ->post('/products', $this->borang(['gambar' => UploadedFile::fake()->image('kabel.jpg')]));

        $produk = Product::withoutGlobalScopes()->first();

        $this->actingAs($this->admin('Syarikat Kedua', 'kedua@ujian.test'))
            ->get("/products/{$produk->id}/gambar")
            ->assertNotFound();
    }

    public function test_produk_tanpa_gambar_memulangkan_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/products', $this->borang());

        $produk = Product::withoutGlobalScopes()->first();

        $this->actingAs($admin)->get("/products/{$produk->id}/gambar")->assertNotFound();
    }

    public function test_gambar_lama_dibuang_apabila_ditukar(): void
    {
        Storage::fake('local');

        $admin = $this->admin();

        $this->actingAs($admin)->post('/products', $this->borang(['gambar' => UploadedFile::fake()->image('lama.jpg')]));

        $produk = Product::withoutGlobalScopes()->first();
        $lama = $produk->laluan_gambar;

        $this->actingAs($admin)->put("/products/{$produk->id}", $this->borang([
            'gambar' => UploadedFile::fake()->image('baharu.jpg'),
        ]));

        $baharu = $produk->fresh()->laluan_gambar;

        $this->assertNotSame($lama, $baharu);
        Storage::disk('local')->assertMissing($lama);
        Storage::disk('local')->assertExists($baharu);
    }

    public function test_gambar_boleh_dibuang(): void
    {
        Storage::fake('local');

        $admin = $this->admin();

        $this->actingAs($admin)->post('/products', $this->borang(['gambar' => UploadedFile::fake()->image('kabel.jpg')]));

        $produk = Product::withoutGlobalScopes()->first();
        $laluan = $produk->laluan_gambar;

        $this->actingAs($admin)->put("/products/{$produk->id}", $this->borang(['buang_gambar' => 1]));

        $this->assertNull($produk->fresh()->laluan_gambar);
        Storage::disk('local')->assertMissing($laluan);
    }

    public function test_memadam_produk_membuang_gambarnya(): void
    {
        Storage::fake('local');

        $admin = $this->admin();

        $this->actingAs($admin)->post('/products', $this->borang(['gambar' => UploadedFile::fake()->image('kabel.jpg')]));

        $produk = Product::withoutGlobalScopes()->first();
        $laluan = $produk->laluan_gambar;

        $this->actingAs($admin)->delete("/products/{$produk->id}");

        Storage::disk('local')->assertMissing($laluan);
    }
}
