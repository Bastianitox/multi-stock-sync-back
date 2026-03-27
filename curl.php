<?php
$url = "https://multi-stock-sync-back.onrender.com/api/mercadolibre/all-products/7365610229928727?offset=0&limit=50";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Authorization: Bearer 1|testing"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP CODE: " . $httpcode . "\n";
echo $response;
