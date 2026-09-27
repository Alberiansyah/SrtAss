<?php
session_start();
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$historyFile = __DIR__ . '/data/version-history.json';
$maxEntries = 100;

if (!function_exists('loadHistory')) {
function loadHistory($file) {
    if (!file_exists($file)) return ['next_id' => 1, 'entries' => []];
    $fh = fopen($file, 'r');
    if (!$fh) return ['next_id' => 1, 'entries' => []];
    flock($fh, LOCK_SH);
    $data = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $decoded = json_decode($data, true);
    return is_array($decoded) ? $decoded : ['next_id' => 1, 'entries' => []];
}
}

if (!function_exists('saveHistory')) {
function saveHistory($file, $data) {
    $fh = fopen($file, 'c+');
    if (!$fh) return false;
    flock($fh, LOCK_EX);
    ftruncate($fh, 0);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}
}

if (!function_exists('updateHistory')) {
function updateHistory($file, callable $mutator) {
    $fh = fopen($file, 'c+');
    if (!$fh) return ['next_id' => 1, 'entries' => []];

    flock($fh, LOCK_EX);

    $contents = stream_get_contents($fh);
    $data = json_decode($contents, true);
    if (!is_array($data)) {
        $data = ['next_id' => 1, 'entries' => []];
    }
    if (!isset($data['entries']) || !is_array($data['entries'])) {
        $data['entries'] = [];
    }
    if (!isset($data['next_id'])) {
        $data['next_id'] = 1;
    }

    $result = $mutator($data);

    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);

    return $result;
}
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$fileIndex = isset($_GET['file_index']) ? intval($_GET['file_index']) : (isset($_POST['file_index']) ? intval($_POST['file_index']) : -1);

switch ($action) {
    case 'list':
        $data = loadHistory($historyFile);
        $entries = $data['entries'];
        $search = $_GET['search'] ?? '';
        $filtered = [];

        foreach ($entries as $v) {
            if ($fileIndex >= 0 && ($v['file_index'] ?? -1) !== $fileIndex) continue;
            if ($search !== '') {
                $label = mb_strtolower($v['label'] ?? '');
                $searchLower = mb_strtolower($search);
                if (strpos($label, $searchLower) === false) continue;
            }
            $filtered[] = $v;
        }

        $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 200) : 50;
        $offset = isset($_GET['offset']) ? max(intval($_GET['offset']), 0) : 0;

        $total = count($filtered);
        $reversed = array_reverse($filtered);
        $page = array_slice($reversed, $offset, $limit);

        $summary = array_map(function($v) {
            return [
                'id' => $v['id'],
                'timestamp' => $v['timestamp'],
                'label' => $v['label'],
                'type' => $v['type'] ?? 'manual',
                'count' => count($v['subtitles'] ?? []),
                'file_index' => $v['file_index'] ?? -1
            ];
        }, $page);

        echo json_encode([
            'success' => true,
            'versions' => $summary,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit
        ]);
        break;

    case 'snapshot':
        $label = $_POST['label'] ?? 'Manual snapshot';

        if ($fileIndex >= 0) {
            $subtitles = $_SESSION['batch_files'][$fileIndex]['subtitles'] ?? [];
        } else {
            $subtitles = $_SESSION['subtitles'] ?? [];
        }

        $newId = updateHistory($historyFile, function (&$data) use ($label, $subtitles, $fileIndex, $maxEntries) {
            $id = $data['next_id'];

            $data['entries'][] = [
                'id' => $id,
                'timestamp' => date('Y-m-d H:i:s'),
                'label' => $label,
                'type' => 'manual',
                'subtitles' => $subtitles,
                'file_index' => $fileIndex >= 0 ? $fileIndex : -1
            ];
            $data['next_id'] = $id + 1;

            if (count($data['entries']) > $maxEntries) {
                $data['entries'] = array_slice($data['entries'], count($data['entries']) - $maxEntries);
            }

            return $id;
        });

        echo json_encode(['success' => true, 'id' => $newId]);
        break;

    case 'restore':
        $id = intval($_POST['id'] ?? 0);

        if ($fileIndex >= 0) {
            $currentSubtitles = $_SESSION['batch_files'][$fileIndex]['subtitles'] ?? [];
        } else {
            $currentSubtitles = $_SESSION['subtitles'] ?? [];
        }

        $found = updateHistory($historyFile, function (&$data) use ($id, $currentSubtitles, $maxEntries) {
            $match = null;
            foreach ($data['entries'] as $v) {
                if ($v['id'] === $id) {
                    $match = $v;
                    break;
                }
            }

            if ($match === null) {
                return null;
            }

            $autoId = $data['next_id'];

            $data['entries'][] = [
                'id' => $autoId,
                'timestamp' => date('Y-m-d H:i:s'),
                'label' => 'Before restore to #' . $id,
                'type' => 'auto',
                'subtitles' => $currentSubtitles,
                'file_index' => $match['file_index'] ?? -1
            ];
            $data['next_id'] = $autoId + 1;

            if (count($data['entries']) > $maxEntries) {
                $data['entries'] = array_slice($data['entries'], count($data['entries']) - $maxEntries);
            }

            return $match;
        });

        if ($found) {
            if ($fileIndex >= 0) {
                $_SESSION['batch_files'][$fileIndex]['subtitles'] = $found['subtitles'];
            } else {
                $_SESSION['subtitles'] = $found['subtitles'];
            }

            echo json_encode([
                'success' => true,
                'subtitles' => $found['subtitles'],
                'file_index' => $found['file_index'] ?? -1
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Version not found']);
        }
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);

        $deleted = updateHistory($historyFile, function (&$data) use ($id) {
            foreach ($data['entries'] as $k => $v) {
                if ($v['id'] === $id) {
                    array_splice($data['entries'], $k, 1);
                    return true;
                }
            }
            return false;
        });

        if ($deleted) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Version not found']);
        }
        break;

    case 'clear':
        updateHistory($historyFile, function (&$data) use ($fileIndex) {
            if ($fileIndex >= 0) {
                $data['entries'] = array_values(array_filter($data['entries'], function($v) use ($fileIndex) {
                    return ($v['file_index'] ?? -1) !== $fileIndex;
                }));
            } else {
                $data['entries'] = [];
            }
        });

        echo json_encode(['success' => true]);
        break;

    case 'diff':
        $id1 = intval($_GET['id1'] ?? 0);
        $id2 = intval($_GET['id2'] ?? 0);
        $data = loadHistory($historyFile);
        $v1 = null;
        $v2 = null;

        // id1=0 means compare with current state
        if ($id1 !== 0) {
            foreach ($data['entries'] as $v) {
                if ($v['id'] === $id1) { $v1 = $v; break; }
            }
        }

        foreach ($data['entries'] as $v) {
            if ($v['id'] === $id2) { $v2 = $v; break; }
        }

        if (!$v2) {
            echo json_encode(['success' => false, 'error' => 'Version not found']);
            break;
        }

        if ($fileIndex >= 0) {
            $currentSubtitles = $_SESSION['batch_files'][$fileIndex]['subtitles'] ?? [];
        } else {
            $currentSubtitles = $_SESSION['subtitles'] ?? [];
        }

        $subs1 = ($v1 !== null) ? ($v1['subtitles'] ?? []) : $currentSubtitles;
        $subs2 = $v2['subtitles'] ?? [];

        $map1 = [];
        foreach ($subs1 as $i => $s) { $map1[$i] = $s['text'] ?? ''; }
        $map2 = [];
        foreach ($subs2 as $i => $s) { $map2[$i] = $s['text'] ?? ''; }

        $allIndices = array_unique(array_merge(array_keys($map1), array_keys($map2)));
        sort($allIndices);

        $onlyIn1 = [];
        $onlyIn2 = [];
        $modified = [];

        foreach ($allIndices as $idx) {
            $t1 = $map1[$idx] ?? null;
            $t2 = $map2[$idx] ?? null;
            if ($t1 === null && $t2 !== null) {
                $onlyIn2[] = ['index' => $idx, 'text' => $t2];
            } elseif ($t1 !== null && $t2 === null) {
                $onlyIn1[] = ['index' => $idx, 'text' => $t1];
            } elseif ($t1 !== $t2) {
                $modified[] = ['index' => $idx, 'old' => $t1, 'new' => $t2];
            }
        }

        echo json_encode([
            'success' => true,
            'v1' => $v1 ? ['id' => $v1['id'], 'label' => $v1['label'], 'timestamp' => $v1['timestamp']] : ['id' => 0, 'label' => 'Current state', 'timestamp' => date('Y-m-d H:i:s')],
            'v2' => ['id' => $v2['id'], 'label' => $v2['label'], 'timestamp' => $v2['timestamp']],
            'added' => $onlyIn2,
            'removed' => $onlyIn1,
            'modified' => $modified,
            'total_changes' => count($onlyIn1) + count($onlyIn2) + count($modified)
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
