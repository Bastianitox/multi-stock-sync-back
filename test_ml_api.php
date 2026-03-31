<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MercadoLibreCredential;
use Illuminate\Support\Facades\Http;

$clientId = '7365610229928727';

try {
    echo "1. Getting credentials for $clientId...\n";
    $credentials = MercadoLibreCredential::where('client_id', $clientId)->first();
    if (!$credentials) {
        die("Credentials not found\n");
    }

    if ($credentials->isTokenExpired() || empty($credentials->access_token) || $credentials->access_token == 'null') {
        echo "Token expired or missing! Refreshing...\n";
        $refreshResponse = Http::asForm()->timeout(20)->post('https://api.mercadolibre.com/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $credentials->client_id,
            'client_secret' => $credentials->client_secret,
            'refresh_token' => $credentials->refresh_token,
        ]);

        if ($refreshResponse->failed()) {
            die("Refresh failed: " . $refreshResponse->body() . "\n");
        }

        $data = $refreshResponse->json();
        $credentials->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $credentials->refresh_token,
            'expires_at' => now()->addSeconds($data['expires_in']),
        ]);
        echo "Refreshed successfully.\n";
    }

    $token = $credentials->access_token;
    echo "Access Token: " . substr($token, 0, 15) . "...\n";

    echo "\n2. Calling /users/me...\n";
    $userResponse = Http::withToken($token)->get('https://api.mercadolibre.com/users/me');
    echo "Status: " . $userResponse->status() . "\n";
    $userData = $userResponse->json();
    $userId = $userData['id'] ?? null;
    echo "User ID: $userId\n";

    if (!$userId) die("Failed to get user ID\n");

    echo "\n3. Calling /users/$userId/items/search...\n";
    $searchResponse = Http::withToken($token)->get("https://api.mercadolibre.com/users/$userId/items/search", [
        'limit' => 50,
        'offset' => 0
    ]);
    echo "Status: " . $searchResponse->status() . "\n";
    $searchData = $searchResponse->json();
    $productIds = $searchData['results'] ?? [];
    echo "Total found in paging: " . ($searchData['paging']['total'] ?? 'N/A') . "\n";
    echo "Number of IDs returned: " . count($productIds) . "\n";

    if (empty($productIds)) {
        echo "No products found for this user in ML.\n";
        print_r($searchData);
        die();
    }

    echo "IDs: " . implode(', ', array_slice($productIds, 0, 5)) . "...\n";

    echo "\n4. Calling /items (multiget)...\n";
    $chunks = array_chunk($productIds, 20);
    $firstChunk = implode(',', $chunks[0]);
    echo "Fetching: $firstChunk\n";
    
    $itemsResponse = Http::withToken($token)->get("https://api.mercadolibre.com/items", [
        'ids' => $firstChunk,
        'attributes' => 'id,title,price,available_quantity,status,thumbnail,attributes,permalink,seller_custom_field,variations,condition,date_created'
    ]);
    
    echo "Status: " . $itemsResponse->status() . "\n";
    $itemsData = $itemsResponse->json();
    echo "Type of response: " . gettype($itemsData) . "\n";
    
    if (is_array($itemsData) && count($itemsData) > 0) {
        echo "Valid first item code: " . ($itemsData[0]['code'] ?? 'missing') . "\n";
        if (isset($itemsData[0]['body'])) {
            echo "Body title: " . ($itemsData[0]['body']['title'] ?? 'missing') . "\n";
            echo "Has attributes: " . (isset($itemsData[0]['body']['attributes']) ? 'Yes' : 'No') . "\n";
            echo "Has thumbnail: " . (isset($itemsData[0]['body']['thumbnail']) ? 'Yes' : 'No') . "\n";
            echo "Condition: " . ($itemsData[0]['body']['condition'] ?? 'missing') . "\n";
            echo "Date created: " . ($itemsData[0]['body']['date_created'] ?? 'missing') . "\n";
            echo "Available qty: " . ($itemsData[0]['body']['available_quantity'] ?? 'missing') . "\n";
        } else {
            echo "Body missing!\n";
            print_r($itemsData[0]);
        }
    } else {
        echo "Invalid items format or empty array!\n";
        print_r($itemsData);
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
