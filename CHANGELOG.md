# Sejarah Perubahan

Perubahan penting pada WKY Inventory, yang terbaharu di atas.

Projek ini belum menggunakan nombor versi, jadi entri dikumpulkan mengikut tarikh.
Setiap entri menerangkan perubahan dari sudut pengguna sistem; sebab teknikal di
sebalik sesuatu keputusan disimpan dalam mesej commit dan `README.md`.

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
