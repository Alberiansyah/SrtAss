<?php

namespace App\Models;

use Countable;
use IteratorAggregate;
use ArrayIterator;

class BatchCollection implements Countable, IteratorAggregate
{
    private array $files = [];

    public function __construct(array $files = [])
    {
        foreach ($files as $f) {
            $this->files[] = $f instanceof BatchFile ? $f : new BatchFile($f);
        }
    }

    public function add(BatchFile $file): void
    {
        $this->files[] = $file;
    }

    public function get(int $index): ?BatchFile
    {
        return $this->files[$index] ?? null;
    }

    public function remove(int $index): void
    {
        array_splice($this->files, $index, 1);
    }

    public function all(): array
    {
        return $this->files;
    }

    public function toArray(): array
    {
        return array_map(fn(BatchFile $f) => $f->toArray(), $this->files);
    }

    public function count(): int
    {
        return count($this->files);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->files);
    }
}