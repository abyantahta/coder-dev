<?php

namespace Database\Seeders;

use App\Models\ProcurementItem;
use Illuminate\Database\Seeder;

class ProcurementPriceSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            'ATK-001' => 3500,
            'ATK-002' => 45000,
            'ATK-003' => 12000,
            'ATK-004' => 85000,
            'ATK-005' => 8500,
            'ATK-006' => 125000,
            'ATK-007' => 7000,
            'ATK-008' => 15000,
        ];

        foreach ($prices as $code => $price) {
            ProcurementItem::where('item_code', $code)->update(['price' => $price]);
        }
    }
}
