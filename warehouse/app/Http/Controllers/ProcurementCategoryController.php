<?php

namespace App\Http\Controllers;

use App\Models\ProcurementCategory;
use Illuminate\Http\Request;

class ProcurementCategoryController extends Controller
{
    public function index()
    {
        $categories = ProcurementCategory::with('parent')->orderBy('name')->get();
        $parents = ProcurementCategory::whereNull('parent_id')->orderBy('name')->get();
        return view('procurement.master.categories.index', compact('categories', 'parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100|unique:procurement_categories,name',
            'parent_id' => 'nullable|exists:procurement_categories,id',
        ]);
        ProcurementCategory::create($request->only('name', 'parent_id'));
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, ProcurementCategory $procurementCategory)
    {
        $request->validate([
            'name'      => 'required|string|max:100|unique:procurement_categories,name,' . $procurementCategory->id,
            'parent_id' => 'nullable|exists:procurement_categories,id',
        ]);

        if ((int) $request->parent_id === $procurementCategory->id) {
            return back()->with('error', 'Kategori tidak bisa jadi parent untuk dirinya sendiri.');
        }

        $procurementCategory->update([
            'name'      => $request->name,
            'parent_id' => $request->parent_id ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);
        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(ProcurementCategory $procurementCategory)
    {
        if ($procurementCategory->items()->exists()) {
            return back()->with('error', 'Kategori ini masih dipakai oleh item ATK — tidak bisa dihapus.');
        }
        if ($procurementCategory->children()->exists()) {
            return back()->with('error', 'Kategori ini masih punya sub-kategori — hapus/pindahkan sub-kategorinya dulu.');
        }
        $procurementCategory->delete();
        return back()->with('success', 'Kategori dihapus.');
    }
}
