<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laci navigasi pada telefon.
 *
 * Bar sisi dahulunya `hidden md:block`, jadi pengguna telefon langsung tiada
 * navigasi — hanya butang tindakan pantas. Laci ini menggunakan elemen bar sisi
 * yang sama dan bukan salinan kedua menu, kerana dua salinan bermakna setiap
 * pautan baharu perlu ditambah dua kali.
 */
class LaciNavTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(string $peranan = 'admin'): User
    {
        return User::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'name' => 'Ujian',
            'email' => $peranan.'@ujian.test',
            'peranan' => $peranan,
            'password' => 'password123',
        ]);
    }

    public function test_butang_laci_ada_pada_setiap_halaman_sistem(): void
    {
        $pengguna = $this->pengguna();

        foreach (['dashboard', 'products.index', 'stock.index', 'analytics.index'] as $laluan) {
            $this->actingAs($pengguna)
                ->get(route($laluan))
                ->assertSee('data-laci-buka', false)
                ->assertSee('aria-controls="laci-nav"', false);
        }
    }

    public function test_laci_membawa_menu_yang_sama_dan_bukan_salinan_kedua(): void
    {
        $html = $this->actingAs($this->pengguna())->get(route('dashboard'))->getContent();

        // Satu elemen laci sahaja, dan setiap pautan menu muncul sekali.
        $this->assertSame(1, substr_count($html, 'id="laci-nav"'));
        $this->assertSame(1, substr_count($html, 'href="'.route('products.index').'"'));
        $this->assertSame(1, substr_count($html, 'href="'.route('analytics.index').'"'));
    }

    public function test_laci_membawa_latar_dan_butang_tutupnya_sendiri(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertSee('data-laci-latar', false)
            ->assertSee('data-laci-tutup', false);
    }

    /*
     | Kelas bar-sisi mesti kekal: mod cetak menyembunyikannya dengan nama itu,
     | dan laci yang tercetak di tepi setiap muka surat membazir dakwat.
     */
    public function test_laci_kekal_tersembunyi_semasa_cetak(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('reports.monthly'))
            ->assertSee('bar-sisi', false)
            ->assertSee('tanpa-cetak', false);
    }

    public function test_halaman_awam_tiada_laci(): void
    {
        $this->get('/')->assertDontSee('data-laci-buka', false);
        $this->get('/login')->assertDontSee('data-laci-buka', false);
    }

    public function test_staf_juga_mendapat_laci(): void
    {
        $this->actingAs($this->pengguna('staf'))
            ->get(route('dashboard'))
            ->assertSee('data-laci-buka', false);
    }
}
