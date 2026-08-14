<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Butang kembali kontekstual pada bar atas.
 *
 * Destinasinya dikira daripada nama laluan semasa dan bukan daripada sejarah
 * pelayar: url()->previous() boleh menunjuk ke tapak lain, ke halaman yang baru
 * sahaja dipadam, atau ke borang yang baru sahaja dihantar.
 */
class ButangKembaliTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@ujian.test'],
            [
                'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
                'name' => 'Ujian',
                'peranan' => 'admin',
                'password' => 'password123',
            ],
        );
    }

    public function test_halaman_borang_membawa_butang_kembali_ke_senarainya(): void
    {
        $pengguna = $this->pengguna();

        $jangkaan = [
            'stock.create' => 'stock.index',
            'products.create' => 'products.index',
            'categories.create' => 'categories.index',
            'stock-counts.create' => 'stock-counts.index',
        ];

        foreach ($jangkaan as $laluan => $induk) {
            $this->actingAs($pengguna)
                ->get(route($laluan))
                ->assertSee('href="'.route($induk).'"', false);
        }
    }

    public function test_halaman_butiran_membawa_butang_kembali(): void
    {
        $pengguna = $this->pengguna();

        $produk = Product::create([
            'workspace_id' => $pengguna->workspace_id,
            'sku' => 'BRG-1', 'nama' => 'Barang', 'unit' => 'unit',
            'harga_kos' => 5, 'harga_jual' => 10, 'stok' => 1, 'stok_minimum' => 1,
        ]);

        $this->actingAs($pengguna)
            ->get(route('products.show', $produk))
            ->assertSee('href="'.route('products.index').'"', false);
    }

    /*
     | Halaman senarai dan dashboard ialah puncak setiap cabangnya. Butang
     | kembali di situ tiada tempat untuk pergi.
     */
    public function test_halaman_senarai_dan_dashboard_tiada_butang_kembali(): void
    {
        $pengguna = $this->pengguna();

        foreach (['dashboard', 'products.index', 'stock.index'] as $laluan) {
            $this->actingAs($pengguna)
                ->get(route($laluan))
                ->assertDontSee('aria-label="'.__('wky.aksi.kembali').'"', false);
        }
    }

    /*
     | Laporan Bulanan ialah halaman nav peringkat atas tanpa senarai induk.
     | reports.index tidak wujud, jadi tiada butang yang dipaparkan — dan bukan
     | pautan rosak ke laluan yang tiada.
     */
    public function test_halaman_tanpa_senarai_induk_tiada_butang_kembali(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('reports.monthly'))
            ->assertOk()
            ->assertDontSee('aria-label="'.__('wky.aksi.kembali').'"', false);
    }
}
