<?php
session_start();
require __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $indices = [];
    if (isset($_POST['indices'])) {
        $indices = json_decode($_POST['indices'], true);
    } elseif (isset($_POST['index'])) {
        $indices = [$_POST['index']];
    }

    if (!empty($indices) && isset($_SESSION['subtitles'])) {
        rsort($indices);
        $deleted = 0;
        foreach ($indices as $index) {
            $index = (int)$index;
            if (isset($_SESSION['subtitles'][$index])) {
                array_splice($_SESSION['subtitles'], $index, 1);
                $deleted++;
            }
        }
        echo json_encode(['success' => true, 'deleted' => $deleted, 'count' => count($_SESSION['subtitles'])]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
