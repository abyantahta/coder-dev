<?php

namespace App\Http\Controllers;

use App\Models\ItemCodePrefix;
use App\Models\ProcurementItem;
use Illuminate\Http\Request;

class ItemCodePrefixController extends Controller
{
    public function index()
    {
        $prefixes = ItemCodePrefix::orderBy('code')->get();
        return view('procurement.master.prefixes.index', compact('prefixes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'  => 'required|string|max:10|alpha|unique:item_code_prefixes,code',
            'label' => 'required|string|max:100',
        ]);
        ItemCodePrefix::create([
            'code'  => strtoupper($request->code),
            'label' => $request->label,
        ]);
        return back()->with('success', 'Prefix kode berhasil ditambahkan.');
    }

    public function update(Request $request, ItemCodePrefix $itemCodePrefix)
    {
        $request->validate([
            'label' => 'required|string|max:100',
        ]);
        $itemCodePrefix->update([
            'label'     => $request->label,
            'is_active' => $request->boolean('is_active'),
        ]);
        return back()->with('success', 'Prefix kode berhasil diperbarui.');
    }

    public function destroy(ItemCodePrefix $itemCodePrefix)
    {
        if (ProcurementItem::where('item_code', 'like', $itemCodePrefix->code . '-%')->exists()) {
            return back()->with('error', 'Prefix ini masih dipakai oleh item ATK — tidak bisa dihapus.');
        }
        $itemCodePrefix->delete();
        return back()->with('success', 'Prefix kode dihapus.');
    }

    /** Preview kode item berikutnya buat prefix tertentu — dipanggil via AJAX di form tambah item. */
    public function nextCode(Request $request)
    {
        $request->validate(['prefix' => 'required|string|max:10']);
        return response()->json(['code' => ProcurementItem::generateItemCode(strtoupper($request->prefix))]);
    }
}
