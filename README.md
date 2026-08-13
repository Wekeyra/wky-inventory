# WKY Inventory

Sistem pengurusan inventori berasaskan Laravel 13 — merekod produk, kategori, pembekal, dan
setiap pergerakan stok masuk/keluar dengan jejak audit penuh.

Setiap syarikat yang mendaftar mendapat **ruang kerjanya sendiri** dengan inventori yang
berasingan sepenuhnya, jadi satu pemasangan boleh menampung banyak syarikat tanpa data
bertemu antara satu sama lain.

## Modul

| Modul | Keterangan |
|---|---|
| **Dashboard** | Kad statistik, carta Ringkasan Bulanan (kemasukan vs. pengeluaran 6 bulan), amaran stok rendah, pergerakan terkini, dan borang Tambah Stok Pantas |
| **Produk** | CRUD produk dengan SKU, harga kos/jual, unit, paras stok minimum |
| **Kategori** | Pengelasan produk. Tidak boleh dipadam selagi masih digunakan produk |
| **Pembekal** | Maklumat pembekal dan senarai produk yang dibekalkan |
| **Imbas Invois** | Ambil gambar terus dengan kamera atau muat naik foto/PDF — AI membaca baris barang, memadankannya dengan produk, dan merekod stok masuk selepas disahkan. Boleh juga disimpan dahulu dan dibaca kemudian |
| **Kiraan Stok** | Sesi kiraan fizikal (stock take): sistem simpan gambaran baki, staf masukkan kiraan sebenar, sistem tunjuk perbezaan dan laraskan stok selepas disahkan |
| **Pergerakan Stok** | Rekod stok masuk, keluar, dan pelarasan — setiap satu menyimpan baki sebelum/selepas |
| **Laporan Bulanan** | Pecahan masuk/keluar per produk mengikut bulan, perubahan bersih, dan susun atur mesra cetak |
| **Pengguna** | Pengurusan akaun dan peranan (admin / staf) dalam ruang kerja sendiri. Hanya admin boleh akses |

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

## Butang tindakan pantas

Setiap halaman dalam sistem membawa satu butang bulat terapung di penjuru bawah kanan
([`partials/butang-pantas.blade.php`](resources/views/partials/butang-pantas.blade.php)) yang
membuka empat pintasan: **Imbas Resit, Muat Naik, Tambah Produk, Tambah Kategori**.

Ia menggunakan pencetus `data-jatuh` yang sama seperti menu lain, jadi ia mewarisi
tutup-bila-klik-luar dan tutup-bila-Escape tanpa JavaScript baharu.

Imbas Resit dan Muat Naik kedua-duanya menuju ke halaman imbas yang sama — ia memang satu
halaman — tetapi membawa `?mod=`. `mod=kamera` menekan butang kamera terus; `mod=fail` hanya
**menumpukan** medan fail dan tidak membuka pemilih fail, kerana pelayar menyekat pembukaan
dialog fail tanpa gerak isyarat pengguna.

## Halaman pendaratan

`/` ialah halaman pendaratan awam. Pengguna yang sudah log masuk dialihkan terus ke dashboard,
kerana `/` ialah URL yang paling kerap ditanda buku dan halaman pemasaran hanya menambah satu
klik sebelum kerja sebenar.

Navigasinya — **Utama, Ciri, Harga, Inventori, Tentang Kami** — ialah pautan penambat ke seksyen
pada halaman yang sama. Senarai pautan ditakrifkan **sekali** dalam `landing.blade.php` dan
dipakai oleh nav desktop dan nav mudah alih, supaya kedua-duanya tidak boleh terpesong apabila
pautan ditambah. Ujian `test_setiap_pautan_nav_mempunyai_seksyennya` memastikan setiap pautan
mempunyai seksyen dengan `id` yang sepadan — pautan yang menatal ke tempat kosong tidak akan
lepas.

Salinan seksyen **Ciri** dikongsi dengan halaman pendaftaran (`wky.auth.ciri_*`) dan bukan
disalin, supaya kedua-dua halaman tidak menyimpang apabila teksnya disunting.

> ⚠️ **Harga pada halaman itu ialah contoh, bukan harga sebenar.** Tukar kunci
> `landing.harga_*` dalam `lang/ms/wky.php` **dan** `lang/en/wky.php` kepada tawaran sebenar
> anda sebelum memasarkan halaman ini.

## Ruang kerja

Setiap syarikat memiliki satu **ruang kerja**. Produk, kategori, pembekal, pergerakan stok,
sesi kiraan, imbasan invois dan pengguna semuanya dimiliki oleh satu ruang kerja, dan tidak
pernah bertemu data ruang kerja lain.

Pengasingan dikuatkuasakan pada peringkat **model** melalui
[`MilikRuangKerja`](app/Models/Concerns/MilikRuangKerja.php), bukan dengan menapis pada setiap
pertanyaan. Sebabnya mudah: satu pertanyaan yang terlepas sudah cukup untuk membocorkan data
satu syarikat kepada syarikat lain. Dengan skop global pada model, laluan yang tidak pernah
disentuh pun tetap terasing — termasuk pengikatan model pada laluan, yang memulangkan **404**
apabila rekod itu milik ruang kerja lain, bukan sekadar menyembunyikannya.

Beberapa akibat yang perlu diketahui semasa menyunting kod:

- Baris baharu ditanda dengan ruang kerja pengguna secara automatik semasa `creating`. Tiada
  controller perlu menetapkan `workspace_id` sendiri.
- Apabila tiada sesiapa log masuk — migrasi, arahan konsol — skop tidak dipasang, kerana
  konteks itu memang perlu melihat semua ruang kerja. Kod di situ mesti menapis
  `workspace_id` sendiri; lihat `ruang-kerja:kosongkan` sebagai contoh.
- Model `User` **tidak** menggunakan skop ini. Skop itu perlu membaca pengguna yang sedang log
  masuk, dan membacanya melalui model yang sama akan berulang tanpa henti. Pemilikan pengguna
  disemak terus dalam `UserController`.
- Kod dan SKU unik **dalam ruang kerja**, bukan merentas sistem. Dua syarikat berlainan bebas
  menggunakan `ELK-001` yang sama. Peraturan `unique` dan `exists` dalam controller turut
  berskop, jadi id milik syarikat lain tidak boleh dipaut dengan menyuapnya terus ke borang.

### Memisahkan akaun sedia ada

Migrasi ruang kerja meletakkan semua akaun yang wujud sebelum ini dalam satu ruang kerja lalai
supaya tiada data hilang. Akaun yang sepatutnya berasingan boleh dipindahkan ke ruang kerja
kosong miliknya:

```bash
php artisan pengguna:pisah emel@syarikat.com --nama="Nama Syarikat"
```

Data lama kekal milik ruang kerja asal. Arahan ini menolak permintaan apabila akaun itu
satu-satunya pengguna dalam ruang kerjanya, kerana memindahkannya akan meninggalkan data di
situ tanpa sesiapa yang boleh mencapainya.

## Akaun dan log masuk

Sesiapa boleh mendaftar di `/daftar` dengan mengisi nama syarikat. Sistem mencipta ruang kerja
baharu yang kosong dan pendaftar menjadi **admin** ruang kerja itu, jadi dia boleh menambah
stafnya sendiri melalui halaman Pengguna. Peranan ditetapkan dalam controller dan bukan
daripada borang, supaya pendaftaran sendiri tidak boleh menghasilkan admin sistem.

### Log masuk Google

Butang **Teruskan dengan Google** muncul pada halaman log masuk dan pendaftaran hanya apabila
kedua-dua kunci di bawah diisi. Tanpa kunci, butang itu tersembunyi dan laluannya memulangkan
404 — pemasangan tanpa Google tetap berfungsi penuh.

| Kunci `.env` | Kegunaan |
|---|---|
| `GOOGLE_CLIENT_ID` | Client ID daripada Google Cloud Console |
| `GOOGLE_CLIENT_SECRET` | Client secret |
| `GOOGLE_REDIRECT_URI` | Lalai `{APP_URL}/auth/google/callback` |

Daftar kelayakan OAuth di https://console.cloud.google.com/apis/credentials sebagai *Web
application*, dan masukkan `{APP_URL}/auth/google/callback` sebagai *Authorized redirect URI*.

Emel Google yang belum disahkan ditolak, kerana emel yang tidak disahkan tidak boleh
dipercayai untuk memadankan akaun sedia ada. Akaun Google baharu memulakan ruang kerjanya
sendiri; akaun sedia ada yang emelnya sepadan akan dipautkan secara automatik.

## Keperluan

- PHP 8.3+
- MySQL 8
- Composer
- Node.js 20+ dan npm (untuk membina aset antara muka)

## Pemasangan

```bash
git clone https://github.com/Wekeyra/wky-inventory.git
cd wky-inventory
```

Cipta database terlebih dahulu:

```sql
CREATE DATABASE wky_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Kemudian pasang segalanya dengan satu arahan:

```bash
composer setup
```

`composer setup` menjalankan `composer install`, menyalin `.env.example` kepada `.env`, menjana
kunci aplikasi, menjalankan migrasi, memasang pakej npm, dan membina aset. Langkah yang sama
secara manual:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Database mesti wujud **sebelum** `composer setup` dijalankan, kerana langkah migrasi di dalamnya
menyambung terus mengikut tetapan `DB_*`. Kalau pangkalan data anda bukan `wky_inventory` pada
`root` tanpa kata laluan, salin `.env` dan betulkan tetapan itu dahulu, kemudian jalankan langkah
manual di atas.

Jalankan pelayan:

```bash
php artisan serve
```

Semasa membangunkan antara muka, jalankan `npm run dev` dalam terminal berasingan supaya
perubahan CSS dan Blade dimuat semula secara automatik. Sebagai ganti, `composer dev` menjalankan
pelayan, pendengar baris gilir, log langsung (`pail`), dan Vite serentak dalam satu terminal.

## Akaun pertama

Sistem bermula **kosong sepenuhnya** — tiada produk contoh dan tiada akaun terbina. Buka
`/daftar`, isi nama syarikat anda, dan sistem mencipta ruang kerja kosong dengan anda sebagai
adminnya. Staf ditambah kemudian melalui halaman Pengguna.

Tiada akaun berkata laluan lalai sengaja dibuat, kerana halaman pendaftaran terbuka kepada umum
dan kelayakan lalai yang terlepas pandang pada URL awam bermakna sesiapa boleh masuk.

### Mengosongkan ruang kerja sedia ada

Ruang kerja yang mewarisi data lama boleh dikosongkan supaya ia bermula bersih:

```bash
php artisan ruang-kerja:kosongkan "Nama Syarikat"
```

Arahan memaparkan bilangan setiap jenis rekod yang akan dibuang dan meminta pengesahan dahulu.
Akaun pengguna dan ruang kerja itu sendiri tidak disentuh — hanya produk, kategori, pembekal,
pergerakan stok, sesi kiraan dan imbasan invois. Fail invois yang tersimpan turut dibuang.
Gunakan `--force` untuk melangkau soalan pengesahan dalam skrip.

## Deploy

Selepas setiap deploy yang membawa migrasi baharu:

```bash
php artisan migrate --force
```

Letakkan arahan ini dalam *deploy command* hos anda supaya ia berjalan automatik. Migrasi yang
tidak dijalankan menyebabkan setiap halaman yang menyentuh pangkalan data memulangkan ralat
500, sementara halaman statik seperti log masuk masih kelihatan normal — corak yang mudah
disalah anggap sebagai pepijat kod.

## Ujian

```bash
composer test
```

`composer test` menjalankan `config:clear` dahulu sebelum `php artisan test`. Konfigurasi yang
telah dicache akan mengekalkan nilai `.env` lama semasa ujian dijalankan — termasuk kunci API
sebenar — jadi membersihkannya dahulu memastikan ujian membaca apa yang ditetapkan oleh suite
itu sendiri. `php artisan test` sahaja tetap berfungsi apabila tiada cache konfigurasi.

Ujian berjalan pada **SQLite dalam ingatan** (`phpunit.xml`), bukan MySQL. Database pembangunan
anda tidak disentuh, dan tiada persediaan pangkalan data diperlukan untuk menjalankan suite ini.

- `tests/Feature/InventoryTest.php` — kawalan akses, paparan semua halaman utama, logik pergerakan
  stok (termasuk penolakan stok keluar yang melebihi baki), dan laporan bulanan.
- `tests/Feature/StockCountTest.php` — aliran kiraan stok: gambaran baki, penapisan kategori,
  draf yang tidak mengubah stok, pelarasan selepas pengesahan, dan sekatan pada sesi yang selesai.
- `tests/Feature/LocaleTest.php` — penukaran BM/EN, kekekalan pilihan merentas halaman,
  terjemahan mesej flash dan pengesahan, serta keselarian kunci antara dua fail bahasa.
- `tests/Feature/InvoiceScanTest.php` — imbasan invois: padanan SKU dan nama, baris tanpa
  padanan, pemilihan manual, baris dilangkau, pengesahan yang merekod stok masuk, dan
  pengendalian ralat AI. Menggunakan pengekstrak palsu — tiada panggilan API sebenar.
- `tests/Feature/WorkspaceIsolationTest.php` — membina dua syarikat lengkap dan memastikan
  tiada laluan membocorkan data antara keduanya: senarai, dashboard, capaian terus melalui
  URL, dan percubaan merekod stok untuk produk syarikat lain. Turut mengesahkan dua syarikat
  boleh menggunakan SKU yang sama tanpa berlanggar.
- `tests/Feature/RegistrationTest.php` — pendaftaran sendiri, ruang kerja berasingan bagi
  setiap pendaftaran, dan kemunculan butang Google mengikut konfigurasi.
- `tests/Feature/ButangPantasTest.php` — butang tindakan pantas: kehadirannya pada setiap
  halaman sistem, keempat-empat pintasan, `?mod=` yang membezakan Imbas dan Muat Naik, dan
  ketiadaannya pada halaman awam.
- `tests/Feature/KataLaluanTest.php` — butang mata pada kelima-lima medan kata laluan,
  termasuk `type="button"` supaya menekannya tidak menghantar borang.
- `tests/Feature/LandingTest.php` — halaman pendaratan: setiap pautan nav mempunyai seksyennya,
  ketiga-tiga pakej harga dipaparkan, pilihan bahasa dihormati, dan pengguna yang sudah log
  masuk dialihkan ke dashboard.
- `tests/Feature/PisahkanPenggunaTest.php` — arahan `pengguna:pisah`, termasuk penolakan
  apabila akaun itu satu-satunya pengguna dalam ruang kerjanya.
- `tests/Feature/KosongkanRuangKerjaTest.php` — arahan `ruang-kerja:kosongkan`: semua rekod
  inventori dibuang, akaun pengguna kekal, ruang kerja lain tidak disentuh, dan tiada apa
  yang dibuang apabila pengesahan ditolak.

## Imbas Invois (AI)

Muat naik foto atau PDF invois; Claude membaca baris barangnya dan sistem memadankannya
dengan produk sedia ada. **Stok tidak berubah semasa imbasan** — anda melihat skrin semakan
dahulu, dan hanya menekan *Sahkan & Rekod Stok Masuk* yang menjana pergerakan stok.

### Ambil gambar terus

Butang **Ambil Gambar** membuka pratonton kamera dalam halaman dan menyerahkan hasilnya kepada
medan fail yang sama, jadi semua pengesahan dan laluan muat naik kekal tidak berubah. Pratonton
kecil dengan butang *Ambil Semula* muncul selepas menangkap, supaya gambar kabur dapat diganti
sebelum membazir satu panggilan AI.

- Gambar dikecilkan kepada **2000px** sebelum dihantar. Teks invois masih tajam untuk dibaca,
  tetapi failnya jauh lebih kecil daripada keluaran penuh kamera telefon moden.
- Kamera belakang digunakan pada telefon (`facingMode: environment`). Butang *Tukar Kamera*
  muncul hanya apabila peranti mempunyai lebih daripada satu kamera.
- Kamera dimatikan apabila modal ditutup melalui butang, kekunci Escape mahupun klik latar,
  supaya lampu kamera tidak kekal menyala.
- Pratonton dalam halaman memerlukan **HTTPS**. Pada halaman biasa seperti `http://…` di
  Laragon, butang itu jatuh kepada input `capture="environment"` — yang tetap membuka aplikasi
  kamera pada telefon. Ini sekatan keselamatan pelayar, bukan pepijat.

### Simpan dahulu, baca kemudian

Butang **Simpan Sahaja** menyimpan gambar tanpa memanggil AI. Imbasan itu berstatus *Belum
Dibaca* sehingga sesiapa menekan **Baca dengan AI** pada halamannya. Berguna untuk menangkap
invois di kaunter dengan pantas dan memprosesnya kemudian, dan ia berfungsi walaupun kunci API
belum ditetapkan.

Gambar juga disimpan **sebelum** AI dipanggil pada aliran imbas biasa. Kalau bacaan gagal —
perkhidmatan sibuk, invois tidak jelas, kunci tiada — gambar tidak hilang; imbasan kekal
*Belum Dibaca* dan boleh dicuba semula tanpa memuat naik semula.

Imbasan yang belum dibaca tidak boleh disahkan, kerana tiada baris untuk direkod sebagai stok.

### Persediaan

1. Daftar di https://console.anthropic.com dan jana kunci API.
2. Masukkan dalam `.env`:

   ```
   ANTHROPIC_API_KEY=sk-ant-...
   ```

3. `php artisan config:clear`

Tanpa kunci, halaman Imbas Invois masih boleh dibuka tetapi memaparkan arahan persediaan dan
butang imbas dilumpuhkan — tiada permintaan dihantar. **Simpan Sahaja** tetap berfungsi, jadi
invois masih boleh ditangkap dan dibaca selepas kunci ditetapkan.

Pada hos seperti Laravel Cloud, kunci diletakkan dalam *Environment Variables* environment
berkenaan dan bukan dalam fail `.env` repositori.

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
| `ANTHROPIC_EFFORT` | `medium` | Tahap usaha (`low`, `medium`, `high`, `xhigh`, `max`) — naikkan ke `high` untuk invois yang sukar dibaca |
| `ANTHROPIC_TIMEOUT` | `180` | Had masa panggilan (saat); juga menaikkan had masa PHP |
| `ANTHROPIC_SAIZ_MAKS_KB` | `10240` | Saiz maksimum fail yang dimuat naik |

[`InvoiceExtractor`](app/Services/Invoice/InvoiceExtractor.php) ialah antara muka, jadi
pembekal AI boleh ditukar tanpa menyentuh controller — dan ujian menggantikannya dengan
pelaksanaan palsu supaya suite ujian tidak pernah memanggil API sebenar.

## Dwibahasa (BM / EN)

Antara muka tersedia dalam Bahasa Melayu (lalai) dan English. Butang **BM / EN** di bar atas
setiap halaman — termasuk halaman log masuk dan pendaftaran — menukar bahasa serta-merta.
Pilihan disimpan dalam sesi, jadi ia kekal sehingga pengguna menukarnya semula.

Pautan bahasa menuju ke URL halaman semasa dengan `?bahasa=xx`, yang dibaca oleh middleware
`SetLocale`. Ia menyimpan pilihan **dan** terus menggunakannya dalam permintaan yang sama, jadi
menukar bahasa hanya satu permintaan HTTP dan bukan dua. Kerana pautan dibina dengan
`fullUrlWithQuery()`, penapis carian dan nombor halaman pada URL semasa turut dikekalkan.
Laluan lama `/bahasa/{locale}` masih berfungsi untuk pautan yang ditanda buku.

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
| `resources/views/components/jenama-wky.blade.php` | Kata jenama *WKY INVENTORY* mengikut warna logo |
| `resources/views/components/latar-log-masuk.blade.php` | Latar konstelasi dan siluet bandar untuk halaman log masuk dan pendaftaran |
| `resources/views/components/medan-kata-laluan.blade.php` | Medan kata laluan berserta butang mata |
| `resources/views/components/tajuk-seksyen.blade.php` | Tajuk seksyen halaman pendaratan: garis aksen, tajuk, teks pengenalan |
| `resources/views/partials/butang-google.blade.php` | Butang log masuk Google; menyembunyikan dirinya apabila OAuth belum dikonfigur |

Nama jenama ditulis sebagai teks dan **bukan** dibaca daripada `config('app.name')`. Laravel Cloud
menetapkan `APP_NAME` kepada slug aplikasi, yang akan memaparkan `wky-inventory` bertanda sempang
pada halaman log masuk produksi.

### Butang mata kata laluan

Setiap medan kata laluan dalam sistem — log masuk, pendaftaran, dan borang pengguna — menggunakan
`<x-medan-kata-laluan>`, yang menambah butang mata untuk menyemak apa yang ditaip. Butang itu
mesti membawa `type="button"`: butang tanpa jenis di dalam borang dikira sebagai butang hantar,
jadi menekan mata akan menghantar borang. Ujian `KataLaluanTest` menguatkuasakan hal ini pada
kelima-lima medan.

Label *Tunjuk*/*Sembunyi* dibawa sebagai atribut `data-*` pada butang, jadi JavaScript menukarnya
tanpa perlu tahu bahasa halaman.

### Menukar logo

Letakkan fail logo anda di `public/images/` sebagai `logo-wky.svg`, `.png`, `.webp`, atau
`.jpg`. Komponen akan menggunakannya secara automatik dan melangkau lukisan SVG terbina —
tiada perubahan kod diperlukan. Buang fail itu untuk kembali kepada lukisan SVG.

### Palet

Sistem mempunyai **satu tema sahaja** — gelap pekat dengan aksen merah. Tiada suis terang/gelap,
jadi setiap warna boleh dipilih untuk satu latar dan tidak perlu berfungsi pada dua-dua.

Palet keseluruhan dikawal oleh token `--color-*` di bahagian atas `app.css`. Tukar nilai di situ
dan jalankan `npm run build` untuk menukar rupa seluruh aplikasi.

Tiga perkara yang perlu diberi perhatian apabila menyunting:

- `@apply` dalam Tailwind v4 hanya menerima **utiliti**, bukan kelas komponen tersuai. Kelas
  seperti `.btn-utama` ditulis penuh dan tidak saling `@apply` antara satu sama lain.
- Nama kelas mesti muncul sebagai teks penuh supaya Tailwind dapat mengesannya. Sebab itu
  `StockCount::kelasStatus()` dan `StockMovement::kelasJenis()` memulangkan nama kelas lengkap
  (`lencana-kuning`) dan bukan potongan yang dicantum (`lencana-` . `$warna`).
- Peraturan `:-webkit-autofill` dalam `app.css` kelihatan pelik tetapi **jangan dipermudahkan**.
  Chrome mengecat medan yang diisi automatik dengan latar birunya sendiri dan menandanya
  `!important` dalam gaya ejen pengguna, jadi `background-color` biasa tidak dapat menindihnya —
  hanya `box-shadow: inset 0 0 0 100px` yang cukup tebal berjaya dicat di atasnya. `transition`
  yang panjangnya 100000s pula menahan warna asal Chrome daripada berkelip seketika sebelum
  bayang itu muncul, dan gelang fokus ditulis semula kerana ia hilang bersama `box-shadow`.
  Medan pada `.kad-log-masuk` mendapat versinya sendiri kerana kad itu lebih hitam daripada
  permukaan lalai; di situ cahaya merah disenaraikan **sebelum** isian legap, kerana bayang
  pertama dicat paling atas.

## Nota teknikal

- Kemas kini stok dibungkus dalam transaksi dengan `lockForUpdate()` untuk mengelak dua
  pengguna mengubah baki produk yang sama secara serentak.
- `public/build` tidak disimpan dalam Git. Selepas `git clone` atau `git pull` yang menyentuh
  antara muka, jalankan `npm run build` semula.
- Halaman log masuk dan pendaftaran menggunakan `overflow-x-hidden` pada `<body>`, bukan
  `overflow-hidden`. `overflow-hidden` mematikan skrol menegak sepenuhnya, yang menjadikan
  kandungan di bawah kad — termasuk pautan kembali ke log masuk — tidak boleh dicapai.
- Latar konstelasi menggunakan kedudukan `fixed` supaya halaman yang lebih panjang daripada
  skrin kekal sama rupa semasa diskrol, bukan meregang mengikut tinggi dokumen.
- Borang dengan lebih daripada satu butang hantar — seperti *Imbas* dan *Simpan Sahaja* —
  membawa pilihannya dalam medan tersembunyi dan bukan dalam nilai butang. Butang yang
  dilumpuhkan dalam pengendali `submit` boleh menyebabkan nama dan nilainya tercicir daripada
  data borang, jadi tindakan yang dipilih hilang tepat pada saat ia diperlukan.
- Arahan yang membuang data (`ruang-kerja:kosongkan`) memaparkan kiraan setiap jenis rekod dan
  meminta pengesahan sebelum menyentuh apa-apa. `--force` hanya untuk skrip bukan interaktif.
