<?php

namespace App\Http\Controllers\MercadoLibre\Reportes;

use App\Http\Controllers\Controller;
use App\Models\MercadoLibreCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class getProductSellerController extends Controller
{
    public function getProductSeller(Request $request, $client_id)
    {
        try {
            // Cachear credenciales por 10 minutos
            $cacheKey = 'ml_credentials_' . $client_id;
            $credentials = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($client_id) {
                return MercadoLibreCredential::where('client_id', $client_id)->first();
            });

            if (!$credentials) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontraron credenciales válidas para el client_id proporcionado.',
                ], 404);
            }

            // ✅ Refrescar token automáticamente si está vencido
            if ($credentials->isTokenExpired()) {
                $refreshResponse = Http::asForm()->timeout(20)->post('https://api.mercadolibre.com/oauth/token', [
                    'grant_type' => 'refresh_token',
                    'client_id' => $credentials->client_id,
                    'client_secret' => $credentials->client_secret,
                    'refresh_token' => $credentials->refresh_token,
                ]);

                if ($refreshResponse->failed()) {
                    return response()->json(['error' => 'No se pudo refrescar el token de Mercado Libre: ' . $refreshResponse->body()], 401);
                }

                $data = $refreshResponse->json();
                $credentials->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $credentials->refresh_token,
                    'expires_at' => now()->addSeconds($data['expires_in']),
                ]);
            }

            // Obtener ID del usuario
            $userResponse = Http::withToken($credentials->access_token)
                ->timeout(20)
                ->get("https://api.mercadolibre.com/users/me");

            if ($userResponse->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al obtener información del usuario desde Mercado Libre: ' . $userResponse->body(),
                ], 500);
            }

            $userData = $userResponse->json();
            $userId = $userData['id'] ?? null;
            
            if (!$userId) {
                return response()->json(['error' => 'No se pudo obtener el ID de usuario de Mercado Libre'], 500);
            }

            $limit = intval($request->query('limit', 50));
            if ($limit > 50) $limit = 50;
            
            $offset = intval($request->query('offset', 0));
            $q = $request->query('q');

            // 🔍 Buscar por ID exacto si comienza con MLC
            if (!empty($q) && str_starts_with(strtoupper($q), 'MLC')) {
                $productResponse = Http::withToken($credentials->access_token)
                    ->timeout(20)
                    ->get("https://api.mercadolibre.com/items/{$q}");

                if ($productResponse->ok()) {
                    $productData = $productResponse->json();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Producto encontrado por ID.',
                        'products' => [[
                            'id' => $productData['id'],
                            'title' => $productData['title'],
                            'price' => $productData['price'],
                            'date_created' => $productData['date_created'] ?? null,
                            'available_quantity' => $productData['available_quantity'],
                            'condition' => $productData['condition'],
                            'status' => $productData['status'],
                            'pictures' => $productData['pictures'] ?? [],
                            'thumbnail' => $productData['thumbnail'] ?? '',
                            'attributes' => $productData['attributes'] ?? [],
                            'permalink' => $productData['permalink'],
                            'sku' => $productData['seller_custom_field'] ?? 'No disponible',
                            'variations' => collect($productData['variations'] ?? [])->map(function ($v) {
                                return [
                                    'variation_id' => $v['id'],
                                    'seller_custom_field' => $v['seller_custom_field'] ?? 'No disponible',
                                    'sku' => $v['seller_custom_field'] ?? 'No disponible',
                                    'available_quantity' => $v['available_quantity'],
                                ];
                            })->toArray()
                        ]],
                        'cantidad' => 1,
                        'cantidad_total' => 1,
                    ]);
                }
            }

            // 🔍 Buscar por texto o sin q (paginado)
            $params = [
                'limit' => $limit,
                'offset' => $offset
            ];
            if (!empty($q)) $params['q'] = $q;

            $searchResponse = Http::withToken($credentials->access_token)
                ->timeout(20)
                ->get("https://api.mercadolibre.com/users/{$userId}/items/search", $params);

            if ($searchResponse->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al obtener los productos desde campo search de ML.',
                ], 500);
            }

            $searchData = $searchResponse->json();
            $productIds = $searchData['results'] ?? [];
            $total = $searchData['paging']['total'] ?? count($productIds);
            
            $allProducts = [];
            if (!empty($productIds)) {
                $chunks = array_chunk($productIds, 20);
                foreach ($chunks as $chunk) {
                    $idsParam = implode(',', $chunk);
                    $productResponse = Http::withToken($credentials->access_token)
                        ->timeout(15)
                        ->get("https://api.mercadolibre.com/items", [
                            'ids' => $idsParam,
                            'attributes' => 'id,title,price,available_quantity,status,thumbnail,attributes,permalink,seller_custom_field,variations,condition,date_created'
                        ]);

                    if ($productResponse->ok()) {
                        $items = $productResponse->json();
                        if (is_array($items)) {
                            foreach ($items as $itemWrapper) {
                                if (isset($itemWrapper['code']) && $itemWrapper['code'] == 200 && isset($itemWrapper['body'])) {
                                    $productData = $itemWrapper['body'];
                                    $allProducts[] = [
                                        'id' => $productData['id'],
                                        'title' => $productData['title'],
                                        'price' => $productData['price'],
                                        'date_created' => $productData['date_created'] ?? '2000-01-01T00:00:00.000Z',
                                        'available_quantity' => $productData['available_quantity'],
                                        'status' => $productData['status'],
                                        'thumbnail' => $productData['thumbnail'] ?? '',
                                        'attributes' => $productData['attributes'] ?? [],
                                        'permalink' => $productData['permalink'] ?? '',
                                        'sku' => $productData['seller_custom_field'] ?? 'No disponible',
                                        'variations' => collect($productData['variations'] ?? [])->map(function ($v) {
                                            return [
                                                'variation_id' => $v['id'],
                                                'sku' => $v['seller_custom_field'] ?? 'No disponible',
                                                'available_quantity' => $v['available_quantity'],
                                            ];
                                        })->toArray()
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            // Ordenar por fecha descendente
            usort($allProducts, function ($a, $b) {
                return strtotime($b['date_created']) - strtotime($a['date_created']);
            });

            return response()->json([
                'status' => 'success',
                'cantidad' => $total,
                'cantidad_mostrada' => count($allProducts),
                'debug_info' => [
                    'userId' => $userId,
                    'search_total' => $total,
                    'ids_found' => count($productIds),
                    'first_id' => !empty($productIds) ? $productIds[0] : null,
                    'all_products_count' => count($allProducts)
                ],
                'products' => $allProducts,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno en el servidor: ' . $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine()
            ], 500);
        }
    }

    public function updateSku(Request $request, $client_id, $item_id)
    {
        $credentials = MercadoLibreCredential::where('client_id', $client_id)->first();
        if (!$credentials) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron credenciales válidas para el client_id proporcionado.',
            ], 404);
        }

        $sku = $request->input('sku');
        if (!$sku) {
            return response()->json([
                'status' => 'error',
                'message' => 'Debe enviar el campo sku.',
            ], 400);
        }

        $url = "https://api.mercadolibre.com/items/{$item_id}";
        $payload = [
            'seller_custom_field' => $sku
        ];

        $response = Http::withToken($credentials->access_token)
            ->withHeaders(['Accept' => 'application/json'])
            ->put($url, $payload);

        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'message' => 'SKU actualizado correctamente.',
                'company_id' => $client_id,
                'item_id' => $item_id,
                'sku' => $sku,
            ]);
        } else {
            $error = $response->json();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar SKU.',
                'company_id' => $client_id,
                'item_id' => $item_id,
                'error' => $error['message'] ?? 'Error desconocido',
            ], $response->status());
        }
    }
}
