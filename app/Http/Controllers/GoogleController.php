<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as PenggunaGoogle;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirectSymfony;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Butang Google hanya dipaparkan apabila kunci OAuth telah ditetapkan,
     * jadi laluan ini juga menolak permintaan apabila ia belum dikonfigur.
     */
    public static function dikonfigur(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    public function redirect(): RedirectSymfony
    {
        abort_unless(static::dikonfigur(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(static::dikonfigur(), 404);

        try {
            $akaun = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->kembaliDenganRalat(__('wky.auth.google_gagal'));
        }

        // Google boleh memulangkan emel yang belum disahkan bagi domain tertentu.
        // Tanpa pengesahan itu, emel tidak boleh dipercayai untuk memadan akaun.
        if (blank($akaun->getEmail()) || ! ($akaun->user['email_verified'] ?? false)) {
            return $this->kembaliDenganRalat(__('wky.auth.google_emel_tidak_sah'));
        }

        Auth::login($this->cariAtauDaftar($akaun), true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function cariAtauDaftar(PenggunaGoogle $akaun): User
    {
        $pengguna = User::where('google_id', $akaun->getId())
            ->orWhere('email', $akaun->getEmail())
            ->first();

        if (! $pengguna) {
            $nama = $akaun->getName() ?: Str::before($akaun->getEmail(), '@');

            // Akaun Google baharu memulakan ruang kerjanya sendiri, sama seperti
            // pendaftaran biasa, dan menjadi admin di dalamnya.
            return DB::transaction(fn () => User::create([
                'workspace_id' => Workspace::create(['nama' => $nama])->id,
                'name' => $nama,
                'email' => $akaun->getEmail(),
                'google_id' => $akaun->getId(),
                'peranan' => 'admin',
            ]));
        }

        // Akaun sedia ada yang log masuk dengan Google buat kali pertama.
        if (blank($pengguna->google_id)) {
            $pengguna->update(['google_id' => $akaun->getId()]);
        }

        return $pengguna;
    }

    private function kembaliDenganRalat(string $mesej): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $mesej]);
    }
}
