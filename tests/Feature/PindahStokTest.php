<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pemindahan stok antara gudang, berserta peringkat dalam perjalanan. */
class PindahStokTest extends TestCase
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

    private function produk(int $stok = 50, string $sku = 'BRG-1'): Product
    {
        return Product::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'sku' => $sku,
            'nama' => 'Barang Ujian',
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => $stok,
            'stok_minimum' => 2,
        ]);
    }

    private function gudang(string $kod = 'AMPANG', string $nama = 'Cawangan Ampang'): Location
    {
        return Location::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'kod' => $kod,
            'nama' => $nama,
            'aktif' => true,
        ]);
    }

    /** @param array<string, mixed> $ganti */
    private function hantar(User $admin, Product $produk, Location $tujuan, int $kuantiti = 20, array $ganti = []): void
    {
        $this->actingAs($admin)->post('/pindah-stok', array_merge([
            'location_asal_id' => Location::lalai()->id,
            'location_tujuan_id' => $tujuan->id,
            'baris' => [['product_id' => $produk->id, 'kuantiti' => $kuantiti]],
        ], $ganti));
    }

    public function test_menghantar_menolak_baki_asal_dan_menahan_stok_dalam_perjalanan(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $tujuan = $this->gudang();

        $this->hantar($admin, $produk, $tujuan);

        $pemindahan = StockTransfer::first();

        $this->assertSame('dalam_perjalanan', $pemindahan->status);
        $this->assertSame('PDH-' . now()->format('Y') . '-001', $pemindahan->kod);

        // Jumlah stok syarikat tidak berubah — barang itu masih miliknya.
        $this->assertSame(50, $produk->fresh()->stok);
        $this->assertSame(30, (int) $produk->balances()->where('location_id', Location::lalai()->id)->value('kuantiti'));
        $this->assertSame(0, (int) $produk->balances()->where('location_id', $tujuan->id)->value('kuantiti'));
        $this->assertSame(20, $produk->fresh()->dalamPerjalanan());
        $this->assertSame(0, $produk->fresh()->bezaLokasi());
    }

    public function test_menerima_memasukkan_stok_ke_gudang_tujuan(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $tujuan = $this->gudang();

        $this->hantar($admin, $produk, $tujuan);

        $pemindahan = StockTransfer::first();

        $this->actingAs($admin)->post("/pindah-stok/{$pemindahan->id}/terima")
            ->assertRedirect("/pindah-stok/{$pemindahan->id}");

        $this->assertSame('selesai', $pemindahan->fresh()->status);
        $this->assertSame(50, $produk->fresh()->stok);
        $this->assertSame(20, (int) $produk->balances()->where('location_id', $tujuan->id)->value('kuantiti'));
        $this->assertSame(0, $produk->fresh()->dalamPerjalanan());
        $this->assertSame(0, $produk->fresh()->bezaLokasi());
    }

    public function test_membatalkan_memulangkan_stok_ke_gudang_asal(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $tujuan = $this->gudang();

        $this->hantar($admin, $produk, $tujuan);

        $pemindahan = StockTransfer::first();

        $this->actingAs($admin)->delete("/pindah-stok/{$pemindahan->id}");

        $this->assertSame('dibatalkan', $pemindahan->fresh()->status);
        $this->assertSame(50, (int) $produk->balances()->where('location_id', Location::lalai()->id)->value('kuantiti'));
        $this->assertSame(0, $produk->fresh()->dalamPerjalanan());
    }

    public function test_pemindahan_yang_sudah_diterima_tidak_boleh_diterima_lagi(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $tujuan = $this->gudang();

        $this->hantar($admin, $produk, $tujuan);

        $pemindahan = StockTransfer::first();

        $this->actingAs($admin)->post("/pindah-stok/{$pemindahan->id}/terima");

        $this->actingAs($admin)->from("/pindah-stok/{$pemindahan->id}")
            ->post("/pindah-stok/{$pemindahan->id}/terima")
            ->assertSessionHasErrors('status');

        // Baki tujuan kekal 20; penerimaan kedua tidak menggandakannya.
        $this->assertSame(20, (int) $produk->balances()->where('location_id', $tujuan->id)->value('kuantiti'));
    }

    public function test_menghantar_melebihi_baki_gudang_asal_ditolak(): void
    {
        $admin = $this->admin();
        $produk = $this->produk(stok: 5);
        $tujuan = $this->gudang();

        $this->actingAs($admin)->from('/pindah-stok/create')->post('/pindah-stok', [
            'location_asal_id' => Location::lalai()->id,
            'location_tujuan_id' => $tujuan->id,
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 10]],
        ])->assertRedirect('/pindah-stok/create')->assertSessionHas('ralat');

        $this->assertSame(0, StockTransfer::count());
        $this->assertSame(5, (int) $produk->balances()->where('location_id', Location::lalai()->id)->value('kuantiti'));
    }

    public function test_gudang_asal_dan_tujuan_mesti_berbeza(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $lalai = Location::lalai();

        $this->actingAs($admin)->post('/pindah-stok', [
            'location_asal_id' => $lalai->id,
            'location_tujuan_id' => $lalai->id,
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ])->assertSessionHasErrors('location_asal_id');

        $this->assertSame(0, StockTransfer::count());
    }

    public function test_pemindahan_tanpa_baris_ditolak(): void
    {
        $admin = $this->admin();
        $tujuan = $this->gudang();

        $this->actingAs($admin)->from('/pindah-stok/create')->post('/pindah-stok', [
            'location_asal_id' => Location::lalai()->id,
            'location_tujuan_id' => $tujuan->id,
            'baris' => [['product_id' => '', 'kuantiti' => '']],
        ])->assertSessionHas('ralat');

        $this->assertSame(0, StockTransfer::count());
    }

    /** Produk yang sama dipilih dua kali digabungkan menjadi satu baris. */
    public function test_produk_berulang_digabungkan(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $tujuan = $this->gudang();

        $this->hantar($admin, $produk, $tujuan, ganti: [
            'baris' => [
                ['product_id' => $produk->id, 'kuantiti' => 5],
                ['product_id' => $produk->id, 'kuantiti' => 7],
            ],
        ]);

        $pemindahan = StockTransfer::first();

        $this->assertCount(1, $pemindahan->items);
        $this->assertSame(12, $pemindahan->items->first()->kuantiti);
        $this->assertSame(38, (int) $produk->balances()->where('location_id', Location::lalai()->id)->value('kuantiti'));
    }

    /**
     * Pemindahan tidak boleh muncul sebagai kemasukan atau pengeluaran:
     * laporan bulanan mengira 'masuk' dan 'keluar' sebagai stok yang benar-benar
     * datang atau pergi, dan barang yang berpindah rak bukan salah satunya.
     */
    public function test_pemindahan_tidak_dikira_dalam_laporan_bulanan(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $tujuan = $this->gudang();

        $this->hantar($admin, $produk, $tujuan);
        $this->actingAs($admin)->post('/pindah-stok/' . StockTransfer::first()->id . '/terima');

        $this->assertSame(2, StockMovement::where('jenis', 'pindah')->count());

        $this->actingAs($admin)->get('/laporan/bulanan')
            ->assertOk()
            ->assertViewHas('jumlahMasuk', 0)
            ->assertViewHas('jumlahKeluar', 0);
    }

    public function test_pemindahan_merekod_kedua_dua_peringkat_dalam_jejak_audit(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $tujuan = $this->gudang();

        $this->hantar($admin, $produk, $tujuan);
        $this->actingAs($admin)->post('/pindah-stok/' . StockTransfer::first()->id . '/terima');

        $this->assertSame(
            ['pindah_hantar', 'pindah_terima'],
            StockMovement::orderBy('id')->pluck('sebab')->all(),
        );

        $gerak = StockMovement::first();

        $this->assertSame(Location::lalai()->id, $gerak->location_id);
        $this->assertSame($tujuan->id, $gerak->location_tujuan_id);
        // Jumlah stok syarikat tidak berubah, jadi kedua-dua baki itu sama.
        $this->assertSame($gerak->stok_sebelum, $gerak->stok_selepas);
    }

    public function test_pemindahan_syarikat_lain_memulangkan_404(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();
        $tujuan = $this->gudang();

        $this->hantar($admin, $produk, $tujuan);

        $pemindahan = StockTransfer::withoutGlobalScopes()->first();

        $this->actingAs($this->admin('Syarikat Kedua', 'kedua@ujian.test'))
            ->get("/pindah-stok/{$pemindahan->id}")
            ->assertNotFound();
    }

    public function test_gudang_syarikat_lain_tidak_boleh_dijadikan_tujuan(): void
    {
        $admin = $this->admin();
        $produk = $this->produk();

        $lain = $this->admin('Syarikat Kedua', 'kedua@ujian.test');
        $gudangLain = Location::withoutGlobalScopes()->where('workspace_id', $lain->workspace_id)->first();

        $this->actingAs($admin)->post('/pindah-stok', [
            'location_asal_id' => Location::lalai()->id,
            'location_tujuan_id' => $gudangLain->id,
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5]],
        ])->assertSessionHasErrors('location_tujuan_id');

        $this->assertSame(0, StockTransfer::count());
    }
}
