<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    /**
     * Kirim pesan teks biasa (parse_mode Markdown lama, satu bintang untuk bold)
     * ke chat_id tertentu. Dipakai untuk notifikasi dari Admin Panel ke user,
     * mis. update status request film.
     */
    public static function send($chatId, string $text): bool
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            Log::error('TELEGRAM_BOT_TOKEN belum diset di .env, notifikasi dibatalkan.');
            return false;
        }

        if (!$chatId) {
            Log::warning('TelegramNotifier::send dipanggil tanpa chat_id, dilewati.');
            return false;
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);

        if ($response->failed()) {
            Log::error('TelegramNotifier::send gagal: ' . $response->body());
            return false;
        }

        return true;
    }
}
