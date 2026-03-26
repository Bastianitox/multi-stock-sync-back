<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryNames = [
            'Electrónica', 'Ropa', 'Hogar', 'Juguetes', 'Deportes', 
            'Belleza', 'Herramientas', 'Libros', 'Mascotas', 'Papelería'
        ];

        $this->command->info('Ensuring Categories exist...');
        $categories = collect();
        foreach ($categoryNames as $name) {
            $categories->push(\App\Models\Category::firstOrCreate(['nombre' => $name]));
        }

        $this->command->info('Creating 150 Products...');
        \App\Models\Product::factory()->count(150)->recycle($categories)->create();

        $this->command->info('Creating 300 Stock items in Warehouses...');
        \App\Models\StockWarehouse::factory()->count(300)->create();

        $this->command->info('Creating 250 dummy Clients...');
        \App\Models\Client::factory()->count(250)->create();

        $this->command->info('Creating 400 dummy Sales for existing and new clients...');
        \App\Models\Sale::factory()->count(400)->create();
        
        $this->command->info('Test data populated successfully!');
    }
}
