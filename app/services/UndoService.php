<?php

namespace App\Services;

use App\Models\SubtitleCollection;
use App\Core\Session;

class UndoService
{
    public function restoreSubtitles(array $state): void
    {
        Session::set('subtitles', $state);
    }

    public function restoreBatchSubtitles(array $state, int $fileIndex): void
    {
        $batch = Session::get('batch_files', []);
        if (isset($batch[$fileIndex])) {
            $batch[$fileIndex]['subtitles'] = $state;
            Session::set('batch_files', $batch);
        }
    }
}