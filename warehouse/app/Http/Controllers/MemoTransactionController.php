<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MemoStock;
use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemoTransactionController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $transactions = Transaction::with(['user', 'details.item'])
            ->whereIn('trans_type', ['memo_in', 'memo_out'])
            ->latest()
            ->paginate(15);

        $memoItems = Item::with('memoStock')
            ->where('is_memo', true)
            ->where('is_active', true)
            ->get();

        return view('transactions.memo.index', compact('transactions', 'memoItems'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'trans_type' => 'required|in:memo_in,memo_out',
            'notes'      => 'nullable|string|max:500',
            'items'      => 'required|array|min:1',
            'items.*.item_id'  => 'required|exists:items,id',
            'items.*.qty'      => 'required|numeric|min:0.01',
        ]);

        // Check for memo_out: ensure sufficient stock
        if ($request->trans_type === 'memo_out') {
            foreach ($request->items as $itemData) {
                $item      = Item::find($itemData['item_id']);
                $memoStock = MemoStock::where('item_id', $item->id)->first();
                $available = $memoStock?->qty_on_hand ?? 0;

                if ($available < $itemData['qty']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stok memo {$item->description} tidak mencukupi. Tersedia: {$available}",
                    ], 422);
                }
            }
        }

        $transaction = DB::transaction(function () use ($request) {
            $trx = Transaction::create([
                'trans_no'   => Transaction::generateTransNo($request->trans_type),
                'trans_type' => $request->trans_type,
                'user_id'    => auth()->id(),
                'status'     => 'completed',
                'notes'      => $request->notes,
                'trans_date' => today(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            foreach ($request->items as $itemData) {
                $trx->details()->create([
                    'item_id'       => $itemData['item_id'],
                    'qty_requested' => $itemData['qty'],
                    'qty_approved'  => $itemData['qty'],
                    'notes'         => $itemData['notes'] ?? null,
                ]);

                $type = $request->trans_type === 'memo_in' ? 'in' : 'out';
                $this->stockService->updateMemoStock($itemData['item_id'], $itemData['qty'], $type);
            }

            return $trx;
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Transaksi memo berhasil dicatat.',
            'trans_no' => $transaction->trans_no,
        ]);
    }
}
