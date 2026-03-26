<?php

namespace App\Http\Controllers\Warehouses;

use App\Models\Company;
use App\Models\Warehouse;
use App\Models\StockWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class warehouseNewCompanyController{
        /**
        /**
     * Create a new company.
     */
    public function company_store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'client_id' => 'required|string|max:100', // El frontend debe enviarlo
            ]);

            $company = Company::create([
                'name' => $validated['name'],
                'client_id' => $validated['client_id'],
            ]);

            return response()->json([
                'message' => 'Empresa creada con éxito.',
                'data' => $company
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear empresa:', ['error' =>  config('app.debug') ? $e->getMessage() : 'Error interno en el servidor.']);
            return response()->json(['message' => 'Error al crear la empresa.', 'error' =>  config('app.debug') ? $e->getMessage() : 'Error interno en el servidor.'], 500);
        }
    }

    public function company_store_by_url($name, $client_id)
    {
        try {
            // Validación básica
            if (empty($name) || !is_numeric($client_id)) {
                return response()->json(['message' => 'Parámetros inválidos.'], 422);
            }

            // Crear empresa
            $company = Company::create([
                'name' => $name,
                'client_id' => $client_id,
            ]);

            return response()->json([
                'message' => 'Empresa creada con éxito por URL.',
                'data' => $company
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear empresa por URL:', ['error' =>  config('app.debug') ? $e->getMessage() : 'Error interno en el servidor.']);
            return response()->json(['message' => 'Error al crear la empresa.', 'error' =>  config('app.debug') ? $e->getMessage() : 'Error interno en el servidor.'], 500);
        }
    }
}