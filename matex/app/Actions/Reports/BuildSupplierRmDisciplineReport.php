<?php

namespace App\Actions\Reports;

use App\Models\Company;
use App\Models\DeliverySchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildSupplierRmDisciplineReport
{
    /**
     * Plan = scheduled_date setelah konfirmasi Supplier RM.
     * Aktual kirim = tanggal ship_confirmed_at.
     * Terlambat bila aktual > plan.
     */
    public function execute(): array
    {
        $schedules = DeliverySchedule::query()
            ->with([
                'purchaseOrder:id,po_number,supplier_rm_id,rm_confirmed_at',
                'purchaseOrder.supplierRm:id,code,name',
                'purchaseOrderItem.item:id,item_number,description',
                'deliveryNote:id,delivery_schedule_id,dn_number',
            ])
            ->whereHas('purchaseOrder', fn ($q) => $q->whereNotNull('rm_confirmed_at'))
            ->whereNotNull('ship_confirmed_at')
            ->orderByDesc('ship_confirmed_at')
            ->get();

        $pendingOverdue = DeliverySchedule::query()
            ->with([
                'purchaseOrder:id,po_number,supplier_rm_id,rm_confirmed_at',
                'purchaseOrder.supplierRm:id,code,name',
                'purchaseOrderItem.item:id,item_number,description',
            ])
            ->whereHas('purchaseOrder', fn ($q) => $q->whereNotNull('rm_confirmed_at'))
            ->whereNull('ship_confirmed_at')
            ->whereDate('scheduled_date', '<', now()->toDateString())
            ->orderBy('scheduled_date')
            ->get();

        $shipments = $schedules->map(fn (DeliverySchedule $s) => $this->mapShipment($s));
        $overdue = $pendingOverdue->map(fn (DeliverySchedule $s) => $this->mapPendingOverdue($s));

        $bySupplier = $this->aggregateBySupplier($shipments, $overdue);

        $suppliers = Company::query()
            ->rawMat()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active'])
            ->map(function (Company $company) use ($bySupplier) {
                $stats = $bySupplier->get($company->id) ?? $this->emptyStats();

                return array_merge($stats, [
                    'id' => $company->id,
                    'code' => $company->code,
                    'name' => $company->name,
                    'is_active' => $company->is_active,
                ]);
            })
            ->sortBy([
                ['late_count', 'desc'],
                ['pending_overdue_count', 'desc'],
                ['name', 'asc'],
            ])
            ->values();

        return [
            'suppliers' => $suppliers,
            'shipments' => $shipments->values(),
            'pending_overdue' => $overdue->values(),
            'summary' => [
                'supplier_count' => $suppliers->count(),
                'total_shipments' => $shipments->count(),
                'late_count' => $shipments->where('is_late', true)->count(),
                'on_time_count' => $shipments->where('is_late', false)->count(),
                'pending_overdue_count' => $overdue->count(),
                'total_days_late' => (int) $shipments->sum('days_late'),
                'avg_days_late' => $this->avgDaysLate($shipments->where('is_late', true)),
            ],
        ];
    }

    private function mapShipment(DeliverySchedule $schedule): array
    {
        $planDate = Carbon::parse($schedule->scheduled_date)->startOfDay();
        $actualDate = Carbon::parse($schedule->ship_confirmed_at)->startOfDay();
        $daysLate = $actualDate->greaterThan($planDate)
            ? (int) $planDate->diffInDays($actualDate)
            : 0;
        $isLate = $daysLate > 0;

        return [
            'id' => $schedule->id,
            'supplier_id' => $schedule->purchaseOrder->supplier_rm_id,
            'supplier_code' => $schedule->purchaseOrder->supplierRm?->code,
            'supplier_name' => $schedule->purchaseOrder->supplierRm?->name,
            'po_id' => $schedule->purchase_order_id,
            'po_number' => $schedule->purchaseOrder->po_number,
            'dn_number' => $schedule->deliveryNote?->dn_number,
            'item_number' => $schedule->purchaseOrderItem?->item?->item_number,
            'item_description' => $schedule->purchaseOrderItem?->item?->description,
            'plan_date' => $planDate->toDateString(),
            'actual_date' => $actualDate->toDateString(),
            'days_late' => $isLate ? $daysLate : 0,
            'is_late' => $isLate,
            'status' => $schedule->status->value,
            'status_label' => $schedule->status->label(),
        ];
    }

    private function mapPendingOverdue(DeliverySchedule $schedule): array
    {
        $planDate = Carbon::parse($schedule->scheduled_date)->startOfDay();
        $today = now()->startOfDay();
        $daysLate = $today->greaterThan($planDate)
            ? (int) $planDate->diffInDays($today)
            : 0;

        return [
            'id' => $schedule->id,
            'supplier_id' => $schedule->purchaseOrder->supplier_rm_id,
            'supplier_code' => $schedule->purchaseOrder->supplierRm?->code,
            'supplier_name' => $schedule->purchaseOrder->supplierRm?->name,
            'po_id' => $schedule->purchase_order_id,
            'po_number' => $schedule->purchaseOrder->po_number,
            'item_number' => $schedule->purchaseOrderItem?->item?->item_number,
            'item_description' => $schedule->purchaseOrderItem?->item?->description,
            'plan_date' => $planDate->toDateString(),
            'days_late' => $daysLate,
            'status' => $schedule->status->value,
            'status_label' => $schedule->status->label(),
        ];
    }

    private function aggregateBySupplier(Collection $shipments, Collection $overdue): Collection
    {
        $supplierIds = $shipments->pluck('supplier_id')
            ->merge($overdue->pluck('supplier_id'))
            ->unique()
            ->filter();

        return $supplierIds->mapWithKeys(function ($supplierId) use ($shipments, $overdue) {
            $rows = $shipments->where('supplier_id', $supplierId);
            $late = $rows->where('is_late', true);
            $total = $rows->count();
            $lateCount = $late->count();
            $onTime = $total - $lateCount;
            $onTimeRate = $total > 0 ? round(($onTime / $total) * 100, 1) : null;
            $avgDaysLate = $this->avgDaysLate($late);
            $pendingCount = $overdue->where('supplier_id', $supplierId)->count();

            return [
                $supplierId => [
                    'total_shipments' => $total,
                    'on_time_count' => $onTime,
                    'late_count' => $lateCount,
                    'pending_overdue_count' => $pendingCount,
                    'total_days_late' => (int) $late->sum('days_late'),
                    'avg_days_late' => $avgDaysLate,
                    'max_days_late' => (int) ($late->max('days_late') ?? 0),
                    'on_time_rate' => $onTimeRate,
                    'sr_score' => $this->score($onTimeRate, $lateCount, $avgDaysLate, $pendingCount),
                ],
            ];
        });
    }

    private function emptyStats(): array
    {
        return [
            'total_shipments' => 0,
            'on_time_count' => 0,
            'late_count' => 0,
            'pending_overdue_count' => 0,
            'total_days_late' => 0,
            'avg_days_late' => 0,
            'max_days_late' => 0,
            'on_time_rate' => null,
            'sr_score' => null,
        ];
    }

    private function avgDaysLate(Collection $lateRows): float
    {
        if ($lateRows->isEmpty()) {
            return 0.0;
        }

        return round((float) $lateRows->avg('days_late'), 1);
    }

    /**
     * Skor kedisiplinan (SR) 0–100.
     * Basis on-time rate, dipotong penalti frekuensi & durasi terlambat + pending overdue.
     */
    private function score(?float $onTimeRate, int $lateCount, float $avgDaysLate, int $pendingOverdue): ?int
    {
        if ($onTimeRate === null && $lateCount === 0 && $pendingOverdue === 0) {
            return null;
        }

        $base = $onTimeRate ?? 100;
        $penalty = ($lateCount * 2) + ($avgDaysLate * 1.5) + ($pendingOverdue * 3);

        return (int) max(0, min(100, round($base - $penalty)));
    }
}
