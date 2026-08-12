<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengeluarkan pengguna yang sesinya masih hidup tetapi statusnya sudah tidak
 * aktif lagi — contohnya akaun yang ditolak selepas ia log masuk.
 */
class EnsureUserIsAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna && ! $pengguna->isAktif()) {
            $menunggu = $pengguna->isMenunggu();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => $menunggu ? __('wky.auth.akaun_menunggu') : __('wky.auth.akaun_ditolak'),
            ]);
        }

        return $next($request);
    }
}
