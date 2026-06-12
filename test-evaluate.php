<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.gemini.api_key');
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $key;
$prompt = 'Kembalikan HANYA JSON berikut tanpa teks lain: {"score_achievement": 8, "score_efficiency": 8, "score_contribution": 8, "score_problem_solving": 8, "ai_feedback": "Bagus"}';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature' => 0.2,
        'maxOutputTokens' => 512,
    ]
]));
$res = curl_exec($ch);
$data = json_decode($res, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'null';
echo "Raw Text:\n$text\n";
