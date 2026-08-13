<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InvoiceScan;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KosongkanRuangKerjaTest extends TestCase
{
    use RefreshDatabase;

    private function isikan(string $nama, string $emel): Workspace
    {
        $ruang = Workspace::create(['nama' => $nama]);

        $pengguna = User::create([
            'workspace_id' => $ruang->id,
            'name' => "Admin {$nama}",
            'email' => $emel,
            'peranan' => 'admin',
            'password' => 'password123',
        ]);

        $kategori = Category::create(['workspace_id' => $ruang->id, 'kod' => 'KAT', 'nama' => "Kategori {$nama}"]);
        $pembekal = Supplier::create(['workspace_id' => $ruang->id, 'kod' => 'SUP', 'nama' => "Pembekal {$nama}"]);

        $produk = Product::create([
            'workspace_id' => $ruang->id,
            'sku' => 'SKU-1',
            'nama' => "Produk {$nama}",
            'category_id' => $kategori->id,
            'supplier_id' => $pembekal->id,
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => 5,
            'stok_minimum' => 1,
        ]);

        StockMovement::create([
            'workspace_id' => $ruang->id,
            'product_id' => $produk->id,
            'user_id' => $pengguna->id,
            'jenis' => 'masuk',
            'kuantiti' => 5,
            'stok_sebelum' => 0,
            'stok_selepas' => 5,
        ]);

        InvoiceScan::create([
            'workspace_id' => $ruang->id,
            'kod' => "SCAN-{$nama}",
            'status' => 'draf',
            'laluan_fail' => "invois/{$nama}.jpg",
            'nama_fail_asal' => 'invois.jpg',
            'jenis_mime' => 'image/jpeg',
            'dibuka_oleh' => $pengguna->id,
        ]);

        $gudangKedua = Location::create([
            'workspace_id' => $ruang->id,
            'kod' => 'KEDUA',
            'nama' => "Gudang Kedua {$nama}",
            'aktif' => true,
        ]);

        $pemindahan = StockTransfer::create([
            'workspace_id' => $ruang->id,
            'kod' => "PDH-{$nama}",
            'status' => 'dalam_perjalanan',
            'location_asal_id' => Location::withoutGlobalScopes()
                ->where('workspace_id', $ruang->id)->where('lalai', true)->value('id'),
            'location_tujuan_id' => $gudangKedua->id,
            'dihantar_oleh' => $pengguna->id,
        ]);

        $pemindahan->items()->create(['product_id' => $produk->id, 'kuantiti' => 2]);

        return $ruang;
    }

    public function test_semua_data_inventori_dibuang(): void
    {
        $this->isikan('Alfa', 'alfa@ujian.test');

        $this->artisan('ruang-kerja:kosongkan', ['ruang' => 'Alfa', '--force' => true])
            ->assertSuccessful();

        $model = [
            Product::class, Category::class, Supplier::class, StockMovement::class,
            InvoiceScan::class, StockTransfer::class, StockBalance::class,
        ];

        foreach ($model as $satu) {
            $this->assertSame(0, $satu::withoutGlobalScopes()->count(), $satu);
        }

        // Gudang ialah struktur ruang kerja, bukan data inventori: ia kekal
        // supaya ruang kerja yang dikosongkan masih ada tempat menerima stok.
        $this->assertSame(2, Location::withoutGlobalScopes()->count());
    }

    public function test_akaun_pengguna_dan_ruang_kerja_kekal(): void
    {
        $ruang = $this->isikan('Alfa', 'alfa@ujian.test');

        $this->artisan('ruang-kerja:kosongkan', ['ruang' => 'Alfa', '--force' => true]);

        $this->assertNotNull($ruang->fresh());
        $this->assertSame(1, User::where('workspace_id', $ruang->id)->count());
    }

    public function test_ruang_kerja_lain_tidak_disentuh(): void
    {
        $this->isikan('Alfa', 'alfa@ujian.test');
        $beta = $this->isikan('Beta', 'beta@ujian.test');

        $this->artisan('ruang-kerja:kosongkan', ['ruang' => 'Alfa', '--force' => true]);

        $this->assertSame(1, Product::withoutGlobalScopes()->where('workspace_id', $beta->id)->count());
        $this->assertSame(1, Category::withoutGlobalScopes()->where('workspace_id', $beta->id)->count());
        $this->assertSame(1, InvoiceScan::withoutGlobalScopes()->where('workspace_id', $beta->id)->count());
    }

    public function test_fail_invois_turut_dibuang(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('invois/Alfa.jpg', 'palsu');

        $this->isikan('Alfa', 'alfa@ujian.test');

        $this->artisan('ruang-kerja:kosongkan', ['ruang' => 'Alfa', '--force' => true]);

        Storage::disk('local')->assertMissing('invois/Alfa.jpg');
    }

    public function test_boleh_dirujuk_dengan_id(): void
    {
        $ruang = $this->isikan('Alfa', 'alfa@ujian.test');

        $this->artisan('ruang-kerja:kosongkan', ['ruang' => (string) $ruang->id, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(0, Product::withoutGlobalScopes()->count());
    }

    public function test_ruang_kerja_tidak_wujud_dilaporkan(): void
    {
        $this->artisan('ruang-kerja:kosongkan', ['ruang' => 'Tiada', '--force' => true])->assertFailed();
    }

    public function test_tanpa_pengesahan_tiada_apa_dibuang(): void
    {
        $this->isikan('Alfa', 'alfa@ujian.test');

        $this->artisan('ruang-kerja:kosongkan', ['ruang' => 'Alfa'])
            ->expectsConfirmation('Buang semua rekod di atas daripada "Alfa"?', 'no')
            ->assertSuccessful();

        $this->assertSame(1, Product::withoutGlobalScopes()->count());
    }

    public function test_ruang_kerja_kosong_dilaporkan_tanpa_ralat(): void
    {
        Workspace::create(['nama' => 'Kosong']);

        $this->artisan('ruang-kerja:kosongkan', ['ruang' => 'Kosong'])->assertSuccessful();
    }
}
