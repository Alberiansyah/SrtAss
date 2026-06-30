<?php

namespace App\Services;

use App\Models\SubtitleCollection;
use App\Models\Subtitle;

class AssParser
{
    public function parse(string $content): array
    {
        $subtitles = [];
        $styles = [];
        $scriptInfo = '';
        $projectGarbage = '';
        $currentSection = '';
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = rtrim($line);

            if (preg_match('/^\[(.+)\]$/', $line, $m)) {
                $currentSection = $m[1];
                continue;
            }

            $line = trim($line);
            if (empty($line) || $line[0] === ';' || str_starts_with($line, 'Comment:')) continue;

            match ($currentSection) {
                'Script Info' => $scriptInfo .= $line . "\n",
                'V4+ Styles', 'V4 Styles' => $this->parseStyleLine($line, $styles),
                'Events' => $this->parseDialogueLine($line, $subtitles),
                'Aegisub Project Garbage' => $projectGarbage .= $line . "\n",
                default => null,
            };
        }

        return [
            'subtitles' => new SubtitleCollection($subtitles),
            'styles' => $styles,
            'scriptInfo' => trim($scriptInfo),
            'projectGarbage' => trim($projectGarbage),
        ];
    }

    private function parseStyleLine(string $line, array &$styles): void
    {
        if (str_starts_with($line, 'Format:')) return;
        if (str_starts_with($line, 'Style:')) {
            $styles[] = $line;
        }
    }

    private function parseDialogueLine(string $line, array &$subtitles): void
    {
        if (!str_starts_with($line, 'Dialogue:')) return;

        $parts = explode(',', $line, 10);
        if (count($parts) < 10) return;

        $subtitles[] = new Subtitle([
            'start' => trim($parts[1]),
            'end' => trim($parts[2]),
            'style' => trim($parts[3]),
            'text' => trim($parts[9]),
        ]);
    }
}