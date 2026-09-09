<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MinimumStock;
use App\Services\StockService;
use Illuminate\Http\Request;

class MinimumStockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $items = Item::with(['departmentStocks', 'memoStock', 'minimumStock'])
            ->where('is_active', true)
            ->get()
            ->map(function ($item) {
                $item->current_qty   = $item->getQtyOnHand();
                $item->below_minimum = $item->isBelowMinimum();
                return $item;
            });

        $belowMinCount = $items->where('below_minimum', true)->count();

        return view('admin.min-stock.index', compact('items', 'belowMinCount'));
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'min_qty'   => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        MinimumStock::updateOrCreate(
            ['item_id' => $item->id, 'warehouse' => $item->warehouse],
            [
                'min_qty'   => $request->min_qty,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Stok minimum berhasil diperbarui.']);
    }

    public function generatePR()
    {
        $pr = $this->stockService->checkAndGeneratePR(auth()->id());

        if (!$pr) {
            return back()->with('success', 'Tidak ada item yang melewati batas stok minimum.');
        }

        return redirect()->route('admin.purchase-requests.show', $pr->id)
            ->with('success', "Purchase Request {$pr->pr_no} berhasil dibuat dari {$pr->details->count()} item.");
    }
}
