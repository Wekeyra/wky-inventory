<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(): User
    {
        $ruang = Workspace::create(['nama' => 'Syarikat Ujian']);

        return User::create([
            'workspace_id' => $ruang->id,
            'name' => 'Admin',
            'email' => 'admin@ujian.test',
            'password' => bcrypt('rahsia123'),
            'peranan' => 'admin',
        ]);
    }

    public function test_tetamu_melihat_halaman_pendaratan(): void
    {
        $respons = $this->get('/');

        $respons->assertOk()
            ->assertSee(__('wky.landing.hero_tajuk'))
            ->assertSee(__('wky.landing.cta_tajuk'));
    }

    /*
     | Setiap pautan nav mesti mempunyai seksyen dengan id yang sepadan, jika tidak
     | pautan itu menatal ke tempat yang tiada apa-apa.
     */
    public function test_setiap_pautan_nav_mempunyai_seksyennya(): void
    {
        $respons = $this->get('/');

        foreach (['utama', 'ciri', 'harga', 'inventori', 'tentang'] as $seksyen) {
            $respons->assertSee('href="#'.$seksyen.'"', false);
            $respons->assertSee('id="'.$seksyen.'"', false);
        }
    }

    public function test_ketiga_tiga_pakej_harga_dipaparkan(): void
    {
        $respons = $this->get('/');

        foreach (['percuma', 'perniagaan', 'enterprise'] as $pakej) {
            $respons->assertSee(__("wky.landing.harga_{$pakej}_nama"));

            for ($i = 1; $i <= 4; $i++) {
                $respons->assertSee(__("wky.landing.harga_{$pakej}_ciri_{$i}"));
            }
        }
    }

    public function test_halaman_pendaratan_menghormati_pilihan_bahasa(): void
    {
        $this->get('/?bahasa=en')->assertSee('Pricing')->assertSee('About Us');
        $this->get('/?bahasa=ms')->assertSee('Harga')->assertSee('Tentang Kami');
    }

    /**
     * Halaman log masuk ialah jalan buntu tanpa pautan ini: nav halaman
     * pendaratan tiada di sini, jadi pelawat yang menekan "Log In" dan berubah
     * fikiran hanya boleh kembali melalui butang back pelayar.
     */
    public function test_halaman_log_masuk_ada_pautan_kembali_ke_halaman_pendaratan(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('href="'.route('landing').'"', false)
            ->assertSee('Kembali ke Utama');

        $this->get('/login?bahasa=en')
            ->assertOk()
            ->assertSee('Back to Home');
    }

    /*
     | '/' ialah URL yang paling kerap ditanda buku, jadi pengguna yang sudah log
     | masuk tidak sepatutnya mendarat pada halaman pemasaran.
     */
    public function test_pengguna_log_masuk_dialih_ke_dashboard(): void
    {
        $this->actingAs($this->pengguna())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }
}
