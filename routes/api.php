<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelegramController;
use App\Http\Controllers\Api\MovieRequestController;
use App\Models\Package;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// API Webhook Telegram
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);

// API Integrated Midtrans (dipanggil dari halaman /checkout di dalam TWA)
Route::middleware('telegram.auth')->post('/payment/create', [PaymentController::class, 'createTransaction']);
Route::post('/payment/callback', [PaymentController::class, 'handleCallback']);

// Daftar paket untuk ditampilkan di TWA (tab Paket & halaman checkout)
Route::get('/packages', function () {
    return response()->json(
        Package::where('is_active', true)->orderBy('price')->get()
    );
});

Route::get('/packages/{package}', function (Package $package) {
    if (!$package->is_active) {
        return response()->json(['message' => 'Paket tidak tersedia'], 404);
    }
    return response()->json($package);
});

// Katalog film untuk Beranda TWA (gantikan demoDramas di app.blade.php)
Route::get('/movies', function () {
    $movies = \App\Models\Movie::active()
        ->withCount('episodes')
        ->latest()
        ->get()
        ->map(function ($movie) {
            return [
                'id' => $movie->id,
                'title' => $movie->title,
                'slug' => $movie->slug,
                'genre' => $movie->genre,
                'cover' => $movie->cover_url,
                'type' => $movie->type,
                // untuk series: jumlah episode yang sudah diinput admin.
                // untuk single: dianggap 1 "episode" (file tunggal).
                'episodes' => $movie->type === 'series' ? $movie->episodes_count : 1,
            ];
        });

    return response()->json($movies);
});

// Detail film untuk halaman /movie/{id} (gantikan demoMovies statis di movie-detail.blade.php)
Route::get('/movies/{movie}', function (\App\Models\Movie $movie) {
    if (!$movie->is_active) {
        return response()->json(['message' => 'Film tidak ditemukan'], 404);
    }

    $movie->loadCount('episodes');

    return response()->json([
        'id' => $movie->id,
        'title' => $movie->title,
        'slug' => $movie->slug,
        'genre' => $movie->genre,
        'cover' => $movie->cover_url,
        'type' => $movie->type,
        'synopsis' => $movie->description,
        'episodes' => $movie->type === 'series' ? $movie->episodes_count : 1,
        'episode_list' => $movie->type === 'series'
            ? $movie->episodes()->orderBy('episode_number')->get(['id', 'episode_number', 'title'])
            : [],
    ]);
});

// Request judul film baru (hanya untuk user yang berlangganan aktif)
Route::middleware('telegram.auth')->post('/movie-requests', [MovieRequestController::class, 'store']);

// Riwayat request film milik user
Route::middleware('telegram.auth')->get('/movie-requests', [MovieRequestController::class, 'index']);

// Integration TWA
Route::middleware('telegram.auth')->get('/user/status', function (Request $request) {
    $user = $request->attributes->get('verified_telegram_user');

    $isSubscribed = $user && $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture();

    return response()->json([
        'is_subscribed' => $isSubscribed,
        'user' => $user ? [
            'first_name' => $user->first_name,
            'expired_at' => $user->expired_at ? $user->expired_at->format('Y-m-d H:i:s') : null,
        ] : null
    ]);
});
