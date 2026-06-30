<?php

namespace App\Services;

use App\Models\SubtitleCollection;

class TimingService
{
    public function shift(SubtitleCollection $subtitles, int $offsetMs): void
    {
        $subtitles->shiftTiming($offsetMs);
    }

    public function scaleToDuration(SubtitleCollection $subtitles, float $targetDuration): void
    {
        $currentDuration = $subtitles->getTotalDuration();
        if ($currentDuration <= 0) return;

        $ratio = $targetDuration / $currentDuration;
        $subtitles->scaleTiming($ratio);
    }
}