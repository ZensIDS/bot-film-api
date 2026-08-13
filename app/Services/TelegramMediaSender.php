<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramMediaSender
{
    /**
     * Kirim satu episode/film ke chat pribadi user via Telegram, dengan:
     * - protect_content aktif (Telegram mencegah forward/save/screen-record di sisi client resmi)
     * - tombol "Episode Selanjutnya" di bawah video (kalau ada), supaya user bisa lanjut nonton
     *   tanpa perlu balik ke TWA setiap episode.
     * - status VIP DICEK ULANG setiap kali dipanggil (bukan cuma sekali di awal), karena user
     *   bisa saja tetap lanjut menonton berhari-hari lewat tombol chat, di luar sesi TWA awal.
     *
     * @return array{ok: bool, message: string}
     */
    public static function sendEpisode(string $telegramChatId, Movie $movie, ?Episode $episode = null): array
    {
        $user = self::authorizeOrNotify($telegramChatId);
        if (!$user) {
            return ['ok' => false, 'message' => 'User belum berlangganan VIP aktif.'];
        }

        if (!$movie->is_active) {
            self::sendMessage($telegramChatId, "⚠️ Film ini sudah tidak tersedia.");
            return ['ok' => false, 'message' => 'Film tidak aktif.'];
        }

        $fileId = $episode ? $episode->telegram_file_id : $movie->telegram_file_id;

        if (!$fileId) {
            self::sendMessage($telegramChatId, "⚠️ Video belum tersedia untuk episode ini, coba lagi nanti ya.");
            return ['ok' => false, 'message' => 'telegram_file_id kosong.'];
        }

        $caption = $episode
            ? "🎬 *{$movie->title}*\nEpisode {$episode->episode_number}" . ($episode->title ? " — {$episode->title}" : '')
            : "🎬 *{$movie->title}*";

        $keyboard = self::buildKeyboard($movie, $episode);

        $token = config('services.telegram.bot_token');
        if (!$token) {
            Log::error('TELEGRAM_BOT_TOKEN belum diset di .env, sendVideo dibatalkan.');
            return ['ok' => false, 'message' => 'Bot token belum diset.'];
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendVideo", array_filter([
            'chat_id' => $telegramChatId,
            'video' => $fileId,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
            // WAJIB: mencegah user forward/save/screen-record video ini di Telegram client resmi.
            'protect_content' => true,
            'reply_markup' => $keyboard ? json_encode($keyboard) : null,
        ]));

        if ($response->failed()) {
            Log::error('Telegram sendVideo gagal: ' . $response->body());
            self::sendMessage($telegramChatId, "⚠️ Gagal mengirim video, coba lagi beberapa saat lagi.");
            return ['ok' => false, 'message' => 'Telegram API gagal mengirim video.'];
        }

        return ['ok' => true, 'message' => 'Video terkirim ke chat Telegram-mu.'];
    }

    /**
     * Pastikan user (berdasarkan chat_id Telegram) statusnya VIP aktif.
     * Kalau tidak, langsung kirim notice + tombol buka TWA untuk beli paket, lalu return null.
     */
    private static function authorizeOrNotify(string $telegramChatId): ?User
    {
        $user = User::where('telegram_id', $telegramChatId)->first();
        $isActive = $user && $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture();

        if ($isActive) {
            return $user;
        }

        $webAppUrl = config('services.telegram.webapp_url');

        $keyboard = $webAppUrl ? [
            'inline_keyboard' => [[
                ['text' => '💎 Beli Paket VIP', 'web_app' => ['url' => rtrim($webAppUrl, '/') . '/app']],
            ]],
        ] : null;

        $text = "🔒 Langganan VIP kamu sudah tidak aktif, jadi video tidak bisa dikirim.\n\nYuk perpanjang dulu biar bisa lanjut nonton.";

        if ($keyboard) {
            $token = config('services.telegram.bot_token');
            if ($token) {
                Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $telegramChatId,
                    'text' => $text,
                    'parse_mode' => 'Markdown',
                    'reply_markup' => json_encode($keyboard),
                ]);
            }
        } else {
            self::sendMessage($telegramChatId, $text);
        }

        return null;
    }

    /**
     * Susun tombol navigasi di bawah video: SEMUA episode setelah yang baru dikirim,
     * masing-masing jadi baris tombol sendiri (bukan cuma "Episode Selanjutnya" + "Ulangi").
     * Contoh: abis kirim Episode 1 -> tombol Episode 2, 3, 4, dst muncul semua di bawahnya.
     * Abis user klik Episode 2 -> pesan baru muncul dengan tombol Episode 3, 4, dst (episode
     * 1 & 2 otomatis tidak ditampilkan lagi karena sudah lewat).
     * Film tipe single tidak dikasih tombol navigasi (cuma satu file, tidak ada episode lain).
     */
    private static function buildKeyboard(Movie $movie, ?Episode $episode): ?array
    {
        if (!$episode) {
            return null;
        }

        $upcoming = Episode::where('movie_id', $movie->id)
            ->where('episode_number', '>', $episode->episode_number)
            ->orderBy('episode_number')
            ->limit(20) // batasi biar keyboard tidak kepanjangan untuk series yang episodenya puluhan
            ->get();

        if ($upcoming->isEmpty()) {
            return null;
        }

        $rows = $upcoming->map(function (Episode $ep) use ($movie) {
            $label = "▶️ Episode {$ep->episode_number}" . ($ep->title ? " — {$ep->title}" : '');
            return [['text' => $label, 'callback_data' => "watch:{$movie->id}:{$ep->id}"]];
        })->toArray();

        return ['inline_keyboard' => $rows];
    }

    private static function sendMessage(string $chatId, string $text): void
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
