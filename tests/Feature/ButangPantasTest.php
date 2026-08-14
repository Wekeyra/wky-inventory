<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButangPantasTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(string $peranan = 'admin'): User
    {
        $ruang = Workspace::create(['nama' => 'Syarikat Ujian']);

        return User::create([
            'workspace_id' => $ruang->id,
            'name' => 'Ujian',
            'email' => $peranan.'@ujian.test',
            'password' => bcrypt('rahsia123'),
            'peranan' => $peranan,
        ]);
    }

    public function test_butang_pantas_ada_pada_setiap_halaman_sistem(): void
    {
        $pengguna = $this->pengguna();

        foreach (['dashboard', 'products.index', 'categories.index', 'stock.index'] as $laluan) {
            $this->actingAs($pengguna)
                ->get(route($laluan))
                ->assertSee('data-jatuh="menu-pantas"', false);
        }
    }

    public function test_keempat_empat_pintasan_dipaparkan(): void
    {
        $respons = $this->actingAs($this->pengguna())->get(route('dashboard'));

        $respons->assertSee(__('wky.pantas.imbas_resit'))
            ->assertSee(__('wky.pantas.muat_naik'))
            ->assertSee(__('wky.pantas.stok_masuk'))
            ->assertSee(__('wky.pantas.stok_keluar'));
    }

    /*
     | Sama seperti pasangan imbas: kedua-dua pintasan stok menuju ke borang
     | pergerakan stok yang sama, jadi ?jenis= yang membezakannya.
     */
    public function test_pintasan_stok_membawa_jenis_yang_berbeza(): void
    {
        $respons = $this->actingAs($this->pengguna())->get(route('dashboard'));

        $respons->assertSee(route('stock.create', ['jenis' => 'masuk']), false)
            ->assertSee(route('stock.create', ['jenis' => 'keluar']), false);
    }

    /*
     | Pintasan itu tidak berguna kalau borang tetap terbuka pada "masuk":
     | pengguna yang menekan Stok Keluar akan merekod stok masuk tanpa sedar.
     */
    public function test_borang_stok_membuka_jenis_yang_diminta(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('stock.create', ['jenis' => 'keluar']))
            ->assertSee('<option value="keluar" selected>', false);
    }

    /*
     | ?jenis= datang daripada URL, jadi ia boleh membawa apa sahaja. Nilai
     | yang tidak dikenali mesti jatuh kembali kepada "masuk"; kalau tidak
     | borang terbuka tanpa satu pun jenis dipilih.
     */
    public function test_jenis_yang_tidak_dikenali_jatuh_kembali_kepada_masuk(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('stock.create', ['jenis' => 'entah-apa']))
            ->assertSee('<option value="masuk" selected>', false);
    }

    /*
     | Imbas Resit dan Muat Naik menuju ke halaman yang sama, jadi ?mod= yang
     | membezakannya. Tanpa itu kedua-dua pintasan jadi sama.
     */
    public function test_pintasan_imbas_membawa_mod_yang_berbeza(): void
    {
        $respons = $this->actingAs($this->pengguna())->get(route('dashboard'));

        $respons->assertSee(route('invoice-scans.create', ['mod' => 'kamera']), false)
            ->assertSee(route('invoice-scans.create', ['mod' => 'fail']), false);
    }

    public function test_staf_juga_mendapat_butang_pantas(): void
    {
        $this->actingAs($this->pengguna('staf'))
            ->get(route('dashboard'))
            ->assertSee('data-jatuh="menu-pantas"', false);
    }

    /*
     | Butang ini hanya untuk pengguna yang sudah log masuk; halaman awam tidak
     | sepatutnya menawarkan pintasan yang semuanya memerlukan akaun.
     */
    public function test_halaman_awam_tiada_butang_pantas(): void
    {
        $this->get('/')->assertDontSee('data-jatuh="menu-pantas"', false);
        $this->get('/login')->assertDontSee('data-jatuh="menu-pantas"', false);
    }
}
