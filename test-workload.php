<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $u = App\Models\User::first();
    auth()->login($u);
    $c = app()->make(App\Http\Controllers\WorkloadReportController::class);
    $staff = App\Models\User::find(6); // Anisa has ID 6 maybe? Or ID 20? Let's check. 
    $c->show($staff, 6, 2026);
    echo "OK";
} catch (\Throwable $e) {
    echo "ERROR: " . get_class($e) . " - " . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}
