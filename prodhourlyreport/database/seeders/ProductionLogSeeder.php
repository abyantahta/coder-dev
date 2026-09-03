<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionLogSeeder extends Seeder
{
    public function run(): void
    {
        // Line 1 / Model A — a full-day run, plus a second product picked up
        // in the afternoon on the same line/model.
        $this->seedJourney(
            product: Product::where('name', 'Product A1')->firstOrFail(),
            leaderEmail: 'leader1@example.com',
            checkpoints: [
                ['hour' => 8, 'production' => 55, 'reject' => 2, 'repair' => 1],
                ['hour' => 9, 'production' => 118, 'reject' => 4, 'repair' => 2],
                ['hour' => 10, 'production' => 185, 'reject' => 6, 'repair' => 3],
                ['hour' => 11, 'production' => 240, 'reject' => 8, 'repair' => 4],
                ['hour' => 12, 'production' => 290, 'reject' => 9, 'repair' => 5],
                ['hour' => 13, 'production' => 355, 'reject' => 11, 'repair' => 6],
                ['hour' => 14, 'production' => 420, 'reject' => 13, 'repair' => 7],
                ['hour' => 15, 'production' => 480, 'reject' => 15, 'repair' => 8],
                ['hour' => 16, 'production' => 540, 'reject' => 17, 'repair' => 9],
            ],
        );

        $this->seedJourney(
            product: Product::where('name', 'Product A2')->firstOrFail(),
            leaderEmail: 'leader1@example.com',
            checkpoints: [
                ['hour' => 13, 'production' => 30, 'reject' => 1, 'repair' => 0],
                ['hour' => 14, 'production' => 68, 'reject' => 2, 'repair' => 1],
                ['hour' => 15, 'production' => 110, 'reject' => 3, 'repair' => 1],
                ['hour' => 16, 'production' => 155, 'reject' => 4, 'repair' => 2],
            ],
        );

        // Line 1 / Model B — a shorter morning-only run.
        $this->seedJourney(
            product: Product::where('name', 'Product B1')->firstOrFail(),
            leaderEmail: 'leader1@example.com',
            checkpoints: [
                ['hour' => 8, 'production' => 20, 'reject' => 0, 'repair' => 0],
                ['hour' => 9, 'production' => 45, 'reject' => 1, 'repair' => 0],
                ['hour' => 10, 'production' => 72, 'reject' => 2, 'repair' => 1],
                ['hour' => 11, 'production' => 98, 'reject' => 2, 'repair' => 1],
            ],
        );

        // Line 2 / Model C — a full-day run, plus a second product picked up
        // in the afternoon.
        $this->seedJourney(
            product: Product::where('name', 'Product C1')->firstOrFail(),
            leaderEmail: 'leader2@example.com',
            checkpoints: [
                ['hour' => 8, 'production' => 42, 'reject' => 1, 'repair' => 0],
                ['hour' => 9, 'production' => 90, 'reject' => 2, 'repair' => 1],
                ['hour' => 10, 'production' => 145, 'reject' => 4, 'repair' => 2],
                ['hour' => 11, 'production' => 195, 'reject' => 5, 'repair' => 2],
                ['hour' => 12, 'production' => 230, 'reject' => 6, 'repair' => 3],
                ['hour' => 13, 'production' => 280, 'reject' => 7, 'repair' => 3],
                ['hour' => 14, 'production' => 335, 'reject' => 9, 'repair' => 4],
                ['hour' => 15, 'production' => 390, 'reject' => 10, 'repair' => 5],
            ],
        );

        $this->seedJourney(
            product: Product::where('name', 'Product C2')->firstOrFail(),
            leaderEmail: 'leader2@example.com',
            checkpoints: [
                ['hour' => 12, 'production' => 25, 'reject' => 0, 'repair' => 0],
                ['hour' => 13, 'production' => 58, 'reject' => 1, 'repair' => 1],
                ['hour' => 14, 'production' => 95, 'reject' => 2, 'repair' => 1],
                ['hour' => 15, 'production' => 130, 'reject' => 3, 'repair' => 2],
            ],
        );
    }

    private function seedJourney(Product $product, string $leaderEmail, array $checkpoints): void
    {
        $leader = User::where('email', $leaderEmail)->firstOrFail();
        $productModel = $product->productModel;
        $line = $productModel->line;

        foreach ($checkpoints as $point) {
            ProductionLog::create([
                'line_id' => $line->id,
                'product_model_id' => $productModel->id,
                'product_id' => $product->id,
                'user_id' => $leader->id,
                'logged_at' => today()->setTime($point['hour'], 0),
                'total_production' => $point['production'],
                'total_reject' => $point['reject'],
                'total_repair' => $point['repair'],
            ]);
        }
    }
}
