# WKY Inventory

Sistem pengurusan inventori berasaskan Laravel 13 untuk kegunaan dalaman — merekod produk,
kategori, pembekal, dan setiap pergerakan stok masuk/keluar dengan jejak audit penuh.

## Modul

| Modul | Keterangan |
|---|---|
| **Dashboard** | Kad statistik, carta Ringkasan Bulanan (kemasukan vs. pengeluaran 6 bulan), amaran stok rendah, pergerakan terkini, dan borang Tambah Stok Pantas |
| **Produk** | CRUD produk dengan SKU, harga kos/jual, unit, paras stok minimum |
| **Kategori** | Pengelasan produk. Tidak boleh dipadam selagi masih digunakan produk |
| **Pembekal** | Maklumat pembekal dan senarai produk yang dibekalkan |
| **Imbas Invois** | Muat naik foto atau PDF invois — AI membaca baris barang, memadankannya dengan produk, dan merekod stok masuk selepas disahkan |
| **Kiraan Stok** | Sesi kiraan fizikal (stock take): sistem simpan gambaran baki, staf masukkan kiraan sebenar, sistem tunjuk perbezaan dan laraskan stok selepas disahkan |
| **Pergerakan Stok** | Rekod stok masuk, keluar, dan pelarasan — setiap satu menyimpan baki sebelum/selepas |
| **Laporan Bulanan** | Pecahan masuk/keluar per produk mengikut bulan, perubahan bersih, dan susun atur mesra cetak |
| **Pengguna** | Pengurusan akaun dan peranan (admin / staf). Hanya admin boleh akses |

Kuantiti stok **tidak boleh** diubah terus melalui borang produk. Ia hanya berubah melalui modul
Pergerakan Stok atau pengesahan sesi Kiraan Stok, supaya setiap perubahan baki mempunyai rekod
siapa, bila, dan sebab.

### Aliran Kiraan Stok

1. **Buka sesi** — pilih skop (semua kategori atau satu kategori). Sistem menyenaraikan semua produk
   aktif dan menyimpan baki semasa sebagai *Kuantiti Rekod*. Stok belum berubah.
2. **Isi kiraan** — staf memasukkan *Kuantiti Fizikal*. Perbezaan dikira serta-merta dalam pelayar.
   Boleh disimpan sebagai draf dan disambung kemudian; produk yang dibiarkan kosong dilangkau.
3. **Sahkan** — setiap produk yang berbeza menjana satu pergerakan stok jenis `pelarasan` dengan
   rujukan kod sesi, dan baki produk ditetapkan kepada kuantiti fizikal.

Pada langkah pengesahan, baki dibaca semula daripada pangkalan data dan bukan daripada gambaran
sesi, kerana stok mungkin berubah antara pembukaan sesi dan pengesahan. Sesi yang telah selesai
atau dibatalkan tidak boleh diubah lagi.

## Keperluan

- PHP 8.3+
- MySQL 8
- Composer
- Node.js 20+ dan npm (untuk membina aset antara muka)

## Pemasangan

```bash
git clone https://github.com/Wekeyra/wky-inventory.git
cd wky-inventory
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run build
```

Cipta database, kemudian jalankan migration dan seeder:

```sql
CREATE DATABASE wky_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
php artisan serve
```

Semasa membangunkan antara muka, jalankan `npm run dev` dalam terminal berasingan supaya
perubahan CSS dan Blade dimuat semula secara automatik.

## Akaun contoh (daripada seeder)

| Emel | Kata Laluan | Peranan |
|---|---|---|
| `admin@wekeyra.test` | `password123` | Admin |
| `staf@wekeyra.test` | `password123` | Staf |

> Tukar kata laluan ini sebelum sistem digunakan untuk data sebenar.

## Ujian

```bash
php artisan test
```

- `tests/Feature/InventoryTest.php` — kawalan akses, paparan semua halaman utama, logik pergerakan
  stok (termasuk penolakan stok keluar yang melebihi baki), dan laporan bulanan.
- `tests/Feature/StockCountTest.php` — aliran kiraan stok: gambaran baki, penapisan kategori,
  draf yang tidak mengubah stok, pelarasan selepas pengesahan, dan sekatan pada sesi yang selesai.
- `tests/Feature/LocaleTest.php` — penukaran BM/EN, kekekalan pilihan merentas halaman,
  terjemahan mesej flash dan pengesahan, serta keselarian kunci antara dua fail bahasa.
- `tests/Feature/InvoiceScanTest.php` — imbasan invois: padanan SKU dan nama, baris tanpa
  padanan, pemilihan manual, baris dilangkau, pengesahan yang merekod stok masuk, dan
  pengendalian ralat AI. Menggunakan pengekstrak palsu — tiada panggilan API sebenar.

## Imbas Invois (AI)

Muat naik foto atau PDF invois; Claude membaca baris barangnya dan sistem memadankannya
dengan produk sedia ada. **Stok tidak berubah semasa imbasan** — anda melihat skrin semakan
dahulu, dan hanya menekan *Sahkan & Rekod Stok Masuk* yang menjana pergerakan stok.

### Persediaan

1. Daftar di https://console.anthropic.com dan jana kunci API.
2. Masukkan dalam `.env`:

   ```
   ANTHROPIC_API_KEY=sk-ant-...
   ```

3. `php artisan config:clear`

Tanpa kunci, halaman Imbas Invois masih boleh dibuka tetapi memaparkan arahan persediaan
dan butang imbas dilumpuhkan — tiada permintaan dihantar.

> ⚠️ **Imej invois dihantar ke pelayan Anthropic** untuk dibaca. Jangan imbas dokumen yang
> mengandungi maklumat yang tidak boleh keluar dari organisasi anda.

### Padanan produk

Padanan dibuat mengikut turutan: **SKU tepat**, kemudian **nama tepat**. Kedua-duanya
dinormalkan (huruf kecil, tanda baca dan ruang dibuang), jadi `ELK-001`, `elk 001`, dan
`ELK_001` dianggap sama.

Padanan kabur **tidak** digunakan. Padanan yang salah akan menambah stok pada produk yang
tidak berkaitan tanpa disedari, jadi baris yang tidak padan sengaja ditinggalkan kepada
pengguna untuk dipilih sendiri daripada senarai jatuh. Padanan yang ditukar oleh pengguna
ditanda *Dipilih manual* supaya jelas mana satu datang daripada AI.

### Konfigurasi

| Kunci `.env` | Lalai | Kegunaan |
|---|---|---|
| `ANTHROPIC_API_KEY` | — | Kunci API; wajib untuk mengimbas |
| `ANTHROPIC_MODEL` | `claude-opus-5` | Model yang digunakan |
| `ANTHROPIC_EFFORT` | `medium` | Tahap usaha — naikkan ke `high` untuk invois yang sukar dibaca |
| `ANTHROPIC_TIMEOUT` | `180` | Had masa panggilan (saat); juga menaikkan had masa PHP |
| `ANTHROPIC_SAIZ_MAKS_KB` | `10240` | Saiz maksimum fail yang dimuat naik |

[`InvoiceExtractor`](app/Services/Invoice/InvoiceExtractor.php) ialah antara muka, jadi
pembekal AI boleh ditukar tanpa menyentuh controller — dan ujian menggantikannya dengan
pelaksanaan palsu supaya suite ujian tidak pernah memanggil API sebenar.

## Dwibahasa (BM / EN)

Antara muka tersedia dalam Bahasa Melayu (lalai) dan English. Butang **BM / EN** di bar atas
setiap halaman — termasuk halaman log masuk — menukar bahasa serta-merta. Pilihan disimpan
dalam sesi, jadi ia kekal sehingga pengguna menukarnya semula.

| Fail | Kandungan |
|---|---|
| `lang/{ms,en}/wky.php` | Semua teks antara muka: menu, tajuk, label medan, butang, mesej |
| `lang/ms/validation.php` | Mesej pengesahan BM + nama medan (`attributes`) |
| `lang/ms/{auth,pagination}.php` | Mesej log masuk dan pautan penomboran |
| `config/bahasa.php` | Senarai bahasa yang disokong |

Untuk menambah bahasa ketiga: salin folder `lang/ms` ke kod locale baharu, terjemah isinya,
dan tambah satu baris dalam `config/bahasa.php`. Tiada perubahan pada view diperlukan.

Kunci yang tiada dalam satu bahasa akan jatuh semula kepada `APP_FALLBACK_LOCALE` (English),
jadi tiada teks yang hilang. Ujian `test_kedua_dua_fail_bahasa_mempunyai_kunci_yang_sama`
memastikan kedua-dua fail `wky.php` sentiasa selari.

## Antara muka

Dibina dengan **Tailwind CSS v4** melalui Vite. Tiada CDN — CSS, JavaScript, dan fon semuanya
dibungkus ke dalam `public/build`, jadi sistem berfungsi sepenuhnya tanpa sambungan internet.

| Fail | Peranan |
|---|---|
| `resources/css/app.css` | Token warna `@theme` dan kelas komponen (`.kad`, `.btn-utama`, `.jadual`, `.lencana-*`) |
| `resources/js/app.js` | Menu jatuh, modal, tutup amaran, dan Chart.js — pengganti Bootstrap JS |
| `resources/views/components/ikon.blade.php` | Ikon SVG terbaris (`<x-ikon nama="kotak" />`) |
| `resources/views/components/logo-wky.blade.php` | Logo — guna fail sebenar jika ada, jika tidak lukis SVG |
| `resources/views/components/latar-log-masuk.blade.php` | Latar konstelasi dan siluet bandar untuk halaman log masuk |

### Menukar logo

Letakkan fail logo anda di `public/images/` sebagai `logo-wky.svg`, `.png`, `.webp`, atau
`.jpg`. Komponen akan menggunakannya secara automatik dan melangkau lukisan SVG terbina —
tiada perubahan kod diperlukan. Buang fail itu untuk kembali kepada lukisan SVG.

Palet keseluruhan sistem dikawal oleh token `--color-*` di bahagian atas `app.css`. Tukar nilai
di situ dan jalankan `npm run build` untuk menukar rupa seluruh aplikasi.

Dua perkara yang perlu diberi perhatian apabila menyunting:

- `@apply` dalam Tailwind v4 hanya menerima **utiliti**, bukan kelas komponen tersuai. Kelas
  seperti `.btn-utama` ditulis penuh dan tidak saling `@apply` antara satu sama lain.
- Nama kelas mesti muncul sebagai teks penuh supaya Tailwind dapat mengesannya. Sebab itu
  `StockCount::kelasStatus()` dan `StockMovement::kelasJenis()` memulangkan nama kelas lengkap
  (`lencana-kuning`) dan bukan potongan yang dicantum (`lencana-` . `$warna`).

## Nota teknikal

- Kemas kini stok dibungkus dalam transaksi dengan `lockForUpdate()` untuk mengelak dua
  pengguna mengubah baki produk yang sama secara serentak.
- `public/build` tidak disimpan dalam Git. Selepas `git clone` atau `git pull` yang menyentuh
  antara muka, jalankan `npm run build` semula.
