<?php
session_start();
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['index']) && isset($_POST['text'])) {
        // Indeks dikirim dalam format "fileIndex-subtitleIndex"
        $indices = explode('-', $_POST['index']);
        if (count($indices) == 2) {
            $fileIndex = (int)$indices[0];
            $subtitleIndex = (int)$indices[1];

            if (isset($_SESSION['batch_files'][$fileIndex]['subtitles'][$subtitleIndex])) {
                // Perbarui teks subtitle di session untuk file batch
                $_SESSION['batch_files'][$fileIndex]['subtitles'][$subtitleIndex]['text'] = $_POST['text'];
                // Terapkan penggantian kata dengan highlight untuk tampilan
                echo replaceWords($_POST['text'], true);
                exit;
            }
        }
    }
}
http_response_code(400);
echo 'Invalid request';
