<?php
session_start();
require __DIR__ . '/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$fileIndex = isset($_GET['file_index']) ? intval($_GET['file_index']) : (isset($_POST['file_index']) ? intval($_POST['file_index']) : -1);

switch ($action) {
    case 'list':
        $history = $_SESSION['version_history'] ?? [];
        $filtered = [];
        foreach ($history as $v) {
            if ($fileIndex >= 0) {
                if (($v['file_index'] ?? -1) === $fileIndex) {
                    $filtered[] = $v;
                }
            } else {
                $filtered[] = $v;
            }
        }
        $summary = array_map(function($v) {
            return [
                'id' => $v['id'],
                'timestamp' => $v['timestamp'],
                'label' => $v['label'],
                'count' => count($v['subtitles'] ?? []),
                'file_index' => $v['file_index'] ?? -1
            ];
        }, $filtered);
        echo json_encode(['success' => true, 'versions' => array_reverse($summary)]);
        break;

    case 'snapshot':
        $label = $_POST['label'] ?? 'Manual snapshot';
        if (!isset($_SESSION['version_history'])) {
            $_SESSION['version_history'] = [];
        }
        $id = count($_SESSION['version_history']) + 1;

        if ($fileIndex >= 0) {
            // Batch mode: save specific file's subtitles
            $subtitles = $_SESSION['batch_files'][$fileIndex]['subtitles'] ?? [];
        } else {
            $subtitles = $_SESSION['subtitles'] ?? [];
        }

        $_SESSION['version_history'][] = [
            'id' => $id,
            'timestamp' => date('Y-m-d H:i:s'),
            'label' => $label,
            'subtitles' => $subtitles,
            'file_index' => $fileIndex >= 0 ? $fileIndex : -1
        ];
        if (count($_SESSION['version_history']) > 50) {
            array_shift($_SESSION['version_history']);
        }
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'restore':
        $id = intval($_POST['id'] ?? 0);
        $history = $_SESSION['version_history'] ?? [];
        $found = null;
        foreach ($history as $v) {
            if ($v['id'] === $id) {
                $found = $v;
                break;
            }
        }
        if ($found) {
            $autoId = count($history) + 1;
            // Save current state first
            if ($fileIndex >= 0) {
                $currentSubtitles = $_SESSION['batch_files'][$fileIndex]['subtitles'] ?? [];
            } else {
                $currentSubtitles = $_SESSION['subtitles'] ?? [];
            }
            $_SESSION['version_history'][] = [
                'id' => $autoId,
                'timestamp' => date('Y-m-d H:i:s'),
                'label' => 'Before restore to #' . $id,
                'subtitles' => $currentSubtitles,
                'file_index' => $found['file_index'] ?? -1
            ];

            if ($fileIndex >= 0) {
                $_SESSION['batch_files'][$fileIndex]['subtitles'] = $found['subtitles'];
            } else {
                $_SESSION['subtitles'] = $found['subtitles'];
            }
            echo json_encode(['success' => true, 'subtitles' => $found['subtitles'], 'file_index' => $found['file_index'] ?? -1]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Version not found']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
