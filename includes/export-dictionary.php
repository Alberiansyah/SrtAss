<?php
session_start();
require_once __DIR__ . '/../functions.php';

$dictionary = $_SESSION['dictionary'] ?? [];

if (empty($dictionary)) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

ksort($dictionary, SORT_STRING | SORT_FLAG_CASE);

$format = $_GET['format'] ?? 'json';

if ($format === 'txt') {
    $content = "# Dictionary Export\n";
    $content .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($dictionary as $key => $value) {
        $content .= "$key=$value\n";
    }
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="dictionary_' . date('Y-m-d') . '.txt"');
} else {
    $content = json_encode($dictionary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="dictionary_' . date('Y-m-d') . '.json"');
}

echo $content;
exit;
