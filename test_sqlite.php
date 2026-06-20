<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$type = Illuminate\Support\Facades\DB::select("PRAGMA table_info(daily_task_entries);");
print_r($type);
$fanny = App\Models\User::where("name", "like", "%Fanny%")->first();
$tasks1 = App\Models\DailyTaskEntry::where("user_id", $fanny->id)->whereYear("task_date", 2026)->whereMonth("task_date", 6)->get();
echo "Count with whereMonth: " . $tasks1->count() . "\n";
$tasks2 = App\Models\DailyTaskEntry::where("user_id", $fanny->id)->where("task_date", "like", "2026-06%")->get();
echo "Count with LIKE: " . $tasks2->count() . "\n";

