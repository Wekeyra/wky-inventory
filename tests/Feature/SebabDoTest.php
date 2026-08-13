<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sebab pergerakan stok dan Delivery Order. */
class SebabDoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $syarikat = 'Syarikat Ujian', string $emel = 'admin@ujian.test'): User
    {
        return User::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => $syarikat])->id,
            'name' => 'Admin Ujian',
            'email' => $emel,
            'peranan' => 'admin',
            'password' => 'password123',
        ]);
    }

    private function produk(string $syarikat = 'Syarikat Ujian', string $sku = 'BRG-1'): Product
    {
        return Product::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => $syarikat])->id,
            'sku' => $sku,
            'nama' => 'Barang Ujian',
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => 100,
            'stok_minimum' => 5,
        ]);
    }

    public function test_sebab_wajib_diisi(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'kuantiti' => 5,
        ])->assertSessionHasErrors('sebab');

        $this->assertSame(100, $produk->fresh()->stok);
    }

    /** Sebab dikunci mengikut jenis supaya "stok masuk kerana jualan" tidak boleh direkodkan. */
    public function test_sebab_mesti_padan_dengan_jenis(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'jualan', 'kuantiti' => 5,
        ])->assertSessionHasErrors('sebab');

        $this->assertSame(100, $produk->fresh()->stok);
    }

    public function test_sebab_direkod_bersama_pergerakan(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'sampel', 'kuantiti' => 3,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produk->id,
            'jenis' => 'keluar',
            'sebab' => 'sampel',
        ]);
    }

    public function test_senarai_pergerakan_boleh_ditapis_mengikut_sebab(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan', 'kuantiti' => 3,
            'rujukan' => 'RUJ-JUALAN',
        ]);
        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'rosak', 'kuantiti' => 1,
            'rujukan' => 'RUJ-ROSAK',
        ]);

        $this->actingAs($admin)->get('/stock?sebab=rosak')
            ->assertOk()
            ->assertSee('RUJ-ROSAK')
            ->assertDontSee('RUJ-JUALAN');
    }

    public function test_pengesahan_kiraan_stok_direkod_sebagai_kiraan_fizikal(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/kiraan-stok', []);

        $sesi = StockCount::first();
        $item = $sesi->items()->first();

        $this->actingAs($admin)->put("/kiraan-stok/{$sesi->id}", ['kuantiti' => [$item->id => 90]]);
        $this->actingAs($admin)->post("/kiraan-stok/{$sesi->id}/sahkan");

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produk->id,
            'jenis' => 'pelarasan',
            'sebab' => 'kiraan_fizikal',
        ]);
    }

    public function test_stok_keluar_menjana_nombor_do(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan',
            'kuantiti' => 4, 'penerima' => 'Kedai Ampang',
        ]);

        $gerak = StockMovement::first();

        $this->assertSame('DO-' . now()->format('Y') . '-001', $gerak->no_do);
        $this->assertSame('Kedai Ampang', $gerak->penerima);
    }

    public function test_nombor_do_bertambah_dan_berskop_ruang_kerja(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        foreach ([1, 2] as $ignored) {
            $this->actingAs($admin)->post('/stock', [
                'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan', 'kuantiti' => 1,
            ]);
        }

        $tahun = now()->format('Y');

        $this->assertSame(
            ["DO-{$tahun}-001", "DO-{$tahun}-002"],
            StockMovement::orderBy('id')->pluck('no_do')->all(),
        );

        // Syarikat kedua bermula semula dari 001; nombor dokumen tidak dikongsi.
        $lain = $this->admin('Syarikat Kedua', 'kedua@ujian.test');
        $produkLain = $this->produk('Syarikat Kedua', 'BRG-2');

        $this->actingAs($lain)->post('/stock', [
            'product_id' => $produkLain->id, 'jenis' => 'keluar', 'sebab' => 'jualan', 'kuantiti' => 1,
        ]);

        $this->assertSame("DO-{$tahun}-001", StockMovement::latest('id')->first()->no_do);
    }

    public function test_stok_masuk_tiada_nombor_do(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 4,
        ]);

        $this->assertNull(StockMovement::first()->no_do);
    }

    public function test_halaman_do_memaparkan_butiran_penghantaran(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan',
            'kuantiti' => 4, 'penerima' => 'Kedai Ampang',
        ]);

        $gerak = StockMovement::first();

        $this->actingAs($admin)->get("/stock/{$gerak->id}/do")
            ->assertOk()
            ->assertSee($gerak->no_do)
            ->assertSee('Kedai Ampang')
            ->assertSee('Barang Ujian')
            ->assertSee('Syarikat Ujian');
    }

    public function test_do_bagi_pergerakan_masuk_memulangkan_404(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 4,
        ]);

        $this->actingAs($admin)->get('/stock/' . StockMovement::first()->id . '/do')->assertNotFound();
    }

    public function test_do_syarikat_lain_memulangkan_404(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan', 'kuantiti' => 4,
        ]);

        $gerak = StockMovement::withoutGlobalScopes()->first();

        $this->actingAs($this->admin('Syarikat Kedua', 'kedua@ujian.test'))
            ->get("/stock/{$gerak->id}/do")
            ->assertNotFound();
    }
}
