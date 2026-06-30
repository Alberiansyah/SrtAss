<?php

namespace App\Services;

use App\Models\SubtitleCollection;
use Sastrawi\Stemmer\StemmerFactory;
use Sastrawi\Dictionary\ArrayDictionary;

class WordDetector
{
    private array $dictionary;
    private ?\Sastrawi\Stemmer\Stemmer $stemmer = null;

    public function __construct()
    {
        $path = __DIR__ . '/../../content/json/id-words.txt';
        if (file_exists($path)) {
            $words = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $this->dictionary = array_flip($words ?? []);
        } else {
            $this->dictionary = [];
        }
    }

    private function getStemmer(): \Sastrawi\Stemmer\Stemmer
    {
        if ($this->stemmer === null) {
            $dict = new ArrayDictionary(array_keys($this->dictionary));
            $factory = new StemmerFactory();
            $this->stemmer = $factory->createStemmer();
            $ref = new \ReflectionProperty($this->stemmer, 'dictionary');
            $ref->setAccessible(true);
            $ref->setValue($this->stemmer, $dict);
        }
        return $this->stemmer;
    }

    public function detectNonIndonesian(string $text): array
    {
        $words = $this->extractWords($text);
        $unknown = [];

        foreach ($words as $word) {
            $lower = mb_strtolower($word);
            if (isset($this->dictionary[$lower])) continue;
            if (preg_match('/^[0-9\s\W]+$/', $lower)) continue;

            try {
                $stemmed = $this->getStemmer()->stem($lower);
                if (!isset($this->dictionary[$stemmed])) {
                    $unknown[] = $word;
                }
            } catch (\Throwable) {
                $unknown[] = $word;
            }
        }

        return array_unique($unknown);
    }

    public function detectNonIndonesianWithLines(SubtitleCollection $subtitles): array
    {
        $result = [];
        $i = 1;
        foreach ($subtitles as $sub) {
            $unknown = $this->detectNonIndonesian($sub->text);
            foreach ($unknown as $word) {
                $result[] = [
                    'word' => $word,
                    'line' => $i,
                ];
            }
            $i++;
        }
        return $result;
    }

    public function getDictionaryChanges(SubtitleCollection $subtitles, \App\Models\Dictionary $dictionary): array
    {
        $changes = [];
        $i = 1;

        foreach ($subtitles as $sub) {
            $text = $sub->text;
            foreach ($dictionary->getSortedByLength() as $key => $value) {
                $pattern = '/\b' . preg_quote($key, '/') . '\b/u';
                if (preg_match($pattern, $text)) {
                    $pos = mb_stripos($text, $key);
                    $ctxStart = max(0, $pos - 20);
                    $ctxLen = min(40, mb_strlen($text) - $ctxStart);
                    $context = ($ctxStart > 0 ? '...' : '') . mb_substr($text, $ctxStart, $ctxLen) . ($ctxStart + $ctxLen < mb_strlen($text) ? '...' : '');

                    $changes[] = [
                        'line' => $i,
                        'original' => $key,
                        'replacement' => $value,
                        'context' => $context,
                    ];
                }
            }
            $i++;
        }

        usort($changes, fn($a, $b) => $a['line'] - $b['line']);
        return $changes;
    }

    private function extractWords(string $text): array
    {
        preg_match_all('/[\p{L}\']+/u', strip_tags($text), $matches);
        return $matches[0] ?? [];
    }
}