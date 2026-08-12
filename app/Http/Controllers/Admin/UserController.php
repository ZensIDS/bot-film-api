<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Manual Extend VIP — dipakai admin buat nambah masa aktif user
     * (mis. komplain pembayaran, hadiah promo, dsb — di luar alur otomatis Midtrans).
     */
    public function extendVip(Request $request, User $user)
    {
        $data = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $base = ($user->expired_at && $user->expired_at->isFuture())
            ? $user->expired_at
            : Carbon::now();

        $user->update([
            'is_subscribed' => true,
            'expired_at' => $base->copy()->addDays($data['days']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'VIP ' . ($user->first_name ?: $user->telegram_id) . ' berhasil diperpanjang ' . $data['days'] . ' hari, aktif hingga ' . $user->expired_at->format('d M Y H:i') . '.');
    }

    /**
     * Cabut status VIP user secara manual (mis. pembayaran fraud/refund).
     */
    public function revokeVip(User $user)
    {
        $user->update([
            'is_subscribed' => false,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'VIP ' . ($user->first_name ?: $user->telegram_id) . ' berhasil dicabut.');
    }
}
