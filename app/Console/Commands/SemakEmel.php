<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Menghantar satu emel ujian untuk mengesahkan konfigurasi emel.
 *
 * MAIL_MAILER=log ialah lalai Laravel, dan ia gagal secara paling senyap yang
 * mungkin: emel "berjaya dihantar" ke fail log, borang Lupa Kata Laluan berkata
 * pautan sudah dihantar, dan tiada apa yang sampai kepada sesiapa. Tiada ralat,
 * tiada amaran — cuma pengguna yang menunggu emel yang tidak akan tiba.
 *
 * Arahan ini menyebut pemacu yang sedang digunakan sebelum menghantar, supaya
 * kegagalan senyap itu menjadi kegagalan yang kelihatan.
 */
class SemakEmel extends Command
{
    protected $signature = 'wky:semak-emel {emel : Alamat penerima ujian}';

    protected $description = 'Hantar emel ujian untuk mengesahkan tetapan MAIL_*';

    public function handle(): int
    {
        $pemacu = config('mail.default');
        $emel = $this->argument('emel');

        $this->line('Pemacu emel: <options=bold>'.$pemacu.'</>');
        $this->line('Dari        : '.config('mail.from.address'));

        if ($pemacu === 'log') {
            $this->newLine();
            $this->error('Pemacu ialah "log" — emel TIDAK dihantar ke mana-mana.');
            $this->line('Ia ditulis ke storage/logs/laravel.log sahaja. Pautan Lupa Kata Laluan');
            $this->line('tidak akan sampai kepada sesiapa. Tetapkan MAIL_MAILER=smtp pada produksi.');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                'Emel ujian daripada '.config('app.name').'. Kalau anda menerimanya, tetapan emel sudah betul.',
                fn ($mesej) => $mesej->to($emel)->subject('Ujian emel '.config('app.name')),
            );
        } catch (Throwable $ralat) {
            $this->newLine();
            $this->error('Gagal menghantar: '.$ralat->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Emel ujian dihantar ke {$emel}. Semak peti masuk dan folder spam.");

        return self::SUCCESS;
    }
}
