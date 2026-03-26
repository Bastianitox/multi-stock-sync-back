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
        // Migración obsoleta: las columnas ya tienen los nombres correctos
        // desde sus migraciones de creación (create_attributes_table, create_products_table).
        // No se hace ningún cambio para evitar errores en SQLite y MySQL.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            //$table->renameColumn('product_id', 'producto_id');
            //$table->renameColumn('attribute_id', 'atributo_id');
            //$table->renameColumn('value', 'valor');
            $table->string('valor')->nullable()->change();
            
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->renameColumn('name', 'nombre');
            $table->renameColumn('category_id', 'categoria_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('name', 'nombre');
            $table->renameColumn('price', 'precio');
            $table->renameColumn('category_id', 'categoria_id');
        });
    }
};
