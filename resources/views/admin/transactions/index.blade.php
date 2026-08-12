@extends('admin.layout')

@section('title', 'Riwayat Transaksi')
@section('page_title', 'Riwayat Transaksi')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5">
        <p class="text-xs text-[var(--text-muted)] mb-1">Total Pendapatan (SUCCESS)</p>
        <p class="font-display text-2xl font-semibold text-[var(--gold)]">
            Rp {{ number_format($summary['success_total'], 0, ',', '.') }}
        </p>
    </div>
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5">
        <p class="text-xs text-[var(--text-muted)] mb-1">Transaksi Menunggu Pembayaran</p>
        <p class="font-display text-2xl font-semibold text-white">{{ $summary['pending_count'] }}</p>
    </div>
</div>

<div class="flex items-center justify-between gap-4 mb-5 flex-wrap">
    <form method="GET" class="flex items-center gap-2 flex-wrap">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari invoice code..."
            class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-4 py-2 text-sm text-white placeholder:text-[var(--text-muted)] focus:outline-none focus:border-[var(--gold)] w-64"
        >
        <select name="status" onchange="this.form.submit()"
            class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]">
            <option value="">Semua Status</option>
            <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
            <option value="SUCCESS" {{ request('status') === 'SUCCESS' ? 'selected' : '' }}>Success</option>
            <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>Failed</option>
            <option value="EXPIRED" {{ request('status') === 'EXPIRED' ? 'selected' : '' }}>Expired</option>
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
                <th class="px-5 py-3">Invoice</th>
                <th class="px-5 py-3">User</th>
                <th class="px-5 py-3">Paket</th>
                <th class="px-5 py-3">Nominal</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $trx)
                <tr class="border-b border-[var(--hairline)] last:border-0 hover:bg-[var(--surface-2)]/40">
                    <td class="px-5 py-3 font-mono text-xs text-[var(--text)]">{{ $trx->invoice_code }}</td>
                    <td class="px-5 py-3">
                        <p class="font-semibold text-white truncate">{{ $trx->user->first_name ?? '(user dihapus)' }}</p>
                        <p class="text-[11px] text-[var(--text-muted)]">{{ $trx->user->telegram_id ?? '—' }}</p>
                    </td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">{{ $trx->package->name ?? '(paket dihapus)' }}</td>
                    <td class="px-5 py-3 text-[var(--text)]">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-3">
                        @php
                            $badge = match($trx->status) {
                                'SUCCESS' => 'bg-green-900/30 text-green-300',
                                'PENDING' => 'bg-yellow-900/30 text-yellow-300',
                                default => 'bg-red-900/30 text-[#F27C97]',
                            };
                        @endphp
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $badge }}">{{ $trx->status }}</span>
                    </td>
                    <td class="px-5 py-3 text-[var(--text-muted)]">{{ $trx->created_at->format('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-[var(--text-muted)] text-sm">
                        Belum ada transaksi.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">
    {{ $transactions->links() }}
</div>

@endsection
