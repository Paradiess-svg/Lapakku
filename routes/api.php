<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HeroController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ETALASE & PEMBELIAN STOREFRONT SAAS (PUBLIC ROUTES - TANPA TOKEN)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Endpoint Konsumsi Etalase Depan Anak FE
Route::get('/store/{toko_id}/hero', [HeroController::class, 'publicHero']);
Route::get('/store/{toko_id}/kategori', [KategoriController::class, 'publicKategori']);
Route::get('/store/{toko_id}/produk', [OrderController::class, 'publicProducts']);
Route::get('/produk/{produk_id}/gallery', [GalleryController::class, 'publicGallery']);

// Proses checkout belanja pembeli umum
Route::post('/checkout', [OrderController::class, 'checkout']);


/*
|--------------------------------------------------------------------------
| DASHBOARD TENANT MANAGEMENT (PROTECTED ROUTES - WAJIB BEARER TOKEN)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Setup Profil & Billing Toko Tenant
    Route::post('/store/setup/step1', [TokoController::class, 'setupStep1']);
    Route::post('/store/setup/step2', [TokoController::class, 'setupStep2']);

    // Manajemen Konten Marketing & Dropdown
    Route::post('/kategori', [KategoriController::class, 'store']);
    Route::post('/hero', [HeroController::class, 'store']);
    Route::post('/gallery', [GalleryController::class, 'store']);

    // CRUD Produk Toko Tenant
    Route::post('/produk', [ProdukController::class, 'store']);
    Route::get('/produk', [ProdukController::class, 'index']);
    Route::get('/produk/{id}', [ProdukController::class, 'show']);
    Route::post('/produk/{id}', [ProdukController::class, 'update']);
    Route::delete('/produk/{id}', [ProdukController::class, 'destroy']);

    // Manajemen Transaksi Masuk
    Route::get('/store/orders', [OrderController::class, 'tenantOrders']);
    Route::post('/store/orders/{id}/status', [OrderController::class, 'updateStatus']);
});