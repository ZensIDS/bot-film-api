<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelegramController;
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
Route::post('/payment/create', [PaymentController::class, 'createTransaction']);
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

// Integration TWA
Route::get('/user/status', function (Request $request) {
    $telegramId = $request->query('telegram_id');

    if (!$telegramId) {
        return response()->json(['is_subscribed' => false, 'message' => 'Telegram ID required'], 400);
    }

    $user = User::where('telegram_id', $telegramId)->first();

    $isSubscribed = $user && $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture();

    return response()->json([
        'is_subscribed' => $isSubscribed,
        'user' => $user ? [
            'first_name' => $user->first_name,
            'expired_at' => $user->expired_at ? $user->expired_at->format('Y-m-d H:i:s') : null,
        ] : null
    ]);
});
