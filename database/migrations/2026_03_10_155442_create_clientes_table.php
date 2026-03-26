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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->boolean('extranjero')->default(false);
            $table->string('rut')->unique();
            $table->string('razon_social')->nullable();
            $table->string('giro')->nullable();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('direccion');
            $table->string('comuna');
            $table->string('region');
            $table->string('ciudad');
            $table->foreignId('tipo_cliente_id')->constrained('tipo_clientes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
