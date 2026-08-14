<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menahan laluan modul lanjutan yang belum dihidupkan untuk ruang kerja ini.
 *
 * 404 dan bukan 403: modul yang tidak dihidupkan sepatutnya tidak wujud dari
 * sudut pandang ruang kerja itu. 403 memberitahu "ada sesuatu di sini yang anda
 * tidak boleh sentuh", yang menimbulkan soalan tentang kebenaran sedangkan
 * jawapannya cuma satu suis dalam Tetapan.
 *
 * Ini lapisan kedua, bukan satu-satunya. Nav sisi dan butang tindakan pantas
 * sudah menapis pautannya; middleware ini menahan URL yang ditaip terus atau
 * ditanda buku sebelum ciri itu dimatikan.
 */
class EnsureCiriAktif
{
    public function handle(Request $request, Closure $next, string $ciri): Response
    {
        abort_unless($request->user()?->workspace?->adaCiri($ciri), 404);

        return $next($request);
    }
}
