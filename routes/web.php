<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// TWA View - MiniApp
Route::get('/app', function () {
    return view('app');
});

// TWA View - Checkout Page
Route::get('/checkout', function () {
    return view('checkout');
});

// TWA View - Halaman detail film
Route::get('/movie/{id}', function ($id) {
    return view('movie-detail', ['id' => $id]);
});

// TWA View - Halaman request judul film (khusus pelanggan aktif)
Route::get('/request-film', function () {
    return view('request-film');
});

// ==============================
// Admin Panel
// ==============================
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest-only (belum login)
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Admin\AuthController::class, 'login'])->name('login.submit');
    });

    // Butuh login sebagai admin
    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('logout');

        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Manajemen Film & Episode
        Route::resource('movies', \App\Http\Controllers\Admin\MovieController::class);
        Route::post('movies/{movie}/episodes', [\App\Http\Controllers\Admin\MovieController::class, 'storeEpisode'])->name('movies.episodes.store');
        Route::put('movies/{movie}/episodes/{episode}', [\App\Http\Controllers\Admin\MovieController::class, 'updateEpisode'])->name('movies.episodes.update');
        Route::delete('movies/{movie}/episodes/{episode}', [\App\Http\Controllers\Admin\MovieController::class, 'destroyEpisode'])->name('movies.episodes.destroy');

        // Manajemen Paket Langganan & Harga
        Route::get('packages', [\App\Http\Controllers\Admin\PackageController::class, 'index'])->name('packages.index');
        Route::post('packages', [\App\Http\Controllers\Admin\PackageController::class, 'store'])->name('packages.store');
        Route::put('packages/{package}', [\App\Http\Controllers\Admin\PackageController::class, 'update'])->name('packages.update');
        Route::post('packages/{package}/toggle-active', [\App\Http\Controllers\Admin\PackageController::class, 'toggleActive'])->name('packages.toggle-active');
        Route::post('packages/{package}/mark-featured', [\App\Http\Controllers\Admin\PackageController::class, 'markFeatured'])->name('packages.mark-featured');
        Route::post('packages/{package}/unmark-featured', [\App\Http\Controllers\Admin\PackageController::class, 'unmarkFeatured'])->name('packages.unmark-featured');
        Route::delete('packages/{package}', [\App\Http\Controllers\Admin\PackageController::class, 'destroy'])->name('packages.destroy');

        // Manajemen User & Langganan
        Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::post('users/{user}/extend-vip', [\App\Http\Controllers\Admin\UserController::class, 'extendVip'])->name('users.extend-vip');
        Route::post('users/{user}/revoke-vip', [\App\Http\Controllers\Admin\UserController::class, 'revokeVip'])->name('users.revoke-vip');

        // Riwayat Transaksi
        Route::get('transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'index'])->name('transactions.index');
        Route::post('transactions/withdraw', [\App\Http\Controllers\Admin\TransactionController::class, 'withdraw'])->name('transactions.withdraw');

        // Pengelolaan Request Film
        Route::get('movie-requests', [\App\Http\Controllers\Admin\MovieRequestController::class, 'index'])->name('movie-requests.index');
        Route::post('movie-requests/{movieRequest}/status', [\App\Http\Controllers\Admin\MovieRequestController::class, 'updateStatus'])->name('movie-requests.update-status');
    });
});
