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
        $validated = $request->validate([
            'telegram_id' => 'required|string',
            'movie_title' => 'required|string|max:255',
            'source' => 'required|string|max:100',
        ]);

        $user = User::where('telegram_id', $validated['telegram_id'])->first();

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
}
