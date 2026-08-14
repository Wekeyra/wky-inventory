# Sejarah Perubahan

Perubahan penting pada WKY Inventory, yang terbaharu di atas.

Projek ini belum menggunakan nombor versi, jadi entri dikumpulkan mengikut tarikh.
Setiap entri menerangkan perubahan dari sudut pengguna sistem; sebab teknikal di
sebalik sesuatu keputusan disimpan dalam mesej commit dan `README.md`.

## 2026-08-14

### Diperbaiki

- **Ruang kamera invois jauh lebih besar pada telefon.** Modal kamera kini penuh skrin, dan
  pratontonnya mengambil semua ruang tinggi yang tinggal dan bukan lagi kotak landskap 4:3.
  Invois potret pada skrin potret: kotak lama membuang lebih separuh skrin dan memaksa pengguna
  mengangkat telefon lebih jauh, yang menjadikan teks invois terlalu halus untuk dibaca AI.
- **Carta Ringkasan Bulanan pada dashboard kini benar-benar dilukis.** Skripnya berjalan sebelum
  Chart.js sempat dimuatkan, jadi kad itu kekal kosong pada setiap muatan.

### Ditambah

- **Ciri lanjutan kini boleh dihidup-matikan setiap ruang kerja** melalui *Tetapan → Ciri
  Lanjutan* (admin sahaja). Syarikat yang baru mendaftar bermula dengan lapan fungsi teras sahaja
  — pendaftaran produk, stok masuk, stok keluar, baki masa nyata, amaran stok rendah, pelarasan,
  laporan dan jejak audit. Gudang berbilang, Imbas Invois, Pesanan Belian, Jualan dan Analitik
  dibuka kemudian, apabila syarikat itu benar-benar memerlukannya.
- **Ruang kerja sedia ada tidak berubah.** Semua modul kekal hidup selepas naik taraf, kerana
  mematikan modul yang sudah berdata bukan naik taraf — itu kehilangan.
- **Mematikan modul hanya menyembunyikannya.** Datanya kekal dan menghidupkannya semula
  memulangkan segala-galanya seperti sedia kala; suis ini bukan butang padam yang menyamar.
- **Butang tambah produk pada borang stok dan modal Tambah Stok Pantas.** Produk yang hilang
  paling kerap disedari tepat semasa cuba merekod stoknya. Ia paling ketara pada pemasangan
  baharu, yang bermula tanpa satu produk pun — sebelum ini borang stok pertama yang dilihat
  pengguna ialah borang yang tidak boleh dihantar, tanpa apa-apa pada skrin itu memberitahunya ke
  mana perlu pergi. Menyimpan produk daripada borang stok memulangkannya ke situ dengan produk
  baharu itu **sudah terpilih**.
- **Menu navigasi pada telefon.** Butang hamburger di sebelah tajuk halaman membuka laci yang
  menyelinap masuk dari kiri. Sebelum ini bar sisi tersembunyi sepenuhnya di bawah saiz tablet,
  jadi pengguna telefon langsung tiada navigasi — hanya butang tindakan pantas yang terapung.
  Laci menutup dengan Escape, sentuhan pada latar, butang tutupnya sendiri, dan apabila skrin
  melebar semula ke saiz desktop.
- **Halaman Analitik.** Cadangan pesanan semula yang mengira kadar penggunaan sebenar dan bukan
  paras minimum sahaja, produk paling menguntungkan (disusun mengikut untung dan bukan kuantiti),
  pusing ganti inventori, dan stok mati mengikut nilai yang tersekat. Tempoh boleh ditukar antara
  30 hari hingga setahun.
- **Cadangan reorder terus menjadi permohonan pembelian.** Tanda produk yang hendak dipesan, tekan
  satu butang, dan borang permohonan terbuka dengan semua baris dan kuantitinya sudah terisi.
- **Imbasan invois boleh dipautkan kepada pesanan belian.** Mengesahkan imbasan itu memajukan
  kuantiti diterima pesanan dan menutupnya apabila lengkap. Sebelum ini pesanan kekal *Diluluskan*
  selama-lamanya walaupun invoisnya sudah dibayar dan barangnya sudah di rak. Kuantiti yang
  melebihi baki pesanan tetap masuk ke stok — barang itu memang sampai — tetapi tidak dikira
  terhadap pesanan.
- **Modul Jualan, dengan kos barang dijual dan untung kasar.** Setiap baris jualan membekukan dua
  harga — harga yang dibayar pelanggan, dan kos barang itu pada masa ia keluar. Untung kasar
  dikira daripada kedua-duanya, jadi menukar harga produk kemudian tidak menulis semula
  keuntungan yang sudah berlaku.
- **Untung kasar bulanan pada Laporan Bulanan**, berserta jumlah jualan, kos barang dijual dan
  peratus margin. Ia hanya muncul pada bulan yang ada jualan.
- **Jualan yang kosnya tidak lengkap ditandakan.** Produk yang harga kosnya belum pernah
  ditetapkan meninggalkan kos sebagai tidak diketahui, dan COGS sifar akan menghasilkan untung
  kasar yang menyamai keseluruhan jualan — angka yang kelihatan hebat dan sepenuhnya palsu.
  Jualan begitu dibawa dengan amaran pada senarai, halaman jualan, dan laporan bulanan.
- **Modul Pesanan Belian.** Permohonan pembelian → kelulusan admin → penerimaan barang, dalam
  satu rekod yang melalui keseluruhan aliran. Permohonan yang diluluskan *menjadi* PO dan bukan
  disalin menjadi dokumen kedua.
- **Kelulusan berperingkat yang pertama dalam sistem.** Staf boleh memohon tetapi tidak boleh
  meluluskan; hanya admin boleh meluluskan atau menolak, dan keputusan itu direkod berserta siapa
  dan bila. Permohonan yang ditolak boleh membawa sebabnya.
- **Penerimaan separa.** Satu pesanan boleh diterima berkali-kali sehingga setiap baris penuh,
  dan ia bertukar *Selesai* dengan sendirinya apabila itu berlaku. Menerima lebih daripada yang
  dipesan ditolak; lebihan sebenar perlu direkod sebagai stok masuk biasa.
- **Kos yang diluluskan pada PO dicap pada pergerakan stok** semasa barang diterima, jadi harga
  yang diluluskan itulah yang masuk ke dalam kira-kira — bukan harga kos produk yang mungkin sudah
  berubah antara kelulusan dan penghantaran.
- **Kos seunit pada setiap pergerakan stok dan setiap lot batch.** Kos yang dibayar dibekukan
  pada rekod, jadi laporan bulan lepas tidak lagi berubah nilainya apabila harga pembekal naik
  bulan ini. Borang stok masuk meminta kos (kosong bermakna guna harga kos produk), dan imbasan
  invois menyimpan harga unit yang sudah pun dibacanya daripada invois — sebelum ini harga itu
  dibaca kemudian dibuang.
- **Stok keluar daripada lot membawa kos lot itu**, bukan harga kos semasa produk. Apabila lot
  dipilih, sistem tahu dengan tepat unit mana yang keluar.
- **Kos dipaparkan** pada senarai Pergerakan Stok (kos seunit dan jumlahnya) dan pada senarai lot
  di halaman produk. Pergerakan yang berlaku sebelum ciri ini dipasang berbunyi *Tidak direkod*,
  bukan RM 0.00.
- **Nilai stok dikira daripada kos lot** bagi produk yang dijejak batchnya, dan bukan lagi
  daripada harga kos semasa produk. Produk lain kekal seperti dahulu, dan lot yang belum berkos
  jatuh kepada harga kos produk supaya tiada stok lama lenyap daripada jumlah.
- **Butang tambah kategori pada penapis halaman Produk.** Kategori yang hilang paling kerap
  disedari tepat semasa cuba menapis dengannya, jadi butang **+** kecil di sebelah penapis itu
  membuka borang kategori baharu terus. Simpan dan Batal kedua-duanya pulang ke halaman Produk,
  jadi kategori baharu boleh terus dipilih tanpa mencari jalan kembali.

### Diubah

- **Butang tindakan pantas kini menawarkan Stok Masuk dan Stok Keluar**, menggantikan Tambah
  Produk dan Tambah Kategori. Kedua-duanya membuka borang pergerakan stok dengan jenisnya sudah
  dipilih dan senarai sebabnya sudah ditapis — bukan sekadar borang kosong. Produk dan kategori
  masih boleh ditambah daripada halamannya sendiri, yang sememangnya tempat kerja itu bermula.

- **Palet baharu: matahari terbenam gudang.** Oren panas di atas, magenta di tengah, ungu pekat
  di bawah. Halaman log masuk, pendaftaran dan pendaratan membawa kecerunan itu penuh, berserta
  siluet bandar lilac dan hiasan gudang 3D yang kini bertona merah jambu-oren.
- **Jenama kini dua tona, bukan satu.** Magenta untuk isian pejal (lencana, pautan nav aktif,
  gelang fokus) dan oren untuk aksen teks serta kata jenama *WKY*. Kedua-duanya bertemu sebagai
  kecerunan pada setiap tindakan utama — butang *Simpan*, butang *Log Masuk*, dan butang tindakan
  pantas yang terapung.
- **Halaman sistem turut bertukar.** Tema gelap tidak lagi kelabu neutral: kad, bar sisi dan
  jadual kini duduk atas ungu plum, dalam keluarga warna yang sama seperti halaman auth. Tema
  terang menjadi krim kemerahan dengan teks ungu pekat.
- **Butang Log Masuk dan Daftar bukan lagi butang logam perak**, sebaliknya membawa kecerunan
  jenama selebar kad seperti dalam reka bentuk.
- **Carta Ringkasan Bulanan mengikut tema.** Warna garis, label dan grid dahulunya ditulis tetap
  dalam halaman dashboard — nilai tema gelap yang tidak pernah berubah walaupun pada tema terang.
  Kini ia dibaca daripada token tema. Carta masih perlu dimuat semula selepas menogol tema,
  kerana Chart.js menyimpan warna dalam konfigurasinya sendiri.
- **Teks sekunder pada tema gelap dicerahkan** daripada kelabu kepada lavender terang. Ia perlu
  dibaca di atas jalur magenta halaman auth, bukan hanya di atas kad.

### Diperbaiki

- **Halaman pendaratan tidak lagi menatal melalui jalur oren.** Latar kecerunan yang dilekatkan
  pada viewport bermakna setiap seksyen halaman yang panjang itu akan melalui bahagian paling
  cerah, dan teks sekunder hilang di atasnya. Kecerunan kini dihadkan kepada skrin pertama;
  selebihnya halaman menatal ke atas warna hujung yang rata.

### Nota

- Merah `bahaya` masih token berasingan daripada warna jenama, tetapi jenama kini condong ke
  merah jambu — jadi warna sahaja tidak lagi cukup untuk membezakan *Padam* daripada *Simpan*.
  Pengasingan itu kini bergantung pada bentuk: tindakan merosakkan bergaya garis dengan ikon,
  tindakan utama ialah blok berkecerunan pejal.
- Fail logo tidak disentuh. `public/images/logo-wky.png` dan `logo-wky-w.png` kekal seperti asal.

## 2026-08-13

### Ditambah

- **Tema terang dan gelap.** Butang suria/bulan di bar atas menukar tema, dan ia muncul pada
  dashboard, log masuk, pendaftaran serta halaman pendaratan. Pilihan diingati pada peranti
  itu; lawatan pertama mengikut tetapan sistem pengendalian pengguna. Halaman ditetapkan
  temanya sebelum dicat, jadi tiada kelipan putih semasa memuat.
- **Pautan "Langkau ke kandungan"** pada setiap halaman sistem, tersembunyi sehingga difokus.
  Pengguna papan kekunci tidak perlu lagi menekan Tab melalui seluruh nav sisi pada setiap
  halaman.
- **Perangkap fokus dalam modal.** Tab kini berkitar di dalam modal yang terbuka dan tidak
  boleh terkeluar ke kandungan di belakangnya. Menutup modal memulangkan fokus ke butang yang
  membukanya, bukan mencampakkannya ke atas halaman.
- **Gudang dan cawangan.** Setiap ruang kerja bermula dengan satu Gudang Utama, dan gudang
  lain boleh ditambah. Baki setiap produk kini disimpan bagi setiap gudang, berserta catatan
  rak atau bin, sementara jumlah keseluruhan produk kekal seperti biasa. Halaman produk
  memaparkan pecahan mengikut gudang.
- **Pemindahan stok** antara gudang dalam dua peringkat: menghantar menolak baki gudang asal,
  dan stok itu berada "dalam perjalanan" sehingga gudang tujuan mengesahkan penerimaan.
  Pemindahan yang belum diterima boleh dibatalkan dan stoknya dipulangkan.
- **Lokasi pada setiap pergerakan stok.** Stok keluar kini disemak terhadap baki gudang
  berkenaan, bukan hanya jumlah keseluruhan — satu gudang tidak boleh menghantar barang yang
  berada di gudang lain. Senarai pergerakan boleh ditapis mengikut gudang.
- **Barcode produk dan pengimbas.** Setiap produk boleh membawa barcode selain SKUnya, dan
  kod boleh dimasukkan dengan menaipnya, dengan pengimbas USB di kaunter, atau dengan kamera
  peranti. Butang kamera menyembunyikan dirinya pada pelayar tanpa `BarcodeDetector`.
  Pengimbas tersedia pada borang produk, carian senarai produk, dan borang pergerakan stok.
- **Gambar produk**, disimpan pada cakera peribadi dan dihidangkan melalui laluan berskop
  ruang kerja seperti fail invois. Gambar lama dibuang apabila diganti atau dipadam.
- **Nombor batch, tarikh luput dan nombor siri**, dihidupkan produk demi produk. Stok masuk
  meminta nombor lot; stok keluar meminta lot mana yang diambil, disusun mengikut tarikh
  luput terawal. Imbasan invois mencipta lot penerimaan bernamakan nombor invois.
- **Amaran batch hampir tamat tempoh** pada dashboard, bagi lot yang sudah luput atau akan
  luput dalam 30 hari. Kad itu hanya muncul apabila ada lot sedemikian.
- **Sebab pergerakan stok** — jualan, sampel, kegunaan dalaman, rosak, hilang, pemulangan,
  pembelian, kiraan fizikal. Senarainya dikunci mengikut jenis pergerakan, dan senarai
  pergerakan boleh ditapis mengikutnya.
- **Delivery Order** bagi setiap stok keluar: nombor berjujukan dalam ruang kerja
  (`DO-2026-001`), medan penerima, dan halaman cetakan dengan ruang tandatangan.
- **Produk dicipta sendiri semasa imbasan.** Baris invois yang tiada padanan tidak lagi
  ditinggalkan kosong — sistem menciptanya sebagai produk baharu dan terus memadankannya,
  menggunakan kod pembekal sebagai SKU supaya invois berikutnya daripada pembekal yang
  sama padan dengan sendirinya. Client hanya perlu mengambil gambar dan menekan
  *Sahkan & Rekod Stok Masuk* sekali.
- **Butang edit dan padam** pada senarai imbasan invois, mengikut corak ikon yang sama
  seperti halaman Produk.
- **Pautan "Cipta produk dari baris ini"** pada baris yang padanannya dikosongkan. Ia
  membuka borang produk dengan SKU, nama dan harga kos daripada invois sudah terisi, dan
  memautkan produk baharu itu kembali kepada baris berkenaan selepas disimpan.
- **Pautan "Kembali ke Utama"** pada halaman log masuk. Sebelum ini halaman itu jalan
  buntu bagi pelawat yang datang dari halaman pendaratan dan berubah fikiran.
- **Hiasan gudang 3D** pada halaman log masuk dan pendaftaran: kotak terbuka di atas
  palet, rak tiga tingkat, label kod bar, dan forklift. Semuanya berputar perlahan dan
  condong mengikut gerakan tetikus, dan berhenti apabila `prefers-reduced-motion`
  ditetapkan. Dibina daripada satah CSS, jadi tiada kos muat turun tambahan.
- **Tanda jenama W** menggantikan ikon kotak generik pada kepala bar sisi.

### Diubah

- **Warna jenama bertukar daripada merah kepada tanah liat hangat**, dan merah sebenar kini
  dikhaskan untuk amaran, ralat dan butang padam sahaja. Sebelum ini butang *Simpan* dan butang
  *Padam* berkongsi warna yang sama dan hanya berbeza pada bentuknya.
- **Tema lalai kini terang.** Pemasangan sedia ada yang mahu kekal gelap hanya perlu menekan
  togol sekali; pilihan itu diingati selepas itu.
- **Butang menunjukkan hierarki yang lebih jelas** antara tindakan utama, tindakan kedua,
  tindakan neutral dan tindakan merosakkan, dan memberi maklum balas ringkas apabila ditekan.
- **`ruang-kerja:kosongkan` turut membuang pemindahan stok** dan baki gudang, supaya ruang
  kerja yang dikosongkan tidak meninggalkan rekod pemindahan yang produknya sudah hilang.
  Gudang itu sendiri kekal.
- **Sesi kiraan stok kini terikat pada satu gudang.** Kuantiti rekod yang disimpan ialah baki
  gudang itu dan bukan jumlah keseluruhan produk, dan pelarasannya hanya menyentuh baki di
  situ. Sesi lama dinisbahkan kepada Gudang Utama oleh migrasi.
- **Pelarasan stok menetapkan baki gudang yang dipilih**, kemudian jumlah produk dikira semula
  daripada semua gudang. Pada pemasangan satu gudang, hasilnya sama seperti sebelum ini.
- **Setiap pergerakan stok kini memerlukan sebab.** Borang lama yang hanya menghantar jenis
  dan kuantiti akan ditolak. Ini termasuk borang Tambah Stok Pantas pada dashboard.
- **Padam imbasan benar-benar memadam.** Sebelum ini butang itu hanya menukar status
  kepada *Dibatalkan* dan barisnya kekal dalam senarai selama-lamanya. Kini rekod dan
  gambar invoisnya dibuang terus. Hanya imbasan draf boleh dipadam — imbasan yang telah
  disahkan sudah menjana pergerakan stok yang merujuk kodnya.
- **Butang *Sahkan & Rekod Stok Masuk* tidak lagi bertanya.** Halaman imbasan itu sendiri
  sudah menjadi skrin semakan. Butang Padam dan sesi Kiraan Stok tetap bertanya.

### Dibetulkan

- Nota pada halaman imbas yang berbunyi "Stock does **None** change now" dalam bahasa
  Inggeris. Versi Melayunya turut salah — "Stok **Tiada** berubah sekarang" — cuma kurang
  ketara.

### Dokumentasi

- Seksyen baharu untuk tema terang/gelap: turutan keutamaan yang memilih tema, sebab pilihan
  disimpan dalam `localStorage` dan bukan sesi pelayan seperti pilihan bahasa, dan sebab skrip
  temanya mesti kekal menyekat di dalam `<head>` dan tidak boleh dipindahkan ke `app.js`.
- Seksyen Palet ditulis semula. Ia masih menyatakan sistem ini bertema gelap tunggal tanpa suis
  terang/gelap — tepat bertentangan dengan apa yang kini wujud. Versi baharu menerangkan
  pengasingan `aksen` daripada `bahaya`, dan sebab nilai RGB mentah diletakkan di luar `@theme`.
- Seksyen baharu untuk kebolehcapaian: pautan langkau, perangkap fokus modal, fokus yang
  dikembalikan selepas modal ditutup, dan `aria-pressed` pada togol tema.
- `README.md` diselaraskan semula dengan kod: arahan `composer setup`, `composer dev`
  dan `composer test` yang sudah wujud tetapi tidak pernah didokumenkan, nota bahawa
  ujian berjalan pada SQLite dalam ingatan, tiga komponen antara muka yang tertinggal
  daripada jadual, seksyen baharu untuk butang mata kata laluan, dan seksyen palet
  yang menerangkan sebab peraturan `:-webkit-autofill` tidak boleh dipermudahkan.
- Seksyen padanan produk ditulis semula kerana ia masih menerangkan sistem yang sudah
  tidak wujud: baris tanpa padanan tidak lagi ditinggalkan kepada pengguna. Seksyen
  baharu ditambah untuk pemadaman imbasan, ketiadaan dialog pengesahan, dan hiasan 3D.
- `ANTHROPIC_TIMEOUT` dan `ANTHROPIC_SAIZ_MAKS_KB` dimasukkan ke dalam `.env.example`.
  Kedua-duanya sudah didokumenkan dalam README tetapi tiada dalam fail contoh.
- Seksyen baharu untuk perkara yang selama ini hanya wujud dalam kod: tempat fail invois
  disimpan dan sebab ia dihidangkan melalui laluan dan bukan `public/`; apa yang berlaku
  semasa pengesahan imbasan, termasuk rujukan yang dicap pada pergerakan stok dan padanan
  nama pembekal; serta sekatan pengurusan pengguna yang menghalang admin daripada
  menurunkan pangkat atau memadam akaunnya sendiri.
- Nota bahawa emel pengguna unik merentas seluruh sistem sedangkan SKU dan kod jujukan unik
  dalam ruang kerja sahaja — perbezaan yang mudah disalah anggap sebagai terlepas pandang.
- Nota bahawa membatalkan sesi kiraan mengekalkan rekodnya, berbeza daripada memadam
  imbasan invois yang membuangnya terus.
- Butiran kecil yang tertinggal: carian dan tapisan pada halaman Produk dan Pergerakan
  Stok, sekatan padam pada pembekal, `npm run build` sebagai langkah deploy yang wajib,
  laluan kesihatan `/up` yang tidak menyentuh pangkalan data, dan jadual ujian harian
  di GitHub Actions.
- Seksyen baharu untuk barcode dan pengimbas, gambar produk, batch dan tarikh luput, serta
  sebab pergerakan dan Delivery Order — termasuk had yang diketahui: pelarasan menyeluruh
  tidak menyentuh lot, jadi jumlah lot boleh terpesong daripada baki produk dan perbezaan
  itu dipaparkan pada halaman produk.
- Seksyen gudang dan pemindahan stok: hubungan antara jumlah produk, baki setiap gudang dan
  stok dalam perjalanan; sebab pemindahan direkod sebagai jenis tersendiri dan bukan sepasang
  masuk/keluar; serta had bahawa baki batch belum dipecahkan mengikut gudang.

### Penyelenggaraan

- Alur kerja ujian dibetulkan. Ia dicetuskan pada cawangan `master` sedangkan projek
  ini menggunakan `main`, jadi tiada ujian pernah berjalan selepas push. Ia juga kini
  membina aset sebelum menguji, kerana setiap susun atur memanggil `@vite` dan
  `public/build` tidak disimpan dalam Git.
- Alur kerja warisan rangka Laravel (`issues.yml`, `pull-requests.yml`,
  `update-changelog.yml`) dibuang. Ketiga-tiganya memanggil alur kerja milik organisasi
  Laravel yang tidak berkaitan dengan repositori ini.
- Fail ini ditulis semula. Sebelum ini ia mengandungi nota keluaran rangka
  `laravel/laravel`, bukan sejarah projek ini.

## 2026-08-12

### Ditambah

- **Ruang kerja setiap syarikat.** Produk, kategori, pembekal, pergerakan stok, sesi
  kiraan, imbasan invois dan pengguna dimiliki oleh satu ruang kerja dan tidak pernah
  bertemu data ruang kerja lain. Pengasingan dikuatkuasakan pada peringkat model, jadi
  laluan yang tidak pernah disentuh pun tetap terasing.
- **Pendaftaran sendiri.** Sesiapa boleh mendaftar di `/daftar` dengan nama syarikat
  dan menjadi admin ruang kerjanya sendiri.
- **Log masuk Google.** Muncul hanya apabila kelayakan OAuth diisi; pemasangan tanpa
  Google tetap berfungsi penuh.
- **Halaman pendaratan awam** di `/` dengan nav Utama, Ciri, Harga, Inventori, dan
  Tentang Kami. Pengguna yang sudah log masuk dialihkan terus ke dashboard.
- **Butang tindakan pantas** terapung pada setiap halaman sistem, membuka empat
  pintasan: Imbas Resit, Muat Naik, Tambah Produk, Tambah Kategori.
- **Butang mata** pada setiap medan kata laluan untuk menyemak apa yang ditaip.
- **Ambil gambar invois terus** dengan kamera peranti, termasuk pratonton, tukar
  kamera, dan pengecilan imej sebelum dihantar.
- **Simpan Sahaja** pada imbasan invois: gambar direkod tanpa memanggil AI dan boleh
  dibaca kemudian. Gambar juga disimpan sebelum AI dipanggil pada aliran biasa, jadi
  bacaan yang gagal tidak menghilangkan invois.
- Arahan `pengguna:pisah` untuk memindahkan akaun lama ke ruang kerjanya sendiri.
- Arahan `ruang-kerja:kosongkan` untuk mengosongkan ruang kerja yang mewarisi data
  lama, dengan kiraan dan pengesahan sebelum apa-apa dibuang.

### Diubah

- Penukaran bahasa kini satu permintaan HTTP dan bukan dua, dan mengekalkan penapis
  carian serta nombor halaman pada URL semasa.
- Halaman daftar boleh diskrol dan membawa pautan kembali ke log masuk.
- Halaman auth mendapat kata jenama WKY INVENTORY dan latar grafit; logo sebenar
  menggantikan lukisan SVG terbina.
- Medan borang yang diisi automatik oleh pelayar kini mengikut tema gelap.

### Dibuang

- Kelulusan admin untuk akaun baharu. Akaun yang mendaftar terus boleh digunakan.
- Data contoh. Pemasangan baharu bermula kosong sepenuhnya — tiada produk contoh dan
  tiada akaun berkata laluan lalai, kerana halaman pendaftaran terbuka kepada umum.

## 2026-08-10

### Ditambah

- **Sistem inventori asas:** produk, kategori, pembekal, dan pergerakan stok dengan
  jejak audit penuh. Kuantiti stok hanya berubah melalui modul Pergerakan Stok, tidak
  pernah melalui borang produk.
- **Dashboard** dengan kad statistik, carta Ringkasan Bulanan, amaran stok rendah,
  pergerakan terkini, dan borang Tambah Stok Pantas.
- **Laporan Bulanan** dengan pecahan masuk/keluar per produk dan susun atur mesra
  cetak.
- **Kiraan Stok:** sesi kiraan fizikal yang menyimpan gambaran baki, menerima kiraan
  sebenar, dan melaraskan stok selepas disahkan.
- **Imbas Invois (AI):** Claude membaca baris barang invois dan memadankannya dengan
  produk sedia ada. Stok tidak berubah sehingga imbasan disahkan.
- **Dwibahasa BM/EN** dengan penukar bahasa di bar atas setiap halaman, termasuk
  halaman log masuk.
- **Antara muka Tailwind CSS v4** melalui Vite, tanpa CDN — sistem berfungsi
  sepenuhnya tanpa sambungan internet.
- **Halaman log masuk** dengan logo berjenama dan latar konstelasi.
