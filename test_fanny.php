<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$fanny = App\Models\User::where("name", "like", "%Fanny%")->first();
if ($fanny) {
    echo "Fanny ID: " . $fanny->id . "\n";
    $tasks = App\Models\DailyTaskEntry::where("user_id", $fanny->id)->get();
    echo "Total Tasks: " . $tasks->count() . "\n";
    foreach($tasks as $t) {
        echo "Task Date: " . ($t->task_date ? $t->task_date->format("Y-m-d") : "NULL") . " - " . substr($t->task_description,0,20) . "\n";
    }
} else {
    echo "Fanny not found\n";
}

