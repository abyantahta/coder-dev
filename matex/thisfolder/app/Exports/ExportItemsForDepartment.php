<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportItemsForDepartment implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Item::query()->with(['category', 'department'])->orderBy('no_asset');
    }

    public function headings(): array
    {
        return ['no_asset', 'name', 'category', 'department'];
    }

    /**
     * @param Item $item
     */
    public function map($item): array
    {
        return [
            $item->no_asset,
            $item->name,
            $item->category?->name,
            $item->department?->name,
        ];
    }
}
