<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isPersona = $this->faker->boolean(70); // 70% personas naturales, 30% empresas
        
        return [
            'tipo_cliente_id' => $isPersona ? 2 : 1, // 2 = Persona Natural, 1 = Empresa/Otro
            'extranjero' => 0,
            'rut' => $this->faker->unique()->numerify('########-#'),
            'nombres' => $isPersona ? $this->faker->firstName() : 'Empresa',
            'apellidos' => $isPersona ? $this->faker->lastName() : 'S.A.',
            'razon_social' => !$isPersona ? $this->faker->company() : null,
            'giro' => !$isPersona ? $this->faker->jobTitle() : null,
            'direccion' => $this->faker->streetAddress(),
            'comuna' => $this->faker->city(),
            'region' => $this->faker->state(),
            'ciudad' => $this->faker->city(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
