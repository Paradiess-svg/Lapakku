<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\StoreManagerController;
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
Route::get('/store/{toko_id}/contact-settings', [TokoController::class, 'publicContactSettings']);
Route::get('/produk/{produk_id}/gallery', [GalleryController::class, 'publicGallery']);
Route::get('/store/{toko_id}/order/{order_id}', [OrderController::class, 'publicTrackOrder']);

// Proses checkout belanja pembeli umum
Route::post('/checkout', [OrderController::class, 'checkout']);


/*
|--------------------------------------------------------------------------
| DASHBOARD TENANT MANAGEMENT (PROTECTED ROUTES - WAJIB BEARER TOKEN)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Setup Profil & Billing Toko Tenant
    Route::get('/store/me', [TokoController::class, 'me']);
    Route::post('/store/setup/step1', [TokoController::class, 'setupStep1']);
    Route::post('/store/setup/step2', [TokoController::class, 'setupStep2']);
    Route::get('/store/contact-settings', [TokoController::class, 'contactSettings']);
    Route::post('/store/contact-settings', [TokoController::class, 'updateContactSettings']);
    Route::get('/store/managers', [StoreManagerController::class, 'index']);
    Route::post('/store/managers', [StoreManagerController::class, 'store']);
    Route::post('/store/managers/{id}', [StoreManagerController::class, 'update']);
    Route::delete('/store/managers/{id}', [StoreManagerController::class, 'destroy']);

    // Manajemen Konten Marketing & Dropdown
    Route::get('/kategori', [KategoriController::class, 'index']);
    Route::post('/kategori', [KategoriController::class, 'store']);
    Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);
    Route::get('/hero', [HeroController::class, 'index']);
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

    // Super Admin HQ
    Route::get('/super-admin/summary', [SuperAdminController::class, 'summary']);
    Route::get('/super-admin/stores', [SuperAdminController::class, 'stores']);
    Route::get('/super-admin/stores/{id}', [SuperAdminController::class, 'storeDetail']);
    Route::get('/super-admin/users', [SuperAdminController::class, 'users']);
    Route::get('/super-admin/domains', [SuperAdminController::class, 'domains']);
    Route::post('/super-admin/stores/{id}/payment', [SuperAdminController::class, 'updatePayment']);
    Route::post('/super-admin/stores/{id}/status', [SuperAdminController::class, 'updateStoreStatus']);
    Route::post('/super-admin/stores/{id}/domain', [SuperAdminController::class, 'updateDomain']);
    Route::get('/super-admin/plans', [SuperAdminController::class, 'plans']);
    Route::post('/super-admin/plans', [SuperAdminController::class, 'storePlan']);
    Route::post('/super-admin/plans/{id}', [SuperAdminController::class, 'updatePlan']);
    Route::delete('/super-admin/plans/{id}', [SuperAdminController::class, 'deletePlan']);
});
