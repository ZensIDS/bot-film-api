<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\MovieRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->buildStats();
        $charts = $this->buildChartData();

        $recentTransactions = Transaction::with(['user', 'package'])
            ->latest()
            ->limit(5)
            ->get();

        $recentRequests = MovieRequest::with('user')
            ->where('status', 'PENDING')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'charts', 'recentTransactions', 'recentRequests'));
    }

    /**
     * Angka-angka ringkas untuk kartu shortcut di atas dashboard.
     */
    private function buildStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        $totalRevenue = Transaction::where('status', 'SUCCESS')->sum('amount');
        $totalWithdrawn = Withdrawal::sum('amount');

        return [
            'total_users' => User::count(),
            'active_vip' => User::where('is_subscribed', true)
                ->where('expired_at', '>', $now)
                ->count(),
            'total_movies' => Movie::count(),
            'active_movies' => Movie::where('is_active', true)->count(),
            'pending_requests' => MovieRequest::where('status', 'PENDING')->count(),
            'revenue_this_month' => Transaction::where('status', 'SUCCESS')
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount'),
            'transactions_this_month' => Transaction::where('status', 'SUCCESS')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'balance' => $totalRevenue - $totalWithdrawn,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * Data time-series & breakdown untuk grafik (Chart.js), dirangkum 14 hari terakhir
     * supaya query tetap ringan dan grafik tetap mudah dibaca.
     */
    private function buildChartData(): array
    {
        $days = collect(range(13, 0))->map(fn($i) => Carbon::now()->subDays($i)->toDateString());

        // Revenue harian (hanya transaksi SUCCESS) 14 hari terakhir.
        $revenueRaw = Transaction::where('status', 'SUCCESS')
            ->where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        // User baru per hari 14 hari terakhir.
        $usersRaw = User::where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $revenueSeries = $days->map(fn($d) => (float) ($revenueRaw[$d] ?? 0))->values();
        $usersSeries = $days->map(fn($d) => (int) ($usersRaw[$d] ?? 0))->values();
        $labels = $days->map(fn($d) => Carbon::parse($d)->translatedFormat('d M'))->values();

        // Breakdown status transaksi (all-time), buat donut chart.
        $statusBreakdown = Transaction::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => $labels,
            'revenue_series' => $revenueSeries,
            'users_series' => $usersSeries,
            'status_labels' => $statusBreakdown->keys()->values(),
            'status_series' => $statusBreakdown->values()->values(),
        ];
    }
}
