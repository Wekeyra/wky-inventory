<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RegistrationTest extends TestCase
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

    private function daftar(array $ganti = []): TestResponse
    {
        return $this->post('/daftar', array_merge([
            'name' => 'Pengguna Baharu',
            'email' => 'baharu@ujian.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $ganti));
    }

    public function test_halaman_log_masuk_memaparkan_pautan_daftar(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('register'));
    }

    public function test_halaman_daftar_boleh_dipapar(): void
    {
        $this->get('/daftar')->assertOk();
    }

    public function test_pendaftaran_terus_log_masuk_ke_dashboard(): void
    {
        $this->daftar()
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $pengguna = User::where('email', 'baharu@ujian.test')->firstOrFail();

        $this->assertSame('staf', $pengguna->peranan);
        $this->assertAuthenticatedAs($pengguna);
    }

    public function test_pengguna_baharu_terus_boleh_guna_sistem(): void
    {
        $this->daftar();

        $this->get('/dashboard')->assertOk();
        $this->get('/products')->assertOk();
    }

    public function test_pendaftaran_tidak_boleh_menetapkan_peranan_admin(): void
    {
        $this->daftar(['peranan' => 'admin']);

        $pengguna = User::where('email', 'baharu@ujian.test')->firstOrFail();

        $this->assertSame('staf', $pengguna->peranan);
    }

    public function test_pengguna_baharu_tidak_boleh_urus_pengguna(): void
    {
        $this->daftar();

        $this->get('/users')->assertForbidden();
    }

    public function test_emel_berulang_ditolak(): void
    {
        $this->admin();

        $this->daftar(['email' => 'admin@ujian.test'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_kata_laluan_perlu_disahkan(): void
    {
        $this->daftar(['password_confirmation' => 'lain12345'])->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_kata_laluan_pendek_ditolak(): void
    {
        $this->daftar(['password' => 'abc123', 'password_confirmation' => 'abc123'])
            ->assertSessionHasErrors('password');
    }

    public function test_admin_tidak_boleh_menurunkan_peranan_sendiri(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => 'Admin Ujian',
            'email' => 'admin@ujian.test',
            'peranan' => 'staf',
        ])->assertRedirect(route('users.index'));

        $this->assertSame('admin', $admin->fresh()->peranan);
    }

    public function test_laluan_google_tidak_wujud_tanpa_konfigurasi(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get('/auth/google')->assertNotFound();
        $this->get('/login')->assertOk()->assertDontSee('Google');
    }

    public function test_butang_google_muncul_apabila_dikonfigur(): void
    {
        config([
            'services.google.client_id' => 'ujian-id',
            'services.google.client_secret' => 'ujian-secret',
        ]);

        $this->get('/login')->assertOk()->assertSee(route('google.redirect'));
        $this->get('/daftar')->assertOk()->assertSee(route('google.redirect'));
    }

    public function test_ubah_hala_google_menuju_ke_google(): void
    {
        config([
            'services.google.client_id' => 'ujian-id',
            'services.google.client_secret' => 'ujian-secret',
        ]);

        $this->get('/auth/google')->assertRedirectContains('accounts.google.com');
    }
}
