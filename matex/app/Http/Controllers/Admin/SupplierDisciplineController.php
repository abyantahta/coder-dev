<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Reports\BuildSupplierRmDisciplineReport;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierDisciplineController extends Controller
{
    public function index(Request $request, BuildSupplierRmDisciplineReport $report): Response
    {
        $supplierId = $request->integer('supplier_id') ?: null;
        $data = $report->execute();

        $suppliers = collect($data['suppliers']);
        $shipments = collect($data['shipments']);
        $pending = collect($data['pending_overdue']);

        if ($supplierId) {
            $suppliers = $suppliers->where('id', $supplierId)->values();
            $shipments = $shipments->where('supplier_id', $supplierId)->values();
            $pending = $pending->where('supplier_id', $supplierId)->values();
        }

        $summary = $supplierId
            ? [
                'supplier_count' => $suppliers->count(),
                'total_shipments' => $shipments->count(),
                'late_count' => $shipments->where('is_late', true)->count(),
                'on_time_count' => $shipments->where('is_late', false)->count(),
                'pending_overdue_count' => $pending->count(),
                'total_days_late' => (int) $shipments->sum('days_late'),
                'avg_days_late' => $shipments->where('is_late', true)->avg('days_late')
                    ? round((float) $shipments->where('is_late', true)->avg('days_late'), 1)
                    : 0,
            ]
            : $data['summary'];

        return Inertia::render('Admin/Discipline/Index', [
            'summary' => $summary,
            'suppliers' => $supplierId ? $suppliers : $data['suppliers'],
            'shipments' => $shipments,
            'pending_overdue' => $pending,
            'filters' => [
                'supplier_id' => $supplierId,
            ],
            'supplierOptions' => Company::query()
                ->rawMat()
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }
}
