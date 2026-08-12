<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Memindahkan satu akaun ke ruang kerja kosong miliknya sendiri.
 *
 * Diperlukan untuk akaun yang wujud sebelum ruang kerja diperkenalkan: migrasi
 * meletakkan kesemuanya dalam satu ruang kerja lalai supaya tiada data hilang,
 * jadi akaun yang sepatutnya berasingan perlu dipisahkan secara manual.
 */
class PisahkanPengguna extends Command
{
    protected $signature = 'pengguna:pisah
                            {emel : Emel akaun yang hendak dipisahkan}
                            {--nama= : Nama ruang kerja baharu (lalai: nama pengguna)}';

    protected $description = 'Pindahkan pengguna ke ruang kerja kosong miliknya sendiri';

    public function handle(): int
    {
        $pengguna = User::where('email', $this->argument('emel'))->first();

        if (! $pengguna) {
            $this->error("Tiada akaun dengan emel {$this->argument('emel')}.");

            return self::FAILURE;
        }

        // Memindahkan satu-satunya pengguna sesebuah ruang kerja akan
        // meninggalkan data di situ tanpa sesiapa yang boleh mencapainya.
        $bilLain = User::where('workspace_id', $pengguna->workspace_id)
            ->whereKeyNot($pengguna)
            ->count();

        if ($pengguna->workspace_id !== null && $bilLain === 0) {
            $this->error("{$pengguna->name} ialah satu-satunya pengguna dalam ruang kerjanya. Memisahkannya akan meninggalkan data itu tanpa pemilik.");

            return self::FAILURE;
        }

        $nama = $this->option('nama') ?: $pengguna->name;
        $lama = $pengguna->workspace?->nama ?? '—';

        $ruangKerja = DB::transaction(function () use ($pengguna, $nama) {
            $ruangKerja = Workspace::create(['nama' => $nama]);

            // Pengguna menjadi admin ruang kerjanya sendiri supaya dia boleh
            // menambah staf, sama seperti pendaftaran baharu.
            $pengguna->update([
                'workspace_id' => $ruangKerja->id,
                'peranan' => 'admin',
            ]);

            return $ruangKerja;
        });

        $this->info("{$pengguna->name} dipindahkan daripada ruang kerja \"{$lama}\" ke \"{$ruangKerja->nama}\" yang kosong.");

        return self::SUCCESS;
    }
}
