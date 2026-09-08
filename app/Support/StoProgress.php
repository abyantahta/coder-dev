<?php

namespace App\Support;

class StoProgress
{
    /**
     * The fraction of `$total` that `$done` represents, as a value from
     * 0 to 1. Returns 0 when `$total` is 0 instead of dividing by zero.
     */
    public static function ratio(int $done, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return $done / $total;
    }
}
