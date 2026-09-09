<?php

namespace App\Services;

use App\Models\ItemDepartmentBudget;
use App\Models\ItemDepartmentBudgetTopup;
use App\Models\ProcurementRequestDetail;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * Budget bulan berjalan = default_budget (permanen) + top-up khusus bulan itu.
     * Bulan baru otomatis kembali ke default karena tidak ada row top-up untuk
     * year/month yang baru.
     */
    public function effectiveBudget(int $departmentId, int $procurementItemId, ?int $year = null, ?int $month = null): float
    {
        $year  ??= now()->year;
        $month ??= now()->month;

        $default = ItemDepartmentBudget::where('department_id', $departmentId)
            ->where('procurement_item_id', $procurementItemId)
            ->value('default_budget') ?? 0;

        $topup = ItemDepartmentBudgetTopup::where('department_id', $departmentId)
            ->where('procurement_item_id', $procurementItemId)
            ->where('year', $year)
            ->where('month', $month)
            ->sum('amount');

        return (float) $default + (float) $topup;
    }

    /**
     * Total nilai (qty x harga) request untuk item+departemen ini bulan berjalan,
     * dihitung dari request yang sudah disubmit (bukan draft) dan belum ditolak.
     */
    public function consumed(int $departmentId, int $procurementItemId, ?int $year = null, ?int $month = null): float
    {
        $year  ??= now()->year;
        $month ??= now()->month;

        return (float) ProcurementRequestDetail::join('procurement_requests', 'procurement_requests.id', '=', 'procurement_request_details.request_id')
            ->join('procurement_items', 'procurement_items.id', '=', 'procurement_request_details.item_id')
            ->where('procurement_requests.department_id', $departmentId)
            ->where('procurement_request_details.item_id', $procurementItemId)
            ->where('procurement_requests.status', '!=', 'draft')
            ->where('procurement_requests.status', '!=', 'rejected')
            ->whereYear('procurement_requests.req_date', $year)
            ->whereMonth('procurement_requests.req_date', $month)
            ->select(DB::raw('COALESCE(SUM(procurement_request_details.qty * procurement_items.price), 0) as total'))
            ->value('total');
    }

    public function remaining(int $departmentId, int $procurementItemId, ?int $year = null, ?int $month = null): float
    {
        return $this->effectiveBudget($departmentId, $procurementItemId, $year, $month)
            - $this->consumed($departmentId, $procurementItemId, $year, $month);
    }

    /**
     * Dipakai saat submit request: apakah baris ini (qty x harga item) melebihi
     * sisa budget bulan ini untuk departemen tsb. Tidak memblokir — cuma flag.
     */
    public function isOverBudget(int $departmentId, int $procurementItemId, float $amount): bool
    {
        return $amount > $this->remaining($departmentId, $procurementItemId);
    }
}
