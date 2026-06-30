<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../functions.php';

$isBatchMode = isset($_GET['batch_mode']) && ($_GET['batch_mode'] === 'true' || $_GET['batch_mode'] === '1');
$response = [];

if ($isBatchMode && isset($_SESSION['batch_files'])) {
    foreach ($_SESSION['batch_files'] as $fileIndex => $file) {
        $subtitles = $file['subtitles'] ?? [];
        $changes = getDictionaryChanges($subtitles);
        if (!empty($changes)) {
            $response[] = [
                'file_index' => $fileIndex,
                'file_name' => $file['file_name'] ?? 'Unknown',
                'changes' => $changes
            ];
        }
    }
} else {
    $subtitles = $_SESSION['subtitles'] ?? [];
    $response = getDictionaryChanges($subtitles);
}

echo json_encode($response);