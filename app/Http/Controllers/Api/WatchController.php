<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Movie;
use App\Services\TelegramMediaSender;
use Illuminate\Http\Request;

class WatchController extends Controller
{
    /**
     * Dipanggil dari tombol "Tonton Sekarang" di movie-detail.blade.php.
     * Setelah ini, user lanjut nonton episode berikutnya langsung dari tombol
     * di chat Telegram (lihat TelegramController::handleWebhook, callback_data "watch:..."),
     * tidak perlu balik ke TWA lagi tiap episode.
     */
    public function send(Request $request, Movie $movie)
    {
        $user = $request->attributes->get('verified_telegram_user');

        if (!$user) {
            return response()->json(['message' => 'Verifikasi Telegram gagal.'], 401);
        }

        $isSubscribed = $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture();
        if (!$isSubscribed) {
            return response()->json(['message' => 'Fitur nonton hanya untuk pelanggan VIP aktif.'], 403);
        }

        if (!$movie->is_active) {
            return response()->json(['message' => 'Film tidak ditemukan.'], 404);
        }

        $episode = null;

        if ($movie->type === 'series') {
            $data = $request->validate([
                'episode_id' => 'nullable|integer|exists:episodes,id',
            ]);

            $episode = isset($data['episode_id'])
                ? Episode::where('movie_id', $movie->id)->findOrFail($data['episode_id'])
                : Episode::where('movie_id', $movie->id)->orderBy('episode_number')->firstOrFail();
        }

        $result = TelegramMediaSender::sendEpisode($user->telegram_id, $movie, $episode);

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json([
            'message' => 'Video sudah dikirim ke chat Telegram-mu. Buka chat bot untuk mulai nonton — episode selanjutnya juga bisa langsung diakses dari sana.',
        ]);
    }
}
