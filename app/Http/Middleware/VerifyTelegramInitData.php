<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyTelegramInitData
{
    /**
     * Memverifikasi signature HMAC-SHA256 dari Telegram.WebApp.initData yang
     * dikirim frontend TWA, lalu meregister/mengambil User terkait dan
     * menempelkannya ke request supaya controller tidak lagi mempercayai
     * telegram_id mentah yang dikirim client (rawan dipalsukan lewat DevTools/curl).
     *
     * Cara pakai di frontend: kirim header berikut di setiap request yang dilindungi
     *   X-Telegram-Init-Data: <isi Telegram.WebApp.initData>
     *
     * Referensi resmi algoritma verifikasi:
     * https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
     */
    public function handle(Request $request, Closure $next)
    {
        $initData = $request->header('X-Telegram-Init-Data') ?? $request->input('init_data');

        if (!$initData) {
            return response()->json(['message' => 'initData Telegram tidak ditemukan.'], 401);
        }

        // DEBUG SEMENTARA: catat initData mentah persis seperti dikirim TWA, sebelum
        // di-parse sama sekali, buat dibandingkan langsung dengan raw_body di webhook bot.
        Log::info('RAW initData TWA', [
            'raw_init_data' => $initData,
            'PHP_INT_SIZE' => PHP_INT_SIZE,
        ]);

        parse_str($initData, $parsed);

        if (!isset($parsed['hash'])) {
            return response()->json(['message' => 'initData tidak valid (hash tidak ada).'], 401);
        }

        $hash = $parsed['hash'];
        unset($parsed['hash']);

        // Susun data_check_string: semua field selain hash, urut alfabetis, format key=value, digabung \n
        ksort($parsed);
        $dataCheckArr = [];
        foreach ($parsed as $key => $value) {
            $dataCheckArr[] = "{$key}={$value}";
        }
        $dataCheckString = implode("\n", $dataCheckArr);

        $botToken = config('services.telegram.bot_token');

        if (!$botToken) {
            Log::error('TELEGRAM_BOT_TOKEN tidak diset, tidak bisa verifikasi initData.');
            return response()->json(['message' => 'Konfigurasi server tidak lengkap.'], 500);
        }

        // secret_key = HMAC_SHA256(data: bot_token, key: "WebAppData")
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        // computed hash = HMAC_SHA256(data: data_check_string, key: secret_key)
        $computedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($computedHash, $hash)) {
            Log::warning('Verifikasi initData Telegram gagal (hash tidak cocok).');
            return response()->json(['message' => 'Verifikasi Telegram gagal.'], 401);
        }

        // Tolak initData yang sudah kadaluarsa (default maks 24 jam) untuk mencegah replay attack.
        $maxAgeSeconds = 60 * 60 * 24;
        if (isset($parsed['auth_date']) && (time() - (int) $parsed['auth_date']) > $maxAgeSeconds) {
            return response()->json(['message' => 'Sesi Telegram sudah kadaluarsa, silakan buka ulang bot.'], 401);
        }

        if (!isset($parsed['user'])) {
            return response()->json(['message' => 'Data user Telegram tidak ditemukan di initData.'], 401);
        }

        $tgUserData = json_decode($parsed['user'], true, 512, JSON_BIGINT_AS_STRING);

        if (!isset($tgUserData['id'])) {
            return response()->json(['message' => 'ID user Telegram tidak valid.'], 401);
        }

        $telegramId = (string) $tgUserData['id'];

        // Auto-register/update user berdasarkan data Telegram yang sudah terverifikasi.
        // Ini juga menyelesaikan kasus user membuka TWA langsung tanpa pernah /start di bot dulu.
        $user = User::firstOrCreate(
            ['telegram_id' => $telegramId],
            [
                'username' => $tgUserData['username'] ?? null,
                'first_name' => $tgUserData['first_name'] ?? null,
            ]
        );

        if (
            ($tgUserData['username'] ?? null) !== $user->username ||
            ($tgUserData['first_name'] ?? null) !== $user->first_name
        ) {
            $user->update([
                'username' => $tgUserData['username'] ?? $user->username,
                'first_name' => $tgUserData['first_name'] ?? $user->first_name,
            ]);
        }

        // Simpan hasil verifikasi ke request agar controller bisa pakai id/​user yang
        // SUDAH TERVERIFIKASI, bukan yang dikirim mentah oleh client.
        $request->attributes->set('verified_telegram_id', $telegramId);
        $request->attributes->set('verified_telegram_user', $user);

        return $next($request);
    }
}
