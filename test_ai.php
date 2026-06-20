<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$fanny = App\Models\User::where("name", "like", "%Fanny%")->first();
$tasks = App\Models\DailyTaskEntry::where("user_id", $fanny->id)->whereMonth("task_date", 6)->with("aiEvaluation")->get();
foreach($tasks as $t) {
    if ($t->aiEvaluation) {
        echo "Found AI eval: " . $t->aiEvaluation->final_score . "\n";
    }
}
echo "Done\n";

