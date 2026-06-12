<?php
$key = 'put_key_here'; // Will inject from .env
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.gemini.api_key');
$url = 'https://generativelanguage.googleapis.com/v1/models?key=' . $key;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (strpos($m['name'], 'flash') !== false) {
            echo $m['name'] . "\n";
        }
    }
} else {
    echo "Error: " . $res . "\n";
}
