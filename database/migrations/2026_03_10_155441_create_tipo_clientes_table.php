<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        // Insertar registros por defecto obligatorios para crear clientes
        DB::table('tipo_clientes')->insert([
            ['id' => 1, 'nombre' => 'Empresa'],
            ['id' => 2, 'nombre' => 'Persona natural']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_clientes');
    }
};
