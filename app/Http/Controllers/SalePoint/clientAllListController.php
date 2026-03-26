<?php

namespace App\Http\Controllers\SalePoint;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;


class clientAllListController
{
    public function clientAllList(Request $request){
        
        $limit = $request->input('limit', 50);
        $search = $request->input('search', '');

        $query = Client::query();

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                // Buscador inteligente contra cualquier dato identificatorio
                $q->where('nombres', 'LIKE', "%{$search}%")
                  ->orWhere('apellidos', 'LIKE', "%{$search}%")
                  ->orWhere('rut', 'LIKE', "%{$search}%")
                  ->orWhere('razon_social', 'LIKE', "%{$search}%");
            });
        }

        // Ordenar alfabéticamente
        $query->orderBy('nombres', 'asc');

        $clients = $query->paginate($limit);
        return response()->json($clients);
    }
}