<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Notifications\AturSemulaKataLaluan;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Lupa kata laluan, dan set semula melalui pautan emel.
 *
 * Tanpa aliran ini, staf yang lupa kata laluannya bergantung sepenuhnya pada
 * admin — dan admin yang lupa kata laluannya sendiri terkunci di luar tanpa
 * jalan langsung.
 */
class LupaKataLaluanTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(string $emel = 'ada@ujian.test'): User
    {
        return User::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'name' => 'Ujian',
            'email' => $emel,
            'peranan' => 'admin',
            'password' => 'kata-laluan-lama',
        ]);
    }

    public function test_halaman_lupa_boleh_dicapai_tanpa_log_masuk(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee(__('wky.auth.lupa_tajuk'));
    }

    public function test_pautan_log_masuk_menawarkan_lupa_kata_laluan(): void
    {
        $this->get(route('login'))->assertSee(route('password.request'), false);
    }

    public function test_pautan_dihantar_kepada_akaun_yang_wujud(): void
    {
        Notification::fake();

        $pengguna = $this->pengguna();

        $this->post(route('password.email'), ['email' => $pengguna->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($pengguna, AturSemulaKataLaluan::class);
    }

    /*
     | Notifikasi terbina Laravel hanya berbahasa Inggeris. Emel yang tiba dalam
     | bahasa berlainan daripada skrin yang baru sahaja diminta pengguna
     | kelihatan seperti emel palsu.
     */
    public function test_notifikasi_terbina_laravel_tidak_digunakan(): void
    {
        Notification::fake();

        $pengguna = $this->pengguna();

        $this->post(route('password.email'), ['email' => $pengguna->email]);

        Notification::assertNotSentTo($pengguna, ResetPassword::class);
    }

    /*
     | Jawapannya mesti sama sama ada emel itu wujud atau tidak. Membezakannya
     | menjadikan borang ini alat untuk mengesahkan siapa mempunyai akaun — pada
     | halaman yang tidak memerlukan log masuk.
     */
    public function test_emel_tidak_wujud_memberi_jawapan_yang_sama(): void
    {
        Notification::fake();

        $adaAkaun = $this->post(route('password.email'), ['email' => $this->pengguna()->email]);
        $tiadaAkaun = $this->post(route('password.email'), ['email' => 'entah@sapa.test']);

        $this->assertSame(
            $adaAkaun->getSession()->get('status'),
            $tiadaAkaun->getSession()->get('status'),
        );

        Notification::assertCount(1);
    }

    public function test_kata_laluan_boleh_ditetapkan_dengan_token_yang_sah(): void
    {
        Notification::fake();

        $pengguna = $this->pengguna();

        $this->post(route('password.email'), ['email' => $pengguna->email]);

        $token = null;

        Notification::assertSentTo($pengguna, AturSemulaKataLaluan::class, function ($notifikasi) use (&$token) {
            $token = $notifikasi->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $pengguna->email,
            'password' => 'rahsia-baharu-123',
            'password_confirmation' => 'rahsia-baharu-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('rahsia-baharu-123', $pengguna->fresh()->password));
    }

    public function test_token_palsu_ditolak(): void
    {
        $pengguna = $this->pengguna();

        $this->post(route('password.update'), [
            'token' => 'token-yang-direka',
            'email' => $pengguna->email,
            'password' => 'rahsia-baharu-123',
            'password_confirmation' => 'rahsia-baharu-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('kata-laluan-lama', $pengguna->fresh()->password));
    }

    public function test_kata_laluan_pendek_ditolak(): void
    {
        Notification::fake();

        $pengguna = $this->pengguna();
        $this->post(route('password.email'), ['email' => $pengguna->email]);

        $token = null;
        Notification::assertSentTo($pengguna, AturSemulaKataLaluan::class, function ($notifikasi) use (&$token) {
            $token = $notifikasi->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $pengguna->email,
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('kata-laluan-lama', $pengguna->fresh()->password));
    }

    /*
     | Kata laluan yang ditetapkan semula selalunya bermakna yang lama sudah
     | tidak dipercayai, jadi sesi "ingat saya" pada peranti lain diputuskan.
     */
    public function test_token_ingat_saya_dikitar_semula(): void
    {
        Notification::fake();

        $pengguna = $this->pengguna();
        $pengguna->forceFill(['remember_token' => 'token-lama-yang-panjang'])->save();

        $this->post(route('password.email'), ['email' => $pengguna->email]);

        $token = null;
        Notification::assertSentTo($pengguna, AturSemulaKataLaluan::class, function ($notifikasi) use (&$token) {
            $token = $notifikasi->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $pengguna->email,
            'password' => 'rahsia-baharu-123',
            'password_confirmation' => 'rahsia-baharu-123',
        ]);

        $this->assertNotSame('token-lama-yang-panjang', $pengguna->fresh()->remember_token);
    }

    public function test_pengguna_yang_sudah_log_masuk_tidak_perlu_halaman_ini(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('password.request'))
            ->assertRedirect(route('dashboard'));
    }
}
