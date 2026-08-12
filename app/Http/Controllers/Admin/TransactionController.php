<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
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
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'success_total' => Transaction::where('status', 'SUCCESS')->sum('amount'),
            'pending_count' => Transaction::where('status', 'PENDING')->count(),
        ];

        return view('admin.transactions.index', compact('transactions', 'summary'));
    }
}
