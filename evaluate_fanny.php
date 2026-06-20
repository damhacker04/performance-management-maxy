<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$fanny = App\Models\User::where("name", "like", "%Fanny%")->first();
if (!$fanny) {
    die("Fanny not found\n");
}

$tasks = App\Models\DailyTaskEntry::where("user_id", $fanny->id)
    ->whereMonth("task_date", 6)
    ->whereYear("task_date", 2026)
    ->get();

echo "Evaluating " . $tasks->count() . " tasks for Fanny in June 2026...\n";

foreach ($tasks as $task) {
    echo "Processing Task ID " . $task->id . " - " . substr($task->task_description, 0, 20) . "...\n";
    try {
        App\Jobs\EvaluateDailyTaskJob::dispatchSync($task->id);
        echo " -> Success\n";
    } catch (\Exception $e) {
        echo " -> Failed: " . $e->getMessage() . "\n";
    }
}
echo "All done!\n";

