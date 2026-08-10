# WKY Inventory

Sistem pengurusan inventori berasaskan Laravel 13 untuk kegunaan dalaman — merekod produk,
kategori, pembekal, dan setiap pergerakan stok masuk/keluar dengan jejak audit penuh.

## Modul

| Modul | Keterangan |
|---|---|
| **Dashboard** | Ringkasan jumlah produk, nilai stok, amaran stok rendah, dan pergerakan terkini |
| **Produk** | CRUD produk dengan SKU, harga kos/jual, unit, paras stok minimum |
| **Kategori** | Pengelasan produk. Tidak boleh dipadam selagi masih digunakan produk |
| **Pembekal** | Maklumat pembekal dan senarai produk yang dibekalkan |
| **Pergerakan Stok** | Rekod stok masuk, keluar, dan pelarasan — setiap satu menyimpan baki sebelum/selepas |
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

- Antara muka menggunakan Bootstrap 5 melalui CDN — tiada langkah `npm run build` diperlukan.
- Kemas kini stok dibungkus dalam transaksi dengan `lockForUpdate()` untuk mengelak dua
  pengguna mengubah baki produk yang sama secara serentak.
