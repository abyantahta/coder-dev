<?php

namespace App\Http\Controllers;

use App\Models\Transaction;

class MyTransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['details.item'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('transactions.my-transactions.index', compact('transactions'));
    }

    public function show(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $transaction->load(['details.item', 'department']);
        return view('transactions.my-transactions.show', compact('transaction'));
    }
}
