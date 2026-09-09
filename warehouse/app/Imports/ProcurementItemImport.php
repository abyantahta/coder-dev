<?php

namespace App\Imports;

use App\Models\ProcurementCategory;
use App\Models\ProcurementItem;
use App\Models\Uom;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProcurementItemImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public int $imported = 0;
    public int $skipped  = 0;

    public function model(array $row): ?ProcurementItem
    {
        if (empty($row['kode_item']) || empty($row['nama_item'])) {
            $this->skipped++;
            return null;
        }

        $uom = Uom::where('code', strtoupper(trim($row['uom'] ?? 'PCS')))->first();
        if (!$uom) {
            $uom = Uom::first();
        }

        $categoryId = null;
        if (!empty($row['kategori'])) {
            $categoryId = ProcurementCategory::firstOrCreate(['name' => trim($row['kategori'])])->id;
        }

        $this->imported++;

        return ProcurementItem::updateOrCreate(
            ['item_code' => strtoupper(trim($row['kode_item']))],
            [
                'name'        => $row['nama_item'],
                'uom_id'      => $uom->id,
                'category_id' => $categoryId,
                'is_active'   => true,
            ]
        );
    }
}
