<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KataLaluanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
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

    public function test_halaman_log_masuk_mempunyai_butang_mata(): void
    {
        $this->get('/login')
            ->assertSee('data-tunjuk-kata-laluan="password"', false);
    }

    public function test_kedua_dua_medan_pendaftaran_mempunyai_butang_mata(): void
    {
        $respons = $this->get('/daftar');

        $respons->assertSee('data-tunjuk-kata-laluan="password"', false)
            ->assertSee('data-tunjuk-kata-laluan="password_confirmation"', false);
    }

    public function test_borang_pengguna_mempunyai_butang_mata(): void
    {
        $respons = $this->actingAs($this->admin())->get(route('users.create'));

        $respons->assertSee('data-tunjuk-kata-laluan="password"', false)
            ->assertSee('data-tunjuk-kata-laluan="password_confirmation"', false);
    }

    /*
     | Butang di dalam borang tanpa type="button" dikira sebagai butang hantar,
     | jadi menekan mata akan menghantar borang itu.
     */
    public function test_butang_mata_bukan_butang_hantar(): void
    {
        $this->get('/login')
            ->assertSee('<button type="button" class="butang-mata"', false);
    }

    /*
     | Medan mesti kekal type="password" pada muatan pertama; hanya JavaScript
     | yang menukarnya selepas pengguna menekan mata.
     */
    public function test_medan_bermula_tersembunyi(): void
    {
        $this->get('/login')
            ->assertSee('<input type="password" id="password"', false);
    }

    public function test_log_masuk_masih_berfungsi_dengan_medan_baharu(): void
    {
        $pengguna = $this->admin();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'rahsia123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($pengguna);
    }
}
