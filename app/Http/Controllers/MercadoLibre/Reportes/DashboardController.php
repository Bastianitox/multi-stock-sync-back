<?php

namespace App\Http\Controllers\MercadoLibre\Reportes;

use App\Http\Controllers\Controller;
use App\Models\MercadoLibreCredential;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function getStats(Request $request, $client_id)
    {
        try {
            // 1. Obtener credenciales
            $cacheKey = 'ml_credentials_' . $client_id;
            $credentials = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($client_id) {
                return MercadoLibreCredential::where('client_id', $client_id)->first();
            });

            if (!$credentials) {
                return response()->json(['status' => 'error', 'message' => 'Credenciales no encontradas'], 404);
            }

            // Refrescar token si es necesario (sin logs para evitar crashes en Render)
            if ($credentials->isTokenExpired()) {
                $refreshResponse = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
                    'grant_type' => 'refresh_token',
                    'client_id' => $credentials->client_id,
                    'client_secret' => $credentials->client_secret,
                    'refresh_token' => $credentials->refresh_token,
                ]);

                if ($refreshResponse->successful()) {
                    $data = $refreshResponse->json();
                    $credentials->update([
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? $credentials->refresh_token,
                        'expires_at' => now()->addSeconds($data['expires_in']),
                    ]);
                }
            }

            // 2. Obtener ID de usuario
            $userResponse = Http::withToken($credentials->access_token)->get('https://api.mercadolibre.com/users/me');
            if ($userResponse->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Token inválido o expirado'], 401);
            }
            $userId = $userResponse->json()['id'];

            // 3. Obtener Total de Productos (Búsqueda rápida con limit 0)
            $searchResponse = Http::withToken($credentials->access_token)
                ->get("https://api.mercadolibre.com/users/{$userId}/items/search", ['limit' => 0]);
            $totalProducts = $searchResponse->json()['paging']['total'] ?? 0;

            // 4. Obtener Total de Ventas (Órdenes pagadas)
            $ordersResponse = Http::withToken($credentials->access_token)
                ->get("https://api.mercadolibre.com/orders/search", [
                    'seller' => $userId,
                    'order.status' => 'paid',
                    'limit' => 0
                ]);
            $totalSales = $ordersResponse->json()['paging']['total'] ?? 0;

            // 5. Obtener Total de Clientes (Local)
            $totalClients = Client::count();

            // 6. Obtener Stock Crítico (Muestreo de los primeros 50 ítems activos)
            // Nota: Debido a límites de RAM en Render, no podemos procesar los 6000 items en tiempo real.
            // Retornaremos un conteo basado en los más recientes para mantener la velocidad.
            $criticResponse = Http::withToken($credentials->access_token)
                ->get("https://api.mercadolibre.com/users/{$userId}/items/search", [
                    'status' => 'active',
                    'limit' => 50
                ]);
            
            $criticCount = 0;
            $items = $criticResponse->json()['results'] ?? [];
            if (!empty($items)) {
                $idsParam = implode(',', $items);
                $detailsResponse = Http::withToken($credentials->access_token)
                    ->get("https://api.mercadolibre.com/items", [
                        'ids' => $idsParam,
                        'attributes' => 'available_quantity'
                    ]);
                
                if ($detailsResponse->successful()) {
                    foreach ($detailsResponse->json() as $item) {
                        if (isset($item['body']['available_quantity']) && $item['body']['available_quantity'] <= 3) {
                            $criticCount++;
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $totalProducts,
                    'sales' => $totalSales,
                    'clients' => $totalClients,
                    'stockCritico' => $criticCount,
                    'isPartialStock' => $totalProducts > 50
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
