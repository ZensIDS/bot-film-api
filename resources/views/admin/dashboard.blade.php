@extends('admin.layout')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('extra_css')
<style>
    .stat-card{ background: var(--surface); border: 1px solid var(--hairline); border-radius: 16px; padding: 18px; }
    .stat-icon{ width: 38px; height: 38px; border-radius: 10px; display:flex; align-items:center; justify-content:center; font-size: 17px; }
    .shortcut-card{ background: var(--surface); border: 1px solid var(--hairline); border-radius: 16px; padding: 16px; display:flex; align-items:center; gap: 12px; transition: all .15s; }
    .shortcut-card:hover{ background: var(--surface-2); border-color: var(--gold); }
    .chart-card{ background: var(--surface); border: 1px solid var(--hairline); border-radius: 16px; padding: 20px; }
    .activity-row{ display:flex; align-items:center; justify-content:space-between; gap:10px; padding: 10px 0; border-bottom: 1px solid var(--hairline); }
    .activity-row:last-child{ border-bottom: none; }
    .pill{ font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 999px; text-transform: uppercase; letter-spacing: .02em; }
</style>
@endsection

@section('content')

<!-- ============ Shortcut Statistik ============ -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon bg-[rgba(232,177,86,0.15)] mb-3">👤</div>
        <p class="text-xs text-[var(--text-muted)] mb-1">Total User</p>
        <p class="font-display text-2xl font-semibold text-white">{{ number_format($stats['total_users']) }}</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-[rgba(34,197,94,0.15)] mb-3">💎</div>
        <p class="text-xs text-[var(--text-muted)] mb-1">VIP Aktif</p>
        <p class="font-display text-2xl font-semibold text-green-300">{{ number_format($stats['active_vip']) }}</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-[rgba(232,177,86,0.15)] mb-3">💰</div>
        <p class="text-xs text-[var(--text-muted)] mb-1">Revenue Bulan Ini</p>
        <p class="font-display text-xl font-semibold text-[var(--gold-soft)]">Rp{{ number_format($stats['revenue_this_month'], 0, ',', '.') }}</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-[rgba(96,165,250,0.15)] mb-3">🏦</div>
        <p class="text-xs text-[var(--text-muted)] mb-1">Saldo (Revenue - Withdraw)</p>
        <p class="font-display text-xl font-semibold text-blue-300">Rp{{ number_format($stats['balance'], 0, ',', '.') }}</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-[rgba(232,177,86,0.15)] mb-3">🎬</div>
        <p class="text-xs text-[var(--text-muted)] mb-1">Total Film</p>
        <p class="font-display text-2xl font-semibold text-white">{{ number_format($stats['total_movies']) }}</p>
        <p class="text-[10px] text-[var(--text-muted)] mt-0.5">{{ $stats['active_movies'] }} aktif</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-[rgba(194,53,90,0.15)] mb-3">📥</div>
        <p class="text-xs text-[var(--text-muted)] mb-1">Request Pending</p>
        <p class="font-display text-2xl font-semibold text-[#F27C97]">{{ number_format($stats['pending_requests']) }}</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-[rgba(34,197,94,0.15)] mb-3">🧾</div>
        <p class="text-xs text-[var(--text-muted)] mb-1">Transaksi Sukses (Bulan Ini)</p>
        <p class="font-display text-2xl font-semibold text-white">{{ number_format($stats['transactions_this_month']) }}</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-[rgba(232,177,86,0.15)] mb-3">📊</div>
        <p class="text-xs text-[var(--text-muted)] mb-1">Total Revenue (all-time)</p>
        <p class="font-display text-xl font-semibold text-[var(--gold-soft)]">Rp{{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
    </div>
</div>

<!-- ============ Shortcut Menu Cepat ============ -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('admin.movies.create') }}" class="shortcut-card">
        <div class="stat-icon bg-[rgba(232,177,86,0.15)]">➕</div>
        <div>
            <p class="text-sm font-semibold text-white">Tambah Film</p>
            <p class="text-[11px] text-[var(--text-muted)]">Upload film/episode baru</p>
        </div>
    </a>
    <a href="{{ route('admin.movie-requests.index') }}" class="shortcut-card">
        <div class="stat-icon bg-[rgba(194,53,90,0.15)]">📥</div>
        <div>
            <p class="text-sm font-semibold text-white">Review Request Film</p>
            <p class="text-[11px] text-[var(--text-muted)]">{{ $stats['pending_requests'] }} menunggu direview</p>
        </div>
    </a>
    <a href="{{ route('admin.users.index') }}" class="shortcut-card">
        <div class="stat-icon bg-[rgba(96,165,250,0.15)]">👤</div>
        <div>
            <p class="text-sm font-semibold text-white">Kelola User</p>
            <p class="text-[11px] text-[var(--text-muted)]">Extend / revoke VIP</p>
        </div>
    </a>
</div>

<!-- ============ Grafik ============ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="chart-card lg:col-span-2">
        <p class="text-sm font-semibold text-white mb-4">Revenue 14 Hari Terakhir</p>
        <canvas id="revenueChart" height="110"></canvas>
    </div>
    <div class="chart-card">
        <p class="text-sm font-semibold text-white mb-4">Status Transaksi</p>
        <canvas id="statusChart" height="110"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="chart-card lg:col-span-2">
        <p class="text-sm font-semibold text-white mb-4">User Baru 14 Hari Terakhir</p>
        <canvas id="usersChart" height="110"></canvas>
    </div>

    <!-- ============ Aktivitas Terbaru ============ -->
    <div class="chart-card">
        <p class="text-sm font-semibold text-white mb-3">Request Film Terbaru</p>
        @forelse ($recentRequests as $req)
            <div class="activity-row">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ $req->movie_title }}</p>
                    <p class="text-[10px] text-[var(--text-muted)]">{{ $req->user->first_name ?? 'User' }} · {{ $req->created_at->diffForHumans() }}</p>
                </div>
                <span class="pill bg-yellow-900/30 text-yellow-300">Pending</span>
            </div>
        @empty
            <p class="text-xs text-[var(--text-muted)]">Belum ada request pending 🎉</p>
        @endforelse
    </div>
</div>

<!-- ============ Transaksi Terbaru ============ -->
<div class="chart-card">
    <p class="text-sm font-semibold text-white mb-3">Transaksi Terbaru</p>
    <div class="overflow-x-auto -mx-1">
        <table class="w-full text-xs min-w-[560px]">
            <thead>
                <tr class="text-left text-[var(--text-muted)] border-b border-[var(--hairline)]">
                    <th class="py-2 px-1 font-medium">Invoice</th>
                    <th class="py-2 px-1 font-medium">User</th>
                    <th class="py-2 px-1 font-medium">Paket</th>
                    <th class="py-2 px-1 font-medium">Jumlah</th>
                    <th class="py-2 px-1 font-medium">Status</th>
                    <th class="py-2 px-1 font-medium">Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentTransactions as $trx)
                    @php
                        $statusPill = match($trx->status) {
                            'SUCCESS' => 'bg-green-900/30 text-green-300',
                            'PENDING' => 'bg-yellow-900/30 text-yellow-300',
                            'EXPIRED' => 'bg-gray-700/40 text-gray-300',
                            default => 'bg-red-900/30 text-[#F27C97]',
                        };
                    @endphp
                    <tr class="border-b border-[var(--hairline)] last:border-none">
                        <td class="py-2.5 px-1 font-mono text-[11px]">{{ $trx->invoice_code }}</td>
                        <td class="py-2.5 px-1">{{ $trx->user->first_name ?? '-' }}</td>
                        <td class="py-2.5 px-1">{{ $trx->package->name ?? '-' }}</td>
                        <td class="py-2.5 px-1">Rp{{ number_format($trx->amount, 0, ',', '.') }}</td>
                        <td class="py-2.5 px-1"><span class="pill {{ $statusPill }}">{{ $trx->status }}</span></td>
                        <td class="py-2.5 px-1 text-[var(--text-muted)]">{{ $trx->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-center text-[var(--text-muted)]">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('extra_js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const chartLabels = @json($charts['labels']);
    const revenueSeries = @json($charts['revenue_series']);
    const usersSeries = @json($charts['users_series']);
    const statusLabels = @json($charts['status_labels']);
    const statusSeries = @json($charts['status_series']);

    const goldColor = '#E8B156';
    const gridColor = 'rgba(232,177,86,0.08)';
    const textMuted = '#9C93AF';

    Chart.defaults.color = textMuted;
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

    // Revenue harian (line chart)
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Revenue',
                data: revenueSeries,
                borderColor: goldColor,
                backgroundColor: 'rgba(232,177,86,0.12)',
                tension: 0.35,
                fill: true,
                pointRadius: 2,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: gridColor }, ticks: { callback: (v) => 'Rp' + v.toLocaleString('id-ID') } },
            }
        }
    });

    // User baru harian (bar chart)
    new Chart(document.getElementById('usersChart'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'User Baru',
                data: usersSeries,
                backgroundColor: 'rgba(96,165,250,0.55)',
                borderRadius: 6,
                maxBarThickness: 24,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: gridColor }, ticks: { precision: 0 } },
            }
        }
    });

    // Breakdown status transaksi (donut chart)
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusSeries,
                backgroundColor: ['#22C55E', '#EAB308', '#C2355A', '#6B7280'],
                borderColor: '#16131F',
                borderWidth: 2,
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
        }
    });
</script>
@endsection
