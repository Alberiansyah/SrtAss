<?php
session_start();
header('Content-Type: application/json');

$isBatchMode = isset($_GET['batch_mode']) && $_GET['batch_mode'] == 'true';
$response = [];

if ($isBatchMode && isset($_SESSION['batch_files'])) {
    foreach ($_SESSION['batch_files'] as $fileIndex => $file) {
        $fileKey = 'file_' . $fileIndex;
        $fileWords = isset($_SESSION['non_indonesian_words'][$fileKey]) ?
            array_values($_SESSION['non_indonesian_words'][$fileKey]) : [];

        // Filter hanya kata unik untuk file ini
        $uniqueWords = [];
        $seenWords = [];

        foreach ($fileWords as $wordData) {
            $wordKey = $wordData['word'] . '|' . $wordData['line'];
            if (!isset($seenWords[$wordKey])) {
                $uniqueWords[] = $wordData;
                $seenWords[$wordKey] = true;
            }
        }

        usort($uniqueWords, function ($a, $b) {
            return $a['line'] - $b['line'];
        });

        $response[] = [
            'file_index' => $fileIndex,
            'file_name' => $file['file_name'],
            'words' => $uniqueWords
        ];
    }
} else {
    $fileWords = isset($_SESSION['non_indonesian_words']['single']) ?
        array_values($_SESSION['non_indonesian_words']['single']) : [];

    // Filter duplikat untuk mode single file
    $uniqueWords = [];
    $seenWords = [];

    foreach ($fileWords as $wordData) {
        $wordKey = $wordData['word'] . '|' . $wordData['line'];
        if (!isset($seenWords[$wordKey])) {
            $uniqueWords[] = $wordData;
            $seenWords[$wordKey] = true;
        }
    }

    usort($uniqueWords, function ($a, $b) {
        return $a['line'] - $b['line'];
    });

    $response = $uniqueWords;
}

echo json_encode($response);
