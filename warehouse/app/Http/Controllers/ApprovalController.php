<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $pending = Transaction::with(['user', 'department', 'details.item'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $history = Transaction::with(['user', 'department', 'approver'])
            ->whereIn('status', ['approved', 'rejected', 'completed'])
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.approvals.index', compact('pending', 'history'));
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['user', 'department', 'approver', 'details.item.stock', 'details.item.memoStock', 'details.item.minimumStock']);
        return view('admin.approvals.show', compact('transaction'));
    }

    public function approve(Request $request, Transaction $transaction)
    {
        $request->validate([
            'approved_quantities' => 'required|array',
            'approved_quantities.*' => 'numeric|min:0',
        ]);

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Transaksi ini sudah diproses.');
        }

        // Update approved quantities per detail
        foreach ($request->approved_quantities as $detailId => $qty) {
            $transaction->details()->where('id', $detailId)->update(['qty_approved' => $qty]);
        }

        $transaction->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Post to QAD inventory issue
        $transaction->refresh()->load('details.item');
        $result = $this->stockService->reduceQADStock($transaction);

        // Update memo stocks
        foreach ($transaction->details as $detail) {
            if ($detail->item->is_memo && $detail->qty_approved > 0) {
                $this->stockService->updateMemoStock($detail->item_id, $detail->qty_approved, 'out');
            }
        }

        $transaction->update(['status' => 'completed']);

        $message = 'Transaksi ' . $transaction->trans_no . ' berhasil disetujui';
        if (!$result['success'] ?? false) {
            $message .= ' (catatan: sync ke QAD gagal, cek log)';
        }

        return redirect()->route('admin.approvals.index')->with('success', $message);
    }

    public function reject(Request $request, Transaction $transaction)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Transaksi ini sudah diproses.');
        }

        $transaction->update([
            'status'        => 'rejected',
            'approved_by'   => auth()->id(),
            'approved_at'   => now(),
            'reject_reason' => $request->reason,
        ]);

        return redirect()->route('admin.approvals.index')
            ->with('success', 'Transaksi ' . $transaction->trans_no . ' ditolak.');
    }
}
