<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\MercadoLibreCredential;

$token = "APP_USR-7365610229928727-033012-1cf88010ad63b13bc7a82267c44b11cf-304267223";

$userResponse = Http::withToken($token)->get('https://api.mercadolibre.com/users/me');
$userId = $userResponse->json()['id'] ?? null;

$searchResponse = Http::withToken($token)->get("https://api.mercadolibre.com/users/$userId/items/search", ['limit' => 50, 'offset' => 0]);
$searchData = $searchResponse->json();
$productIds = $searchData['results'] ?? [];

echo "Found " . count($productIds) . " items in search.\n";

$mapped = 0;
if (!empty($productIds)) {
    $chunks = array_chunk($productIds, 20);
    foreach ($chunks as $chunk) {
        $idsParam = implode(',', $chunk);
        $itemsResponse = Http::withToken($token)->get("https://api.mercadolibre.com/items", [
            'ids' => $idsParam,
            'attributes' => 'id,title,price,available_quantity,status,thumbnail,attributes,permalink,seller_custom_field,variations,condition,date_created'
        ]);
        $items = $itemsResponse->json();
        if (is_array($items)) {
            foreach ($items as $itemWrapper) {
                if (isset($itemWrapper['code']) && $itemWrapper['code'] == 200 && isset($itemWrapper['body'])) {
                    $mapped++;
                } else {
                    echo "Item failed. Code: " . ($itemWrapper['code'] ?? 'None') . "\n";
                    if (isset($itemWrapper['body'])) print_r($itemWrapper['body']);
                }
            }
        } else {
            echo "Not an array: " . json_encode($items) . "\n";
        }
    }
}
echo "Mapped items: $mapped\n";
