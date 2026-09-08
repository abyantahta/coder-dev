<?php

namespace App\Services;

use Carbon\Carbon;

class DepreciationScheduler
{
    /**
     * Build the month-by-month depreciation ledger for an asset, from its
     * service date up to either its disposal date or the end of the current
     * month (whichever applies), stopping once accumulated depreciation
     * reaches cost.
     *
     * @return list<array{month:int, year:int, depreciation:float, nbv:float, depreciation_per_month:float}>
     */
    public function buildSchedule(
        float $cost,
        float $depreciationPerMonth,
        Carbon $serviceDate,
        ?Carbon $disposalDate,
        Carbon $asOf
    ): array {
        $runningDate = $serviceDate->copy()->endOfMonth();
        $endOfMonth = $asOf->copy()->endOfMonth();
        $depreciation = 0.0;
        $rows = [];

        while (
            ($depreciation - $cost <= 1)
            && ($disposalDate ? $runningDate->lt($disposalDate) : $runningDate->lt($endOfMonth))
        ) {
            if ($depreciation - $cost > 0) {
                $depreciation = $cost;
            }

            $rows[] = [
                'month' => $runningDate->month,
                'year' => $runningDate->year,
                'depreciation' => $depreciation,
                'nbv' => $cost - $depreciation,
                'depreciation_per_month' => $depreciationPerMonth,
            ];

            $depreciation += $depreciationPerMonth;
            $runningDate = $runningDate->copy()->addMonth();
        }

        return $rows;
    }
}
