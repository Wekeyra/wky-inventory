<?php

namespace Tests\Feature;

use App\Models\InvoiceScan;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Nombor batch, tarikh luput, dan amaran hampir tamat tempoh. */
class BatchLuputTest extends TestCase
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

    private function produk(bool $jejak = true, int $stok = 0, string $sku = 'UBT-1'): Product
    {
        return Product::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'sku' => $sku,
            'nama' => 'Ubat Batuk',
            'unit' => 'botol',
            'harga_kos' => 5,
            'harga_jual' => 12,
            'stok' => $stok,
            'stok_minimum' => 2,
            'jejak_batch' => $jejak,
        ]);
    }

    public function test_stok_masuk_mencipta_batch(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id,
            'jenis' => 'masuk',
            'sebab' => 'pembelian',
            'kuantiti' => 30,
            'no_batch' => 'B-2026-01',
            'tarikh_luput' => now()->addMonths(6)->format('Y-m-d'),
        ])->assertRedirect('/stock');

        $this->assertSame(30, $produk->fresh()->stok);
        $this->assertDatabaseHas('product_batches', [
            'product_id' => $produk->id,
            'no_batch' => 'B-2026-01',
            'kuantiti' => 30,
        ]);

        // Pergerakan menunjuk kepada lot yang terlibat, bukan hanya kepada produk.
        $this->assertNotNull($produk->movements()->first()->product_batch_id);
    }

    public function test_kemasukan_kedua_menambah_lot_sedia_ada(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        foreach ([30, 20] as $kuantiti) {
            $this->actingAs($admin)->post('/stock', [
                'product_id' => $produk->id,
                'jenis' => 'masuk',
                'sebab' => 'pembelian',
                'kuantiti' => $kuantiti,
                'no_batch' => 'B-2026-01',
            ]);
        }

        $this->assertSame(1, ProductBatch::count());
        $this->assertSame(50, ProductBatch::first()->kuantiti);
        $this->assertSame(50, $produk->fresh()->stok);
    }

    public function test_no_batch_wajib_untuk_produk_yang_dijejak(): void
    {
        $produk = $this->produk();

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id,
            'jenis' => 'masuk',
            'sebab' => 'pembelian',
            'kuantiti' => 10,
        ])->assertSessionHasErrors('no_batch');

        $this->assertSame(0, $produk->fresh()->stok);
    }

    public function test_produk_biasa_tidak_meminta_batch(): void
    {
        $produk = $this->produk(jejak: false);

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id,
            'jenis' => 'masuk',
            'sebab' => 'pembelian',
            'kuantiti' => 10,
        ])->assertSessionHasNoErrors();

        $this->assertSame(10, $produk->fresh()->stok);
        $this->assertSame(0, ProductBatch::count());
    }

    public function test_stok_keluar_menolak_baki_batch_yang_dipilih(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 30, 'no_batch' => 'B-1',
        ]);

        $batch = ProductBatch::first();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan',
            'kuantiti' => 12, 'product_batch_id' => $batch->id,
        ])->assertRedirect('/stock');

        $this->assertSame(18, $batch->fresh()->kuantiti);
        $this->assertSame(18, $produk->fresh()->stok);
    }

    public function test_keluar_melebihi_baki_batch_ditolak(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'B-1',
        ]);
        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'B-2',
        ]);

        $batch = ProductBatch::where('no_batch', 'B-1')->first();

        // Produk ada 20 unit semuanya, tetapi lot ini hanya ada 10.
        $this->actingAs($admin)->from('/stock/create')->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan',
            'kuantiti' => 15, 'product_batch_id' => $batch->id,
        ])->assertRedirect('/stock/create')->assertSessionHas('ralat');

        $this->assertSame(10, $batch->fresh()->kuantiti);
        $this->assertSame(20, $produk->fresh()->stok);
    }

    public function test_batch_wajib_semasa_keluar_bagi_produk_yang_dijejak(): void
    {
        $produk = $this->produk(stok: 10);

        $this->actingAs($this->admin())->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan', 'kuantiti' => 5,
        ])->assertSessionHasErrors('product_batch_id');
    }

    public function test_batch_milik_produk_lain_ditolak(): void
    {
        $satu = $this->produk(sku: 'UBT-1');
        $dua = $this->produk(stok: 10, sku: 'UBT-2');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $satu->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'B-1',
        ]);

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $dua->id, 'jenis' => 'keluar', 'sebab' => 'jualan',
            'kuantiti' => 5, 'product_batch_id' => ProductBatch::first()->id,
        ])->assertSessionHasErrors('product_batch_id');

        $this->assertSame(10, $dua->fresh()->stok);
    }

    public function test_dashboard_menunjukkan_batch_hampir_luput(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'HAMPIR', 'tarikh_luput' => now()->addDays(10)->format('Y-m-d'),
        ]);
        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'JAUH', 'tarikh_luput' => now()->addYear()->format('Y-m-d'),
        ]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('HAMPIR')
            ->assertDontSee('JAUH');
    }

    public function test_batch_kosong_tidak_diberi_amaran(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'HABIS', 'tarikh_luput' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'keluar', 'sebab' => 'jualan',
            'kuantiti' => 10, 'product_batch_id' => ProductBatch::first()->id,
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertDontSee('HABIS');
    }

    public function test_halaman_produk_menyenaraikan_lot(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'LOT-AAA', 'tarikh_luput' => now()->addMonths(3)->format('Y-m-d'),
        ]);

        $this->actingAs($admin)->get("/products/{$produk->id}")
            ->assertOk()
            ->assertSee('LOT-AAA');
    }

    /**
     * Pelarasan menyeluruh menetapkan baki produk tanpa menyebut lot, jadi jumlah
     * lot boleh terpesong. Perbezaan itu mesti kelihatan, bukan disembunyikan.
     */
    public function test_beza_antara_baki_produk_dan_jumlah_lot_dipaparkan(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'LOT-AAA',
        ]);

        // Pelarasan menyeluruh: baki produk menjadi 4, tetapi lot masih menyimpan 10.
        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'pelarasan', 'sebab' => 'kiraan_fizikal', 'kuantiti' => 4,
        ]);

        $this->assertSame(-6, $produk->fresh()->bezaBatch());

        $this->actingAs($admin)->get("/products/{$produk->id}")
            ->assertOk()
            ->assertSee(__('wky.batch.beza', ['beza' => -6]));
    }

    public function test_tarikh_luput_boleh_dikemas_kini(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'B-1',
        ]);

        $batch = ProductBatch::first();

        $this->actingAs($admin)
            ->from("/products/{$produk->id}")
            ->put("/products/{$produk->id}/batch/{$batch->id}", [
                'tarikh_luput' => '2027-01-31',
                'no_siri' => 'SN-99',
            ])
            ->assertRedirect("/products/{$produk->id}");

        $batch->refresh();

        $this->assertSame('2027-01-31', $batch->tarikh_luput->format('Y-m-d'));
        $this->assertSame('SN-99', $batch->no_siri);
        // Kuantiti tidak boleh disunting di sini; ia hanya bergerak melalui stok.
        $this->assertSame(10, $batch->kuantiti);
    }

    public function test_batch_produk_lain_tidak_boleh_disunting_melalui_url_produk_ini(): void
    {
        $satu = $this->produk(sku: 'UBT-1');
        $dua = $this->produk(sku: 'UBT-2');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $satu->id, 'jenis' => 'masuk', 'sebab' => 'pembelian',
            'kuantiti' => 10, 'no_batch' => 'B-1',
        ]);

        $batch = ProductBatch::first();

        $this->actingAs($admin)
            ->put("/products/{$dua->id}/batch/{$batch->id}", ['tarikh_luput' => '2027-01-31'])
            ->assertNotFound();

        $this->assertNull($batch->fresh()->tarikh_luput);
    }

    /**
     * Invois tidak membawa nombor batch, jadi satu penghantaran menjadi satu lot
     * yang dinamakan mengikut rujukan invois. Tanpa ini, baki batch akan
     * tertinggal di belakang baki produk setiap kali stok masuk melalui imbasan.
     */
    public function test_pengesahan_imbasan_mencipta_lot_penerimaan(): void
    {
        $produk = $this->produk();
        $admin = $this->admin();

        $imbasan = InvoiceScan::create([
            'workspace_id' => $admin->workspace_id,
            'kod' => 'SCAN-2026-001',
            'status' => 'draf',
            'no_invois' => 'INV-77',
            'laluan_fail' => 'invois/ujian.jpg',
            'nama_fail_asal' => 'ujian.jpg',
            'jenis_mime' => 'image/jpeg',
            'dibuka_oleh' => $admin->id,
            'dibaca_pada' => now(),
        ]);

        $imbasan->items()->create([
            'product_id' => $produk->id,
            'nama_invois' => 'Ubat Batuk',
            'kuantiti' => 24,
            'kaedah_padanan' => 'nama',
        ]);

        $this->actingAs($admin)->post("/imbas-invois/{$imbasan->id}/sahkan");

        $this->assertSame(24, $produk->fresh()->stok);
        $this->assertDatabaseHas('product_batches', [
            'product_id' => $produk->id,
            'no_batch' => 'INV-77',
            'kuantiti' => 24,
        ]);
    }
}
