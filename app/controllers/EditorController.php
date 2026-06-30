<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Response;
use App\Models\Dictionary;
use App\Models\SubtitleCollection;
use App\Services\SrtParser;
use App\Services\AssParser;
use App\Services\SrtExporter;
use App\Services\AssExporter;
use App\Services\WordReplacer;
use App\Services\WordDetector;
use App\Services\TimingService;
use App\Services\UndoService;

class EditorController
{
    private Dictionary $dictionary;
    private WordReplacer $wordReplacer;
    private WordDetector $wordDetector;
    private SrtExporter $srtExporter;
    private AssExporter $assExporter;
    private TimingService $timingService;
    private UndoService $undoService;

    public function __construct()
    {
        $this->dictionary = new Dictionary();
        $this->wordReplacer = new WordReplacer($this->dictionary);
        $this->wordDetector = new WordDetector();
        $this->srtExporter = new SrtExporter();
        $this->assExporter = new AssExporter();
        $this->timingService = new TimingService();
        $this->undoService = new UndoService();

        if (!Session::has('non_indonesian_words')) {
            Session::set('non_indonesian_words', []);
        }
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // File upload
        if (isset($_FILES['subtitle_file']) && $_FILES['subtitle_file']['error'] == UPLOAD_ERR_OK) {
            $this->handleUpload($_FILES['subtitle_file']);
            return;
        }

        // Dictionary
        if (isset($_POST['add_to_dictionary'])) {
            $this->handleAddDictionary();
            return;
        }
        if (isset($_POST['remove_from_dictionary'])) {
            $this->handleRemoveDictionary();
            return;
        }

        // Download
        if (isset($_POST['download'])) {
            $this->handleDownload();
            return;
        }

        // Clear session
        if (isset($_POST['clear_session'])) {
            $this->handleClearSession();
            return;
        }

        // Restore subtitles (undo/redo)
        if (isset($_POST['restore_subtitles'])) {
            $this->handleRestoreSubtitles();
            return;
        }

        // Shift timing
        if (isset($_POST['shift_timing'])) {
            $this->handleShiftTiming();
            return;
        }

        // Scale timing
        if (isset($_POST['scale_timing'])) {
            $this->handleScaleTiming();
            return;
        }
    }

    public function getSubtitleData(): array
    {
        $subtitles = Session::get('subtitles', []);
        $wordReplacer = $this->wordReplacer;

        return array_map(function ($s) use ($wordReplacer) {
            $s['text'] = $wordReplacer->replace($s['text'], false);
            return $s;
        }, $subtitles);
    }

    public function getNonIndonesianWords(): array
    {
        return Session::get('non_indonesian_words.single', []);
    }

    private function handleUpload(array $file): void
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $content = file_get_contents($file['tmp_name']);

        Session::set('file_name', pathinfo($file['name'], PATHINFO_FILENAME));
        Session::set('uploaded_file_name', $file['name']);

        if ($extension === 'srt') {
            $parser = new SrtParser();
            $subtitles = $parser->parse($content);
            Session::set('subtitles', $subtitles->toArray());
            Session::set('styles', []);
        } elseif ($extension === 'ass') {
            $parser = new AssParser();
            $result = $parser->parse($content);
            Session::set('subtitles', $result['subtitles']->toArray());
            Session::set('styles', $result['styles']);
            Session::set('scriptInfo', $result['scriptInfo']);
            Session::set('projectGarbage', $result['projectGarbage']);
        }
    }

    private function handleAddDictionary(): void
    {
        $key = trim($_POST['key']);
        $value = trim($_POST['value']);
        if (!empty($key) && !empty($value)) {
            $this->dictionary->set($key, $value);
            $this->dictionary->save();
        }
    }

    private function handleRemoveDictionary(): void
    {
        $key = $_POST['remove_from_dictionary'];
        if ($this->dictionary->has($key)) {
            $value = $this->dictionary->get($key);
            $this->restoreOriginalText($value, $key);
            $this->dictionary->remove($key);
            $this->dictionary->save();
        }
    }

    private function restoreOriginalText(string $oldValue, string $newKey): void
    {
        $subtitles = Session::get('subtitles', []);
        foreach ($subtitles as &$sub) {
            $sub['text'] = str_replace($oldValue, $newKey, $sub['text']);
        }
        Session::set('subtitles', $subtitles);
    }

    private function handleDownload(): void
    {
        $format = $_POST['format'] ?? '';
        if (!in_array($format, ['srt', 'ass'], true)) {
            Response::error('Invalid format');
        }

        $raw = Session::get('subtitles', []);
        foreach ($raw as &$s) {
            $s['text'] = $this->wordReplacer->replace($s['text'], false);
        }
        unset($s);
        $subtitles = new SubtitleCollection($raw);
        $subtitleType = $_POST['subtitle_type'] ?? 'anime';
        $fileName = Session::get('file_name', 'subtitles');

        if ($format === 'srt') {
            $content = $this->srtExporter->export($subtitles);
            Response::download($content, $fileName . '.srt', 'text/srt');
        } else {
            $styles = Session::get('styles', []);
            $scriptInfo = Session::get('scriptInfo', '');
            $projectGarbage = Session::get('projectGarbage', '');
            $content = $this->assExporter->export($subtitles, $subtitleType, $styles, $scriptInfo, $projectGarbage);
            Response::download($content, $fileName . '.ass', 'application/x-ansi');
        }
    }

    private function handleClearSession(): void
    {
        Session::destroy();
        Response::redirect('index.php');
    }

    private function handleRestoreSubtitles(): void
    {
        $data = json_decode($_POST['restore_subtitles'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $this->undoService->restoreSubtitles($data);
            Response::success();
        }
        Response::error('Invalid data');
    }

    private function handleShiftTiming(): void
    {
        $offsetMs = intval($_POST['shift_timing']);
        $subtitles = new SubtitleCollection(Session::get('subtitles', []));
        $this->timingService->shift($subtitles, $offsetMs);
        Session::set('subtitles', $subtitles->toArray());
        Response::success(['subtitles' => $subtitles->toArray()]);
    }

    private function handleScaleTiming(): void
    {
        $targetDuration = floatval($_POST['scale_timing']);
        $subtitles = new SubtitleCollection(Session::get('subtitles', []));
        $this->timingService->scaleToDuration($subtitles, $targetDuration);
        Session::set('subtitles', $subtitles->toArray());
        Response::success(['subtitles' => $subtitles->toArray()]);
    }
}