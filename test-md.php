<?php
$text = "```json\n{\"score_achievement\": 8, \"score_efficiency\": 8, \"score_contribution\": 8, \"score_problem_solving\": 8, \"ai_feedback\": \"Bagus\"}\n```";
$text = preg_replace('/```(?:json)?\n?(.*?)\n?```/s', '$1', $text);
$text = trim($text);
var_dump(json_decode($text, true));
