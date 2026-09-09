<?php

namespace App\Http\Controllers;

use App\Models\ProcurementPurpose;
use Illuminate\Http\Request;

class ProcurementPurposeController extends Controller
{
    public function index()
    {
        $purposes = ProcurementPurpose::orderBy('name')->get();
        return view('procurement.master.purposes.index', compact('purposes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:procurement_purposes,name',
        ]);
        ProcurementPurpose::create(['name' => $request->name]);
        return back()->with('success', 'Kebutuhan ATK berhasil ditambahkan.');
    }

    public function update(Request $request, ProcurementPurpose $procurementPurpose)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:procurement_purposes,name,' . $procurementPurpose->id,
        ]);
        $procurementPurpose->update([
            'name' => $request->name,
            'is_active' => $request->boolean('is_active'),
        ]);
        return back()->with('success', 'Kebutuhan ATK berhasil diperbarui.');
    }

    public function destroy(ProcurementPurpose $procurementPurpose)
    {
        $procurementPurpose->delete();
        return back()->with('success', 'Kebutuhan ATK dihapus.');
    }
}
