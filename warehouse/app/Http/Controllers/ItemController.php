<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemStock;
use App\Services\QXtendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function __construct(private QXtendService $qxtend) {}

    public function index()
    {
        $items = Item::with(['departmentStocks', 'memoStock', 'minimumStock'])->latest()->paginate(20);
        return view('master.items.index', compact('items'));
    }

    public function edit(Item $item)
    {
        return view('master.items.edit', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'description' => 'required|string|max:200',
            'unit'        => 'nullable|string|max:20',
            'category'    => 'nullable|string|max:100',
            'price'       => 'nullable|numeric|min:0',
            'currency'    => 'nullable|in:IDR,USD',
            'is_memo'     => 'boolean',
            'is_active'   => 'boolean',
            'photo'       => 'nullable|image|max:2048',
        ]);

        $data = $request->only('description', 'unit', 'category', 'price', 'currency') + [
            'is_memo'   => $request->boolean('is_memo'),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('photo')) {
            if ($item->photo) Storage::disk('public')->delete($item->photo);
            $data['photo'] = $request->file('photo')->store('photos/items', 'public');
        }

        $item->update($data);

        return redirect()->route('master.items.index')->with('success', 'Item berhasil diperbarui.');
    }

    public function syncFromQAD()
    {
        $result = $this->qxtend->syncItems();
        $message = "Sync selesai: {$result['synced']} item berhasil";
        if (!empty($result['errors'])) {
            $message .= ", " . count($result['errors']) . " gagal";
        }
        return back()->with('success', $message);
    }

    public function getItemByBarcode(Request $request)
    {
        $item = Item::with(['stock', 'memoStock'])
            ->where('item_code', $request->barcode)
            ->where('is_active', true)
            ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'item'    => [
                'id'          => $item->id,
                'item_code'   => $item->item_code,
                'description' => $item->description,
                'unit'        => $item->unit,
                'qty_on_hand' => $item->getQtyOnHand(),
                'photo'       => $item->photo ? asset('storage/'.$item->photo) : asset('assets/images/item-placeholder.png'),
                'is_memo'     => $item->is_memo,
            ],
        ]);
    }
}
