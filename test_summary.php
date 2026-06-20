<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ctrl = app(App\Http\Controllers\WorkloadReportController::class);
$fanny = App\Models\User::where("name", "like", "%Fanny%")->first();

$reflection = new \ReflectionClass($ctrl);
$method = $reflection->getMethod("buildStaffSummary");
$method->setAccessible(true);
$summary = $method->invoke($ctrl, $fanny, 6, 2026);
print_r($summary);

$method2 = $reflection->getMethod("buildFullStaffData");
$method2->setAccessible(true);
$full = $method2->invoke($ctrl, $fanny, 6, 2026);
echo "Full data task count: " . $full["task_count"] . "\n";

