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
            'status' => 'aktif',
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

    public function test_pendaftaran_mencipta_akaun_menunggu_kelulusan(): void
    {
        $this->daftar()
            ->assertRedirect('/login')
            ->assertSessionHas('status');

        $pengguna = User::where('email', 'baharu@ujian.test')->firstOrFail();

        $this->assertSame('menunggu', $pengguna->status);
        $this->assertSame('staf', $pengguna->peranan);
        $this->assertGuest();
    }

    public function test_pendaftaran_tidak_boleh_menetapkan_peranan_admin(): void
    {
        $this->daftar(['peranan' => 'admin', 'status' => 'aktif']);

        $pengguna = User::where('email', 'baharu@ujian.test')->firstOrFail();

        $this->assertSame('staf', $pengguna->peranan);
        $this->assertSame('menunggu', $pengguna->status);
    }

    public function test_emel_berulang_ditolak(): void
    {
        $this->admin();

        $this->daftar(['email' => 'admin@ujian.test'])->assertSessionHasErrors('email');
    }

    public function test_kata_laluan_perlu_disahkan(): void
    {
        $this->daftar(['password_confirmation' => 'lain12345'])->assertSessionHasErrors('password');
    }

    public function test_akaun_menunggu_tidak_boleh_log_masuk(): void
    {
        User::factory()->menunggu()->create([
            'email' => 'tunggu@ujian.test',
            'password' => 'password123',
        ]);

        $this->post('/login', ['email' => 'tunggu@ujian.test', 'password' => 'password123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akaun_ditolak_tidak_boleh_log_masuk(): void
    {
        User::factory()->create([
            'email' => 'tolak@ujian.test',
            'status' => 'ditolak',
            'password' => 'password123',
        ]);

        $this->post('/login', ['email' => 'tolak@ujian.test', 'password' => 'password123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sesi_hidup_diputuskan_apabila_akaun_tidak_lagi_aktif(): void
    {
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)->get('/dashboard')->assertOk();

        $pengguna->update(['status' => 'ditolak']);

        $this->actingAs($pengguna)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_boleh_meluluskan_akaun(): void
    {
        $menunggu = User::factory()->menunggu()->create();

        $this->actingAs($this->admin())
            ->post(route('users.luluskan', $menunggu))
            ->assertSessionHas('status');

        $this->assertSame('aktif', $menunggu->fresh()->status);
    }

    public function test_admin_boleh_menolak_akaun(): void
    {
        $menunggu = User::factory()->menunggu()->create();

        $this->actingAs($this->admin())
            ->post(route('users.tolak', $menunggu))
            ->assertSessionHas('status');

        $this->assertSame('ditolak', $menunggu->fresh()->status);
    }

    public function test_admin_tidak_boleh_menolak_akaun_sendiri(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('users.tolak', $admin))
            ->assertSessionHas('ralat');

        $this->assertSame('aktif', $admin->fresh()->status);
    }

    public function test_admin_tidak_boleh_menurunkan_peranan_sendiri(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => 'Admin Ujian',
            'email' => 'admin@ujian.test',
            'peranan' => 'staf',
            'status' => 'ditolak',
        ])->assertRedirect(route('users.index'));

        $this->assertSame('admin', $admin->fresh()->peranan);
        $this->assertSame('aktif', $admin->fresh()->status);
    }

    public function test_staf_tidak_boleh_meluluskan_akaun(): void
    {
        $menunggu = User::factory()->menunggu()->create();
        $staf = User::factory()->create(['peranan' => 'staf']);

        $this->actingAs($staf)->post(route('users.luluskan', $menunggu))->assertForbidden();

        $this->assertSame('menunggu', $menunggu->fresh()->status);
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

        $this->get('/auth/google')
            ->assertRedirectContains('accounts.google.com');
    }
}
