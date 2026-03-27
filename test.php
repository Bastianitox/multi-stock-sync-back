<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::first();
if (!$user) {
    echo "No user in database.";
    exit;
}
$token = $user->createToken('test-token')->plainTextToken;

$request = Illuminate\Http\Request::create('/api/mercadolibre/all-products/7365610229928727?offset=0&limit=100', 'GET');
$request->headers->set('Accept', 'application/json');
$request->headers->set('Authorization', 'Bearer ' . $token);

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo substr($response->getContent(), 0, 500); // only print first 500 chars to avoid huge payload
} catch (\Throwable $e) {
    echo "ERROR MESSAGE:\n" . $e->getMessage() . "\n" . $e->getTraceAsString();
}
