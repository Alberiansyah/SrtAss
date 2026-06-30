<?php

namespace App\Models;

class Dictionary
{
    private array $words = [];
    private string $jsonPath;

    public function __construct(?string $jsonPath = null)
    {
        $this->jsonPath = $jsonPath ?? __DIR__ . '/../../content/json/dictionary.json';
        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->jsonPath)) {
            $data = json_decode(file_get_contents($this->jsonPath), true);
            $this->words = is_array($data) ? $data : [];
        }
    }

    public function save(): void
    {
        $sorted = $this->words;
        ksort($sorted, SORT_STRING | SORT_FLAG_CASE);
        $dir = dirname($this->jsonPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->jsonPath, json_encode($sorted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function get(string $key): ?string
    {
        return $this->words[$key] ?? null;
    }

    public function set(string $key, string $value): void
    {
        $this->words[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->words[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->words[$key]);
    }

    public function all(): array
    {
        return $this->words;
    }

    public function getSortedByLength(): array
    {
        $sorted = $this->words;
        uksort($sorted, fn($a, $b) => strlen($b) - strlen($a));
        return $sorted;
    }

    public function getPage(int $page, int $perPage = 50): array
    {
        $all = $this->all();
        $offset = ($page - 1) * $perPage;
        return array_slice($all, $offset, $perPage, true);
    }

    public function search(string $query): array
    {
        if (empty($query)) return $this->all();
        $q = mb_strtolower($query);
        return array_filter($this->words, fn($val, $key) =>
            mb_stripos($key, $q) !== false || mb_stripos($val, $q) !== false,
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function count(): int
    {
        return count($this->words);
    }
}