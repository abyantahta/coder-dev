<?php

namespace App\Http\Controllers;

use App\Models\Uom;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index()
    {
        $uoms = Uom::withCount('procurementItems')->orderBy('code')->get();
        return view('procurement.master.uom.index', compact('uoms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:uoms,code',
            'name' => 'required|string|max:50',
        ]);
        Uom::create(['code' => strtoupper($request->code), 'name' => $request->name]);
        return back()->with('success', 'UOM berhasil ditambahkan.');
    }

    public function update(Request $request, Uom $uom)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:uoms,code,' . $uom->id,
            'name' => 'required|string|max:50',
        ]);
        $uom->update(['code' => strtoupper($request->code), 'name' => $request->name, 'is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'UOM berhasil diperbarui.');
    }

    public function destroy(Uom $uom)
    {
        if ($uom->procurementItems()->exists()) {
            return back()->with('error', 'UOM tidak bisa dihapus, masih digunakan oleh item.');
        }
        $uom->delete();
        return back()->with('success', 'UOM dihapus.');
    }
}
