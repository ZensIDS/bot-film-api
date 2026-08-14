<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MovieRequest;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;

class MovieRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = MovieRequest::query()
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('movie_title', 'like', '%' . $request->q . '%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $recap = [
            'pending' => MovieRequest::where('status', 'PENDING')->count(),
            'approved' => MovieRequest::where('status', 'APPROVED')->count(),
            'rejected' => MovieRequest::where('status', 'REJECTED')->count(),
            'tayang' => MovieRequest::where('status', 'TAYANG')->count(),
        ];

        return view('admin.movie-requests.index', compact('requests', 'recap'));
    }

    /**
     * Ganti status request film. Setelah status berubah, user pengaju otomatis
     * dikirim notifikasi lewat Telegram (chat_id = users.telegram_id).
     */
    public function updateStatus(Request $request, MovieRequest $movieRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:PENDING,APPROVED,REJECTED,TAYANG',
        ]);

        $previousStatus = $movieRequest->status;

        $movieRequest->update([
            'status' => $data['status'],
        ]);

        if ($previousStatus !== $data['status']) {
            $this->notifyUser($movieRequest);
        }

        return redirect()
            ->route('admin.movie-requests.index')
            ->with('success', 'Status request "' . $movieRequest->movie_title . '" berhasil diubah menjadi ' . $data['status'] . '.');
    }

    private function notifyUser(MovieRequest $movieRequest): void
    {
        $movieRequest->loadMissing('user');
        $user = $movieRequest->user;

        if (!$user || !$user->telegram_id) {
            return;
        }

        $text = match ($movieRequest->status) {
            'APPROVED' => "🎉 Kabar baik, {$user->first_name}!\n\n"
                . "Request film *{$movieRequest->movie_title}* kamu telah *disetujui* dan akan segera kami tambahkan ke katalog. Terima kasih sudah request!",
            'REJECTED' => "😔 Halo {$user->first_name},\n\n"
                . "Mohon maaf, request film *{$movieRequest->movie_title}* kamu *belum bisa kami proses* saat ini. Kamu tetap bisa mengajukan request judul lain kapan saja.",
            'TAYANG' => "🍿 Yeay, {$user->first_name}!\n\n"
                . "Film *{$movieRequest->movie_title}* yang kamu request sekarang sudah *tayang* dan bisa langsung kamu tonton di aplikasi. Selamat menonton!",
            default => "ℹ️ Halo {$user->first_name},\n\n"
                . "Status request film *{$movieRequest->movie_title}* kamu diperbarui menjadi *{$movieRequest->status}*.",
        };

        TelegramNotifier::send($user->telegram_id, $text);
    }
}
