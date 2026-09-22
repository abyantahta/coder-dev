<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportItemDepartments implements ToCollection, WithHeadingRow
{
    public int $updated = 0;

    /**
     * Rows left with a blank department column - expected when filling the
     * sheet in gradually, not treated as an error.
     */
    public int $blank = 0;

    /** @var list<string> */
    public array $skipped = [];

    public function collection(Collection $rows): void
    {
        $departmentsByName = Department::all()->keyBy(fn ($department) => strtolower($department->name));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 for zero-index, +1 for the heading row
            $noAsset = trim((string) ($row['no_asset'] ?? ''));
            $departmentName = trim((string) ($row['department'] ?? ''));

            if ($noAsset === '') {
                continue;
            }

            if ($departmentName === '') {
                $this->blank++;
                continue;
            }

            $item = Item::where('no_asset', $noAsset)->first();
            if (!$item) {
                $this->skipped[] = "Row {$rowNumber} ({$noAsset}): no matching item found";
                continue;
            }

            $department = $departmentsByName->get(strtolower($departmentName));
            if (!$department) {
                $this->skipped[] = "Row {$rowNumber} ({$noAsset}): unknown department \"{$departmentName}\"";
                continue;
            }

            $item->update(['department_id' => $department->id]);
            $this->updated++;
        }
    }
}
