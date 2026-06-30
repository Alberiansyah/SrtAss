<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Response;
use App\Models\Dictionary;
use App\Models\SubtitleCollection;
use App\Models\BatchCollection;
use App\Models\BatchFile;
use App\Services\SrtParser;
use App\Services\AssParser;
use App\Services\SrtExporter;
use App\Services\AssExporter;
use App\Services\WordReplacer;
use App\Services\WordDetector;
use App\Services\TimingService;
use App\Services\UndoService;

class BatchEditorController
{
    private Dictionary $dictionary;
    private WordReplacer $wordReplacer;
    private SrtExporter $srtExporter;
    private AssExporter $assExporter;
    private TimingService $timingService;
    private UndoService $undoService;

    public function __construct()
    {
        $this->dictionary = new Dictionary();
        $this->wordReplacer = new WordReplacer($this->dictionary);
        $this->srtExporter = new SrtExporter();
        $this->assExporter = new AssExporter();
        $this->timingService = new TimingService();
        $this->undoService = new UndoService();
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // Upload
        if (isset($_FILES['subtitle_files'])) {
            $this->handleUpload();
            return;
        }

        // Batch download
        if (isset($_POST['batch_download'])) {
            $this->handleDownload();
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

        // Clear session
        if (isset($_POST['clear_session'])) {
            $this->handleClearSession();
            return;
        }

        // Restore batch subtitles
        if (isset($_POST['restore_subtitles_batch'])) {
            $this->handleRestoreSubtitles();
            return;
        }

        // Timing
        if (isset($_POST['shift_timing'])) {
            $this->handleShiftTiming();
            return;
        }
        if (isset($_POST['scale_timing'])) {
            $this->handleScaleTiming();
            return;
        }
    }

    public function getBatchData(): array
    {
        $files = Session::get('batch_files', []);
        $wordReplacer = $this->wordReplacer;

        foreach ($files as &$file) {
            foreach ($file['subtitles'] as &$sub) {
                $sub['text'] = $wordReplacer->replace($sub['text'], false);
            }
        }

        return $files;
    }

    private function handleUpload(): void
    {
        $batchFiles = [];

        foreach ($_FILES['subtitle_files']['error'] as $index => $error) {
            if ($error != UPLOAD_ERR_OK) continue;

            $fileName = $_FILES['subtitle_files']['name'][$index];
            $tmpName = $_FILES['subtitle_files']['tmp_name'][$index];
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $content = file_get_contents($tmpName);

            $file = new BatchFile([
                'uploaded_file_name' => $fileName,
                'file_name' => pathinfo($fileName, PATHINFO_FILENAME),
                'extension' => $extension,
                'format' => $extension,
            ]);

            if ($extension === 'srt') {
                $parser = new SrtParser();
                $file->subtitles = $parser->parse($content);
            } elseif ($extension === 'ass') {
                $parser = new AssParser();
                $result = $parser->parse($content);
                $file->subtitles = $result['subtitles'];
                $file->styles = $result['styles'];
                $file->scriptInfo = $result['scriptInfo'];
                $file->projectGarbage = $result['projectGarbage'];
                $file->format = 'ass';
            }

            $batchFiles[] = $file->toArray();
        }

        foreach ($batchFiles as &$f) {
            $f['log_file'] = 'content/logs/' . preg_replace(
                ['/[^\w\s\-+\[\]]/', '/\s+\./'],
                [' ', '.'],
                $f['file_name']
            ) . '.log';
        }

        Session::set('batch_files', $batchFiles);
    }

    private function handleDownload(): void
    {
        $format = $_POST['format'] ?? '';
        $subtitleType = $_POST['subtitle_type'] ?? 'anime';
        $batchFiles = Session::get('batch_files', []);

        if (empty($batchFiles)) return;

        $zip = new \ZipArchive();
        $tmpFile = tempnam(sys_get_temp_dir(), 'zip');

        if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            Response::error('Failed to create ZIP');
        }

        foreach ($batchFiles as $file) {
            $subtitlesData = $file['subtitles'] ?? [];
            foreach ($subtitlesData as &$s) {
                $s['text'] = $this->wordReplacer->replace($s['text'], false);
            }
            unset($s);
            $subtitles = new SubtitleCollection($subtitlesData);

            if ($format === 'srt') {
                $content = $this->srtExporter->export($subtitles);
                $ext = 'srt';
            } else {
                $styles = $file['styles'] ?? [];
                $scriptInfo = $file['scriptInfo'] ?? '';
                $projectGarbage = $file['projectGarbage'] ?? '';
                $content = $this->assExporter->export($subtitles, $subtitleType, $styles, $scriptInfo, $projectGarbage);
                $ext = 'ass';
            }

            $zip->addFromString($file['file_name'] . '.' . $ext, $content);
        }

        $zip->close();

        $zipName = 'batch_converted_' . time() . '.zip';
        Response::downloadFile($tmpFile, $zipName);
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
        $files = Session::get('batch_files', []);
        foreach ($files as &$file) {
            foreach ($file['subtitles'] as &$sub) {
                $sub['text'] = str_replace($oldValue, $newKey, $sub['text']);
            }
        }
        Session::set('batch_files', $files);
    }

    private function handleClearSession(): void
    {
        Session::destroy();
        Response::redirect('index.php');
    }

    private function handleRestoreSubtitles(): void
    {
        $data = json_decode($_POST['restore_subtitles_batch'], true);
        $fileIndex = intval($_POST['file_index'] ?? -1);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $this->undoService->restoreBatchSubtitles($data, $fileIndex);
            Response::success();
        }
        Response::error('Invalid data');
    }

    private function handleShiftTiming(): void
    {
        $fileIndex = intval($_POST['file_index'] ?? -1);
        $offsetMs = intval($_POST['shift_timing']);
        $files = Session::get('batch_files', []);

        if (isset($files[$fileIndex])) {
            $subtitles = new SubtitleCollection($files[$fileIndex]['subtitles']);
            $this->timingService->shift($subtitles, $offsetMs);
            $files[$fileIndex]['subtitles'] = $subtitles->toArray();
            Session::set('batch_files', $files);
            Response::success(['subtitles' => $subtitles->toArray()]);
        }
        Response::error('File not found');
    }

    private function handleScaleTiming(): void
    {
        $fileIndex = intval($_POST['file_index'] ?? -1);
        $targetDuration = floatval($_POST['scale_timing']);
        $files = Session::get('batch_files', []);

        if (isset($files[$fileIndex])) {
            $subtitles = new SubtitleCollection($files[$fileIndex]['subtitles']);
            $this->timingService->scaleToDuration($subtitles, $targetDuration);
            $files[$fileIndex]['subtitles'] = $subtitles->toArray();
            Session::set('batch_files', $files);
            Response::success(['subtitles' => $subtitles->toArray()]);
        }
        Response::error('File not found');
    }
}