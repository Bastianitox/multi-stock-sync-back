<?php
$request = Illuminate\Http\Request::create('/api/mercadolibre/all-products/7365610229928727?offset=0&limit=100', 'GET');
$controller = new \App\Http\Controllers\MercadoLibre\Reportes\getProductSellerController();
try {
    $response = $controller->getProductSeller($request, '7365610229928727');
    echo $response->getContent();
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
