<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_at' => time(),
            'updated_at' => time(),
            'no_asset' => Str::random(9),
            'encrypted_no_asset' => Str::random(30),
            'name' => fake()->sentence(),
            'category_id' => fake()->randomElement([1,2,3,4,5]),
            'service_date' => fake()->date(),
            'isDisposition' => fake()->randomElement([true,false]),
            'cost' => fake()->randomNumber(),
            'nbv' => fake()->randomNumber(),
            'lokasi' => fake()->randomElement(["Taruma", "Dojo","Pos Satpam", "Gedung Baru", "Gedung Lama"]),
        ];
    }
}
