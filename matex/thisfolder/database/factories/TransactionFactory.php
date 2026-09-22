<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'updated_at' => time(),
            'created_at' => $this->faker->dateTimeBetween('-2 years'),
            'created_by' => 1,
            'updated_by' => 1,
            'item_id' =>$this->faker->randomElement(Item::all())['id'],
            'lokasi' => fake()->randomElement(["Taruma", "Dojo", "Pos Satpam", "Gedung Baru", "Gedung Lama"]),
            'pic' => fake()->randomElement(["Abyan", "Amrullah", "Hilal", "Pietra", "Andrian"]),
            'Kondisi' => fake()->randomElement(["Baik", "Kurang", "Rusak"]),
            'image_path' => fake()->imageUrl(),
            'keterangan' => fake()->sentence(),
            //
        ];
    }
}
