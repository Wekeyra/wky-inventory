<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\InvoiceScanController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductBatchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockCountController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Halaman pendaratan awam; pengguna yang sudah log masuk dialihkan ke dashboard.
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Di luar kumpulan auth supaya bahasa juga boleh ditukar dari halaman log masuk.
Route::get('bahasa/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);

    Route::get('daftar', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('daftar', [RegisterController::class, 'register']);

    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('suppliers', SupplierController::class);
    Route::resource('locations', LocationController::class);
    Route::resource('products', ProductController::class);

    // Gambar dihidangkan melalui laluan kerana ia disimpan pada cakera peribadi,
    // jadi pengikatan model yang berskop ruang kerja turut melindunginya.
    Route::get('products/{product}/gambar', [ProductController::class, 'gambar'])->name('products.gambar');
    Route::put('products/{product}/batch/{batch}', [ProductBatchController::class, 'update'])->name('products.batch.update');

    Route::controller(StockCountController::class)->prefix('kiraan-stok')->name('stock-counts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{stockCount}', 'show')->name('show');
        Route::put('{stockCount}', 'update')->name('update');
        Route::post('{stockCount}/sahkan', 'confirm')->name('confirm');
        Route::delete('{stockCount}', 'destroy')->name('destroy');
    });

    Route::controller(InvoiceScanController::class)->prefix('imbas-invois')->name('invoice-scans.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{invoiceScan}', 'show')->name('show');
        Route::get('{invoiceScan}/fail', 'file')->name('file');
        Route::post('{invoiceScan}/baca', 'read')->name('read');
        Route::put('{invoiceScan}', 'update')->name('update');
        Route::post('{invoiceScan}/sahkan', 'confirm')->name('confirm');
        Route::delete('{invoiceScan}', 'destroy')->name('destroy');
    });

    Route::controller(StockTransferController::class)->prefix('pindah-stok')->name('transfers.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{transfer}', 'show')->name('show');
        Route::post('{transfer}/terima', 'receive')->name('receive');
        Route::delete('{transfer}', 'destroy')->name('destroy');
    });

    /*
     | Purchase Order. Memohon dan menerima terbuka kepada semua pengguna;
     | hanya keputusan lulus/tolak dijaga middleware admin, supaya staf boleh
     | memohon tetapi tidak boleh meluluskan permohonannya sendiri.
     */
    Route::controller(PurchaseOrderController::class)->prefix('pesanan-belian')->name('purchase-orders.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{purchaseOrder}', 'show')->name('show');
        Route::get('{purchaseOrder}/edit', 'edit')->name('edit');
        Route::put('{purchaseOrder}', 'update')->name('update');
        Route::post('{purchaseOrder}/hantar', 'submit')->name('submit');
        Route::post('{purchaseOrder}/keputusan', 'decide')->name('decide')->middleware('admin');
        Route::post('{purchaseOrder}/terima', 'receive')->name('receive');
        Route::post('{purchaseOrder}/batal', 'cancel')->name('cancel');
        Route::delete('{purchaseOrder}', 'destroy')->name('destroy');
    });

    /*
     | Jualan. Tiada laluan sunting atau padam: jualan yang sudah direkod sudah
     | menolak stok dan mencatat kosnya, dan menyuntingnya bermakna menulis
     | semula sejarah pergerakan yang terhasil daripadanya. Kesilapan dibetulkan
     | dengan pergerakan stok pemulangan, seperti mana-mana rekod kewangan.
     */
    Route::controller(SaleController::class)->prefix('jualan')->name('sales.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{sale}', 'show')->name('show');
    });

    Route::get('stock', [StockMovementController::class, 'index'])->name('stock.index');
    Route::get('stock/create', [StockMovementController::class, 'create'])->name('stock.create');
    Route::post('stock', [StockMovementController::class, 'store'])->name('stock.store');
    Route::get('stock/{movement}/do', [StockMovementController::class, 'deliveryOrder'])->name('stock.do');

    Route::get('laporan/bulanan', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('analitik', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::resource('users', UserController::class)->except('show')->middleware('admin');
});
