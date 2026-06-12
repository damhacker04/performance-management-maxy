<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GeminiService;

$gemini = app(GeminiService::class);
$reflection = new ReflectionClass(GeminiService::class);
$method = $reflection->getMethod('callGroq'); // Updated to callGroq
$method->setAccessible(true);
$prompt = 'Kembalikan HANYA JSON berikut tanpa teks lain: {"score_achievement": 8, "score_efficiency": 8, "score_contribution": 8, "score_problem_solving": 8, "ai_feedback": "Bagus"}';
$res = $method->invokeArgs($gemini, [$prompt]);
echo "Result of callGroq:\n";
var_dump($res);
