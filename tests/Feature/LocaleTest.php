<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'workspace_id' => Workspace::firstOrCreate(['nama' => 'Syarikat Ujian'])->id,
            'name' => 'Admin Ujian',
            'email' => 'admin@ujian.test',
            'peranan' => 'admin',
            'password' => 'password123',
        ]);
    }

    public function test_bahasa_lalai_ialah_melayu(): void
    {
        $this->assertSame('ms', config('app.locale'));

        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Amaran Stok Rendah')
            ->assertDontSee('Low Stock Alerts');
    }

    public function test_menukar_ke_english_menterjemah_antara_muka_dalam_permintaan_yang_sama(): void
    {
        // Tiada ubah hala: halaman terus dipaparkan dalam bahasa baharu.
        $this->actingAs($this->admin())
            ->get('/dashboard?bahasa=en')
            ->assertOk()
            ->assertSee('Low Stock Alerts')
            ->assertSee('Stock Counts')
            ->assertDontSee('Amaran Stok Rendah');
    }

    public function test_menukar_kembali_ke_bm(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/dashboard?bahasa=en');
        $this->actingAs($admin)->get('/dashboard?bahasa=ms')->assertOk()->assertSee('Amaran Stok Rendah');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Amaran Stok Rendah');
    }

    public function test_penukar_bahasa_mengekalkan_penapis_pada_url(): void
    {
        $this->actingAs($this->admin())
            ->get('/products?cari=pen&stok_rendah=1')
            ->assertOk()
            ->assertSee('cari=pen', false)
            ->assertSee('bahasa=en', false);
    }

    public function test_bahasa_tidak_disokong_pada_url_diabaikan(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard?bahasa=de')
            ->assertOk()
            ->assertSee('Amaran Stok Rendah');
    }

    public function test_pilihan_bahasa_kekal_merentas_halaman(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/dashboard?bahasa=en');

        $this->actingAs($admin)->get('/products')->assertOk()->assertSee('Selling Price (RM)');
        $this->actingAs($admin)->get('/products/create')->assertOk()->assertSee('Add Product');
        $this->actingAs($admin)->get('/suppliers')->assertOk()->assertSee('Contact Person');
        $this->actingAs($admin)->get('/kiraan-stok')->assertOk()->assertSee('Physical Stock Count Sessions');
    }

    public function test_locale_tidak_disokong_dipulangkan_404(): void
    {
        $this->actingAs($this->admin())->get('/bahasa/de')->assertNotFound();
    }

    public function test_halaman_log_masuk_boleh_tukar_bahasa_tanpa_log_masuk(): void
    {
        $this->get('/login')->assertOk()->assertSee('Kata Laluan');

        $this->get('/login?bahasa=en')->assertOk()->assertSee('Password')->assertDontSee('Kata Laluan');

        $this->get('/login')->assertOk()->assertSee('Password');
    }

    public function test_laluan_bahasa_lama_masih_berfungsi(): void
    {
        // Dikekalkan untuk pautan lama yang mungkin ditanda buku.
        $this->get('/bahasa/en')->assertRedirect();

        $this->get('/login')->assertOk()->assertSee('Password');
    }

    public function test_mesej_flash_mengikut_bahasa_terpilih(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/categories', ['kod' => 'A1', 'nama' => 'Satu'])
            ->assertSessionHas('status', 'Kategori berjaya ditambah.');

        $this->actingAs($admin)->get('/dashboard?bahasa=en');

        $this->actingAs($admin)->post('/categories', ['kod' => 'A2', 'nama' => 'Two'])
            ->assertSessionHas('status', 'Category added successfully.');
    }

    public function test_mesej_pengesahan_diterjemah(): void
    {
        $admin = $this->admin();

        // Nama medan datang daripada lang/ms/validation.php 'attributes'.
        $this->actingAs($admin)->from('/categories/create')->post('/categories', [])
            ->assertSessionHasErrors(['kod' => 'Medan Kod wajib diisi.']);

        $this->actingAs($admin)->get('/dashboard?bahasa=en');

        $this->actingAs($admin)->from('/categories/create')->post('/categories', [])
            ->assertSessionHasErrors(['kod' => 'The kod field is required.']);
    }

    /**
     * Nota pada halaman imbas pernah mengisi pemegang tempat :tidak dengan
     * wky.umum.tiada, yang bermaksud "None" dalam bahasa Inggeris — halaman
     * produksi memaparkan "Stock does None change now". Ayat yang menafikan
     * sesuatu tidak boleh dicantum daripada perkataan tunggal, jadi ujian ini
     * menyemak ayat penuh dalam kedua-dua bahasa.
     */
    public function test_nota_imbas_menegaskan_stok_tidak_berubah_dalam_kedua_dua_bahasa(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/imbas-invois/create')
            ->assertOk()
            ->assertSee('Stok <strong>tidak</strong> berubah sekarang', false)
            ->assertDontSee('Stok Tiada berubah');

        $this->actingAs($admin)->get('/imbas-invois/create?bahasa=en')
            ->assertOk()
            ->assertSee('Stock does <strong>not</strong> change now', false)
            ->assertDontSee('does None change');
    }

    public function test_kedua_dua_fail_bahasa_mempunyai_kunci_yang_sama(): void
    {
        $ms = array_keys(Arr::dot(require lang_path('ms/wky.php')));
        $en = array_keys(Arr::dot(require lang_path('en/wky.php')));

        sort($ms);
        sort($en);

        $this->assertSame($ms, $en, 'Kunci terjemahan BM dan EN mesti sepadan supaya tiada teks hilang.');
    }
}
