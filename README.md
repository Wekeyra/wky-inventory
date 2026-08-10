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

## Pemasangan

```bash
git clone https://github.com/Wekeyra/wky-inventory.git
cd wky-inventory
composer install
cp .env.example .env
php artisan key:generate
```

Cipta database, kemudian jalankan migration dan seeder:

```sql
CREATE DATABASE wky_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
php artisan serve
```

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

## Nota teknikal

- Antara muka menggunakan Bootstrap 5 dan Chart.js melalui CDN — tiada langkah `npm run build` diperlukan.
- Tema gelap dengan aksen merah ditakrifkan dalam [`public/css/tema.css`](public/css/tema.css). Ia menulis
  ganti pemboleh ubah CSS Bootstrap 5.3 (`--bs-*`), jadi komponen standard mewarisi tema tanpa kelas tambahan.
  Tukar nilai `--wky-*` di bahagian atas fail itu untuk menukar palet keseluruhan sistem.
- Kemas kini stok dibungkus dalam transaksi dengan `lockForUpdate()` untuk mengelak dua
  pengguna mengubah baki produk yang sama secara serentak.
