<?php
session_start();

$isBatchMode = isset($_GET['batch_mode']);
$content = '';
$filename = '';

if ($isBatchMode && isset($_SESSION['batch_files'])) {
    // Mode batch - buat ZIP berisi file terpisah
    $zip = new ZipArchive();
    $zipFilename = 'kata_tidak_dikenali_batch_' . date('Y-m-d') . '.zip';
    $tempDir = sys_get_temp_dir() . '/subtitle_export/';

    // Buat direktori temporary
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Bersihkan direktori temporary
    array_map('unlink', glob($tempDir . '*'));

    if ($zip->open($tempDir . $zipFilename, ZipArchive::CREATE) === TRUE) {
        foreach ($_SESSION['batch_files'] as $fileIndex => $file) {
            $fileKey = 'file_' . $fileIndex;
            $fileContent = "Daftar Kata Tidak Dikenali\n";
            $fileContent .= $file['file_name'] . "\n";
            $fileContent .= "====================================\n\n";

            if (
                isset($_SESSION['non_indonesian_words'][$fileKey]) &&
                !empty($_SESSION['non_indonesian_words'][$fileKey])
            ) {

                $words = $_SESSION['non_indonesian_words'][$fileKey];

                // Urutkan berdasarkan nomor baris
                usort($words, function ($a, $b) {
                    return $a['line'] - $b['line'];
                });

                foreach ($words as $wordData) {
                    $fileContent .= "[No {$wordData['line']}] {$wordData['word']}\n";
                }
            } else {
                $fileContent .= "Tidak ada kata yang tidak dikenali\n";
            }

            // Simpan ke file temporary
            $txtFilename = preg_replace(['/[^\w\s\-+\[\]]/', '/\s+\./'], [' ', '.'], $file['file_name']) . '.txt';
            file_put_contents($tempDir . $txtFilename, $fileContent);

            // Tambahkan ke ZIP
            $zip->addFile($tempDir . $txtFilename, $txtFilename);
        }

        $zip->close();

        // Set header untuk download ZIP
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . filesize($tempDir . $zipFilename));

        // Output file ZIP
        readfile($tempDir . $zipFilename);

        // Hapus file temporary
        array_map('unlink', glob($tempDir . '*'));
        rmdir($tempDir);

        exit;
    } else {
        die('Gagal membuat file ZIP');
    }
} else {
    // Mode single file
    $fileKey = isset($_SESSION['batch_files']) ? 'file_0' : 'single';
    $content = "Daftar Kata Tidak Dikenali\n";
    $content .= "====================================\n\n";

    if (
        isset($_SESSION['non_indonesian_words'][$fileKey]) &&
        !empty($_SESSION['non_indonesian_words'][$fileKey])
    ) {

        $words = $_SESSION['non_indonesian_words'][$fileKey];

        // Urutkan berdasarkan nomor baris
        usort($words, function ($a, $b) {
            return $a['line'] - $b['line'];
        });

        foreach ($words as $wordData) {
            $content .= "[No {$wordData['line']}] {$wordData['word']}\n";
        }
    } else {
        $content .= "Tidak ada kata yang tidak dikenali\n";
    }

    $currentFileName = $_SESSION['file_name'] ?? 'unknown';
    $filename = preg_replace(['/[^\w\s\-+\[\]]/', '/\s+\./'], [' ', '.'], $currentFileName) . '.txt';

    // Set header untuk download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));

    echo $content;
    exit;
}
