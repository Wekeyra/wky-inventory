<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductBatchController extends Controller
{
    /**
     * Mengemas kini tarikh luput dan nombor siri satu lot.
     *
     * Kuantiti sengaja tidak boleh disunting di sini. Baki lot tertakluk pada
     * peraturan yang sama seperti baki produk: ia hanya berubah melalui
     * pergerakan stok, supaya setiap perubahan ada rekod siapa dan mengapa.
     *
     * Maklumat yang boleh disunting pula memang selalunya tidak diketahui pada
     * masa penerimaan — lot yang dicipta daripada imbasan invois tiada tarikh
     * luput sehingga seseorang membaca kotaknya.
     */
    public function update(Request $request, Product $product, ProductBatch $batch): RedirectResponse
    {
        // Kedua-dua model sudah berskop ruang kerja; semakan ini menghalang lot
        // milik produk lain daripada disunting melalui URL produk ini.
        abort_unless($batch->product_id === $product->id, 404);

        $batch->update($request->validate([
            'no_siri' => ['nullable', 'string', 'max:100'],
            'tarikh_luput' => ['nullable', 'date'],
        ]));

        return back()->with('status', __('wky.flash.batch_dikemas_kini', ['batch' => $batch->no_batch]));
    }
}
