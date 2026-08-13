<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->q . '%')
                        ->orWhere('username', 'like', '%' . $request->q . '%')
                        ->orWhere('telegram_id', 'like', '%' . $request->q . '%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'active') {
                    $query->where('is_subscribed', true)
                        ->where('expired_at', '>', now());
                } elseif ($request->status === 'expired') {
                    $query->where(function ($q) {
                        $q->where('is_subscribed', false)
                            ->orWhere('expired_at', '<=', now())
                            ->orWhereNull('expired_at');
                    });
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $recap = [
            'total' => User::count(),
            'active_vip' => User::where('is_subscribed', true)->where('expired_at', '>', now())->count(),
            'expired' => User::where(function ($q) {
                $q->where('is_subscribed', false)
                    ->orWhere('expired_at', '<=', now())
                    ->orWhereNull('expired_at');
            })->count(),
        ];

        return view('admin.users.index', compact('users', 'recap'));
    }

    /**
     * Manual Extend VIP — dipakai admin buat nambah masa aktif user
     * (mis. komplain pembayaran, hadiah promo, dsb — di luar alur otomatis Midtrans).
     * Durasi bisa diinput dalam satuan menit, jam, atau hari lewat popup di frontend.
     */
    public function extendVip(Request $request, User $user)
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1|max:100000',
            'unit' => 'required|in:minutes,hours,days',
        ]);

        $minutesMap = [
            'minutes' => 1,
            'hours' => 60,
            'days' => 60 * 24,
        ];
        $totalMinutes = $data['amount'] * $minutesMap[$data['unit']];

        $base = ($user->expired_at && $user->expired_at->isFuture())
            ? $user->expired_at
            : Carbon::now();

        $user->update([
            'is_subscribed' => true,
            'expired_at' => $base->copy()->addMinutes($totalMinutes),
        ]);

        // Jejak audit: buat lacak kalau ada laporan "sudah di-extend tapi statusnya tidak sesuai"
        // -- bandingkan user_id/telegram_id di sini dengan chat_id yang dicatat saat /status dicek.
        Log::info('Admin extend VIP', [
            'user_id' => $user->id,
            'telegram_id' => $user->telegram_id,
            'username' => $user->username,
            'amount' => $data['amount'],
            'unit' => $data['unit'],
            'expired_at' => $user->expired_at->toDateTimeString(),
        ]);

        $unitLabel = [
            'minutes' => 'menit',
            'hours' => 'jam',
            'days' => 'hari',
        ][$data['unit']];

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'VIP ' . ($user->first_name ?: $user->telegram_id) . ' berhasil diperpanjang ' . $data['amount'] . ' ' . $unitLabel . ', aktif hingga ' . $user->expired_at->format('d M Y H:i') . '.');
    }

    /**
     * Cabut status VIP user secara manual (mis. pembayaran fraud/refund).
     */
    public function revokeVip(User $user)
    {
        $user->update([
            'is_subscribed' => false,
            'expired_at' => null,
        ]);

        Log::info('Admin revoke VIP', [
            'user_id' => $user->id,
            'telegram_id' => $user->telegram_id,
            'username' => $user->username,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'VIP ' . ($user->first_name ?: $user->telegram_id) . ' berhasil dicabut.');
    }
}
