<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Ujian',
            'email' => 'admin@ujian.test',
            'peranan' => 'admin',
            'status' => 'aktif',
            'password' => 'password123',
        ]);
    }

    private function produk(string $sku, int $stok, array $atribut = []): Product
    {
        return Product::create(array_merge([
            'sku' => $sku,
            'nama' => "Produk {$sku}",
            'unit' => 'unit',
            'harga_kos' => 10,
            'harga_jual' => 20,
            'stok' => $stok,
            'stok_minimum' => 5,
            'aktif' => true,
        ], $atribut));
    }

    private function bukaSesi(User $pengguna, array $data = []): StockCount
    {
        $this->actingAs($pengguna)->post('/kiraan-stok', $data);

        return StockCount::latest('id')->firstOrFail();
    }

    public function test_membuka_sesi_menyimpan_gambaran_stok_semasa(): void
    {
        $this->produk('A', 40);
        $this->produk('B', 15);

        $sesi = $this->bukaSesi($this->admin());

        $this->assertSame('draf', $sesi->status);
        $this->assertSame(2, $sesi->items()->count());
        $this->assertEqualsCanonicalizing([40, 15], $sesi->items()->pluck('kuantiti_rekod')->all());
        $this->assertNull($sesi->items()->first()->kuantiti_fizikal);
    }

    public function test_produk_tidak_aktif_dikecualikan(): void
    {
        $this->produk('AKTIF', 10);
        $this->produk('MATI', 10, ['aktif' => false]);

        $sesi = $this->bukaSesi($this->admin());

        $this->assertSame(['AKTIF'], $sesi->items()->with('product')->get()->pluck('product.sku')->all());
    }

    public function test_skop_kategori_menghadkan_senarai_produk(): void
    {
        $kategori = Category::create(['kod' => 'K1', 'nama' => 'Kategori Satu']);
        $this->produk('DALAM', 10, ['category_id' => $kategori->id]);
        $this->produk('LUAR', 10);

        $sesi = $this->bukaSesi($this->admin(), ['category_id' => $kategori->id]);

        $this->assertSame(['DALAM'], $sesi->items()->with('product')->get()->pluck('product.sku')->all());
    }

    public function test_sesi_tanpa_produk_aktif_ditolak(): void
    {
        $this->produk('MATI', 10, ['aktif' => false]);

        $this->actingAs($this->admin())
            ->from('/kiraan-stok/create')
            ->post('/kiraan-stok', [])
            ->assertSessionHas('ralat');

        $this->assertSame(0, StockCount::count());
    }

    public function test_menyimpan_draf_tidak_mengubah_stok(): void
    {
        $produk = $this->produk('A', 40);
        $admin = $this->admin();
        $sesi = $this->bukaSesi($admin);
        $item = $sesi->items()->first();

        $this->actingAs($admin)
            ->put("/kiraan-stok/{$sesi->id}", ['kuantiti' => [$item->id => 33]])
            ->assertSessionHas('status');

        $this->assertSame(33, $item->fresh()->kuantiti_fizikal);
        $this->assertSame(40, $produk->fresh()->stok, 'Stok tidak boleh berubah sebelum pengesahan.');
        $this->assertSame(0, StockMovement::count());
        $this->assertSame('draf', $sesi->fresh()->status);
    }

    public function test_pengesahan_melaraskan_stok_dan_menjana_pergerakan(): void
    {
        $kurang = $this->produk('KURANG', 40);
        $lebih = $this->produk('LEBIH', 10);
        $sama = $this->produk('SAMA', 25);
        $admin = $this->admin();

        $sesi = $this->bukaSesi($admin);
        $item = $sesi->items()->with('product')->get()->keyBy('product.sku');

        $this->actingAs($admin)->put("/kiraan-stok/{$sesi->id}", [
            'kuantiti' => [
                $item['KURANG']->id => 37,
                $item['LEBIH']->id => 13,
                $item['SAMA']->id => 25,
            ],
        ]);

        $this->actingAs($admin)
            ->post("/kiraan-stok/{$sesi->id}/sahkan")
            ->assertRedirect("/kiraan-stok/{$sesi->id}");

        $this->assertSame(37, $kurang->fresh()->stok);
        $this->assertSame(13, $lebih->fresh()->stok);
        $this->assertSame(25, $sama->fresh()->stok);

        // Hanya produk yang berbeza menjana pergerakan.
        $this->assertSame(2, StockMovement::count());
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $kurang->id,
            'jenis' => 'pelarasan',
            'stok_sebelum' => 40,
            'stok_selepas' => 37,
            'rujukan' => $sesi->kod,
        ]);

        $sesi->refresh();
        $this->assertSame('selesai', $sesi->status);
        $this->assertSame($admin->id, $sesi->disahkan_oleh);
        $this->assertNotNull($sesi->disahkan_pada);
    }

    public function test_produk_yang_dibiarkan_kosong_tidak_disentuh(): void
    {
        $dikira = $this->produk('DIKIRA', 40);
        $dilangkau = $this->produk('LANGKAU', 99);
        $admin = $this->admin();

        $sesi = $this->bukaSesi($admin);
        $item = $sesi->items()->with('product')->get()->keyBy('product.sku');

        $this->actingAs($admin)->put("/kiraan-stok/{$sesi->id}", [
            'kuantiti' => [$item['DIKIRA']->id => 35, $item['LANGKAU']->id => null],
        ]);
        $this->actingAs($admin)->post("/kiraan-stok/{$sesi->id}/sahkan");

        $this->assertSame(35, $dikira->fresh()->stok);
        $this->assertSame(99, $dilangkau->fresh()->stok);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_pengesahan_tanpa_sebarang_kiraan_ditolak(): void
    {
        $produk = $this->produk('A', 40);
        $admin = $this->admin();
        $sesi = $this->bukaSesi($admin);

        $this->actingAs($admin)
            ->from("/kiraan-stok/{$sesi->id}")
            ->post("/kiraan-stok/{$sesi->id}/sahkan")
            ->assertSessionHas('ralat');

        $this->assertSame('draf', $sesi->fresh()->status);
        $this->assertSame(40, $produk->fresh()->stok);
    }

    public function test_sesi_selesai_tidak_boleh_diubah_lagi(): void
    {
        $produk = $this->produk('A', 40);
        $admin = $this->admin();
        $sesi = $this->bukaSesi($admin);
        $item = $sesi->items()->first();

        $this->actingAs($admin)->put("/kiraan-stok/{$sesi->id}", ['kuantiti' => [$item->id => 30]]);
        $this->actingAs($admin)->post("/kiraan-stok/{$sesi->id}/sahkan");

        $this->actingAs($admin)
            ->put("/kiraan-stok/{$sesi->id}", ['kuantiti' => [$item->id => 5]])
            ->assertSessionHasErrors('status');

        $this->assertSame(30, $produk->fresh()->stok, 'Stok kekal seperti selepas pengesahan pertama.');
    }

    public function test_membatalkan_sesi_tidak_melaraskan_stok(): void
    {
        $produk = $this->produk('A', 40);
        $admin = $this->admin();
        $sesi = $this->bukaSesi($admin);
        $item = $sesi->items()->first();

        $this->actingAs($admin)->put("/kiraan-stok/{$sesi->id}", ['kuantiti' => [$item->id => 12]]);

        $this->actingAs($admin)
            ->delete("/kiraan-stok/{$sesi->id}")
            ->assertRedirect('/kiraan-stok');

        $this->assertSame('dibatalkan', $sesi->fresh()->status);
        $this->assertSame(40, $produk->fresh()->stok);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_pengesahan_menggunakan_baki_terkini_bukan_gambaran_lama(): void
    {
        $produk = $this->produk('A', 40);
        $admin = $this->admin();
        $sesi = $this->bukaSesi($admin);
        $item = $sesi->items()->first();

        $this->actingAs($admin)->put("/kiraan-stok/{$sesi->id}", ['kuantiti' => [$item->id => 50]]);

        // Stok berubah selepas sesi dibuka tetapi sebelum disahkan.
        $this->actingAs($admin)->post('/stock', ['product_id' => $produk->id, 'jenis' => 'keluar', 'kuantiti' => 5]);

        $this->actingAs($admin)->post("/kiraan-stok/{$sesi->id}/sahkan");

        $this->assertSame(50, $produk->fresh()->stok);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produk->id,
            'jenis' => 'pelarasan',
            'stok_sebelum' => 35,
            'stok_selepas' => 50,
        ]);
    }

    public function test_halaman_kiraan_stok_boleh_dipapar(): void
    {
        $this->produk('A', 10);
        $admin = $this->admin();
        $sesi = $this->bukaSesi($admin);

        foreach (['/kiraan-stok', '/kiraan-stok/create', "/kiraan-stok/{$sesi->id}"] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_kod_sesi_dijana_mengikut_turutan(): void
    {
        $this->produk('A', 10);
        $admin = $this->admin();

        $pertama = $this->bukaSesi($admin);
        $kedua = $this->bukaSesi($admin);

        $tahun = now()->format('Y');
        $this->assertSame("KIRA-{$tahun}-001", $pertama->kod);
        $this->assertSame("KIRA-{$tahun}-002", $kedua->kod);
    }
}
