<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        // Locale lalai daripada config digunakan jika sesi belum menyimpan pilihan
        // atau pilihan tersimpan sudah tidak lagi disokong.
        if (is_string($locale) && array_key_exists($locale, config('bahasa.sokongan'))) {
            App::setLocale($locale);
        }

        // Nama bulan daripada translatedFormat() ikut locale Carbon, yang berasingan
        // daripada locale aplikasi, jadi ia diselaraskan di sini.
        Carbon::setLocale(App::getLocale());

        return $next($request);
    }
}
