<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MemoStock;
use App\Models\Transaction;
use App\Services\QXtendService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReturnController extends Controller
{
    public function __construct(
        private QXtendService $qxtend,
        private StockService $stockService,
    ) {}

    public function scan()
    {
        return view('transactions.goods-return.scan');
    }

    public function getItemInfo(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        $item = Item::with(['departmentStocks', 'memoStock'])
            ->where('item_code', $request->barcode)
            ->where('is_active', true)
            ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'item'    => [
                'id'          => $item->id,
                'item_code'   => $item->item_code,
                'description' => $item->description,
                'unit'        => $item->unit,
                'category'    => $item->category,
                'photo'       => $item->photo ? asset('storage/'.$item->photo) : asset('assets/images/item-placeholder.png'),
                'qty_on_hand' => $item->getQtyOnHand(),
                'is_memo'     => $item->is_memo,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id'  => 'required|exists:items,id',
            'items.*.qty'      => 'required|numeric|min:0.01',
        ]);

        $transaction = DB::transaction(function () use ($request) {
            $trx = Transaction::create([
                'trans_no'    => Transaction::generateTransNo('goods_return'),
                'trans_type'  => 'goods_return',
                'user_id'     => auth()->id(),
                'department_id' => auth()->user()->department_id,
                'status'      => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes'       => $request->notes,
                'trans_date'  => today(),
            ]);

            foreach ($request->items as $itemData) {
                $item = Item::find($itemData['item_id']);
                $qty  = $itemData['qty'];

                $trx->details()->create([
                    'item_id'       => $item->id,
                    'qty_requested' => $qty,
                    'qty_approved'  => $qty,
                    'notes'         => $itemData['notes'] ?? null,
                ]);

                // Tambah kembali ke stok
                if ($item->is_memo) {
                    MemoStock::firstOrCreate(
                        ['item_id' => $item->id],
                        ['qty_on_hand' => 0]
                    )->increment('qty_on_hand', $qty);
                } else {
                    $this->stockService->creditDepartmentStock($item, $trx->department_id, (float) $qty);
                }
            }

            return $trx;
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Pengembalian barang berhasil dicatat. Stok telah ditambahkan kembali.',
            'trans_no' => $transaction->trans_no,
        ]);
    }
}
