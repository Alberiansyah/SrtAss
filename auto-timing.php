<?php
session_start();
require __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';
$fileIndex = isset($_POST['file_index']) ? intval($_POST['file_index']) : -1;
$targetCps = isset($_POST['target_cps']) ? floatval($_POST['target_cps']) : 15;
$minDuration = isset($_POST['min_duration']) ? floatval($_POST['min_duration']) : 1.0;
$maxDuration = isset($_POST['max_duration']) ? floatval($_POST['max_duration']) : 7.0;

if ($fileIndex >= 0) {
    if (!isset($_SESSION['batch_files'][$fileIndex]['subtitles'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    $subtitles = &$_SESSION['batch_files'][$fileIndex]['subtitles'];
} else {
    if (!isset($_SESSION['subtitles'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    $subtitles = &$_SESSION['subtitles'];
}

switch ($action) {
    case 'normalize':
        // Adjust duration based on reading speed (CPS target)
        $adjusted = 0;
        $subCount = count($subtitles);
        for ($i = 0; $i < $subCount; $i++) {
            $sub = &$subtitles[$i];
            $text = preg_replace('/\{[^}]*\}/', '', $sub['text']);
            $text = str_replace(['\N', '\n', '\h'], ' ', $text);
            $charCount = mb_strlen(trim($text));
            $startSec = srtTimestampToSeconds($sub['start']);
            $endSec = srtTimestampToSeconds($sub['end']);
            $duration = $endSec - $startSec;

            // Calculate ideal duration based on CPS target
            $idealDuration = $charCount / $targetCps;

            if ($idealDuration < $minDuration) {
                $idealDuration = $minDuration;
            }
            if ($idealDuration > $maxDuration) {
                $idealDuration = $maxDuration;
            }

            // Don't exceed next subtitle's start
            $nextStart = ($i + 1 < $subCount) ? srtTimestampToSeconds($subtitles[$i + 1]['start']) : INF;

            // Only adjust if current duration is too short or too long
            if ($duration < $idealDuration * 0.7 || $duration > $idealDuration * 1.5) {
                // Adjust end time, clamped before the next subtitle and to a minimum length
                $newEndSec = min($startSec + $idealDuration, $nextStart - 0.05);
                $newEndSec = max($newEndSec, $startSec + 0.05);
                $sub['end'] = secondsToSrtTimestamp($newEndSec);
                $adjusted++;
            }
            unset($sub);
        }
        echo json_encode(['success' => true, 'adjusted' => $adjusted, 'subtitles' => $subtitles]);
        break;

    case 'shift_to_gaps':
        // Receive gap timestamps from client and shift subtitles
        $gaps = isset($_POST['gaps']) ? json_decode($_POST['gaps'], true) : [];
        if (empty($gaps)) {
            echo json_encode(['success' => false, 'error' => 'No gaps provided']);
            exit;
        }

        $shifted = 0;
        foreach ($subtitles as &$sub) {
            $startSec = srtTimestampToSeconds($sub['start']);
            $endSec = srtTimestampToSeconds($sub['end']);
            $duration = $endSec - $startSec;

            // Find nearest gap that fits the subtitle
            foreach ($gaps as $gap) {
                $gapStart = $gap['start'];
                $gapEnd = $gap['end'];
                $gapDuration = $gapEnd - $gapStart;

                // If gap is long enough for this subtitle
                if ($gapDuration >= $duration * 0.8 && $gapDuration <= $duration * 1.5) {
                    // Check if subtitle start is near this gap
                    if (abs($startSec - $gapStart) < 2.0) {
                        $sub['start'] = secondsToSrtTimestamp($gapStart);
                        $sub['end'] = secondsToSrtTimestamp(min($gapStart + $duration, $gapEnd));
                        $shifted++;
                        break;
                    }
                }
            }
        }
        unset($sub);
        echo json_encode(['success' => true, 'shifted' => $shifted, 'subtitles' => $subtitles]);
        break;

    case 'fix_overlaps':
        // Ensure no overlapping subtitles; repeat until stable (max 10 passes)
        $fixed = 0;
        $maxPasses = 10;
        for ($pass = 0; $pass < $maxPasses; $pass++) {
            $changed = false;
            $subCount = count($subtitles);
            for ($i = 1; $i < $subCount; $i++) {
                $prevEnd = srtTimestampToSeconds($subtitles[$i - 1]['end']);
                $currStart = srtTimestampToSeconds($subtitles[$i]['start']);
                $currEnd = srtTimestampToSeconds($subtitles[$i]['end']);
                $currDuration = $currEnd - $currStart;

                if ($currStart < $prevEnd) {
                    // Shift current subtitle after previous
                    $newStart = $prevEnd + 0.1; // 100ms gap
                    $subtitles[$i]['start'] = secondsToSrtTimestamp($newStart);
                    $subtitles[$i]['end'] = secondsToSrtTimestamp($newStart + $currDuration);
                    $fixed++;
                    $changed = true;
                }
            }
            if (!$changed) break;
        }
        echo json_encode(['success' => true, 'fixed' => $fixed, 'subtitles' => $subtitles]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
