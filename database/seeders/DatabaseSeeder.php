<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@wekeyra.test'],
            ['name' => 'Admin Wekeyra', 'peranan' => 'admin', 'password' => Hash::make('password123')],
        );

        User::updateOrCreate(
            ['email' => 'staf@wekeyra.test'],
            ['name' => 'Staf Stor', 'peranan' => 'staf', 'password' => Hash::make('password123')],
        );

        $kategori = collect([
            ['kod' => 'ELK', 'nama' => 'Elektronik', 'keterangan' => 'Peralatan dan aksesori elektronik'],
            ['kod' => 'PJB', 'nama' => 'Alat Tulis', 'keterangan' => 'Keperluan pejabat dan alat tulis'],
            ['kod' => 'PRB', 'nama' => 'Perabot', 'keterangan' => 'Perabot pejabat dan stor'],
        ])->mapWithKeys(fn ($data) => [$data['kod'] => Category::updateOrCreate(['kod' => $data['kod']], $data)]);

        $pembekal = collect([
            ['kod' => 'SUP001', 'nama' => 'Tech Supply Sdn Bhd', 'pegawai_perhubungan' => 'Encik Rahman', 'telefon' => '03-1234 5678', 'emel' => 'sales@techsupply.test'],
            ['kod' => 'SUP002', 'nama' => 'Pejabat Maju Enterprise', 'pegawai_perhubungan' => 'Puan Siti', 'telefon' => '03-8765 4321', 'emel' => 'info@pejabatmaju.test'],
        ])->mapWithKeys(fn ($data) => [$data['kod'] => Supplier::updateOrCreate(['kod' => $data['kod']], $data)]);

        $produk = [
            ['sku' => 'ELK-001', 'nama' => 'Papan Kekunci Mekanikal', 'kategori' => 'ELK', 'pembekal' => 'SUP001', 'unit' => 'unit', 'harga_kos' => 120.00, 'harga_jual' => 189.00, 'stok_minimum' => 5, 'stok_awal' => 24],
            ['sku' => 'ELK-002', 'nama' => 'Tetikus Tanpa Wayar', 'kategori' => 'ELK', 'pembekal' => 'SUP001', 'unit' => 'unit', 'harga_kos' => 45.00, 'harga_jual' => 79.00, 'stok_minimum' => 10, 'stok_awal' => 8],
            ['sku' => 'ELK-003', 'nama' => 'Monitor 24 inci', 'kategori' => 'ELK', 'pembekal' => 'SUP001', 'unit' => 'unit', 'harga_kos' => 480.00, 'harga_jual' => 699.00, 'stok_minimum' => 3, 'stok_awal' => 12],
            ['sku' => 'PJB-001', 'nama' => 'Kertas A4 80gsm (rim)', 'kategori' => 'PJB', 'pembekal' => 'SUP002', 'unit' => 'rim', 'harga_kos' => 12.50, 'harga_jual' => 18.00, 'stok_minimum' => 20, 'stok_awal' => 150],
            ['sku' => 'PJB-002', 'nama' => 'Pen Mata Bulat (kotak)', 'kategori' => 'PJB', 'pembekal' => 'SUP002', 'unit' => 'kotak', 'harga_kos' => 8.00, 'harga_jual' => 15.00, 'stok_minimum' => 15, 'stok_awal' => 12],
            ['sku' => 'PRB-001', 'nama' => 'Kerusi Pejabat Ergonomik', 'kategori' => 'PRB', 'pembekal' => 'SUP002', 'unit' => 'unit', 'harga_kos' => 320.00, 'harga_jual' => 499.00, 'stok_minimum' => 2, 'stok_awal' => 6],
        ];

        foreach ($produk as $data) {
            $item = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'nama' => $data['nama'],
                    'category_id' => $kategori[$data['kategori']]->id,
                    'supplier_id' => $pembekal[$data['pembekal']]->id,
                    'unit' => $data['unit'],
                    'harga_kos' => $data['harga_kos'],
                    'harga_jual' => $data['harga_jual'],
                    'stok_minimum' => $data['stok_minimum'],
                    'stok' => $data['stok_awal'],
                    'aktif' => true,
                ],
            );

            // Setiap produk bermula dengan satu rekod stok masuk supaya jejak audit lengkap dari awal.
            if ($item->movements()->doesntExist()) {
                StockMovement::create([
                    'product_id' => $item->id,
                    'user_id' => $admin->id,
                    'jenis' => 'masuk',
                    'kuantiti' => $data['stok_awal'],
                    'stok_sebelum' => 0,
                    'stok_selepas' => $data['stok_awal'],
                    'rujukan' => 'STOK-AWAL',
                    'catatan' => 'Stok pembukaan sistem.',
                ]);
            }
        }
    }
}
