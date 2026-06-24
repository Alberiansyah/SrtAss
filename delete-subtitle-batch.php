<?php
session_start();
require __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fileIndex = isset($_POST['file_index']) ? (int)$_POST['file_index'] : -1;
    $indices = [];
    if (isset($_POST['indices'])) {
        $indices = json_decode($_POST['indices'], true);
    } elseif (isset($_POST['index'])) {
        $indices = [$_POST['index']];
    }

    if ($fileIndex >= 0 && !empty($indices) && isset($_SESSION['batch_files'][$fileIndex]['subtitles'])) {
        rsort($indices);
        $deleted = 0;
        foreach ($indices as $index) {
            $index = (int)$index;
            if (isset($_SESSION['batch_files'][$fileIndex]['subtitles'][$index])) {
                array_splice($_SESSION['batch_files'][$fileIndex]['subtitles'], $index, 1);
                $deleted++;
            }
        }
        echo json_encode(['success' => true, 'deleted' => $deleted, 'count' => count($_SESSION['batch_files'][$fileIndex]['subtitles'])]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
