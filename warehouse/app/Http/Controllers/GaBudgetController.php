<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ItemDepartmentBudget;
use App\Models\ItemDepartmentBudgetTopup;
use App\Models\ProcurementItem;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class GaBudgetController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    public function index()
    {
        $budgets = ItemDepartmentBudget::with('department', 'procurementItem')
            ->get()
            ->map(function (ItemDepartmentBudget $b) {
                $b->consumed  = $this->budgetService->consumed($b->department_id, $b->procurement_item_id);
                $b->effective = $this->budgetService->effectiveBudget($b->department_id, $b->procurement_item_id);
                $b->remaining = $b->effective - $b->consumed;
                return $b;
            })
            ->sortBy(fn($b) => $b->department->name . $b->procurementItem->name);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $items       = ProcurementItem::where('is_active', true)->orderBy('name')->get();

        return view('procurement.ga.budget.index', compact('budgets', 'departments', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id'       => 'required|exists:departments,id',
            'procurement_item_id' => 'required|exists:procurement_items,id',
            'default_budget'      => 'required|numeric|min:0',
        ]);

        $budget = ItemDepartmentBudget::updateOrCreate(
            ['department_id' => $request->department_id, 'procurement_item_id' => $request->procurement_item_id],
            ['default_budget' => $request->default_budget]
        );

        return back()->with('success', "Budget default untuk {$budget->procurementItem->name} / {$budget->department->name} disimpan.");
    }

    public function update(Request $request, ItemDepartmentBudget $budget)
    {
        $request->validate(['default_budget' => 'required|numeric|min:0']);
        $budget->update(['default_budget' => $request->default_budget]);

        return back()->with('success', 'Budget default berhasil diperbarui.');
    }

    public function topup(Request $request, ItemDepartmentBudget $budget)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes'  => 'nullable|string|max:500',
        ]);

        ItemDepartmentBudgetTopup::create([
            'department_id'       => $budget->department_id,
            'procurement_item_id' => $budget->procurement_item_id,
            'year'                => now()->year,
            'month'               => now()->month,
            'amount'              => $request->amount,
            'notes'               => $request->notes,
            'added_by'            => auth()->id(),
        ]);

        return back()->with('success', 'Top-up budget bulan ini berhasil ditambahkan.');
    }
}
