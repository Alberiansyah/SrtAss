<?php
session_start();
require __DIR__ . '/functions.php';

header('Content-Type: application/json');

$fromIndex = isset($_POST['from_index']) ? intval($_POST['from_index']) : -1;
$toIndex = isset($_POST['to_index']) ? intval($_POST['to_index']) : -1;
$fileIndex = isset($_POST['file_index']) ? intval($_POST['file_index']) : -1;

if ($fileIndex >= 0) {
    // Batch mode
    if (!isset($_SESSION['batch_files'][$fileIndex]['subtitles'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    $subtitles = &$_SESSION['batch_files'][$fileIndex]['subtitles'];
} else {
    // Single mode
    if (!isset($_SESSION['subtitles'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    $subtitles = &$_SESSION['subtitles'];
}

if ($fromIndex < 0 || $toIndex < 0 || $fromIndex >= count($subtitles) || $toIndex >= count($subtitles)) {
    echo json_encode(['success' => false, 'error' => 'Invalid indices']);
    exit;
}

$item = array_splice($subtitles, $fromIndex, 1)[0];
array_splice($subtitles, $toIndex, 0, [$item]);

echo json_encode(['success' => true, 'subtitles' => $subtitles]);
