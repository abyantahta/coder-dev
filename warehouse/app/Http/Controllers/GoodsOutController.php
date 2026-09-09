<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Transaction;
use App\Services\QXtendService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsOutController extends Controller
{
    public function __construct(
        private QXtendService $qxtend,
        private StockService  $stockService
    ) {}

    public function index()
    {
        $query = Transaction::with(['user', 'department', 'details.item'])
            ->where('trans_type', 'goods_out');

        if (auth()->user()->isUser()) {
            $query->where('user_id', auth()->id());
        }

        $transactions = $query->latest()->paginate(15);
        return view('transactions.goods-out.index', compact('transactions'));
    }

    public function scan()
    {
        return view('transactions.goods-out.scan');
    }

    public function getItemInfo(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        $item = Item::with(['departmentStocks', 'memoStock', 'minimumStock'])
            ->where('item_code', $request->barcode)
            ->where('is_active', true)
            ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan.'], 404);
        }

        $qtyOnHand = $item->getQtyOnHand();
        $isOutOfStock = $qtyOnHand <= 0;
        $isBelowMin   = $item->isBelowMinimum();

        // Refresh dari QAD hanya jika sudah dikonfigurasi
        if (!$item->is_memo && $this->qxtend->isConfigured()) {
            $this->stockService->refreshItemStock($item);
            $item->refresh();
            $qtyOnHand = $item->getQtyOnHand();
            $isOutOfStock = $qtyOnHand <= 0;
        }

        return response()->json([
            'success'      => true,
            'item'         => [
                'id'          => $item->id,
                'item_code'   => $item->item_code,
                'description' => $item->description,
                'unit'        => $item->unit,
                'category'    => $item->category,
                'photo'       => $item->photo ? asset('storage/'.$item->photo) : asset('assets/images/item-placeholder.png'),
                'qty_on_hand' => $qtyOnHand,
                'min_qty'     => $item->minimumStock?->min_qty ?? 0,
                'is_memo'     => $item->is_memo,
                'is_out_of_stock' => $isOutOfStock,
                'is_below_min'    => $isBelowMin,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'notes'         => 'nullable|string|max:500',
            'department_id' => 'nullable|exists:departments,id',
            'items'         => 'required|array|min:1',
            'items.*.item_id'       => 'required|exists:items,id',
            'items.*.qty_requested' => 'required|numeric|min:0.01',
        ]);

        try {
            $transaction = DB::transaction(function () use ($request) {
                $trx = Transaction::create([
                    'trans_no'      => Transaction::generateTransNo('goods_out'),
                    'trans_type'    => 'goods_out',
                    'user_id'       => auth()->id(),
                    'department_id' => $request->department_id ?? auth()->user()->department_id,
                    'status'        => 'completed',
                    'approved_by'   => auth()->id(),
                    'approved_at'   => now(),
                    'notes'         => $request->notes,
                    'trans_date'    => today(),
                ]);

                foreach ($request->items as $itemData) {
                    $trx->details()->create([
                        'item_id'       => $itemData['item_id'],
                        'qty_requested' => $itemData['qty_requested'],
                        'qty_approved'  => $itemData['qty_requested'],
                        'notes'         => $itemData['notes'] ?? null,
                    ]);
                }

                // Langsung kurangi stok departemen dan memo
                $trx->load('details.item');
                $this->stockService->reduceQADStock($trx);

                foreach ($trx->details as $detail) {
                    if ($detail->item->is_memo) {
                        $this->stockService->updateMemoStock($detail->item_id, $detail->qty_approved, 'out');
                    }
                }

                return $trx;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Pengambilan barang berhasil dicatat.',
            'trans_no' => $transaction->trans_no,
            'redirect' => route('transactions.goods-out.show', $transaction->id),
        ]);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['user', 'department', 'approver', 'details.item.departmentStocks', 'details.item.memoStock']);
        return view('transactions.goods-out.show', compact('transaction'));
    }
}
