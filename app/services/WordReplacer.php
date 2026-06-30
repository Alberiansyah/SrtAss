<?php

namespace App\Services;

use App\Models\Dictionary;

class WordReplacer
{
    private Dictionary $dictionary;

    public function __construct(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
    }

    public function replace(string $text, bool $applyHighlight = true): string
    {
        $dict = $this->dictionary->getSortedByLength();

        foreach ($dict as $key => $value) {
            $replacement = $applyHighlight
                ? '<span class="highlight">' . $value . '</span>'
                : $value;

            $result = preg_replace(
                '/\b' . preg_quote($key, '/') . '\b/u',
                $replacement,
                $text
            );
            if ($result !== null) {
                $text = $result;
            }

            $result = preg_replace(
                '/(\\\\[nN])' . preg_quote($key, '/') . '\b/u',
                '$1' . $replacement,
                $text
            );
            if ($result !== null) {
                $text = $result;
            }
        }

        return $text;
    }

    public function getChanges(string $text): array
    {
        $changes = [];
        $original = $text;

        foreach ($this->dictionary->getSortedByLength() as $key => $value) {
            $pattern = '/\b' . preg_quote($key, '/') . '\b/u';
            if (preg_match_all($pattern, $original, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $pos = $match[1];
                    $changes[] = [
                        'original' => $key,
                        'replacement' => $value,
                        'position' => $pos,
                    ];
                }
            }
        }

        return $changes;
    }
}