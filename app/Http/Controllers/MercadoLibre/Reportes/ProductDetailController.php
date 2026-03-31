<?php

namespace App\Http\Controllers\MercadoLibre\Reportes;

use App\Http\Controllers\Controller;
use App\Models\MercadoLibreCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ProductDetailController extends Controller
{
    /**
     * Obtiene el detalle completo y la descripción de un solo producto.
     * Esta operación es ligera porque es para una sola unidad (on-demand).
     */
    public function getDetails(Request $request, $client_id, $item_id)
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

            // 2. Refrescar token si es necesario
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

            // 3. Consultar Item y Descripción en paralelo (o secuencial, son solo 2)
            $productResponse = Http::withToken($credentials->access_token)
                ->get("https://api.mercadolibre.com/items/{$item_id}");

            $descriptionResponse = Http::withToken($credentials->access_token)
                ->get("https://api.mercadolibre.com/items/{$item_id}/description");

            if ($productResponse->failed()) {
                return response()->json(['status' => 'error', 'message' => 'No se encontró el producto en Mercado Libre'], 404);
            }

            $productData = $productResponse->json();
            $descriptionData = $descriptionResponse->json();

            // Combinar datos
            $productData['description'] = [
                'plain_text' => $descriptionData['plain_text'] ?? 'Sin descripción disponible.'
            ];

            return response()->json([
                'status' => 'success',
                'data' => $productData
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener detalles: ' . $e->getMessage()
            ], 500);
        }
    }
}
