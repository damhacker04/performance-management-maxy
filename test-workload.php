<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $u = App\Models\User::first();
    auth()->login($u);
    $c = app()->make(App\Http\Controllers\WorkloadReportController::class);
    $req = Illuminate\Http\Request::create('/workload-report', 'GET');
    $c->index($req);
    echo "OK";
} catch (\Throwable $e) {
    echo "ERROR: " . get_class($e) . " - " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
