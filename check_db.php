<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$creds = \App\Models\MercadoLibreCredential::all();
echo "Total credentials: " . $creds->count() . "\n";
foreach($creds as $c) {
    echo $c->client_id . " | " . $c->updated_at . " | " . ($c->access_token ? "HAS_TOKEN" : "NO_TOKEN") . " | " . ($c->refresh_token ? "HAS_REFRESH" : "NO_REFRESH") . "\n";
}
