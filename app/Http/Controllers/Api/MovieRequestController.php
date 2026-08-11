<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovieRequest;
use App\Models\User;
use Illuminate\Http\Request;

class MovieRequestController extends Controller
{
    /**
     * Simpan request judul film baru dari user.
     * Hanya user dengan langganan aktif yang boleh mengirim request.
     */
    public function store(Request $request)
    {
        // Telegram WebApp mengirim `id` sebagai number (bukan string), jadi validasi
        // tidak boleh strict 'string' saja atau akan selalu gagal dengan pesan
        // "The telegram id must be a string". Terima string maupun angka lalu
        // di-cast ke string saat query.
        $validated = $request->validate([
            'telegram_id' => 'required',
            'movie_title' => 'required|string|max:255',
            'source' => 'required|string|max:100',
        ]);

        $telegramId = (string) $validated['telegram_id'];

        $user = User::where('telegram_id', $telegramId)->first();

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
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

        return response()->json([
            'message' => 'Request film berhasil dikirim, terima kasih!',
            'data' => $movieRequest,
        ], 201);
    }

    /**
     * Ambil riwayat request film milik user tertentu, terbaru lebih dulu.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'telegram_id' => 'required',
        ]);

        $telegramId = (string) $validated['telegram_id'];

        $user = User::where('telegram_id', $telegramId)->first();

        if (!$user) {
            return response()->json(['data' => []]);
        }

        $requests = MovieRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'movie_title', 'source', 'status', 'created_at']);

        return response()->json(['data' => $requests]);
    }
}
