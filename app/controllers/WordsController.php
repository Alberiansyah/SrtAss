<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Response;
use App\Models\Dictionary;
use App\Models\SubtitleCollection;
use App\Services\WordDetector;
use App\Services\WordReplacer;

class WordsController
{
    public function getUnknownWords(): void
    {
        $isBatchMode = isset($_GET['batch_mode']) && ($_GET['batch_mode'] === 'true' || $_GET['batch_mode'] === '1');
        $response = [];

        if ($isBatchMode) {
            $files = Session::get('batch_files', []);
            foreach ($files as $fileIndex => $file) {
                $fileKey = 'file_' . $fileIndex;
                $fileWords = Session::get("non_indonesian_words.$fileKey", []);
                $unique = $this->uniqueWords($fileWords);
                $response[] = [
                    'file_index' => $fileIndex,
                    'file_name' => $file['file_name'] ?? 'Unknown',
                    'words' => $unique,
                ];
            }
        } else {
            $fileWords = Session::get('non_indonesian_words.single', []);
            $response = $this->uniqueWords($fileWords);
        }

        Response::json($response);
    }

    public function getDictionaryChanges(): void
    {
        $isBatchMode = isset($_GET['batch_mode']) && ($_GET['batch_mode'] === 'true' || $_GET['batch_mode'] === '1');
        $dict = new Dictionary();
        $detector = new WordDetector();

        if ($isBatchMode) {
            $files = Session::get('batch_files', []);
            $response = [];
            foreach ($files as $fileIndex => $file) {
                $subtitles = new SubtitleCollection($file['subtitles'] ?? []);
                $changes = $detector->getDictionaryChanges($subtitles, $dict);
                if (!empty($changes)) {
                    $response[] = [
                        'file_index' => $fileIndex,
                        'file_name' => $file['file_name'] ?? 'Unknown',
                        'changes' => $changes,
                    ];
                }
            }
            Response::json($response);
        } else {
            $subtitles = new SubtitleCollection(Session::get('subtitles', []));
            $changes = $detector->getDictionaryChanges($subtitles, $dict);
            Response::json($changes);
        }
    }

    private function uniqueWords(array $words): array
    {
        $unique = [];
        $seen = [];
        usort($words, fn($a, $b) => ($a['line'] ?? 0) - ($b['line'] ?? 0));

        foreach ($words as $w) {
            $key = ($w['word'] ?? '') . '|' . ($w['line'] ?? '');
            if (!isset($seen[$key])) {
                $unique[] = $w;
                $seen[$key] = true;
            }
        }

        return $unique;
    }
}