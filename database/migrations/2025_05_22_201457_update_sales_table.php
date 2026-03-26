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
        // Migración obsoleta: las columnas 'products' y 'shipping' nunca existieron
        // en la tabla 'sale'. Se omite para evitar errores en SQLite.
        if (Schema::hasColumn('sale', 'products')) {
            Schema::table('sale', function (Blueprint $table) {
                $table->dropColumn('products');
            });
        }
        if (Schema::hasColumn('sale', 'shipping')) {
            Schema::table('sale', function (Blueprint $table) {
                $table->dropColumn('shipping');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
};
