<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'price' => $this->faker->numberBetween(500, 100000),
            'stock' => $this->faker->numberBetween(0, 500),
            'category_id' => \App\Models\Category::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
