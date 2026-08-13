# Sejarah Perubahan

Perubahan penting pada WKY Inventory, yang terbaharu di atas.

Projek ini belum menggunakan nombor versi, jadi entri dikumpulkan mengikut tarikh.
Setiap entri menerangkan perubahan dari sudut pengguna sistem; sebab teknikal di
sebalik sesuatu keputusan disimpan dalam mesej commit dan `README.md`.

## 2026-08-13

### Ditambah

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
