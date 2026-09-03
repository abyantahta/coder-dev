<?php

namespace Database\Seeders;

use App\Models\Line;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'Line 1' => [
                'Model A' => ['Product A1', 'Product A2'],
                'Model B' => ['Product B1'],
            ],
            'Line 2' => [
                'Model C' => ['Product C1', 'Product C2'],
            ],
        ];

        foreach ($structure as $lineName => $models) {
            $line = Line::create([
                'name' => $lineName,
                'code' => str_replace(' ', '', $lineName),
            ]);

            foreach ($models as $modelName => $products) {
                $productModel = $line->productModels()->create([
                    'name' => $modelName,
                    'code' => str_replace(' ', '', $modelName),
                ]);

                foreach ($products as $productName) {
                    $productModel->products()->create([
                        'name' => $productName,
                        'code' => str_replace(' ', '', $productName),
                    ]);
                }
            }
        }
    }
}
