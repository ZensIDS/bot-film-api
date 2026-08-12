    @extends('admin.layout')

@section('title', 'Request Film')
@section('page_title', 'Pengelolaan Request Film')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5">
        <p class="text-xs text-[var(--text-muted)] mb-1">Menunggu Diproses</p>
        <p class="font-display text-2xl font-semibold text-yellow-300">{{ $recap['pending'] }}</p>
    </div>
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5">
        <p class="text-xs text-[var(--text-muted)] mb-1">Disetujui</p>
        <p class="font-display text-2xl font-semibold text-green-300">{{ $recap['approved'] }}</p>
    </div>
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5">
        <p class="text-xs text-[var(--text-muted)] mb-1">Ditolak</p>
        <p class="font-display text-2xl font-semibold text-[#F27C97]">{{ $recap['rejected'] }}</p>
    </div>
</div>

<div class="flex items-center justify-between gap-4 mb-5 flex-wrap">
    <form method="GET" class="flex items-center gap-2 flex-wrap">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari judul film..."
            class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-4 py-2 text-sm text-white placeholder:text-[var(--text-muted)] focus:outline-none focus:border-[var(--gold)] w-64"
        >
        <select name="status" onchange="this.form.submit()"
            class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]">
            <option value="">Semua Status</option>
            <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
            <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
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
                <th class="px-5 py-3">Judul Diminta</th>
                <th class="px-5 py-3">User</th>
                <th class="px-5 py-3">Sumber</th>
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 text-right">Ubah Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($requests as $req)
                <tr class="border-b border-[var(--hairline)] last:border-0 hover:bg-[var(--surface-2)]/40">
                    <td class="px-5 py-3">
                        <p class="font-semibold text-white">{{ $req->movie_title }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <p class="text-[var(--text)] truncate">{{ $req->user->first_name ?? '(user dihapus)' }}</p>
                        <p class="text-[11px] text-[var(--text-muted)] font-mono">{{ $req->user->telegram_id ?? '—' }}</p>
                    </td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">{{ $req->source ?: '—' }}</td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">{{ $req->created_at->format('d M Y H:i') }}</td>
                    <td class="px-5 py-3">
                        @php
                            $badge = match($req->status) {
                                'APPROVED' => 'bg-green-900/30 text-green-300',
                                'PENDING' => 'bg-yellow-900/30 text-yellow-300',
                                default => 'bg-red-900/30 text-[#F27C97]',
                            };
                        @endphp
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $badge }}">{{ $req->status }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <form action="{{ route('admin.movie-requests.update-status', $req) }}" method="POST"
                            onsubmit="return confirm('Ubah status request ini menjadi ' + this.status.value + '? User akan otomatis mendapat notifikasi di Telegram.');"
                            class="flex items-center justify-end gap-2">
                            @csrf
                            <select name="status"
                                class="bg-[var(--surface-2)] border border-[var(--hairline)] rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-[var(--gold)]">
                                <option value="PENDING" {{ $req->status === 'PENDING' ? 'selected' : '' }}>Pending</option>
                                <option value="APPROVED" {{ $req->status === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                                <option value="REJECTED" {{ $req->status === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            <button type="submit" class="text-xs font-semibold text-[var(--gold-soft)] hover:text-[var(--gold)] whitespace-nowrap">
                                Simpan
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-[var(--text-muted)] text-sm">
                        Belum ada request film dari user.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">
    {{ $requests->links() }}
</div>

@endsection
