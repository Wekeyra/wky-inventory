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
| **Pergerakan Stok** | Rekod stok masuk, keluar, dan pelarasan — setiap satu menyimpan baki sebelum/selepas |
| **Laporan Bulanan** | Pecahan masuk/keluar per produk mengikut bulan, perubahan bersih, dan susun atur mesra cetak |
| **Pengguna** | Pengurusan akaun dan peranan (admin / staf). Hanya admin boleh akses |

Kuantiti stok **tidak boleh** diubah terus melalui borang produk. Ia hanya berubah melalui modul
Pergerakan Stok, supaya setiap perubahan baki mempunyai rekod siapa, bila, dan sebab.

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

Suite `tests/Feature/InventoryTest.php` meliputi kawalan akses, paparan semua halaman utama,
dan logik pergerakan stok (termasuk penolakan stok keluar yang melebihi baki).

## Nota teknikal

- Antara muka menggunakan Bootstrap 5 dan Chart.js melalui CDN — tiada langkah `npm run build` diperlukan.
- Tema gelap dengan aksen merah ditakrifkan dalam [`public/css/tema.css`](public/css/tema.css). Ia menulis
  ganti pemboleh ubah CSS Bootstrap 5.3 (`--bs-*`), jadi komponen standard mewarisi tema tanpa kelas tambahan.
  Tukar nilai `--wky-*` di bahagian atas fail itu untuk menukar palet keseluruhan sistem.
- Kemas kini stok dibungkus dalam transaksi dengan `lockForUpdate()` untuk mengelak dua
  pengguna mengubah baki produk yang sama secara serentak.
