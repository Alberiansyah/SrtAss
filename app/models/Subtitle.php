<?php

namespace App\Models;

class Subtitle
{
    public string $start;
    public string $end;
    public string $text;
    public string $style;
    public int $number;

    public function __construct(array $data = [])
    {
        $this->start = $data['start'] ?? '00:00:00,000';
        $this->end = $data['end'] ?? '00:00:01,000';
        $this->text = $data['text'] ?? '';
        $this->style = $data['style'] ?? 'Default';
        $this->number = $data['number'] ?? 0;
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
            'text' => $this->text,
            'style' => $this->style,
        ];
    }

    public function getDurationSeconds(): float
    {
        return self::timestampToSeconds($this->end) - self::timestampToSeconds($this->start);
    }

    public static function timestampToSeconds(string $ts): float
    {
        if (str_contains($ts, ',')) {
            $parts = explode(',', $ts);
            $time = $parts[0];
            $ms = (int)$parts[1];
        } elseif (str_contains($ts, '.')) {
            $parts = explode('.', $ts);
            $time = $parts[0];
            $ms = ((int)($parts[1] ?? 0)) * 10;
        } else {
            $time = $ts;
            $ms = 0;
        }

        $seg = explode(':', $time);
        $h = (int)($seg[0] ?? 0);
        $m = (int)($seg[1] ?? 0);
        $s = (float)($seg[2] ?? 0);

        return $h * 3600 + $m * 60 + $s + $ms / 1000;
    }

    public static function secondsToTimestamp(float $sec): string
    {
        $h = floor($sec / 3600);
        $m = floor(($sec - $h * 3600) / 60);
        $s = floor($sec - $h * 3600 - $m * 60);
        $ms = round(($sec - floor($sec)) * 1000);
        return sprintf('%02d:%02d:%02d,%03d', $h, $m, $s, $ms);
    }

    public function adjustTimestamps(int $offsetMs): void
    {
        $startSec = self::timestampToSeconds($this->start);
        $endSec = self::timestampToSeconds($this->end);
        $this->start = self::secondsToTimestamp($startSec + $offsetMs / 1000);
        $this->end = self::secondsToTimestamp($endSec + $offsetMs / 1000);
    }

    public function scaleTimestamps(float $ratio): void
    {
        $startSec = self::timestampToSeconds($this->start);
        $endSec = self::timestampToSeconds($this->end);
        $this->start = self::secondsToTimestamp($startSec * $ratio);
        $this->end = self::secondsToTimestamp($endSec * $ratio);
    }
}