# WKY Inventory

Sistem pengurusan inventori berasaskan Laravel 13 — merekod produk, kategori, pembekal, dan
setiap pergerakan stok masuk/keluar dengan jejak audit penuh.

Setiap syarikat yang mendaftar mendapat **ruang kerjanya sendiri** dengan inventori yang
berasingan sepenuhnya, jadi satu pemasangan boleh menampung banyak syarikat tanpa data
bertemu antara satu sama lain.

## Modul

| Modul | Keterangan |
|---|---|
| **Dashboard** | Kad statistik, carta Ringkasan Bulanan (kemasukan vs. pengeluaran 6 bulan), amaran stok rendah, amaran batch hampir tamat tempoh, pergerakan terkini, sesi kiraan terbaharu, dan borang Tambah Stok Pantas |
| **Produk** | CRUD produk dengan SKU, barcode, gambar, harga kos/jual, unit, paras stok minimum, dan pilihan menjejak batch/tarikh luput. Carian nama/SKU/barcode, tapisan kategori dan tapisan stok rendah. Halaman produk turut memaparkan lot dan sejarah pergerakannya |
| **Kategori** | Pengelasan produk. Tidak boleh dipadam selagi masih digunakan produk |
| **Pembekal** | Maklumat pembekal dan senarai produk yang dibekalkan. Sama seperti kategori, tidak boleh dipadam selagi masih ada produk yang memautnya |
| **Gudang** | Gudang dan cawangan, dengan baki setiap produk pada setiap gudang serta catatan rak/bin |
| **Pindah Stok** | Pemindahan antara gudang dalam dua peringkat — hantar dan terima — dengan stok dalam perjalanan di antaranya |
| **Imbas Invois** | Ambil gambar terus dengan kamera atau muat naik foto/PDF — AI membaca baris barang, memadankannya dengan produk (mencipta produk baharu bagi barang yang belum wujud), dan merekod stok masuk selepas disahkan. Boleh juga disimpan dahulu dan dibaca kemudian |
| **Kiraan Stok** | Sesi kiraan fizikal (stock take): sistem simpan gambaran baki, staf masukkan kiraan sebenar, sistem tunjuk perbezaan dan laraskan stok selepas disahkan |
| **Pergerakan Stok** | Rekod stok masuk, keluar, dan pelarasan — setiap satu menyimpan baki sebelum/selepas, sebab, dan lot yang terlibat. Stok keluar menjana Delivery Order yang boleh dicetak. Boleh ditapis mengikut produk, jenis, dan sebab |
| **Laporan Bulanan** | Pecahan masuk/keluar per produk mengikut bulan, perubahan bersih, dan susun atur mesra cetak |
| **Pengguna** | Pengurusan akaun dan peranan (admin / staf) dalam ruang kerja sendiri. Hanya admin boleh akses |

Kuantiti stok **tidak boleh** diubah terus melalui borang produk. Ia hanya berubah melalui modul
Pergerakan Stok atau pengesahan sesi Kiraan Stok, supaya setiap perubahan baki mempunyai rekod
siapa, bila, dan sebab.

### Belum ada

Supaya jelas apa yang sistem ini belum lakukan, dan tidak disangka hilang:

- **Purchase Order.** Tiada permohonan pembelian, kelulusan, penerimaan separa, mahupun
  padanan PO dengan invois pembekal.
- **Kewangan.** Tiada jualan/POS, kos barang dijual (COGS), untung kasar, mahupun kaedah
  kos berlapis seperti FIFO. Nilai stok dikira pada harga kos semasa produk.
- **Kawalan kelulusan berperingkat.** Peranan admin/staf sahaja; tiada had kuasa kelulusan
  mahupun maker-checker.
- **Analitik lanjutan.** Tiada produk paling laris, inventory turnover, DIO, dead stock,
  mahupun ramalan permintaan.
- **Batch mengikut gudang.** Baki lot dikira untuk seluruh ruang kerja, bukan setiap gudang.
  Pemindahan memindahkan kuantiti tanpa menyebut lot mana yang bergerak.

### Aliran Kiraan Stok

1. **Buka sesi** — pilih gudang yang hendak dibilang, dan skop (semua kategori atau satu
   kategori). Sistem menyenaraikan semua produk aktif dan menyimpan baki gudang itu sebagai
   *Kuantiti Rekod*. Stok belum berubah.
2. **Isi kiraan** — staf memasukkan *Kuantiti Fizikal*. Perbezaan dikira serta-merta dalam pelayar.
   Boleh disimpan sebagai draf dan disambung kemudian; produk yang dibiarkan kosong dilangkau.
3. **Sahkan** — setiap produk yang berbeza menjana satu pergerakan stok jenis `pelarasan` dengan
   rujukan kod sesi. Baki **gudang itu** ditetapkan kepada kuantiti fizikal, dan jumlah produk
   bergerak sebanyak perbezaan itu sahaja — gudang lain tidak dibilang dan tidak terjejas.

Pada langkah pengesahan, baki dibaca semula daripada pangkalan data dan bukan daripada gambaran
sesi, kerana stok mungkin berubah antara pembukaan sesi dan pengesahan. Produk yang kuantiti
fizikalnya sama dengan baki dilangkau — tiada pergerakan kosong direkodkan. Sesi yang telah
selesai atau dibatalkan tidak boleh diubah lagi.

Membatalkan sesi hanya menukar statusnya kepada *Dibatalkan*; rekod dan kiraan yang sudah
dimasukkan kekal dalam senarai, cuma tidak boleh diubah lagi. Ini berbeza daripada butang padam
pada imbasan invois, yang membuang rekod itu terus — perbezaan yang perlu diingat apabila
membandingkan kedua-dua modul.

## Gudang dan lokasi

Setiap ruang kerja bermula dengan satu gudang, **Gudang Utama**, yang dicipta serentak dengan
ruang kerja itu sendiri. Syarikat yang hanya ada satu premis tidak perlu menyentuh modul ini
langsung: setiap borang memilih gudang lalai dengan sendirinya.

Dua nombor dijaga untuk setiap produk, dan ia menjawab dua soalan berbeza:

- `products.stok` — **berapa banyak** barang ini ada semuanya.
- `stock_balances` — **di mana** ia berada, satu baris bagi setiap pasangan produk dan gudang,
  berserta catatan rak atau bin.

Hubungan antara keduanya: `stok` = jumlah baki semua gudang + stok dalam perjalanan. Halaman
produk memaparkan pecahan itu, dan menandakan perbezaan dengan lencana apabila jumlahnya tidak
sepadan — kerana angka gudang yang senyap-senyap salah lebih memudaratkan daripada angka yang
jelas tidak berbaki.

Jumlah keseluruhan tidak digantikan oleh jadual baki kerana setiap halaman, laporan dan amaran
stok rendah yang sedia ada bergantung padanya, dan "berapa banyak barang ini ada" memang soalan
yang paling kerap ditanya.

Semua perubahan baki melalui satu tempat,
[`BakiLokasi`](app/Services/Stok/BakiLokasi.php). Empat aliran menyentuh stok — pergerakan
manual, pengesahan imbasan invois, pengesahan kiraan stok, dan pemindahan — dan kalau setiap
satu mengira barisnya sendiri, satu daripadanya akan terlupa mengunci baris atau menyemak baki
negatif.

### Gudang lalai

Satu gudang setiap ruang kerja ditanda **lalai**. Ia menerima stok apabila permintaan tidak
menyebut lokasi: pengesahan imbasan invois, dan borang lama yang belum membawa medan gudang.
Ia tidak boleh dinyahtanda mahupun dipadam — ia hanya berpindah apabila gudang lain ditandakan
sebagai lalai.

Gudang yang masih ada stok tidak boleh dipadam. Membuangnya bermakna baki itu lenyap tanpa
sebarang pergerakan yang menerangkannya: jumlah produk kekal sedangkan tiada gudang yang
memegangnya lagi.

### Kesan pada modul sedia ada

- **Pergerakan stok** membawa gudang. Stok keluar disemak terhadap baki **gudang itu**, bukan
  hanya jumlah keseluruhan — satu gudang tidak boleh menghantar barang yang sebenarnya berada
  di gudang lain.
- **Pelarasan** menetapkan baki gudang yang dipilih, kemudian jumlah produk dikira semula
  daripada semua gudang. Pelarasan bermaksud "inilah yang sebenarnya ada di sini", dan jumlah
  keseluruhan ialah hasil tambahnya.
- **Kiraan stok** terikat pada satu gudang. *Kuantiti Rekod* yang disimpan ialah baki gudang
  itu dan bukan jumlah produk, kerana itulah yang sepatutnya sepadan dengan apa yang dilihat
  di rak. Pelarasannya hanya menyentuh baki di situ; gudang lain tidak dibilang dan tidak
  terjejas.
- **Imbasan invois** memasukkan stok ke gudang lalai, kerana invois tidak menyebut gudang.

## Pemindahan stok

Memindahkan barang antara gudang berlaku dalam dua peringkat, dengan satu peringkat ketiga di
antaranya:

1. **Hantar** — baki gudang asal ditolak serta-merta, dan pemindahan berstatus *Dalam
   Perjalanan*.
2. **Dalam perjalanan** — kuantiti itu tidak berada dalam baki mana-mana gudang, tetapi masih
   dikira dalam jumlah stok syarikat, kerana barang itu masih miliknya.
3. **Terima** — baki gudang tujuan ditambah, dan pemindahan menjadi *Selesai*.

Peringkat tengah itu wujud kerana tanpanya sistem terpaksa memilih antara dua pembohongan:
barang itu masih di gudang asal (sedangkan lori sudah bertolak) atau sudah di gudang tujuan
(sedangkan tiada sesiapa lagi menerimanya).

Pemindahan yang belum diterima boleh **dibatalkan**; stoknya kembali ke gudang asal. Yang sudah
diterima tidak boleh — barangnya sudah berada di gudang tujuan, dan memulangkannya ialah satu
lagi pemindahan yang berhak mendapat rekodnya sendiri.

### Mengapa pemindahan bukan sepasang masuk/keluar

Pemindahan direkodkan sebagai jenis pergerakan tersendiri, `pindah`, dan bukan sebagai stok
keluar di satu gudang berpasangan dengan stok masuk di gudang lain.

Jumlah stok syarikat tidak berubah apabila barang berpindah rak, sedangkan laporan bulanan
mengira `masuk` dan `keluar` sebagai stok yang benar-benar datang dan pergi. Sepasang baris
palsu akan menunjukkan pembelian dan jualan yang tidak pernah berlaku. Jenis tersendiri
terkecuali daripada kiraan itu dengan sendirinya, tanpa satu pun laporan perlu ditulis semula.

Setiap peringkat meninggalkan barisnya sendiri dalam jejak audit — `pindah_hantar`,
`pindah_terima`, `pindah_batal` — dan setiap baris membawa gudang asal dan gudang tujuan.
`stok_sebelum` dan `stok_selepas` pada baris itu adalah sama, yang memang betul: yang berubah
ialah tempat, bukan jumlah.

## Barcode dan pengimbas

Setiap produk boleh membawa satu **barcode** selain SKUnya. Kedua-duanya diasingkan kerana
mereka menjawab soalan berbeza: SKU ialah kod dalaman yang anda pilih sendiri, manakala barcode
ialah kod yang sudah tercetak pada bungkusan oleh pengilang. Ia unik dalam ruang kerja, sama
seperti SKU.

Kod boleh dimasukkan dengan tiga cara, dan ketiga-tiganya berakhir di medan yang sama:

- **Menaipnya** ke dalam medan barcode pada borang produk.
- **Pengimbas USB** di kaunter, yang menaip kod itu seperti papan kekunci. Tiada tetapan
  diperlukan — sistem tidak dapat membezakannya daripada taipan tangan, dan itu memang niatnya.
- **Kamera peranti**, melalui butang imbas di sebelah medan.

Butang kamera menggunakan `BarcodeDetector`, iaitu API pelayar dan bukan pustaka yang dimuat
turun. Pada pelayar yang tidak menyokongnya (Safari, Firefox pada masa ini) butang itu
**tidak dipaparkan langsung**, kerana butang yang menjanjikan sesuatu yang tidak akan berlaku
lebih mengelirukan daripada ketiadaannya. Pengimbas USB tetap berjalan pada pelayar itu.

Pengimbas muncul di tiga tempat: medan barcode pada borang produk, kotak carian pada senarai
produk (mengimbas terus menghantar carian), dan borang pergerakan stok, di mana kod yang
diimbas memilih produknya sendiri daripada senarai.

Pemadanan pada borang stok dibuat **dalam pelayar** dan bukan dengan satu lagi permintaan ke
pelayan, kerana senarai produk sudah pun berada di halaman itu — kod yang diimbas sepatutnya
memilih produknya serta-merta, bukan selepas satu pusingan rangkaian.

## Gambar produk

Setiap produk boleh membawa satu gambar (JPG, PNG, GIF, WEBP; maksimum 4 MB). Ia disimpan pada
cakera **peribadi** yang sama seperti fail invois dan dihidangkan melalui
`products/{produk}/gambar`, jadi gambar produk syarikat lain memulangkan **404** dan bukan
imej. Tiada `storage:link`, dan tiada URL awam yang boleh diteka.

Gambar lama dibuang apabila diganti atau apabila produknya dipadam, supaya storan tidak
mengumpul fail yang tiada sesiapa boleh capai lagi.

## Batch dan tarikh luput

Penjejakan batch dihidupkan **produk demi produk** melalui pilihan *Jejak nombor batch dan
tarikh luput* pada borang produk. Kebanyakan barang SME — skru, kabel, alat tulis — tidak
memerlukannya, dan menghidupkannya untuk semua produk hanya menambah dua medan wajib pada
setiap kemasukan stok.

Bagi produk yang dijejak:

- **Stok masuk** meminta nombor batch, dan pilihan tarikh luput serta nombor siri. Kemasukan
  kedua bagi nombor batch yang sama menambah lot sedia ada, bukan mencipta lot kembar.
- **Stok keluar** meminta lot mana yang diambil. Senarainya disusun mengikut **tarikh luput
  terawal**, jadi lot yang paling hampir tamat tempoh muncul di atas.
- **Dashboard** memaparkan kad amaran bagi lot yang sudah luput atau akan luput dalam 30 hari,
  dan hanya apabila ada lot sedemikian. Kad kosong yang kekal setiap hari akan berhenti dibaca.
- Lot yang sudah habis tidak disenaraikan mahupun diberi amaran.

Imbasan invois turut mencipta lot. Invois tidak membawa nombor batch, jadi satu penghantaran
dianggap satu lot dan dinamakan mengikut nombor invoisnya. Tarikh luputnya dibiarkan kosong
kerana ia memang tidak diketahui pada masa itu, dan diisi kemudian pada halaman produk selepas
kotak sebenar diperiksa.

**Kuantiti lot tidak boleh disunting dengan tangan.** Ia tertakluk pada peraturan yang sama
seperti baki produk: hanya pergerakan stok boleh mengubahnya. Yang boleh disunting pada halaman
produk ialah tarikh luput dan nombor siri.

> ⚠️ Pelarasan menyeluruh — pelarasan manual dan pengesahan sesi kiraan stok — menetapkan baki
> **produk** tanpa menyebut lot mana yang berubah, jadi jumlah lot boleh terpesong daripada baki
> produk. Perbezaan itu dipaparkan sebagai lencana pada jadual batch dan bukan disembunyikan,
> kerana angka batch yang senyap-senyap salah lebih memudaratkan daripada angka yang jelas tidak
> sepadan. Membetulkannya lot demi lot ialah kerja fasa berikutnya, bersama gudang berbilang.

## Sebab pergerakan dan Delivery Order

Setiap pergerakan stok membawa satu **sebab** selain jenisnya. Jenis hanya memberitahu arah
(masuk, keluar, pelarasan); sebab memberitahu mengapa, dan itulah yang membezakan stok yang
menjana wang daripada stok yang lesap.

| Jenis | Sebab yang dibenarkan |
|---|---|
| Masuk | Pembelian, pemulangan pelanggan, lain-lain |
| Keluar | Jualan, sampel percuma, kegunaan dalaman, rosak, hilang, pemulangan kepada pembekal, lain-lain |
| Pelarasan | Kiraan fizikal, rosak, hilang, lain-lain |

Senarai ini ditakrifkan **sekali** dalam [`StockMovement::SEBAB`](app/Models/StockMovement.php):
borang membina pilihannya daripadanya dan pengesahan menyemak terhadapnya, jadi pilihan yang
dipaparkan tidak boleh menjadi pilihan yang ditolak. Gabungan yang tidak masuk akal — *stok
masuk kerana jualan* — ditolak walaupun disuap terus ke dalam borang, kerana laporan yang
mengira jualan daripada medan ini akan rosak olehnya.

Pergerakan yang dijana sistem membawa sebabnya sendiri: pengesahan imbasan invois merekod
`pembelian`, dan pengesahan sesi kiraan stok merekod `kiraan_fizikal`.

### Delivery Order

Setiap **stok keluar** menjana satu nombor DO (`DO-2026-001`) dan halaman cetakan di
`stock/{pergerakan}/do`, lengkap dengan penerima, lot yang dikeluarkan, dan ruang tandatangan
penghantar dan penerima.

Nombornya dijana **dalam transaksi yang sama** seperti pergerakan itu, jadi dua permintaan
serentak tidak boleh berkongsi nombor dokumen. Ia berskop ruang kerja: setiap syarikat bermula
semula dari `001` setiap tahun.

DO dijana daripada rekod pergerakan dan tidak disimpan sebagai dokumen berasingan, jadi apa
yang dicetak sentiasa sepadan dengan apa yang benar-benar keluar daripada stok. Satu DO membawa
satu baris kerana ia terikat pada satu pergerakan; menggabungkan beberapa produk ke dalam satu
dokumen memerlukan lapisan pesanan penghantarannya sendiri.

## Butang tindakan pantas

Setiap halaman dalam sistem membawa satu butang bulat terapung di penjuru bawah kanan
([`partials/butang-pantas.blade.php`](resources/views/partials/butang-pantas.blade.php)) yang
membuka empat pintasan: **Imbas Resit, Muat Naik, Stok Masuk, Stok Keluar**.

Ia menggunakan pencetus `data-jatuh` yang sama seperti menu lain, jadi ia mewarisi
tutup-bila-klik-luar dan tutup-bila-Escape tanpa JavaScript baharu.

Kedua-dua pasangan pintasan itu menuju ke satu halaman yang sama, dan parameter pertanyaan yang
membezakannya:

- **Imbas Resit / Muat Naik** → halaman imbas invois dengan `?mod=`. `mod=kamera` menekan butang
  kamera terus; `mod=fail` hanya **menumpukan** medan fail dan tidak membuka pemilih fail, kerana
  pelayar menyekat pembukaan dialog fail tanpa gerak isyarat pengguna.
- **Stok Masuk / Stok Keluar** → borang pergerakan stok dengan `?jenis=`. Nilainya disaring
  terhadap `masuk`, `keluar` dan `pelarasan` dalam `StockMovementController::create()`; nilai yang
  tidak dikenali jatuh kembali kepada `masuk`. Tanpa saringan itu, `?jenis=` sewenang-wenangnya
  akan membuka borang tanpa satu pun jenis dipilih.

Senarai **sebab** pada borang pergerakan stok ditapis mengikut jenis oleh JavaScript, dan
penapis itu dijalankan sekali semasa halaman dimuat. Jadi pintasan ini membuka borang yang sudah
betul sepenuhnya — jenis dipilih dan senarai sebabnya sepadan — bukan sekadar borang kosong.

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

Halaman log masuk membawa pautan **Kembali ke Utama** di kiri atas. Tanpanya halaman itu
menjadi jalan buntu: nav halaman pendaratan tiada di situ, jadi pelawat yang menekan *Log In*
dan berubah fikiran hanya boleh kembali melalui butang back pelayar. Halaman pendaftaran
mengekalkan pautan kembalinya ke log masuk — dua pautan kembali bertindan di bahagian atas
hanya menambah kekeliruan.

> ⚠️ **Harga pada halaman itu ialah contoh, bukan harga sebenar.** Tukar kunci
> `landing.harga_*` dalam `lang/ms/wky.php` **dan** `lang/en/wky.php` kepada tawaran sebenar
> anda sebelum memasarkan halaman ini.

## Ruang kerja

Setiap syarikat memiliki satu **ruang kerja**. Produk, kategori, pembekal, gudang, baki setiap
gudang, pergerakan stok, pemindahan, sesi kiraan, imbasan invois dan pengguna semuanya dimiliki
oleh satu ruang kerja, dan tidak pernah bertemu data ruang kerja lain.

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
- Kod jujukan imbasan dan sesi kiraan (`SCAN-2026-001`, `KIRA-2026-001`) dinomborkan dengan
  mengira rekod sedia ada, jadi ia turut berskop ruang kerja: setiap syarikat bermula semula
  dari `001` setiap tahun.
- **Emel pengguna pula unik merentas seluruh sistem**, bukan dalam ruang kerja. Satu emel
  bermakna satu akaun dalam satu ruang kerja sahaja; orang yang sama tidak boleh menjadi ahli
  dua syarikat dengan emel yang sama. Ia selari dengan cara log masuk berfungsi — borang hanya
  menerima emel dan kata laluan, tanpa langkah memilih ruang kerja.

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

### Pengurusan pengguna

Halaman Pengguna dibuka kepada **admin** sahaja, melalui middleware `admin`
([`EnsureUserIsAdmin`](app/Http/Middleware/EnsureUserIsAdmin.php)). Akaun yang ditambah di situ
sentiasa masuk ke ruang kerja admin yang menciptanya — tiada medan ruang kerja pada borang.

Dua sekatan menghalang admin daripada mengunci dirinya sendiri di luar sistem:

- Admin yang menyunting akaunnya sendiri kekal **admin** walaupun borang menghantar `staf`.
  Tanpa sekatan ini, satu-satunya admin dalam ruang kerja boleh menurunkan pangkatnya sendiri
  dan tiada sesiapa lagi yang boleh menaikkannya semula.
- Admin tidak boleh memadam akaunnya sendiri.

Model `User` tidak berskop ruang kerja secara automatik (lihat [Ruang kerja](#ruang-kerja)), jadi
`UserController` menyemak pemilikan sendiri dan memulangkan **404** bagi akaun milik syarikat
lain — layanan yang sama seperti model berskop.

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
Akaun pengguna, gudang, dan ruang kerja itu sendiri tidak disentuh — hanya produk, kategori,
pembekal, pergerakan stok, pemindahan, sesi kiraan dan imbasan invois. Baki gudang dan lot
dibuang bersama produknya. Fail invois yang tersimpan turut dibuang. Gunakan `--force` untuk
melangkau soalan pengesahan dalam skrip.

Gudang dikekalkan kerana ia struktur ruang kerja dan bukan data inventori, sama seperti akaun
penggunanya — dan setiap ruang kerja mesti sentiasa ada satu gudang lalai untuk menerima stok.

## Deploy

Selepas setiap deploy yang membawa migrasi baharu:

```bash
php artisan migrate --force
```

Letakkan arahan ini dalam *deploy command* hos anda supaya ia berjalan automatik. Migrasi yang
tidak dijalankan menyebabkan setiap halaman yang menyentuh pangkalan data memulangkan ralat
500, sementara halaman statik seperti log masuk masih kelihatan normal — corak yang mudah
disalah anggap sebagai pepijat kod.

`npm run build` juga wajib pada setiap deploy, kerana `public/build` tidak disimpan dalam Git.
Tanpanya setiap halaman gagal dengan *Vite manifest not found*.

Aplikasi menyediakan `/up` untuk pemeriksaan kesihatan hos. Ia menyentuh aplikasi tetapi bukan
pangkalan data, jadi ia menjawab *sihat* walaupun migrasi belum dijalankan — jangan bergantung
padanya untuk mengesahkan deploy yang lengkap.

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

Suite yang sama berjalan di GitHub Actions ([`.github/workflows/tests.yml`](.github/workflows/tests.yml))
pada setiap push ke `main`, setiap pull request, dan sekali sehari pada tengah malam UTC,
merentas PHP 8.3, 8.4, dan 8.5. Jadual harian itu menangkap kerosakan yang datang dari luar
repositori — kemas kini kebergantungan atau imej pelari — dan bukan daripada commit. Alur kerja itu
menjalankan `npm run build` sebelum menguji: setiap susun atur memanggil `@vite` dan `public/build`
tidak disimpan dalam Git, jadi tanpa binaan itu setiap ujian yang memaparkan halaman akan gagal
dengan *Vite manifest not found* — kegagalan persekitaran yang mudah disalah anggap sebagai pepijat
kod. Langkah itu turut mengesahkan binaan aset masih berjaya sebelum deploy.

- `tests/Feature/InventoryTest.php` — kawalan akses, paparan semua halaman utama, logik pergerakan
  stok (termasuk penolakan stok keluar yang melebihi baki), dan laporan bulanan.
- `tests/Feature/StockCountTest.php` — aliran kiraan stok: gambaran baki, penapisan kategori,
  draf yang tidak mengubah stok, pelarasan selepas pengesahan, dan sekatan pada sesi yang selesai.
- `tests/Feature/LocaleTest.php` — penukaran BM/EN, kekekalan pilihan merentas halaman,
  terjemahan mesej flash dan pengesahan, serta keselarian kunci antara dua fail bahasa.
- `tests/Feature/InvoiceScanTest.php` — imbasan invois: padanan SKU dan nama, produk yang
  dicipta sendiri untuk baris tanpa padanan, pemilihan manual, baris dilangkau, pengesahan
  yang merekod stok masuk, pemadaman imbasan draf, dan pengendalian ralat AI. Menggunakan
  pengekstrak palsu — tiada panggilan API sebenar.
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
  masuk dialihkan ke dashboard. Turut menyemak pautan kembali pada halaman log masuk, tanda
  W pada bar sisi, dan keempat-empat objek hiasan 3D pada halaman auth.
- `tests/Feature/PisahkanPenggunaTest.php` — arahan `pengguna:pisah`, termasuk penolakan
  apabila akaun itu satu-satunya pengguna dalam ruang kerjanya.
- `tests/Feature/KosongkanRuangKerjaTest.php` — arahan `ruang-kerja:kosongkan`: semua rekod
  inventori dibuang termasuk pemindahan dan baki gudang, akaun pengguna dan gudang kekal,
  ruang kerja lain tidak disentuh, dan tiada apa yang dibuang apabila pengesahan ditolak.
- `tests/Feature/BarcodeGambarTest.php` — barcode unik dalam ruang kerja (dan boleh dikongsi
  antara ruang kerja), carian yang menemuinya, serta gambar produk: muat naik, penghidangan
  berskop ruang kerja, penggantian yang membuang fail lama, dan pemadaman.
- `tests/Feature/BatchLuputTest.php` — lot: penciptaan semasa stok masuk, penggabungan
  kemasukan kedua, medan wajib mengikut arah, penolakan baki lot semasa stok keluar, lot milik
  produk lain yang ditolak, amaran luput pada dashboard, dan lot penerimaan daripada imbasan
  invois.
- `tests/Feature/SebabDoTest.php` — sebab pergerakan (wajib, dan mesti padan dengan jenisnya),
  penapisan mengikut sebab, dan Delivery Order: penomboran berskop ruang kerja, halaman
  cetakan, serta 404 bagi pergerakan masuk dan pergerakan syarikat lain.
- `tests/Feature/GudangTest.php` — gudang: penciptaan automatik bagi ruang kerja baharu,
  perpindahan tanda lalai, sekatan padam (lalai dan yang masih berstok), baki per gudang pada
  stok masuk/keluar, permintaan tanpa lokasi yang mendarat di gudang lalai, dan sesi kiraan
  yang hanya melaraskan gudang yang dibilang.
- `tests/Feature/PindahStokTest.php` — pemindahan: baki asal ditolak semasa hantar, stok dalam
  perjalanan, baki tujuan ditambah semasa terima, pembatalan yang memulangkan stok, penolakan
  penghantaran melebihi baki gudang, produk berulang yang digabungkan, dan pengecualian
  pemindahan daripada kiraan masuk/keluar laporan bulanan.

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

### Apa yang berlaku semasa pengesahan

Setiap baris yang dipadankan dan tidak dilangkau menjana satu pergerakan stok jenis `masuk`,
dengan baki dibaca semula di bawah kunci baris kerana stok mungkin berubah antara imbasan
dibuat dan disahkan. Selepas itu imbasan berstatus *Selesai* dan tidak boleh disunting lagi.

Rujukan pada pergerakan itu ialah **nombor invois** apabila AI berjaya membacanya, dan kod
imbasan (`SCAN-2026-001`) apabila tidak. Nombor invois dipilih dahulu kerana itulah yang dicari
orang apabila menyemak semula kemasukan stok terhadap invois kertas.

Nama pembekal yang dibaca daripada invois dipadankan dengan senarai pembekal mengikut **nama
yang sama tepat** (tidak mengira besar kecil huruf) dan mengisi medan pembekal pada imbasan.
Kalau tiada padanan, medan itu dibiarkan kosong untuk dipilih sendiri — pembekal tidak pernah
dicipta automatik, tidak seperti produk.

### Di mana fail invois disimpan

Fail yang dimuat naik diterima dalam format `jpg`, `jpeg`, `png`, `gif`, `webp` dan `pdf`, dan
disimpan pada cakera **`local`** — iaitu `storage/app/private/invois`, bukan `public/`. Halaman
butiran memaparkannya melalui laluan `imbas-invois/{imbasan}/fail`, jadi:

- Fail melalui pengikatan model pada laluan, dan pengikatan itu berskop ruang kerja. Invois
  syarikat lain memulangkan **404**, sama seperti mana-mana rekod lain.
- Tiada `php artisan storage:link` diperlukan, dan tiada URL awam yang boleh diteka. Invois
  membawa harga dan nama pembekal — ia tidak sepatutnya boleh dibuka oleh sesiapa yang
  mempunyai pautannya.

Kerana fail berada dalam `storage/`, direktori itu **mesti kekal** antara deploy. Pada hos yang
cakeranya dibina semula pada setiap deploy, gambar invois akan hilang sementara rekodnya kekal.
Cakera itu dinamakan terus sebagai `local` dalam `InvoiceScanController` (simpan, baca, hidang,
padam) dan **bukan** dibaca daripada `FILESYSTEM_DISK`, jadi berpindah ke storan objek bermakna
menukar takrif cakera `local` dalam `config/filesystems.php` kepada pemacu `s3` — bukan sekadar
menukar satu nilai `.env`.

### Memadam imbasan

Butang tong sampah pada senarai imbasan, dan **Padam Imbasan** pada halaman butirannya,
membuang rekod itu **berserta gambarnya** daripada storan. Ia bukan pembatalan — barisnya
hilang terus daripada senarai.

Hanya imbasan **draf** boleh dipadam. Imbasan yang telah disahkan sudah menjana pergerakan
stok yang merujuk kepadanya, dan membuangnya akan meninggalkan pergerakan yang menunjuk kepada
imbasan yang tidak lagi wujud. Sekatan itu berada dalam controller dan bukan sekadar butang
yang disembunyikan.

Fail dibuang **sebelum** rekod, kerana storan tidak boleh digulung semula seperti transaksi
pangkalan data. Rekod yang failnya sudah hilang masih boleh dipadam kemudian; fail tanpa
rekod pula menjadi sampah yang tiada sesiapa boleh capai.

### Mengapa butang Sahkan tidak bertanya

Halaman butiran imbasan **sendiri** ialah skrin semakan: setiap baris, kuantiti dan produk
yang dipadankan terpampang tepat di atas butang, dan butang itu bertulis apa yang akan
berlaku. Satu kotak dialog yang bertanya perkara yang sama sekali lagi tidak menambah
semakan — ia menjadi kebiasaan yang ditekan tanpa dibaca.

Butang **Padam** di sebelahnya tetap bertanya. Bezanya jelas: mengesahkan merekod apa yang
sudah dilihat, sedangkan memadam membuang kerja yang ada dan tiada apa di skrin yang
menunjukkan apa yang bakal hilang. Sesi **Kiraan Stok** juga masih bertanya, kerana
pelarasannya boleh **menurunkan** stok dan bukan sekadar menambah.

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
tidak berkaitan tanpa disedari — jauh lebih teruk daripada langsung tidak padan.

Baris yang tidak padan **tidak** ditinggalkan kosong: sistem mencipta produk baharu terus
daripada baris itu dan memadankannya. Tujuannya supaya imbasan sampai ke skrin semakan dalam
keadaan sedia direkod, dan pengguna hanya perlu menekan *Sahkan & Rekod Stok Masuk* sekali —
tiada langkah mendaftar produk di tengah jalan.

Kod pembekal pada invois dijadikan SKU produk itu, kerana itulah yang menjadikan invois
berikutnya daripada pembekal yang sama padan dengan sendirinya. Baris tanpa kod mendapat SKU
jana (`AUTO-0001`) dan bergantung pada nama untuk padanan seterusnya. Kod yang dipotong pada
50 aksara dan bertembung dengan SKU sedia ada turut jatuh kepada nombor jana — SKU unik dalam
ruang kerja, dan pelanggarannya akan mematikan imbasan di tengah jalan.

Kuantiti pada invois **tidak** terus menjadi stok produk baharu itu. Stoknya kekal `0` sehingga
imbasan disahkan, kemudian bertambah melalui pergerakan stok yang sama seperti mana-mana
kemasukan lain — jejak auditnya tidak berlubang walaupun produk itu wujud kerana invois.

> ⚠️ Invois tidak membawa **harga jual** mahupun **paras stok minimum**, jadi produk yang
> dicipta begini bermula dengan kedua-duanya `0`. Betulkan di halaman Produk, jika tidak
> amaran stok rendah tidak akan berbunyi untuk produk berkenaan.

Setiap baris membawa label asal-usul padanannya: *Padan SKU*, *Padan nama*, *Produk baharu*
(dicipta automatik), atau *Dipilih manual*. Ini membezakan baris yang belum pernah dilihat
sesiapa daripada baris yang seseorang sahkan dengan matanya sendiri.

Padanan yang salah boleh dikosongkan semula melalui senarai jatuh. Baris yang kosong itu
kemudian memaparkan pautan **Cipta produk dari baris ini**, yang membuka borang produk dengan
SKU, nama dan harga kos daripada invois sudah terisi, dan memautkan produk baharu itu kembali
kepada baris berkenaan selepas disimpan.

Nilai awal borang itu dibaca daripada baris imbasan dalam pangkalan data dan **bukan**
daripada parameter URL, supaya apa yang terisi memang apa yang AI baca dan bukan apa yang
disuap ke dalam pautan. Baris hanya boleh dipaut apabila imbasannya milik ruang kerja
pengguna dan masih draf.

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
| `lang/{ms,en}/validation.php` | Mesej pengesahan + nama medan (`attributes`) |
| `lang/{ms,en}/{auth,pagination}.php` | Mesej log masuk dan pautan penomboran |
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
| `resources/views/components/logo-w.blade.php` | Tanda *W* sahaja, untuk kepala bar sisi |
| `resources/views/components/jenama-wky.blade.php` | Kata jenama *WKY INVENTORY* mengikut warna logo |
| `resources/views/components/latar-log-masuk.blade.php` | Latar konstelasi dan siluet bandar untuk halaman log masuk dan pendaftaran |
| `resources/views/components/hiasan-3d.blade.php` | Empat objek gudang 3D pada latar halaman auth |
| `resources/views/components/medan-kata-laluan.blade.php` | Medan kata laluan berserta butang mata |
| `resources/views/components/imbas-barcode.blade.php` | Butang dan modal pengimbas barcode; menyembunyikan dirinya apabila pelayar tiada `BarcodeDetector` |
| `resources/views/components/tajuk-seksyen.blade.php` | Tajuk seksyen halaman pendaratan: garis aksen, tajuk, teks pengenalan |
| `resources/views/components/togol-tema.blade.php` | Butang togol terang/gelap; ikon suria dan bulan kedua-duanya dalam DOM, CSS yang memilih mana kelihatan |
| `resources/views/partials/skrip-tema.blade.php` | Skrip menyekat dalam `<head>` yang menetapkan tema sebelum halaman dicat |
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

### Latar matahari terbenam

Halaman log masuk, pendaftaran dan pendaratan berkongsi kelas `.latar-log-masuk`: kecerunan
oren → magenta → ungu, dua cahaya bulat, konstelasi titik, dan siluet bandar. Perhentiannya
datang daripada `--login-g1/2/3`, yang ditulis semula oleh `.dark`, jadi kelas itu sendiri tidak
perlu tahu tema mana yang aktif.

Latar itu **dilekatkan pada viewport** (`background-attachment: fixed`) supaya halaman yang lebih
panjang daripada skrin — seperti halaman pendaftaran — kekal sama rupa semasa ditatal.

> ⚠️ Halaman pendaratan menambah `.latar-hero` di sebelah `.latar-log-masuk`, dan ini **bukan
> hiasan**. Halaman itu berpuluh skrin panjangnya; dengan latar yang dilekatkan pada viewport,
> setiap seksyen yang ditatal akan melalui jalur oren paling cerah, dan teks sekunder tidak
> boleh dibaca di atasnya. `.latar-hero` menghadkan kecerunan kepada skrin pertama sahaja
> (`background-size: 100% 100vh`, tidak berulang) dan mengecat selebihnya dengan `--login-g3`
> yang rata. Jangan buang kelas itu untuk "menyeragamkan" halaman.

### Hiasan 3D pada halaman auth

Halaman log masuk dan pendaftaran membawa empat objek gudang — kotak terbuka di atas palet,
rak tiga tingkat, label kod bar, dan forklift — yang berputar perlahan dan condong mengikut
gerakan tetikus.

Semuanya dibina daripada satah CSS, **bukan** model 3D sebenar. Memaparkan fail GLB memerlukan
pustaka seperti three.js: beratus kilobait JavaScript pada halaman yang kerjanya hanya menerima
dua medan teks.

Empat perkara yang tidak jelas daripada membaca kodnya sepintas lalu:

- **Setiap objek membawa perspektifnya sendiri.** Satu perspektif dikongsi pada bekas induk
  akan mengukur semua objek dari titik yang sama, jadi objek di tepi skrin terherot teruk
  kerana dipandang dari sudut yang jauh daripada pusatnya sendiri.
- **Tiga lapisan bersarang pada setiap objek** — `.objek-condong` (tetikus, ditulis oleh
  JavaScript), `.objek-putar` (animasi CSS), kemudian satah-satahnya. `transform` ialah satu
  sifat: kalau JavaScript dan animasi CSS menulis pada elemen yang sama, satu akan memadam
  satu lagi pada setiap bingkai.
- **Kelegapan dikenakan pada setiap satah**, bukan pada bekasnya. `opacity` pada elemen induk
  meratakan anaknya menjadi satu lapisan, jadi rusuk belakang akan hilang di sebalik rusuk
  hadapan dan objek itu menjadi rata.
- **Kedudukan diletak dengan sifat `translate`**, bukan `transform`, supaya `transform` kekal
  bebas untuk lapisan condong.

`data-dalam` pada setiap objek ialah faktor kedalaman parallax: objek yang sepatutnya terasa
lebih dekat bergerak lebih banyak. Tanpa perbezaan itu keempat-empatnya bergoyang serentak
pada sudut yang sama, dan itu bukan parallax.

Putaran berhenti apabila pengguna menetapkan `prefers-reduced-motion`. Parallax pula dimatikan
dalam JavaScript, kerana gerakan yang dicetuskan tetikus tidak dapat dihalang daripada CSS.

### Menukar logo

Letakkan fail logo anda di `public/images/` sebagai `logo-wky.svg`, `.png`, `.webp`, atau
`.jpg`. Komponen akan menggunakannya secara automatik dan melangkau lukisan SVG terbina —
tiada perubahan kod diperlukan. Buang fail itu untuk kembali kepada lukisan SVG.

Kepala bar sisi menggunakan fail **berasingan**, `logo-wky-w.png` — tanda *W* sahaja tanpa
anak panah. Logo penuh tidak sesuai di situ: anak panahnya hitam dan hampir hilang di atas
bar sisi dalam tema gelap, dan pada 28px ia hanya menjadikan tanda itu bersepah. Tanpa fail
itu, komponen jatuh semula kepada ikon kotak terbina, jadi bar sisi tidak pernah kosong.

### Tema terang dan gelap

Sistem membawa **dua tema**, dengan terang sebagai lalai. Butang suria/bulan menukarnya, dan ia
muncul pada keempat-empat susun atur: dashboard, log masuk, pendaftaran, dan halaman pendaratan.

Tema dipilih mengikut turutan ini:

1. Pilihan tersimpan pengguna (`localStorage`, kunci `wky-tema`)
2. Keutamaan sistem pengendalian (`prefers-color-scheme`)
3. Terang

Pilihan disimpan dalam **`localStorage` dan bukan sesi pelayan**, kerana tema ialah keutamaan
*peranti* dan bukan keutamaan *akaun*: orang yang sama mungkin mahu gelap pada telefon di gudang
dan terang pada desktop di pejabat. Ini sengaja berbeza daripada pilihan **bahasa**, yang memang
disimpan dalam sesi pelayan kerana PHP perlu mengetahuinya untuk memilih fail terjemahan sebelum
halaman dijana.

Tema dikenakan dengan kelas `dark` pada `<html>`.

> ⚠️ Skrip dalam [`partials/skrip-tema.blade.php`](resources/views/partials/skrip-tema.blade.php)
> **mesti kekal sebagai skrip menyekat di dalam `<head>`**, dan tidak boleh dipindahkan ke
> `app.js`. `app.js` hanya berjalan selepas DOM sedia, jadi halaman akan dicat dengan tema terang
> dahulu sebelum bertukar gelap — kelipan putih yang ketara pada setiap muatan halaman.

Skrip itu dibungkus dalam `try`/`catch` kerana `localStorage` boleh melontar ralat dalam mod
penyemakan persendirian sesetengah pelayar. Kalau ia gagal, tema terang kekal terpakai — hasil
yang selamat, bukan halaman yang rosak.

Ikon suria dan bulan **kedua-duanya sentiasa berada dalam DOM**, dan CSS yang menentukan mana
satu kelihatan berdasarkan kelas `dark`. JavaScript tidak menyentuh ikon itu langsung. Kalau ia
menukar ikon sendiri, ikon yang betul hanya akan muncul selepas `app.js` dimuatkan — sedangkan
temanya sendiri sudah pun ditetapkan lebih awal oleh skrip dalam `<head>`.

Lukisan SVG logo terbina membaca token warna tema, jadi ia bertukar bersama halaman tanpa
memerlukan dua fail logo berasingan.

### Palet

Palet ialah **matahari terbenam gudang**: oren panas di atas, magenta di tengah, ungu pekat di
bawah. Jenama membawa **dua tona**, bukan satu:

| Token | Tema terang | Tema gelap | Kegunaan |
|---|---|---|---|
| `--color-aksen` | `#c92a5c` | `#d9376b` | Magenta — isian pejal: lencana, pautan nav aktif, gelang fokus |
| `--color-aksen-terang` | `#c2451b` | `#ff9147` | Oren — aksen teks, kata jenama *WKY*, keadaan tuding |

Kedua-duanya bertemu sebagai kecerunan pada tindakan utama. Kecerunan itu ialah satu pemboleh
ubah, `--kecerunan-aksen` (dan `--kecerunan-aksen-pekat` untuk keadaan tuding), supaya `.btn-utama`,
`.btn-nyala` dan `.butang-pantas` tidak boleh terpesong antara satu sama lain.

Perhatikan arah tona **bertukar** antara dua tema: pada tema gelap oren dinaikkan menjadi cerah
kerana ia perlu dibaca di atas ungu, sedangkan pada tema terang oren yang sama digelapkan supaya
boleh dibaca di atas putih.

| Kumpulan token | Kegunaan |
|---|---|
| `--color-latar`, `--color-permukaan`, `--color-tinggi`, `--color-bingkai` | Permukaan dan sempadan |
| `--color-aksen*` | Warna jenama: butang utama, pautan aktif, sorotan |
| `--kecerunan-aksen*` | Kecerunan jenama oren→magenta untuk tindakan utama |
| `--color-bahaya*` | Amaran, ralat, butang padam |
| `--color-teks`, `--color-malap` | Teks utama dan teks sekunder |
| `--login-g1/2/3`, `--siluet-*` | Perhentian kecerunan dan siluet bandar halaman auth |

> ⚠️ Merah `bahaya` masih token berasingan, tetapi jenama kini **condong ke merah jambu** — jadi
> warna sahaja tidak lagi cukup untuk membezakan *Padam* daripada *Simpan*. Pengasingan itu kini
> bergantung pada **bentuk**: tindakan merosakkan sentiasa bergaya garis (`.btn-bahaya`, latar
> lutsinar) dengan ikon, manakala tindakan utama ialah blok berkecerunan pejal. Jangan tukar
> `.btn-bahaya` kepada isian pejal tanpa memikirkan semula perbezaan ini.

Token `@theme` di bahagian atas `app.css` ialah nilai **tema terang**; blok `.dark` selepasnya
menulis semula token yang sama. Kerana `.dark` ditulis kemudian dalam fail, ia menang apabila
`<html>` membawa kelas itu. Tukar nilai di situ dan jalankan `npm run build` untuk menukar rupa
seluruh aplikasi.

Perkara yang perlu diberi perhatian apabila menyunting:

- `@apply` dalam Tailwind v4 hanya menerima **utiliti**, bukan kelas komponen tersuai. Kelas
  seperti `.btn-utama` ditulis penuh dan tidak saling `@apply` antara satu sama lain.
- Butang berkecerunan menetapkan `background-image`, bukan `background-color`. `background-image`
  **tidak boleh dianimasikan**, jadi keadaan tudingnya bertukar serta-merta walaupun peraturan
  asas butang menyenaraikan `transition`. Ia disengajakan; jangan cuba membetulkannya dengan
  menindih lapisan legap di atas kecerunan.
- `--color-malap` pada tema gelap sengaja **jauh lebih cerah** daripada kelabu sekunder biasa
  (`#dcbcd4`). Teks itu bukan sahaja duduk di atas kad ungu tetapi juga di atas jalur magenta
  kecerunan halaman auth, dan lavender pertengahan hilang sepenuhnya di situ.
- Nama kelas mesti muncul sebagai teks penuh supaya Tailwind dapat mengesannya. Sebab itu
  `StockCount::kelasStatus()` dan `StockMovement::kelasJenis()` memulangkan nama kelas lengkap
  (`lencana-kuning`) dan bukan potongan yang dicantum (`lencana-` . `$warna`).
- Nilai RGB mentah (`--rgb-aksen`, `--rgb-bayang`, dan seterusnya) diletakkan dalam `:root` dan
  **bukan** dalam `@theme`. Ia hanya bahan sokongan untuk `rgba()` dalam hiasan 3D dan kad kaca;
  meletakkannya dalam `@theme` menyebabkan Tailwind cuba menjana utiliti warna daripada senarai
  nombor yang bukan warna.
- Warna yang berbeza antara dua tema tetapi tidak muat sebagai token — seperti kelegapan latar
  kad kaca — ditulis sebagai peraturan `.dark .kelas` berasingan. Token sentiasa diutamakan;
  peraturan `.dark` hanya untuk yang benar-benar tidak dapat diungkap sebagai satu nilai.
- Peraturan `:-webkit-autofill` dalam `app.css` kelihatan pelik tetapi **jangan dipermudahkan**.
  Chrome mengecat medan yang diisi automatik dengan latar birunya sendiri dan menandanya
  `!important` dalam gaya ejen pengguna, jadi `background-color` biasa tidak dapat menindihnya —
  hanya `box-shadow: inset 0 0 0 100px` yang cukup tebal berjaya dicat di atasnya. `transition`
  yang panjangnya 100000s pula menahan warna asal Chrome daripada berkelip seketika sebelum
  bayang itu muncul, dan gelang fokus ditulis semula kerana ia hilang bersama `box-shadow`.
  Medan pada `.kad-log-masuk` mendapat versinya sendiri kerana kad itu bertona berbeza daripada
  permukaan lalai; warnanya datang daripada `--login-input-bg-autofill`, iaitu versi **legap**
  bagi `--login-input-bg`, kerana bayang inset perlu mengecat sepenuhnya dan bukan bertindih di
  atas warna lut sinar.

### Kebolehcapaian

Empat perkara dikendalikan untuk pengguna papan kekunci dan pembaca skrin:

- **Pautan langkau.** Setiap halaman sistem bermula dengan pautan *Langkau ke kandungan* yang
  tersembunyi sehingga difokus. Tanpanya, pengguna papan kekunci perlu menekan Tab melalui
  seluruh senarai nav sisi pada setiap halaman sebelum sampai ke kandungan.
- **Perangkap fokus dalam modal.** Semasa modal terbuka, Tab berkitar di dalamnya dan tidak boleh
  keluar ke kandungan di belakang — kandungan yang secara visual sudah tertutup oleh modal itu.
- **Fokus dikembalikan.** Menutup modal (butang, Escape, atau klik latar) memulangkan fokus ke
  butang yang membukanya. Tanpa itu fokus tercicir ke `<body>` dan pengguna terpaksa bermula
  semula dari atas halaman.
- **`aria-pressed` pada togol tema**, disegerakkan dengan keadaan sebenar `<html>` semasa halaman
  dimuatkan dan selepas setiap klik.

## Nota teknikal

- Kemas kini stok dibungkus dalam transaksi dengan `lockForUpdate()` untuk mengelak dua
  pengguna mengubah baki produk yang sama secara serentak. Ini terpakai pada ketiga-tiga jalan
  yang menyentuh baki: pergerakan stok, pengesahan imbasan invois, dan pengesahan sesi kiraan.
- Carta Ringkasan Bulanan dikumpulkan mengikut bulan dalam **PHP**, bukan dengan `GROUP BY`
  dalam SQL, kerana sintaks fungsi tarikh berbeza antara MySQL (aplikasi) dan SQLite (ujian).
  Pertanyaan yang ditulis untuk satu daripadanya akan gagal pada satu lagi.
- Blok yang perlu disembunyikan membawa `hidden` pada **pembungkusnya**, bukan pada elemen
  yang sama seperti `grid` atau `flex`. Kedua-duanya menetapkan `display`, jadi yang terakhir
  dijana dalam CSS akan menang — dan blok yang sepatutnya tersembunyi boleh kekal kelihatan.
- Medan yang disembunyikan oleh JavaScript turut kehilangan `required`nya. Medan wajib yang
  tidak kelihatan menghalang penghantaran borang tanpa menunjukkan kepada pengguna medan mana
  yang menahannya.
- `@json([...])` dengan array berbilang baris terus di dalamnya menghasilkan PHP yang tidak
  sah — kurungan penutupnya tercicir semasa Blade menghurai argumen arahan itu, dan halaman
  gagal dengan *ParseError*. Bina array dalam `@php` dahulu, kemudian `@json($pemboleh)`.
- `app.css` menyenaraikan `storage/framework/views` sebagai sumber Tailwind, jadi saiz CSS yang
  dibina bergantung pada halaman mana yang kebetulan telah dikompil ke dalam cache itu. Untuk
  binaan yang boleh diramal, jalankan `php artisan view:clear` sebelum `npm run build`.
- `public/build` tidak disimpan dalam Git. Selepas `git clone` atau `git pull` yang menyentuh
  antara muka, jalankan `npm run build` semula.
- Halaman log masuk dan pendaftaran menggunakan `overflow-x-hidden` pada `<body>`, bukan
  `overflow-hidden`. `overflow-hidden` mematikan skrol menegak sepenuhnya, yang menjadikan
  kandungan di bawah kad — termasuk pautan kembali ke log masuk — tidak boleh dicapai.
- Latar konstelasi dan hiasan 3D menggunakan kedudukan `fixed` supaya halaman yang lebih
  panjang daripada skrin kekal sama rupa semasa diskrol, bukan meregang mengikut tinggi
  dokumen.
- Gelung animasi parallax berhenti sendiri apabila objek sudah cukup hampir dengan sasarannya,
  jadi tiada bingkai dikira selagi tetikus tidak bergerak. Faktor kedalaman dibaca sekali
  semasa permulaan dan bukan pada setiap bingkai.
- Borang dengan lebih daripada satu butang hantar — seperti *Imbas* dan *Simpan Sahaja* —
  membawa pilihannya dalam medan tersembunyi dan bukan dalam nilai butang. Butang yang
  dilumpuhkan dalam pengendali `submit` boleh menyebabkan nama dan nilainya tercicir daripada
  data borang, jadi tindakan yang dipilih hilang tepat pada saat ia diperlukan.
- Arahan yang membuang data (`ruang-kerja:kosongkan`) memaparkan kiraan setiap jenis rekod dan
  meminta pengesahan sebelum menyentuh apa-apa. `--force` hanya untuk skrip bukan interaktif.
- Mod cetak memaksa hitam di atas putih tanpa mengira tema semasa, jadi mencetak Laporan Bulanan
  dalam tema gelap tetap menghasilkan kertas putih dan bukan blok dakwat hitam.
- Butang hantar pada halaman auth (`.btn-nyala`) membawa kecerunan jenama yang sama seperti
  `.btn-utama`, cuma selebar kad dan dengan bayang bertona aksen. Ia pernah menjadi butang logam
  perak yang sengaja tidak mengikut token warna; perak itu neutral terhadap kedua-dua tema,
  tetapi pada latar matahari terbenam ia kelihatan seperti kawalan sistem pengendalian yang
  tersasar masuk dan bukan tindakan utama halaman.
