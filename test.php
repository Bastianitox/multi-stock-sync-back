<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/mercadolibre/all-products/7365610229928727?offset=0&limit=50', 'GET');
$controller = new \App\Http\Controllers\MercadoLibre\Reportes\getProductSellerController();
try {
    $response = $controller->getProductSeller($request, '7365610229928727');
    echo "Status: " . $response->getStatusCode() . "\n";
    echo substr($response->getContent(), 0, 500); // Only print first 500 chars
} catch (\Throwable $e) {
    echo "FATAL ERROR:\n" . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString();
}
