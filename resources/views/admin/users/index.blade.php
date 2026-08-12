@extends('admin.layout')

@section('title', 'User & Langganan')
@section('page_title', 'User & Langganan')

@section('content')

<div class="flex items-center justify-between gap-4 mb-5 flex-wrap">
    <form method="GET" class="flex items-center gap-2 flex-wrap">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari nama, username, atau telegram ID..."
            class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-4 py-2 text-sm text-white placeholder:text-[var(--text-muted)] focus:outline-none focus:border-[var(--gold)] w-72"
        >
        <select name="status" onchange="this.form.submit()"
            class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>VIP Aktif</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Belum/Kadaluarsa</option>
        </select>
        <button type="submit" class="text-xs font-semibold px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] hover:text-white">
            Cari
        </button>
    </form>
</div>

<div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-[var(--text-muted)] text-xs uppercase tracking-wide border-b border-[var(--hairline)]">
                <th class="px-5 py-3">User</th>
                <th class="px-5 py-3">Telegram ID</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3">Kadaluarsa</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                @php
                    $isActive = $user->is_subscribed && $user->expired_at && $user->expired_at->isFuture();
                @endphp
                <tr class="border-b border-[var(--hairline)] last:border-0 hover:bg-[var(--surface-2)]/40">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-[var(--surface-2)] flex items-center justify-center text-xs font-bold text-[var(--gold-soft)] shrink-0">
                                {{ strtoupper(substr($user->first_name ?: 'U', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-white truncate">{{ $user->first_name ?: '(tanpa nama)' }}</p>
                                <p class="text-[11px] text-[var(--text-muted)] truncate">{{ $user->username ? '@' . $user->username : '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-[var(--text-muted)] font-mono text-xs">{{ $user->telegram_id }}</td>
                    <td class="px-5 py-3">
                        @if ($isActive)
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-green-900/30 text-green-300">VIP Aktif</span>
                        @else
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[var(--surface-2)] text-[var(--text-muted)]">Belum VIP</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">
                        {{ $user->expired_at ? $user->expired_at->format('d M Y H:i') : '—' }}
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <form action="{{ route('admin.users.extend-vip', $user) }}" method="POST" class="flex items-center gap-1.5">
                                @csrf
                                <input type="number" name="days" min="1" max="365" value="30" required
                                    class="w-16 bg-[var(--surface-2)] border border-[var(--hairline)] rounded-lg px-2 py-1.5 text-xs text-white text-center">
                                <button type="submit" class="text-xs font-semibold text-[var(--gold-soft)] hover:text-[var(--gold)] whitespace-nowrap">
                                    + Extend
                                </button>
                            </form>
                            @if ($isActive)
                                <form action="{{ route('admin.users.revoke-vip', $user) }}" method="POST" onsubmit="return confirm('Cabut status VIP user ini?');">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-[var(--crimson)] hover:text-[#F27C97] whitespace-nowrap">
                                        Cabut
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center text-[var(--text-muted)] text-sm">
                        Belum ada user yang terdaftar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">
    {{ $users->links() }}
</div>

@endsection
