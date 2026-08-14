<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovieRequest;
use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;

class MovieRequestController extends Controller
{
    /**
     * Simpan request judul film baru dari user.
     * Hanya user dengan langganan aktif yang boleh mengirim request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'movie_title' => 'required|string|max:255',
            'source' => 'required|string|max:100',
        ]);

        // Route ini pakai middleware telegram.auth, jadi user sudah terverifikasi
        // lewat initData Telegram (bukan telegram_id mentah dari body request).
        $user = $request->attributes->get('verified_telegram_user');

        if (!$user) {
            return response()->json(['message' => 'Verifikasi Telegram gagal.'], 401);
        }

        $isSubscribed = $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture();

        if (!$isSubscribed) {
            return response()->json([
                'message' => 'Fitur request film hanya untuk pelanggan aktif.',
            ], 403);
        }

        $movieRequest = MovieRequest::create([
            'user_id' => $user->id,
            'movie_title' => $validated['movie_title'],
            'source' => $validated['source'],
            'status' => 'PENDING',
        ]);

        $this->notifyRequestReceived($user, $movieRequest);

        return response()->json([
            'message' => 'Request film berhasil dikirim, terima kasih!',
            'data' => $movieRequest,
        ], 201);
    }

    /**
     * Kirim notifikasi Telegram ke user begitu request film baru masuk,
     * biar user tahu requestnya akan direview admin.
     */
    private function notifyRequestReceived(User $user, MovieRequest $movieRequest): void
    {
        if (!$user->telegram_id) {
            return;
        }

        $text = "✅ Terima kasih, {$user->first_name}!\n\n"
            . "Request film *{$movieRequest->movie_title}* kamu sudah kami terima dan akan *segera direview admin*. "
            . "Kami akan kabari lagi lewat sini kalau requestnya *disetujui* atau *ditolak*. Mohon ditunggu ya 🙏";

        TelegramNotifier::send($user->telegram_id, $text);
    }

    /**
     * Ambil riwayat request film milik user tertentu, terbaru lebih dulu.
     */
    public function index(Request $request)
    {
        $user = $request->attributes->get('verified_telegram_user');

        if (!$user) {
            return response()->json(['data' => []]);
        }

        $requests = MovieRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'movie_title', 'source', 'status', 'created_at']);

        return response()->json(['data' => $requests]);
    }
}
