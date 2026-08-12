<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\InvoiceScanController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockCountController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

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
    Route::resource('products', ProductController::class);

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
        Route::put('{invoiceScan}', 'update')->name('update');
        Route::post('{invoiceScan}/sahkan', 'confirm')->name('confirm');
        Route::delete('{invoiceScan}', 'destroy')->name('destroy');
    });

    Route::get('stock', [StockMovementController::class, 'index'])->name('stock.index');
    Route::get('stock/create', [StockMovementController::class, 'create'])->name('stock.create');
    Route::post('stock', [StockMovementController::class, 'store'])->name('stock.store');

    Route::get('laporan/bulanan', [ReportController::class, 'monthly'])->name('reports.monthly');

    Route::resource('users', UserController::class)->except('show')->middleware('admin');
});
