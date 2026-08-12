<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PisahkanPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $asal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asal = Workspace::create(['nama' => 'Wekeyra']);

        User::create([
            'workspace_id' => $this->asal->id,
            'name' => 'Admin Asal',
            'email' => 'admin@ujian.test',
            'peranan' => 'admin',
            'password' => 'password123',
        ]);

        Product::create([
            'workspace_id' => $this->asal->id,
            'sku' => 'LAMA-1',
            'nama' => 'Produk Lama',
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => 5,
            'stok_minimum' => 1,
        ]);
    }

    private function pengguna(string $emel = 'danish@ujian.test'): User
    {
        return User::create([
            'workspace_id' => $this->asal->id,
            'name' => 'Danish Danial',
            'email' => $emel,
            'peranan' => 'staf',
            'password' => 'password123',
        ]);
    }

    public function test_pengguna_dipindahkan_ke_ruang_kerja_kosong(): void
    {
        $danish = $this->pengguna();

        $this->artisan('pengguna:pisah', ['emel' => 'danish@ujian.test'])
            ->assertSuccessful();

        $danish->refresh();

        $this->assertNotSame($this->asal->id, $danish->workspace_id);
        $this->assertSame('Danish Danial', $danish->workspace->nama);
        $this->assertSame('admin', $danish->peranan);
    }

    public function test_dia_tidak_lagi_nampak_data_lama(): void
    {
        $danish = $this->pengguna();

        $this->artisan('pengguna:pisah', ['emel' => 'danish@ujian.test']);

        $this->actingAs($danish->fresh())
            ->get('/products')
            ->assertOk()
            ->assertDontSee('Produk Lama');
    }

    public function test_data_lama_kekal_untuk_pemilik_asal(): void
    {
        $this->pengguna();

        $this->artisan('pengguna:pisah', ['emel' => 'danish@ujian.test']);

        $this->actingAs(User::where('email', 'admin@ujian.test')->firstOrFail())
            ->get('/products')
            ->assertOk()
            ->assertSee('Produk Lama');
    }

    public function test_nama_ruang_kerja_boleh_ditetapkan(): void
    {
        $danish = $this->pengguna();

        $this->artisan('pengguna:pisah', ['emel' => 'danish@ujian.test', '--nama' => 'Kedai Danish'])
            ->assertSuccessful();

        $this->assertSame('Kedai Danish', $danish->fresh()->workspace->nama);
    }

    public function test_emel_tidak_wujud_dilaporkan(): void
    {
        $this->artisan('pengguna:pisah', ['emel' => 'tiada@ujian.test'])->assertFailed();
    }

    public function test_pengguna_terakhir_sesebuah_ruang_kerja_tidak_boleh_dipisahkan(): void
    {
        $sendirian = Workspace::create(['nama' => 'Sendirian']);

        $pengguna = User::create([
            'workspace_id' => $sendirian->id,
            'name' => 'Seorang Diri',
            'email' => 'sorang@ujian.test',
            'peranan' => 'admin',
            'password' => 'password123',
        ]);

        $this->artisan('pengguna:pisah', ['emel' => 'sorang@ujian.test'])->assertFailed();

        $this->assertSame($sendirian->id, $pengguna->fresh()->workspace_id);
    }
}
