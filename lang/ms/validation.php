<?php

/*
|--------------------------------------------------------------------------
| Mesej Pengesahan (Bahasa Melayu)
|--------------------------------------------------------------------------
|
| Hanya peraturan yang digunakan oleh aplikasi ini diterjemahkan. Peraturan
| lain jatuh semula kepada fallback_locale (en) secara automatik, jadi tiada
| mesej yang hilang walaupun senarai ini tidak lengkap.
|
*/

return [

    'array' => 'Medan :attribute mestilah satu senarai.',
    'boolean' => 'Medan :attribute mestilah benar atau palsu.',
    'confirmed' => 'Pengesahan :attribute tidak sepadan.',
    'date' => 'Medan :attribute bukan tarikh yang sah.',
    'date_format' => 'Medan :attribute tidak menepati format :format.',
    'email' => 'Medan :attribute mestilah alamat emel yang sah.',
    'exists' => 'Pilihan :attribute tidak sah.',
    'in' => 'Pilihan :attribute tidak sah.',
    'integer' => 'Medan :attribute mestilah nombor bulat.',
    'numeric' => 'Medan :attribute mestilah nombor.',
    'required' => 'Medan :attribute wajib diisi.',
    'unique' => 'Nilai :attribute tersebut telah digunakan.',

    'max' => [
        'array' => 'Medan :attribute tidak boleh mempunyai lebih daripada :max item.',
        'file' => 'Saiz fail :attribute tidak boleh melebihi :max kilobait.',
        'numeric' => 'Nilai :attribute tidak boleh melebihi :max.',
        'string' => 'Medan :attribute tidak boleh melebihi :max aksara.',
    ],

    'min' => [
        'array' => 'Medan :attribute mesti mempunyai sekurang-kurangnya :min item.',
        'file' => 'Saiz fail :attribute mestilah sekurang-kurangnya :min kilobait.',
        'numeric' => 'Nilai :attribute mestilah sekurang-kurangnya :min.',
        'string' => 'Medan :attribute mestilah sekurang-kurangnya :min aksara.',
    ],

    'password' => [
        'letters' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu huruf.',
        'mixed' => 'Medan :attribute mesti mengandungi huruf besar dan huruf kecil.',
        'numbers' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu nombor.',
        'symbols' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu simbol.',
        'uncompromised' => 'Medan :attribute telah muncul dalam kebocoran data. Sila pilih yang lain.',
    ],

    /*
    | Nama medan yang dipaparkan dalam mesej di atas, supaya ralat berbunyi
    | "Medan Harga Kos wajib diisi" dan bukan "Medan harga_kos wajib diisi".
    */
    'attributes' => [
        'alamat' => 'Alamat',
        'aktif' => 'Status aktif',
        'barcode' => 'Barcode',
        'bulan' => 'Bulan',
        'catatan' => 'Catatan',
        'category_id' => 'Kategori',
        'gambar' => 'Gambar',
        'email' => 'Emel',
        'emel' => 'Emel',
        'harga_jual' => 'Harga Jual',
        'harga_kos' => 'Harga Kos',
        'jejak_batch' => 'Jejak batch',
        'jenis' => 'Jenis',
        'keterangan' => 'Keterangan',
        'kod' => 'Kod',
        'kuantiti' => 'Kuantiti',
        'kuantiti.*' => 'Kuantiti fizikal',
        'location_asal_id' => 'Gudang asal',
        'location_id' => 'Gudang',
        'location_tujuan_id' => 'Gudang tujuan',
        'name' => 'Nama',
        'nama' => 'Nama',
        'no_batch' => 'No. Batch',
        'no_siri' => 'No. Siri',
        'password' => 'Kata Laluan',
        'pegawai_perhubungan' => 'Pegawai Perhubungan',
        'penerima' => 'Penerima',
        'peranan' => 'Peranan',
        'product_batch_id' => 'Batch',
        'product_id' => 'Produk',
        'rujukan' => 'Rujukan',
        'sebab' => 'Sebab',
        'sku' => 'SKU',
        'tarikh_luput' => 'Tarikh luput',
        'stok' => 'Stok',
        'stok_minimum' => 'Stok Minimum',
        'supplier_id' => 'Pembekal',
        'telefon' => 'Telefon',
        'unit' => 'Unit',
    ],

];
