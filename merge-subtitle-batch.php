<?php
session_start();
require __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['file_index']) && isset($_POST['indices'])) {
    $fileIndex = (int)$_POST['file_index'];
    $indices = json_decode($_POST['indices'], true);
    $keepIndex = isset($_POST['keep_index']) ? (int)$_POST['keep_index'] : null;

    if (is_array($indices) && count($indices) >= 2 && isset($_SESSION['batch_files'][$fileIndex]['subtitles'])) {
        $indices = array_map('intval', $indices);
        sort($indices);

        $valid = true;
        foreach ($indices as $i) {
            if (!isset($_SESSION['batch_files'][$fileIndex]['subtitles'][$i])) { $valid = false; break; }
        }

        if ($valid) {
            $first = $indices[0];
            $last = $indices[count($indices) - 1];

            if ($keepIndex !== null && in_array($keepIndex, $indices)) {
                $keptText = $_SESSION['batch_files'][$fileIndex]['subtitles'][$keepIndex]['text'];
            } else {
                $mergedText = '';
                $separator = '';
                foreach ($indices as $i) {
                    $mergedText .= $separator . $_SESSION['batch_files'][$fileIndex]['subtitles'][$i]['text'];
                    $separator = "\n";
                }
                $keptText = $mergedText;
            }

            $_SESSION['batch_files'][$fileIndex]['subtitles'][$first]['end'] = $_SESSION['batch_files'][$fileIndex]['subtitles'][$last]['end'];
            $_SESSION['batch_files'][$fileIndex]['subtitles'][$first]['text'] = $keptText;

            $removed = 0;
            $toRemove = array_slice($indices, 1);
            rsort($toRemove);
            foreach ($toRemove as $i) {
                array_splice($_SESSION['batch_files'][$fileIndex]['subtitles'], $i, 1);
                $removed++;
            }

            echo json_encode(['success' => true, 'merged' => count($indices), 'removed' => $removed, 'count' => count($_SESSION['batch_files'][$fileIndex]['subtitles'])]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
