<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$type = Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM daily_task_entries WHERE Field = \"task_date\"");
print_r($type);

