<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Asegurar que el rol Admin Master existe
        DB::table('rols')->updateOrInsert(
            ['id' => 7],
            [
                'nombre' => 'admin',
                'is_master' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Asegurar que el rol base existe para evitar nulls en el front
        DB::table('rols')->updateOrInsert(
            ['id' => 1],
            [
                'nombre' => 'usuario',
                'is_master' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Hacer que los usuarios existentes sean administradores para el test
        $adminMasterRol = \App\Models\Rol::where('nombre', 'admin')->first();

        if ($adminMasterRol) {
            // Buscamos los usuarios John Doe creados originalmente
            \App\Models\User::query()->update(['role_id' => $adminMasterRol->id]);
            
            // Crear o actualizar un usuario administrador maestro por defecto
            \App\Models\User::updateOrCreate(
                ['email' => 'admin@multistock.cl'],
                [
                    'name' => 'Admin Master',
                    'password' => \Illuminate\Support\Facades\Hash::make('Admin123!'),
                    'role_id' => $adminMasterRol->id,
                ]
            );

            // También intentamos con el ID 26 por si acaso
            $user26 = \App\Models\User::find(26);
            if ($user26) {
                $user26->role_id = $adminMasterRol->id;
                $user26->save();
            }
        }
    }
}
