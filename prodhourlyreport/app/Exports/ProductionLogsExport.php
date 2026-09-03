<?php

namespace App\Exports;

use App\Models\ProductionLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductionLogsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Date', 'Time', 'Line', 'Model', 'Product', 'Leader',
            'Total Production', 'Total Reject', 'Total Repair',
        ];
    }

    public function map($log): array
    {
        /** @var ProductionLog $log */
        return [
            $log->logged_at->format('Y-m-d'),
            $log->logged_at->format('H:i'),
            $log->line?->name,
            $log->productModel?->name,
            $log->product?->name,
            $log->user?->name,
            // Cast to string: PhpSpreadsheet's Worksheet::fromArray() compares
            // each value with `!= null` to decide whether to skip the cell,
            // and 0 == null in PHP, so a literal 0 here would silently vanish
            // from the sheet instead of being written as zero.
            (string) $log->total_production,
            (string) $log->total_reject,
            (string) $log->total_repair,
        ];
    }
}
