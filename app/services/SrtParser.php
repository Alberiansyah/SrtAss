<?php

namespace App\Services;

use App\Models\SubtitleCollection;
use App\Models\Subtitle;

class SrtParser
{
    public function parse(string $content): SubtitleCollection
    {
        $lines = explode("\n", $content);
        $subtitles = [];
        $i = 0;
        $total = count($lines);

        while ($i < $total) {
            $i++;
            if ($i >= $total) break;

            $timeLine = trim($lines[$i] ?? '');
            if (!preg_match('/(\d{2}:\d{2}:\d{2}[,\.]\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2}[,\.]\d{3})/', $timeLine, $matches)) {
                continue;
            }

            $start = $matches[1];
            $end = $matches[2];
            $i++;
            $text = '';
            while ($i < $total && trim($lines[$i]) !== '') {
                $text .= ($text !== '' ? "\n" : '') . $lines[$i];
                $i++;
            }

            $subtitles[] = new Subtitle([
                'start' => $start,
                'end' => $end,
                'text' => $text,
            ]);
        }

        return new SubtitleCollection($subtitles);
    }
}