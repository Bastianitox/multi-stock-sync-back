<?php

namespace App\Http\Controllers\Warehouses;

use App\Models\Company;
use App\Models\Warehouse;
use App\Models\StockWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class warehouseCompanyListController{
    /**
     * List all companies. //No hay ruta en api.php
     */
    public function company_list_all()
    {
        $companies = Company::with('warehouses')->get();
        return response()->json($companies);
    }

    /**
     * List all warehouses flat array for Frontend Dropdowns.
     */
    public function warehouse_list_all()
    {
        $warehouses = Warehouse::with('company')->get();
        return response()->json($warehouses);
    }
}