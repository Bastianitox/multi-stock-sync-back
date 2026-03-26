<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(1000, 500000);
        
        return [
            'warehouse_id' => $this->faker->numberBetween(1, 3), // Suponemos que hay 3 bodegas
            'client_id' => \App\Models\Client::factory(), // Genera un cliente de paso, o se pisa en el seeder
            'amount_total_products' => $this->faker->numberBetween(1, 15),
            'price_subtotal' => $subtotal,
            'price_final' => round($subtotal * 1.19),
            'type_emission' => $this->faker->randomElement(['Boleta', 'Factura']),
            'observation' => $this->faker->optional(0.5)->sentence(),
            'name_companies' => $this->faker->randomElement(['Compañia Local A', 'Compañia Local B']),
            'status_sale' => $this->faker->randomElement(['Realizado', 'Pendiente', 'Cancelado']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }
}
