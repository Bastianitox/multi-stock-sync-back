<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->randomElement([
                'Electrónica', 'Ropa', 'Hogar', 'Juguetes', 'Deportes', 
                'Belleza', 'Herramientas', 'Libros', 'Mascotas', 'Papelería'
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
