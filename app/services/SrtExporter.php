<?php

namespace App\Services;

use App\Models\SubtitleCollection;

class SrtExporter
{
    public function export(SubtitleCollection $subtitles): string
    {
        $srt = '';
        $i = 1;
        foreach ($subtitles as $sub) {
            $text = $sub->text;
            $text = str_replace('{\\i1}', '<i>', $text);
            $text = str_replace('{\\i0}', '</i>', $text);
            $text = str_replace('{\\i}', '</i>', $text);

            $srt .= $i . "\n";
            $srt .= $sub->start . ' --> ' . $sub->end . "\n";
            $srt .= $text . "\n\n";
            $i++;
        }
        return trim($srt);
    }
}