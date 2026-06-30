<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Response;
use App\Models\SubtitleCollection;
use App\Models\Subtitle;
use App\Services\WordReplacer;
use App\Models\Dictionary;

class ApiController
{
    private WordReplacer $wordReplacer;

    public function __construct()
    {
        $this->wordReplacer = new WordReplacer(new Dictionary());
    }

    // Update single subtitle (display.php)
    public function updateSubtitle(): void
    {
        $index = intval($_POST['index'] ?? -1);
        $text = $_POST['text'] ?? '';
        $subtitles = Session::get('subtitles', []);

        if (isset($subtitles[$index])) {
            $subtitles[$index]['text'] = $text;
            Session::set('subtitles', $subtitles);
            echo $this->wordReplacer->replace(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            exit;
        }
    }

    // Update batch subtitle (display-batch.php)
    public function updateBatchSubtitle(): void
    {
        $fileIndex = intval($_POST['file_index'] ?? -1);
        $index = intval($_POST['index'] ?? -1);
        $text = $_POST['text'] ?? '';
        $files = Session::get('batch_files', []);

        if (isset($files[$fileIndex]['subtitles'][$index])) {
            $files[$fileIndex]['subtitles'][$index]['text'] = $text;
            Session::set('batch_files', $files);
            $html = $this->wordReplacer->replace(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            Response::json(['success' => true, 'html' => $html]);
        }
        Response::error('Subtitle not found');
    }

    // Delete single subtitle
    public function deleteSubtitle(): void
    {
        $subtitles = Session::get('subtitles', []);

        if (isset($_POST['index'])) {
            $index = intval($_POST['index']);
            if (isset($subtitles[$index])) {
                array_splice($subtitles, $index, 1);
                Session::set('subtitles', $subtitles);
                Response::json(['success' => true, 'deleted' => 1, 'count' => count($subtitles)]);
            }
        } elseif (isset($_POST['indices'])) {
            $indices = json_decode($_POST['indices'], true);
            if (is_array($indices)) {
                rsort($indices);
                foreach ($indices as $i) {
                    if (isset($subtitles[$i])) {
                        array_splice($subtitles, $i, 1);
                    }
                }
                Session::set('subtitles', $subtitles);
                Response::json(['success' => true, 'deleted' => count($indices), 'count' => count($subtitles)]);
            }
        }
        Response::error('Invalid request');
    }

    // Delete batch subtitle
    public function deleteBatchSubtitle(): void
    {
        $fileIndex = intval($_POST['file_index'] ?? -1);
        $files = Session::get('batch_files', []);

        if (!isset($files[$fileIndex])) {
            Response::error('File not found');
        }

        if (isset($_POST['index'])) {
            $index = intval($_POST['index']);
            if (isset($files[$fileIndex]['subtitles'][$index])) {
                array_splice($files[$fileIndex]['subtitles'], $index, 1);
                Session::set('batch_files', $files);
                Response::json(['success' => true, 'deleted' => 1]);
            }
        } elseif (isset($_POST['indices'])) {
            $indices = json_decode($_POST['indices'], true);
            if (is_array($indices)) {
                rsort($indices);
                foreach ($indices as $i) {
                    if (isset($files[$fileIndex]['subtitles'][$i])) {
                        array_splice($files[$fileIndex]['subtitles'], $i, 1);
                    }
                }
                Session::set('batch_files', $files);
                Response::json(['success' => true, 'deleted' => count($indices)]);
            }
        }
        Response::error('Invalid request');
    }

    // Merge subtitles (single)
    public function mergeSubtitles(): void
    {
        $from = intval($_POST['from'] ?? -1);
        $to = intval($_POST['to'] ?? -1);
        $text = $_POST['text'] ?? '';
        $subtitles = Session::get('subtitles', []);

        $col = new SubtitleCollection($subtitles);
        if ($from >= 0 && $to < $col->count() && $from < $to) {
            $merged = $col->merge($from, $to, $text);
            $removed = $to - $from;
            Session::set('subtitles', $col->toArray());
            Response::json(['success' => true, 'merged' => 1, 'removed' => $removed, 'count' => $col->count()]);
        }
        Response::error('Invalid range');
    }

    // Merge batch subtitles
    public function mergeBatchSubtitles(): void
    {
        $fileIndex = intval($_POST['file_index'] ?? -1);
        $from = intval($_POST['from'] ?? -1);
        $to = intval($_POST['to'] ?? -1);
        $text = $_POST['text'] ?? '';
        $files = Session::get('batch_files', []);

        if (!isset($files[$fileIndex])) {
            Response::error('File not found');
        }

        $col = new SubtitleCollection($files[$fileIndex]['subtitles']);
        if ($from >= 0 && $to < $col->count() && $from < $to) {
            $col->merge($from, $to, $text);
            $files[$fileIndex]['subtitles'] = $col->toArray();
            Session::set('batch_files', $files);
            Response::json(['success' => true, 'merged' => 1]);
        }
        Response::error('Invalid range');
    }
}