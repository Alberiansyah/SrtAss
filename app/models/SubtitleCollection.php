<?php

namespace App\Models;

use Countable;
use IteratorAggregate;
use ArrayIterator;

class SubtitleCollection implements Countable, IteratorAggregate
{
    private array $items = [];

    public function __construct(array $subtitles = [])
    {
        foreach ($subtitles as $s) {
            $this->items[] = $s instanceof Subtitle ? $s : new Subtitle($s);
        }
    }

    public function add(Subtitle $subtitle): void
    {
        $this->items[] = $subtitle;
    }

    public function get(int $index): ?Subtitle
    {
        return $this->items[$index] ?? null;
    }

    public function remove(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    public function removeIndices(array $indices): void
    {
        $indices = array_unique($indices);
        rsort($indices);
        foreach ($indices as $i) {
            $this->remove($i);
        }
    }

    public function merge(int $from, int $to, string $mergedText): Subtitle
    {
        $first = $this->items[$from];
        $last = $this->items[$to];
        $merged = new Subtitle([
            'start' => $first->start,
            'end' => $last->end,
            'text' => $mergedText,
            'style' => $first->style,
        ]);
        $count = $to - $from + 1;
        for ($i = 0; $i < $count; $i++) {
            $this->remove($from);
        }
        array_splice($this->items, $from, 0, [$merged]);
        return $merged;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return array_map(fn(Subtitle $s) => $s->toArray(), $this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function shiftTiming(int $offsetMs): void
    {
        foreach ($this->items as $sub) {
            $sub->adjustTimestamps($offsetMs);
        }
    }

    public function scaleTiming(float $ratio): void
    {
        foreach ($this->items as $sub) {
            $sub->scaleTimestamps($ratio);
        }
    }

    public function getTotalDuration(): float
    {
        if (empty($this->items)) return 0;
        $start = Subtitle::timestampToSeconds($this->items[0]->start);
        $end = Subtitle::timestampToSeconds($this->items[count($this->items) - 1]->end);
        return $end - $start;
    }

    public function findByText(string $search): array
    {
        $search = mb_strtolower($search);
        return array_values(array_filter($this->items, fn(Subtitle $s) => mb_strpos(mb_strtolower($s->text), $search) !== false));
    }
}