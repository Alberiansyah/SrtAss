<?php
session_start();
require __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['file_index']) && isset($_POST['index']) && isset($_POST['text'])) {
        $fileIndex = (int)$_POST['file_index'];
        $subtitleIndex = (int)$_POST['index'];

        if (isset($_SESSION['batch_files'][$fileIndex]['subtitles'][$subtitleIndex])) {
            $_SESSION['batch_files'][$fileIndex]['subtitles'][$subtitleIndex]['text'] = $_POST['text'];
            $highlighted = replaceWords($_POST['text'], true);
            echo json_encode(['success' => true, 'html' => $highlighted]);
            exit;
        }
    }
}
echo json_encode(['success' => false, 'error' => 'Invalid request']);
