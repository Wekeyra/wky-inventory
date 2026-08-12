<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\InvoiceScan;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Membuang semua data inventori sesebuah ruang kerja tanpa menyentuh akaun
 * penggunanya, supaya ruang kerja yang mewarisi data contoh boleh dikosongkan
 * dan digunakan seperti pemasangan baharu.
 */
class KosongkanRuangKerja extends Command
{
    protected $signature = 'ruang-kerja:kosongkan
                            {ruang : Nama atau id ruang kerja}
                            {--force : Teruskan tanpa bertanya}';

    protected $description = 'Buang semua produk, kategori, pembekal dan rekod stok sesebuah ruang kerja';

    public function handle(): int
    {
        $ruang = $this->cariRuangKerja($this->argument('ruang'));

        if (! $ruang) {
            $this->error("Tiada ruang kerja bernama atau berid \"{$this->argument('ruang')}\".");

            return self::FAILURE;
        }

        $kiraan = $this->kiraan($ruang);

        if (array_sum($kiraan) === 0) {
            $this->info("Ruang kerja \"{$ruang->nama}\" sudah kosong.");

            return self::SUCCESS;
        }

        $this->line("Ruang kerja: <options=bold>{$ruang->nama}</>");
        $this->newLine();
        $this->table(['Rekod', 'Bilangan'], collect($kiraan)->map(fn ($bil, $label) => [$label, $bil])->all());
        $this->newLine();
        $this->warn('Akaun pengguna dan ruang kerja itu sendiri tidak disentuh. Tindakan ini tidak boleh dibatalkan.');

        if (! $this->option('force') && ! $this->confirm("Buang semua rekod di atas daripada \"{$ruang->nama}\"?")) {
            $this->line('Dibatalkan. Tiada apa yang dibuang.');

            return self::SUCCESS;
        }

        $this->kosongkan($ruang);

        $this->info("Ruang kerja \"{$ruang->nama}\" telah dikosongkan.");

        return self::SUCCESS;
    }

    private function cariRuangKerja(string $rujukan): ?Workspace
    {
        return Workspace::where('nama', $rujukan)
            ->orWhere('id', ctype_digit($rujukan) ? (int) $rujukan : 0)
            ->first();
    }

    /** @return array<string, int> */
    private function kiraan(Workspace $ruang): array
    {
        return [
            'Produk' => $this->skop(Product::class, $ruang)->count(),
            'Kategori' => $this->skop(Category::class, $ruang)->count(),
            'Pembekal' => $this->skop(Supplier::class, $ruang)->count(),
            'Pergerakan stok' => $this->skop(StockMovement::class, $ruang)->count(),
            'Sesi kiraan stok' => $this->skop(StockCount::class, $ruang)->count(),
            'Imbasan invois' => $this->skop(InvoiceScan::class, $ruang)->count(),
        ];
    }

    private function kosongkan(Workspace $ruang): void
    {
        // Fail invois dibuang di luar transaksi kerana storan tidak boleh
        // digulung semula; rekodnya dibuang selepas fail hilang.
        $this->skop(InvoiceScan::class, $ruang)
            ->pluck('laluan_fail')
            ->filter()
            ->each(fn (string $laluan) => Storage::disk('local')->delete($laluan));

        DB::transaction(function () use ($ruang) {
            // Baris anak dibuang melalui cascade pada kunci asing induknya.
            $this->skop(InvoiceScan::class, $ruang)->delete();
            $this->skop(StockCount::class, $ruang)->delete();
            $this->skop(StockMovement::class, $ruang)->delete();
            $this->skop(Product::class, $ruang)->delete();
            $this->skop(Category::class, $ruang)->delete();
            $this->skop(Supplier::class, $ruang)->delete();
        });
    }

    /**
     * Skop global model membaca pengguna yang sedang log masuk, dan tiada
     * sesiapa log masuk dalam konsol. Jadi ruang kerja ditapis secara terus.
     *
     * @param  class-string<Model>  $model
     */
    private function skop(string $model, Workspace $ruang): Builder
    {
        return $model::query()->where('workspace_id', $ruang->id);
    }
}
