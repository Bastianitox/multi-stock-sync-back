<?php

namespace App\Http\Controllers\MercadoLibre\Products;

use App\Http\Controllers\Controller;
use App\Services\MercadoLibre\MercadoLibreCredentialService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UploadProductsToMultipleStoresController extends Controller
{
    public function upload(Request $request)
    {
        set_time_limit(300); // tiempo de ejecucion en postman maximo. Puedes aumentar o disminuir segun necesidad.

        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls'
        ]);

        $file = $request->file('excel');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $headers = $rows[0];
        $products = array_slice($rows, 1);

        $client_catalog_required = [ // Si el cliente requiere catálogo
            //Credenciales, si se requiere, puede agregar mas.
            '2999003706392728' => false, 
            '83121941762985'   => false,
            '7365610229928727' => false,
            '5822095179207900' => true
        ];
        foreach ($products as $index => $row) {
            foreach ($client_catalog_required as $client_id => $requiresCatalog) { 
                Log::info("🔐 Consultando credenciales para client_id: $client_id");
                $credentials = MercadoLibreCredentialService::getValidCredentials($client_id); // Obtiene las credenciales válidas para el cliente

                if (!$credentials || !$credentials->access_token) {
                    Log::warning("❌ No se pudo obtener credenciales válidas para $client_id");
                    continue;
                }

                $responseCheck = Http::withToken($credentials->access_token)->get('https://api.mercadolibre.com/users/me');
                if ($responseCheck->status() === 401) {
                    $credentials = MercadoLibreCredentialService::refreshToken($client_id);
                    if (!$credentials) {
                        Log::error("❌ No se pudo refrescar token para $client_id");
                        continue;
                    }
                }

                $productData = $this->mapRowToProduct($headers, $row, $requiresCatalog);

                try {
                    $response = Http::withToken($credentials->access_token) // Realiza la solicitud a la API de Mercado Libre
                        ->post('https://api.mercadolibre.com/items', $productData);

                    Log::info("✅ Producto enviado para fila $index a $client_id", [
                        'status' => $response->status(),
                        'body' => $response->json()
                    ]);
                } catch (\Throwable $e) {
                    Log::error("❌ Error inesperado al enviar producto a $client_id", [
                        'fila' => $index,
                        'error' =>  config('app.debug') ? $e->getMessage() : 'Error interno en el servidor.'
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Carga masiva iniciada. Revisa los logs para ver el resultado.']); // Respuesta al usuario, esta la veremos en el postman
    }

    private function mapRowToProduct(array $headers, array $row, bool $useCatalog = false): array
    {
        $map = array_combine($headers, $row);

        $title = $map['Título'] ?? 'Producto sin título'; // Título del producto, si no existe se asigna un título genérico
        $price = (float)($map['Precio'] ?? 1000); // Precio del producto, si no existe se asigna un valor por defecto 
        //(SE USA 1000 CLP PARA QUE TE DE ERROR, ASI NO SE SUBE EL PRODUCTO CON UN PRECIO DEMASIADO BAJO)
        if ($price < 1100) $price = 1100; // Asegura que el precio no sea menor a 1100

        $pictures = [];
        if (!empty($map['Fotos'])) {
            foreach (explode(',', $map['Fotos']) as $url) {
                $pictures[] = ['source' => trim($url)];
            }
        }

        $attributes = array_filter([ // Atributos del producto 
            ['id' => 'ITEM_CONDITION', 'value_name' => strtolower($map['Condición'] ?? 'new')],
            ['id' => 'BRAND', 'value_name' => $map['Marca'] ?? null],
            ['id' => 'MODEL', 'value_name' => $map['Modelo'] ?? null],
            ['id' => 'ORIGINAL', 'value_name' => strtolower($map['Es original?'] ?? 'no') === 'sí' ? 'yes' : 'no'],
            ['id' => 'GTIN', 'value_name' => $map['GTIN'] ?? null],
            ['id' => 'BRAND', 'value_name' => $map['Marca'] ?? null],
            ['id' => 'GENDER', 'value_name' => $map['Género'] ?? null],
            ['id' => 'MODEL', 'value_name' => $map['Modelo'] ?? null],
            ['id' => 'COLOR', 'value_name' => $map['Varía por: Color'] ?? null],
            ['id' => 'SIZE', 'value_name' => $map['Talla'] ?? null],
            ['id' => 'WARRANTY_TYPE', 'value_name' => $map['Tipo de garantía'] ?? null],
            ['id' => 'WARRANTY_TIME', 'value_name' => "{$map['Tiempo de garantía']} {$map['Unidad de Tiempo de garantía']}"],
        ], fn($a) => !empty($a['value_name']));

        $product = [ // Estructura del producto a enviar a la API de Mercado Libre
            'site_id' => 'MLC',
            'category_id' => $this->discoverCategory($title) ?? 'MLC157658',
            'price' => $price,
            'currency_id' => 'CLP',
            'available_quantity' => (int)($map['Stock'] ?? 1),
            'condition' => strtolower($map['Condición'] ?? 'new'),
            'listing_type_id' => 'gold_pro',
            'description' => [
                'plain_text' => $map['Descripción'] ?? ''
            ],
            'shipping' => [
                'mode' => 'me2',
                'local_pick_up' => strtolower($map['Retiro en persona'] ?? 'no') === 'sí',
                'free_shipping' => false
            ],
            'pictures' => $pictures ?: [['source' => "https://http2.mlstatic.com/D_888921-MLC88646569931_072025-O.jpg"]],
            'attributes' => $attributes,
        ];

        if ($useCatalog) { // Si se requiere catálogo, se estructura el producto de manera diferente
            $product['catalog_listing'] = true;
            $product['family_name'] = $title;
        } else {
            $product['title'] = $title; 
            $product['variations'] = [[
                'available_quantity' => (int)($map['Stock'] ?? 1),
                'price' => $price,
                'attribute_combinations' => array_filter([
                    ['id' => 'COLOR', 'value_name' => $map['Varía por: Color'] ?? null],
                    ['id' => 'SIZE', 'value_name' => $map['Talla'] ?? null],
                ], fn($a) => !empty($a['value_name'])),
                'seller_custom_field' => $map['Código de la guía'] ?? null,
                'seller_sku' => $map['SKU'] ?? null,
                'attributes' => [],
                 // GTIN eliminado por errores, REQUIERE REVISION!
                'picture_ids' => []
            ]];
        }

        return $product;
    }

    private function discoverCategory(string $title): ?string //Función para predecir la categoría del producto, se puede quitar si se requiere o llega ser incorrecta al final.
    {
        try {
            $response = Http::get('https://api.mercadolibre.com/sites/MLC/domain_discovery/search', [
                'q' => $title
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data[0]['category_id'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::error("❌ Error al predecir categoría para título: $title", [
                'error' =>  config('app.debug') ? $e->getMessage() : 'Error interno en el servidor.'
            ]);
        }

        return null;
    }
}
