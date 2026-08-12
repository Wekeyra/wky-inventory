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
        // Penukar bahasa menghantar ?bahasa=xx pada URL halaman semasa. Pilihan
        // itu disimpan dan terus digunakan dalam permintaan yang sama, supaya
        // menukar bahasa hanya memerlukan satu permintaan dan bukan dua
        // (ubah hala, kemudian muat semula).
        $pilihan = $request->query('bahasa');

        if ($this->disokong($pilihan)) {
            $request->session()->put('locale', $pilihan);
        }

        $locale = $request->session()->get('locale');

        // Locale lalai daripada config digunakan jika sesi belum menyimpan pilihan
        // atau pilihan tersimpan sudah tidak lagi disokong.
        if ($this->disokong($locale)) {
            App::setLocale($locale);
        }

        // Nama bulan daripada translatedFormat() ikut locale Carbon, yang berasingan
        // daripada locale aplikasi, jadi ia diselaraskan di sini.
        Carbon::setLocale(App::getLocale());

        return $next($request);
    }

    private function disokong(mixed $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, config('bahasa.sokongan'));
    }
}
