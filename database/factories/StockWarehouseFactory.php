<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockWarehouse>
 */
class StockWarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'thumbnail' => $this->faker->imageUrl(200, 200, 'products'),
            'id_mlc' => 'MLC' . $this->faker->unique()->numberBetween(1000000, 9999999),
            'title' => $this->faker->sentence(4),
            'price_clp' => $this->faker->numberBetween(1000, 150000),
            'warehouse_stock' => $this->faker->numberBetween(1, 100),
            'warehouse_id' => $this->faker->numberBetween(1, 3),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
