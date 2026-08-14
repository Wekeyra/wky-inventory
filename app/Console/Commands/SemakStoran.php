<?php

namespace App\Console\Commands;

use App\Models\InvoiceScan;
use App\Models\Product;
use App\Services\Storan\Muatnaik;
use Illuminate\Console\Command;

/**
 * Memeriksa sama ada fail yang dimuat naik masih ada di tempat yang direkodkan.
 *
 * Soalan yang dijawabnya: pada hos berkontena, adakah cakera itu kekal
 * merentas deploy? Tiada cara mengetahuinya daripada kod — ia sifat hos, bukan
 * sifat aplikasi. Jadi arahan ini membandingkan apa yang pangkalan data dakwa
 * ada dengan apa yang benar-benar ada pada cakera, dan perbezaan itulah
 * jawapannya.
 *
 * Cara menggunakannya: jalankan sekali selepas memuat naik satu invois, buat
 * satu deploy, kemudian jalankan sekali lagi. Kalau bilangan "hilang" melompat
 * daripada 0, cakera itu sementara dan MUAT_NAIK_DISK perlu ditukar ke s3.
 */
class SemakStoran extends Command
{
    protected $signature = 'wky:semak-storan {--senarai : Senaraikan setiap fail yang hilang}';

    protected $description = 'Semak sama ada gambar invois dan gambar produk masih ada pada cakera';

    public function handle(): int
    {
        $cakera = Muatnaik::cakera();

        $this->line('Cakera muat naik: <options=bold>'.Muatnaik::nama().'</>');

        if (Muatnaik::tempatan()) {
            $this->warn('Cakera tempatan. Pada hos berkontena, ini selalunya dibina semula pada setiap deploy.');
        }

        $this->newLine();

        // withoutGlobalScopes: tiada sesiapa log masuk dalam konsol, jadi skop
        // ruang kerja akan menapis semuanya menjadi kosong.
        $kumpulan = [
            'Gambar invois' => InvoiceScan::withoutGlobalScopes()->whereNotNull('laluan_fail')->pluck('laluan_fail', 'kod'),
            'Gambar produk' => Product::withoutGlobalScopes()->whereNotNull('laluan_gambar')->pluck('laluan_gambar', 'sku'),
        ];

        $jumlahHilang = 0;

        foreach ($kumpulan as $label => $fail) {
            $hilang = $fail->reject(fn (string $laluan) => $cakera->exists($laluan));
            $jumlahHilang += $hilang->count();

            $this->line(sprintf(
                '%-16s %d direkod, <fg=%s>%d hilang</>',
                $label,
                $fail->count(),
                $hilang->isEmpty() ? 'green' : 'red',
                $hilang->count(),
            ));

            if ($this->option('senarai')) {
                $hilang->each(fn (string $laluan, string $rujukan) => $this->line("    {$rujukan} → {$laluan}"));
            }
        }

        $this->newLine();

        if ($jumlahHilang > 0) {
            $this->error("{$jumlahHilang} fail direkod dalam pangkalan data tetapi tiada pada cakera.");
            $this->line('Kalau ini berlaku selepas deploy, cakera itu sementara. Tetapkan MUAT_NAIK_DISK=s3.');

            return self::FAILURE;
        }

        $this->info('Semua fail yang direkodkan ada pada cakera.');

        return self::SUCCESS;
    }
}
