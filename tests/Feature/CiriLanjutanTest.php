<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suis modul lanjutan bagi setiap ruang kerja.
 *
 * Sistem ini tumbuh melepasi MVP jauh lebih cepat daripada pengguna barunya.
 * Syarikat baharu bermula dengan lapan fungsi asas sahaja; modul lanjutan
 * dibuka apabila ia benar-benar diperlukan.
 */
class CiriLanjutanTest extends TestCase
{
    use RefreshDatabase;

    /** Ruang kerja gaya syarikat baharu: MVP sahaja. */
    private function ruangKerjaBaharu(): Workspace
    {
        return Workspace::create(['nama' => 'Syarikat Baharu', 'ciri' => []]);
    }

    private function pengguna(Workspace $ruang, string $peranan = 'admin'): User
    {
        return User::create([
            'workspace_id' => $ruang->id,
            'name' => 'Ujian',
            'email' => $peranan.'-'.$ruang->id.'@ujian.test',
            'peranan' => $peranan,
            'password' => 'password123',
        ]);
    }

    /*
     | Keputusan "mula ringkas" milik saat sebuah syarikat mendaftar, bukan
     | setiap baris dalam jadual ruang kerja.
     */
    public function test_pendaftaran_syarikat_baharu_bermula_dengan_mvp_sahaja(): void
    {
        $this->post('/daftar', [
            'nama_syarikat' => 'Kedai Baharu',
            'name' => 'Pemilik',
            'email' => 'pemilik@baharu.test',
            'password' => 'rahsia12345',
            'password_confirmation' => 'rahsia12345',
        ]);

        $ruang = Workspace::where('nama', 'Kedai Baharu')->firstOrFail();

        $this->assertSame([], $ruang->ciriAktif());
    }

    /*
     | Ruang kerja yang dicipta tanpa menyatakan cirinya — ujian, seeder,
     | arahan konsol — mendapat semuanya. Tanpa ini, setiap jalan lain untuk
     | mencipta ruang kerja akan senyap-senyap kehilangan modulnya.
     */
    public function test_ruang_kerja_tanpa_ciri_dinyatakan_mendapat_semuanya(): void
    {
        $ruang = Workspace::create(['nama' => 'Ruang Lalai']);

        $this->assertSame(Workspace::CIRI, $ruang->ciriAktif());
    }

    public function test_nav_syarikat_baharu_membawa_teras_tanpa_modul_lanjutan(): void
    {
        $ruang = $this->ruangKerjaBaharu();

        $respons = $this->actingAs($this->pengguna($ruang))->get(route('dashboard'));

        // Teras sentiasa hidup.
        $respons->assertSee(route('products.index'), false)
            ->assertSee(route('stock.index'), false)
            ->assertSee(route('stock-counts.index'), false)
            ->assertSee(route('reports.monthly'), false);

        // Modul lanjutan tiada.
        $respons->assertDontSee(route('purchase-orders.index'), false)
            ->assertDontSee(route('sales.index'), false)
            ->assertDontSee(route('analytics.index'), false)
            ->assertDontSee(route('invoice-scans.index'), false)
            ->assertDontSee(route('locations.index'), false);
    }

    /*
     | 404 dan bukan 403: modul yang tidak dihidupkan sepatutnya tidak wujud
     | dari sudut pandang ruang kerja itu. Ini menahan URL yang ditaip terus
     | atau ditanda buku sebelum ciri itu dimatikan.
     */
    public function test_laluan_modul_yang_dimatikan_pulangkan_404(): void
    {
        $pengguna = $this->pengguna($this->ruangKerjaBaharu());

        foreach ([
            'purchase-orders.index',
            'sales.index',
            'analytics.index',
            'invoice-scans.index',
            'locations.index',
            'transfers.index',
        ] as $laluan) {
            $this->actingAs($pengguna)->get(route($laluan))->assertNotFound();
        }
    }

    public function test_menghidupkan_ciri_membuka_nav_dan_laluannya(): void
    {
        $ruang = $this->ruangKerjaBaharu();
        $admin = $this->pengguna($ruang);

        $this->actingAs($admin)->get(route('sales.index'))->assertNotFound();

        $this->actingAs($admin)
            ->put(route('ciri.update'), ['ciri' => ['jualan']])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('sales.index'))->assertOk();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSee(route('sales.index'), false);

        // Yang lain kekal tertutup: menghidupkan satu tidak membuka semuanya.
        $this->actingAs($admin)->get(route('analytics.index'))->assertNotFound();
    }

    /*
     | Suis ini bukan butang padam yang menyamar. Mematikan Jualan tidak
     | bermakna jualan itu tidak pernah berlaku, dan pergerakan stok yang
     | terhasil daripadanya masih merujuk kodnya.
     */
    public function test_mematikan_modul_tidak_membuang_datanya(): void
    {
        $ruang = Workspace::create(['nama' => 'Ada Data']);
        $admin = $this->pengguna($ruang);

        Product::create([
            'workspace_id' => $ruang->id,
            'sku' => 'BRG-1', 'nama' => 'Barang', 'unit' => 'unit',
            'harga_kos' => 5, 'harga_jual' => 10, 'stok' => 50, 'stok_minimum' => 1,
        ]);

        $produk = Product::where('sku', 'BRG-1')->firstOrFail();

        $this->actingAs($admin)->post(route('sales.store'), [
            'baris' => [['product_id' => $produk->id, 'kuantiti' => 5, 'harga_jual' => 10]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Sale::count());

        // Matikan modul jualan.
        $this->actingAs($admin)->put(route('ciri.update'), ['ciri' => []]);

        $this->actingAs($admin)->get(route('sales.index'))->assertNotFound();
        $this->assertSame(1, Sale::count());

        // Hidupkan semula — jualan itu masih ada.
        $this->actingAs($admin)->put(route('ciri.update'), ['ciri' => ['jualan']]);
        $this->actingAs($admin)->get(route('sales.index'))->assertOk()->assertSee('JL-');
    }

    public function test_staf_tidak_boleh_menukar_ciri(): void
    {
        $ruang = $this->ruangKerjaBaharu();
        $staf = $this->pengguna($ruang, 'staf');

        $this->actingAs($staf)->get(route('ciri.edit'))->assertForbidden();
        $this->actingAs($staf)->put(route('ciri.update'), ['ciri' => ['jualan']])->assertForbidden();

        $this->assertSame([], $ruang->fresh()->ciriAktif());
    }

    public function test_nama_ciri_yang_tidak_dikenali_ditolak(): void
    {
        $ruang = $this->ruangKerjaBaharu();

        $this->actingAs($this->pengguna($ruang))
            ->put(route('ciri.update'), ['ciri' => ['jualan', 'entah-apa']])
            ->assertSessionHasErrors('ciri.1');

        $this->assertSame([], $ruang->fresh()->ciriAktif());
    }

    public function test_pintasan_imbas_hilang_daripada_butang_pantas(): void
    {
        $ruang = $this->ruangKerjaBaharu();

        $this->actingAs($this->pengguna($ruang))
            ->get(route('dashboard'))
            ->assertDontSee(__('wky.pantas.imbas_resit'))
            // Pintasan stok kekal: ia teras, bukan modul lanjutan.
            ->assertSee(__('wky.pantas.stok_masuk'));
    }

    public function test_medan_lokasi_hilang_daripada_borang_stok(): void
    {
        $ruang = $this->ruangKerjaBaharu();

        $this->actingAs($this->pengguna($ruang))
            ->get(route('stock.create'))
            ->assertOk()
            ->assertDontSee('<label for="location_id"', false);
    }

    /*
     | Borang stok tanpa medan lokasi mesti tetap boleh dihantar: pengawal
     | jatuh kepada gudang lalai apabila tiada lokasi diberi.
     */
    public function test_stok_masih_boleh_direkod_tanpa_medan_lokasi(): void
    {
        $ruang = $this->ruangKerjaBaharu();
        $admin = $this->pengguna($ruang);

        Product::create([
            'workspace_id' => $ruang->id,
            'sku' => 'BRG-2', 'nama' => 'Barang Dua', 'unit' => 'unit',
            'harga_kos' => 5, 'harga_jual' => 10, 'stok' => 0, 'stok_minimum' => 1,
        ]);

        $produk = Product::where('sku', 'BRG-2')->firstOrFail();

        $this->actingAs($admin)->post('/stock', [
            'product_id' => $produk->id, 'jenis' => 'masuk', 'sebab' => 'pembelian', 'kuantiti' => 7,
        ])->assertSessionHasNoErrors();

        $this->assertSame(7, $produk->fresh()->stok);
    }

    public function test_halaman_tetapan_menyenaraikan_semua_ciri(): void
    {
        $respons = $this->actingAs($this->pengguna($this->ruangKerjaBaharu()))
            ->get(route('ciri.edit'))
            ->assertOk();

        foreach (Workspace::CIRI as $ciri) {
            $respons->assertSee(__('wky.ciri.'.$ciri));
        }
    }
}
