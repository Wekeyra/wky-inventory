<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PeraturanKataLaluan;

/**
 * Lupa kata laluan, dan set semula melalui pautan emel.
 *
 * Tanpa ini, staf yang lupa kata laluannya bergantung sepenuhnya pada admin
 * untuk menetapkannya semula — dan admin yang lupa kata laluannya sendiri
 * terkunci di luar tanpa jalan langsung.
 */
class KataLaluanController extends Controller
{
    public function showLupa(): View
    {
        return view('auth.lupa-kata-laluan');
    }

    /**
     * Menghantar pautan set semula.
     *
     * Jawapannya sama sama ada emel itu wujud atau tidak. Memberitahu "emel ini
     * tiada dalam sistem" menjadikan borang ini alat untuk mengesahkan siapa
     * mempunyai akaun — pada halaman yang tidak memerlukan log masuk.
     */
    public function hantarPautan(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('wky.auth.pautan_dihantar'));
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.set-kata-laluan', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PeraturanKataLaluan::min(8)],
        ]);

        $keputusan = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $pengguna, string $kataLaluan) {
                $pengguna->forceFill([
                    'password' => Hash::make($kataLaluan),
                    // Token ingat-saya dikitar semula supaya sesi lama pada
                    // peranti lain terputus. Kata laluan yang ditetapkan semula
                    // selalunya bermakna yang lama sudah tidak dipercayai.
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($keputusan !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __($keputusan)]);
        }

        return redirect()->route('login')->with('status', __('wky.auth.kata_laluan_ditetapkan'));
    }
}
