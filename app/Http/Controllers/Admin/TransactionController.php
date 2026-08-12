<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::query()
            ->with(['user', 'package'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('invoice_code', 'like', '%' . $request->q . '%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->date_to);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $successTotal = Transaction::where('status', 'SUCCESS')->sum('amount');
        $withdrawnTotal = Withdrawal::sum('amount');

        $summary = [
            'success_total' => $successTotal,
            'pending_count' => Transaction::where('status', 'PENDING')->count(),
            'withdrawn_total' => $withdrawnTotal,
            'saldo' => $successTotal - $withdrawnTotal,
        ];

        $lastWithdrawal = Withdrawal::latest()->first();

        return view('admin.transactions.index', compact('transactions', 'summary', 'lastWithdrawal'));
    }

    /**
     * Catat penarikan saldo secara manual.
     * Tidak terintegrasi dengan payment gateway / rekening — murni pencatatan
     * supaya admin tahu saldo mana yang sudah ditarik.
     */
    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        $successTotal = Transaction::where('status', 'SUCCESS')->sum('amount');
        $withdrawnTotal = Withdrawal::sum('amount');
        $saldo = $successTotal - $withdrawnTotal;

        if ($data['amount'] > $saldo) {
            return redirect()
                ->route('admin.transactions.index')
                ->with('error', 'Jumlah penarikan melebihi saldo saat ini (Rp ' . number_format($saldo, 0, ',', '.') . ').');
        }

        Withdrawal::create([
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'admin_id' => auth('admin')->id(),
        ]);

        return redirect()
            ->route('admin.transactions.index')
            ->with('success', 'Penarikan saldo sebesar Rp ' . number_format($data['amount'], 0, ',', '.') . ' berhasil dicatat.');
    }
}
