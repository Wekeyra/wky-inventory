<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Gudang, baki per gudang, dan kiraan stok yang terikat pada satu gudang. */
class GudangTest extends TestCase
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

    private function produk(int $stok = 0, string $sku = 'BRG-1', string $syarikat = 'Syarikat Ujian'): Product
    {
        return Product::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => $syarikat])->id,
            'sku' => $sku,
            'nama' => 'Barang Ujian',
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => $stok,
            'stok_minimum' => 2,
        ]);
    }

    private function gudang(string $kod, string $nama, string $syarikat = 'Syarikat Ujian'): Location
    {
        return Location::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => $syarikat])->id,
            'kod' => $kod,
            'nama' => $nama,
            'aktif' => true,
        ]);
    }

    /** Setiap ruang kerja bermula dengan satu gudang, jadi aliran yang tidak bertanya lokasi ada tempat untuk mendarat. */
    public function test_ruang_kerja_baharu_mendapat_gudang_lalai(): void
    {
        $ruangKerja = Workspace::create(['nama' => 'Syarikat Baharu']);

        $lokasi = Location::withoutGlobalScopes()->where('workspace_id', $ruangKerja->id)->get();

        $this->assertCount(1, $lokasi);
        $this->assertTrue($lokasi->first()->lalai);
    }

    public function test_produk_dengan_stok_pembukaan_didudukkan_di_gudang_lalai(): void
    {
        $produk = $this->produk(stok: 50);

        $this->assertSame(50, (int) $produk->balances()->sum('kuantiti'));
        $this->assertSame(0, $produk->bezaLokasi());
    }

    public function test_gudang_baharu_boleh_ditambah(): void
    {
        $this->actingAs($this->admin())
            ->post('/locations', ['kod' => 'AMPANG', 'nama' => 'Cawangan Ampang', 'aktif' => 1])
            ->assertRedirect('/locations');

        $this->assertDatabaseHas('locations', ['kod' => 'AMPANG', 'lalai' => false]);
    }

    /** Satu ruang kerja hanya boleh ada satu gudang lalai pada satu masa. */
    public function test_menandakan_gudang_lalai_menanggalkan_yang_lama(): void
    {
        $admin = $this->admin();
        $lama = Location::lalai();

        $this->actingAs($admin)->post('/locations', [
            'kod' => 'AMPANG', 'nama' => 'Cawangan Ampang', 'aktif' => 1, 'lalai' => 1,
        ]);

        $this->assertFalse($lama->fresh()->lalai);
        $this->assertSame('AMPANG', Location::lalai()->kod);
    }

    public function test_gudang_lalai_tidak_boleh_dipadam(): void
    {
        $admin = $this->admin();
        $lalai = Location::lalai();

        $this->actingAs($admin)->from('/locations')
            ->delete("/locations/{$lalai->id}")
            ->assertRedirect('/locations')
            ->assertSessionHas('ralat');

        $this->assertModelExists($lalai);
    }

    public function test_gudang_berstok_tidak_boleh_dipadam(): void
    {
        $admin = $this->admin();
        $gudang = $this->gudang('AMPANG', 'Cawangan Ampang');
        $produk = $this->produk();

        StockBalance::create([
            'workspace_id' => $admin->workspace_id,
            'product_id' => $produk->id,
            'location_id' => $gudang->id,
            'kuantiti' => 5,
        ]);

        $this->actingAs($admin)->from('/locations')
            ->delete("/locations/{$gudang->id}")
            ->assertSessionHas('ralat');

        $this->assertModelExists($gudang);
    }

    public function test_gudang_kosong_boleh_dipadam(): void
    {
        $admin = $this->admin();
        $gudang = $this->gudang('KOSONG', 'Gudang Kosong');

        $this->actingAs($admin)->delete("/locations/{$gudang->id}")->assertRedirect('/locations');

        $this->assertModelMissing($gudang);
    }

    public function test_gudang_syarikat_lain_memulangkan_404(): void
    {
        $gudang = $this->gudang('AMPANG', 'Cawangan Ampang');

        $this->actingAs($this->admin('Syarikat Kedua', 'kedua@ujian.test'))
            ->get("/locations/{$gudang->id}")
            ->assertNotFound();
    }

    public function test_stok_masuk_menambah_baki_gudang_yang_dipilih(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $gudang = $this->gudang('AMPANG', 'Cawangan Ampang');

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'location_id' => $gudang->id,
            'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 30,
        ])->assertRedirect('/stock');

        $this->assertSame(30, $produk->fresh()->stok);
        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $produk->id, 'location_id' => $gudang->id, 'kuantiti' => 30,
        ]);
        $this->assertSame(0, $produk->fresh()->bezaLokasi());
    }

    /** Satu gudang tidak boleh menghantar barang yang sebenarnya berada di gudang lain. */
    public function test_keluar_melebihi_baki_gudang_ditolak(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(stok: 50);
        $gudang = $this->gudang('AMPANG', 'Cawangan Ampang');

        $this->actingAs($admin)->from('/stock/create')->post('/stock', [
            'product_id' => $produk->id, 'location_id' => $gudang->id,
            'jenis' => 'keluar', 'sebab' => 'jualan', 'kuantiti' => 5,
        ])->assertRedirect('/stock/create')->assertSessionHas('ralat');

        // Stok itu berada di gudang lalai, jadi jumlahnya tidak berubah.
        $this->assertSame(50, $produk->fresh()->stok);
    }

    public function test_pergerakan_tanpa_lokasi_mendarat_di_gudang_lalai(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 10,
        ])->assertRedirect('/stock');

        $this->assertDatabaseHas('stock_balances', [
            'product_id' => $produk->id,
            'location_id' => Location::lalai()->id,
            'kuantiti' => 10,
        ]);
    }

    public function test_pengesahan_imbasan_masuk_ke_gudang_lalai(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        $imbasan = \App\Models\InvoiceScan::create([
            'workspace_id' => $admin->workspace_id,
            'kod' => 'SCAN-2026-001',
            'status' => 'draf',
            'laluan_fail' => 'invois/ujian.jpg',
            'nama_fail_asal' => 'ujian.jpg',
            'jenis_mime' => 'image/jpeg',
            'dibuka_oleh' => $admin->id,
            'dibaca_pada' => now(),
        ]);

        $imbasan->items()->create([
            'product_id' => $produk->id, 'nama_invois' => 'Barang', 'kuantiti' => 12, 'kaedah_padanan' => 'nama',
        ]);

        $this->actingAs($admin)->post("/imbas-invois/{$imbasan->id}/sahkan");

        $this->assertSame(12, $produk->fresh()->stok);
        $this->assertSame(0, $produk->fresh()->bezaLokasi());
    }

    /** Kiraan fizikal berlaku di satu gudang, jadi pelarasannya hanya menyentuh baki di situ. */
    public function test_kiraan_stok_hanya_melaraskan_gudang_yang_dikira(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(stok: 40);
        $gudang = $this->gudang('AMPANG', 'Cawangan Ampang');
        $lalai = Location::lalai();

        // 10 unit di Ampang, 40 di gudang utama.
        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'location_id' => $gudang->id,
            'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 10,
        ]);

        $this->actingAs($admin)->post('/kiraan-stok', ['location_id' => $gudang->id]);

        $sesi = \App\Models\StockCount::first();
        $item = $sesi->items()->first();

        // Gambaran yang disimpan ialah baki Ampang, bukan jumlah 50 unit.
        $this->assertSame(10, $item->kuantiti_rekod);

        $this->actingAs($admin)->put("/kiraan-stok/{$sesi->id}", ['kuantiti' => [$item->id => 7]]);
        $this->actingAs($admin)->post("/kiraan-stok/{$sesi->id}/sahkan");

        $this->assertSame(7, (int) $produk->balances()->where('location_id', $gudang->id)->value('kuantiti'));
        $this->assertSame(40, (int) $produk->balances()->where('location_id', $lalai->id)->value('kuantiti'));
        // Jumlah bergerak sebanyak perbezaan di gudang itu sahaja: 50 - 3.
        $this->assertSame(47, $produk->fresh()->stok);
    }

    public function test_halaman_produk_memaparkan_baki_setiap_gudang(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(stok: 40);
        $this->gudang('AMPANG', 'Cawangan Ampang');

        $this->actingAs($admin)->get("/products/{$produk->id}")
            ->assertOk()
            ->assertSee('Gudang Utama')
            ->assertSee('Cawangan Ampang');
    }
}
