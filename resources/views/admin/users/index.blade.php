@extends('admin.layout')

@section('title', 'User & Langganan')
@section('page_title', 'User & Langganan')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5">
        <p class="text-xs text-[var(--text-muted)] mb-1">Total User Terdaftar</p>
        <p class="font-display text-2xl font-semibold text-white">{{ number_format($recap['total'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5">
        <p class="text-xs text-[var(--text-muted)] mb-1">VIP Aktif</p>
        <p class="font-display text-2xl font-semibold text-[var(--gold)]">{{ number_format($recap['active_vip'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5">
        <p class="text-xs text-[var(--text-muted)] mb-1">Belum/Kadaluarsa</p>
        <p class="font-display text-2xl font-semibold text-white">{{ number_format($recap['expired'], 0, ',', '.') }}</p>
    </div>
</div>

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
                        <div class="flex items-center justify-end gap-3">
                            <button type="button"
                                onclick="openExtendModal('{{ route('admin.users.extend-vip', $user) }}', {{ Illuminate\Support\Js::from($user->first_name ?: $user->telegram_id) }})"
                                class="text-xs font-semibold text-[var(--gold-soft)] hover:text-[var(--gold)] whitespace-nowrap">
                                + Extend
                            </button>
                            @if ($isActive)
                                <button type="button"
                                    onclick="openRevokeModal('{{ route('admin.users.revoke-vip', $user) }}', {{ Illuminate\Support\Js::from($user->first_name ?: $user->telegram_id) }})"
                                    class="text-xs font-semibold text-[var(--crimson)] hover:text-[#F27C97] whitespace-nowrap">
                                    Cabut
                                </button>
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

<!-- ===================== Modal: Extend VIP ===================== -->
<div id="extendModalBackdrop" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl w-full max-w-sm p-6 shadow-2xl">
        <h3 class="font-display text-lg font-semibold text-white mb-1">Perpanjang VIP</h3>
        <p id="extendModalUserName" class="text-sm text-[var(--text-muted)] mb-5">—</p>

        <form id="extendForm" method="POST" action="">
            @csrf
            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-2">Durasi Perpanjangan</label>
            <div class="flex items-center gap-2 mb-1">
                <input
                    type="number"
                    name="amount"
                    id="extendAmount"
                    min="1"
                    max="100000"
                    value="30"
                    required
                    class="w-24 bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white text-center focus:outline-none focus:border-[var(--gold)]"
                >
                <select
                    name="unit"
                    id="extendUnit"
                    required
                    class="flex-1 bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]"
                >
                    <option value="minutes">Menit</option>
                    <option value="hours">Jam</option>
                    <option value="days" selected>Hari</option>
                </select>
            </div>
            <p class="text-[11px] text-[var(--text-muted)] mb-6">
                Contoh: isi <span class="text-[var(--gold-soft)]">30</span> lalu pilih <span class="text-[var(--gold-soft)]">Hari</span> untuk menambah 30 hari masa aktif. Bisa juga dalam satuan Menit atau Jam untuk testing/kasus khusus.
            </p>

            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeExtendModal()"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] hover:text-white">
                    Batal
                </button>
                <button type="submit"
                    class="text-xs font-semibold px-5 py-2 rounded-xl btn-gold">
                    Simpan Extend
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== Modal: Confirm Cabut VIP ===================== -->
<div id="revokeModalBackdrop" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl w-full max-w-sm p-6 shadow-2xl">
        <div class="w-11 h-11 rounded-full bg-red-900/30 flex items-center justify-center text-xl mb-4">
            ⚠️
        </div>
        <h3 class="font-display text-lg font-semibold text-white mb-1">Cabut Status VIP?</h3>
        <p class="text-sm text-[var(--text-muted)] mb-6">
            Anda akan mencabut status VIP dari <span id="revokeModalUserName" class="text-white font-semibold">—</span>.
            Aksi ini langsung aktif dan akses premium user akan dihentikan saat itu juga.
        </p>

        <form id="revokeForm" method="POST" action="">
            @csrf
            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeRevokeModal()"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] hover:text-white">
                    Batal
                </button>
                <button type="submit"
                    class="text-xs font-semibold px-5 py-2 rounded-xl bg-[var(--crimson)] text-white hover:brightness-110">
                    Ya, Cabut VIP
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('extra_js')
<script>
    function openExtendModal(actionUrl, userName) {
        document.getElementById('extendForm').action = actionUrl;
        document.getElementById('extendModalUserName').textContent = userName;
        document.getElementById('extendAmount').value = 30;
        document.getElementById('extendUnit').value = 'days';
        const backdrop = document.getElementById('extendModalBackdrop');
        backdrop.classList.remove('hidden');
        backdrop.classList.add('flex');
    }
    function closeExtendModal() {
        const backdrop = document.getElementById('extendModalBackdrop');
        backdrop.classList.add('hidden');
        backdrop.classList.remove('flex');
    }

    function openRevokeModal(actionUrl, userName) {
        document.getElementById('revokeForm').action = actionUrl;
        document.getElementById('revokeModalUserName').textContent = userName;
        const backdrop = document.getElementById('revokeModalBackdrop');
        backdrop.classList.remove('hidden');
        backdrop.classList.add('flex');
    }
    function closeRevokeModal() {
        const backdrop = document.getElementById('revokeModalBackdrop');
        backdrop.classList.add('hidden');
        backdrop.classList.remove('flex');
    }

    // Tutup modal saat klik area luar (backdrop)
    document.getElementById('extendModalBackdrop').addEventListener('click', function (e) {
        if (e.target === this) closeExtendModal();
    });
    document.getElementById('revokeModalBackdrop').addEventListener('click', function (e) {
        if (e.target === this) closeRevokeModal();
    });

    // Tutup modal dengan tombol Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeExtendModal();
            closeRevokeModal();
        }
    });
</script>
@endsection
