@extends('admin.layout')

@section('title', 'Riwayat Transaksi')
@section('page_title', 'Riwayat Transaksi')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
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

    <!-- Card Saldo Saat Ini + Withdraw -->
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl p-5 flex flex-col justify-between">
        <div>
            <p class="text-xs text-[var(--text-muted)] mb-1">Saldo Saat Ini</p>
            <p class="font-display text-2xl font-semibold text-white">
                Rp {{ number_format($summary['saldo'], 0, ',', '.') }}
            </p>
            <p class="text-[11px] text-[var(--text-muted)] mt-1">
                Sudah ditarik: Rp {{ number_format($summary['withdrawn_total'], 0, ',', '.') }}
                @if ($lastWithdrawal)
                    &middot; terakhir {{ $lastWithdrawal->created_at->format('d M Y H:i') }}
                @endif
            </p>
        </div>
        <button type="button" onclick="openWithdrawModal()"
            class="mt-4 w-full text-xs font-semibold px-4 py-2.5 rounded-xl btn-gold {{ $summary['saldo'] <= 0 ? 'opacity-40 pointer-events-none' : '' }}">
            💸 Tarik Saldo (Withdraw)
        </button>
    </div>
</div>

<div class="flex items-end justify-between gap-4 mb-5 flex-wrap">
    <form method="GET" class="flex items-end gap-2 flex-wrap">
        <div class="flex flex-col gap-1">
            <label class="text-[10px] uppercase tracking-wide text-[var(--text-muted)]">Cari</label>
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari invoice code..."
                class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-4 py-2 text-sm text-white placeholder:text-[var(--text-muted)] focus:outline-none focus:border-[var(--gold)] w-56"
            >
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-[10px] uppercase tracking-wide text-[var(--text-muted)]">Status</label>
            <select name="status" onchange="this.form.submit()"
                class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]">
                <option value="">Semua Status</option>
                <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                <option value="SUCCESS" {{ request('status') === 'SUCCESS' ? 'selected' : '' }}>Success</option>
                <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                <option value="EXPIRED" {{ request('status') === 'EXPIRED' ? 'selected' : '' }}>Expired</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-[10px] uppercase tracking-wide text-[var(--text-muted)]">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-[10px] uppercase tracking-wide text-[var(--text-muted)]">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="bg-[var(--surface)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[var(--gold)]">
        </div>
        <button type="submit" class="text-xs font-semibold px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] hover:text-white">
            Terapkan
        </button>
        @if (request()->anyFilled(['q', 'status', 'date_from', 'date_to']))
            <a href="{{ route('admin.transactions.index') }}" class="text-xs font-semibold px-4 py-2 rounded-xl text-[var(--text-muted)] hover:text-white">
                Reset
            </a>
        @endif
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

<!-- ===================== Modal: Withdraw Saldo ===================== -->
<div id="withdrawModalBackdrop" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="bg-[var(--surface)] border border-[var(--hairline)] rounded-2xl w-full max-w-sm p-6 shadow-2xl">
        <h3 class="font-display text-lg font-semibold text-white mb-1">Tarik Saldo</h3>
        <p class="text-sm text-[var(--text-muted)] mb-1">
            Saldo tersedia: <span class="text-[var(--gold-soft)] font-semibold">Rp {{ number_format($summary['saldo'], 0, ',', '.') }}</span>
        </p>
        <p class="text-[11px] text-[var(--text-muted)] mb-5">
            Catatan ini hanya untuk penanda internal bahwa saldo sudah kamu tarik/pindahkan secara manual. Belum terhubung ke rekening/payment gateway manapun.
        </p>

        <form method="POST" action="{{ route('admin.transactions.withdraw') }}">
            @csrf
            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-2">Nominal Ditarik</label>
            <input
                type="number"
                name="amount"
                id="withdrawAmount"
                min="1"
                max="{{ (int) $summary['saldo'] }}"
                value="{{ (int) $summary['saldo'] }}"
                required
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white mb-4 focus:outline-none focus:border-[var(--gold)]"
            >

            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-2">Catatan (opsional)</label>
            <input
                type="text"
                name="note"
                maxlength="255"
                placeholder="mis. Transfer ke rekening pribadi"
                class="w-full bg-[var(--surface-2)] border border-[var(--hairline)] rounded-xl px-3 py-2 text-sm text-white mb-6 focus:outline-none focus:border-[var(--gold)]"
            >

            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeWithdrawModal()"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-[var(--surface-2)] text-[var(--text)] hover:text-white">
                    Batal
                </button>
                <button type="submit"
                    class="text-xs font-semibold px-5 py-2 rounded-xl btn-gold">
                    Konfirmasi Withdraw
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('extra_js')
<script>
    function openWithdrawModal() {
        const backdrop = document.getElementById('withdrawModalBackdrop');
        backdrop.classList.remove('hidden');
        backdrop.classList.add('flex');
    }
    function closeWithdrawModal() {
        const backdrop = document.getElementById('withdrawModalBackdrop');
        backdrop.classList.add('hidden');
        backdrop.classList.remove('flex');
    }
    document.getElementById('withdrawModalBackdrop').addEventListener('click', function (e) {
        if (e.target === this) closeWithdrawModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeWithdrawModal();
    });
</script>
@endsection
