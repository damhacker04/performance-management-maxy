<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\EvaluateDailyTaskJob;
use App\Models\DailyTaskEntry;

$entries = DailyTaskEntry::whereDoesntHave('aiEvaluation')->latest()->take(5)->get();
echo 'Entries without AI evaluation: ' . $entries->count() . PHP_EOL;
foreach($entries as $e) {
    EvaluateDailyTaskJob::dispatch($e->id)->onQueue('default');
    echo 'Dispatched job for entry ID: ' . $e->id . ' - ' . $e->task_description . PHP_EOL;
}
