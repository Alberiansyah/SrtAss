<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $index = $_POST['index'];
    $newText = $_POST['text'];

    if (isset($_SESSION['subtitles'][$index])) {
        // Simpan teks asli yang dimodifikasi
        $_SESSION['subtitles'][$index]['text'] = $newText;

        // Terapkan replaceWords untuk menambahkan highlight
        require __DIR__ . '/functions.php';
        $highlightedText = replaceWords($newText);

        // Kembalikan teks yang sudah di-highlight
        echo $highlightedText;
    } else {
        echo 'error';
    }
}
