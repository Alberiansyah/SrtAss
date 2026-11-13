<?php
/**
 * Naturalizer: lapisan pengganti kamus yang sadar konteks.
 *
 * Menggabungkan tiga jenis aturan dalam SATU regex (single-pass):
 *  - dictionary  : entri kamus seperti biasa (case-sensitive + fallback case-insensitive)
 *  - preposition : "dengan Anda" -> "denganmu"
 *  - possessive  : "impian saya" -> "impianku"
 *
 * Semua offset yang dikembalikan berupa byte offset (aman untuk substr()).
 */

/**
 * Nilai bawaan (dipakai jika content/json/naturalize.json tidak ada / key tidak ada).
 */
function naturalizer_defaults(): array
{
    return [
        'enabled' => true,
        // Sengaja hanya preposisi yang tidak ambigu secara gramatikal.
        // "seperti", "punya", "tanpa" dihapus: bisa verba/konjungsi sehingga
        // merge klitik menghasilkan makna berbeda ("seperti ia katakan").
        'prepositions' => [
            'dengan', 'untuk', 'buat', 'pada', 'kepada', 'dari', 'tentang',
            'milik', 'bagi', 'terhadap',
        ],
        'pronoun_groups' => [
            1 => ['saya', 'aku'],
            2 => ['kamu', 'kau', 'anda'],
            3 => ['dia', 'ia'],
        ],
        'possessive_nouns' => [
            'impian', 'mimpi', 'nama', 'rumah', 'hati', 'hidup', 'ibu', 'ayah',
            'bapak', 'mama', 'papa', 'kakak', 'adik', 'anak', 'istri', 'suami',
            'keluarga', 'teman', 'sahabat', 'pacar', 'kekasih', 'pasangan', 'mata',
            'wajah', 'muka', 'tangan', 'kaki', 'badan', 'tubuh', 'kepala', 'rambut',
            'mulut', 'bibir', 'hidung', 'telinga', 'perasaan', 'pikiran', 'fikiran',
            'akal', 'jiwa', 'nyawa', 'darah', 'daging', 'tulang', 'kulit', 'suara',
            'kata', 'janji', 'cerita', 'kesalahan', 'salah', 'keinginan', 'harapan',
            'tujuan', 'pekerjaan', 'tugas', 'uang', 'duit', 'mobil', 'motor', 'baju',
            'pakaian', 'sepatu', 'kamar', 'sekolah', 'kantor', 'kelas', 'kota',
            'desa', 'negara', 'dunia', 'jalan', 'waktu', 'umur', 'usia', 'hari',
            'malam', 'pagi', 'siang', 'sore', 'dosa', 'kebahagiaan', 'kesuksesan',
            'kenangan', 'kenikmatan', 'keputusan', 'pilihan', 'alasan', 'masalah',
            'kesempatan', 'rahasia', 'kekuatan', 'kelemahan', 'keberanian', 'rasa',
            'guru', 'murid', 'bos', 'atasan', 'tetangga', 'kawan', 'musuh', 'lawan',
            'putra', 'putri', 'bayi', 'surga', 'neraka', 'rezeki', 'harta',
            'warisan', 'tabungan', 'gaji', 'utang', 'hutang', 'kontrak',
        ],
        'blocked_hosts' => [
            'jangan', 'bukan', 'belum', 'sudah', 'telah', 'masih', 'akan', 'harus',
            'bisa', 'dapat', 'boleh', 'mau', 'ingin', 'pengen', 'pingin',
            'kepengen', 'pernah', 'sedang', 'lagi', 'lalu', 'terus', 'selalu',
            'sering', 'jarang', 'kadang', 'mungkin', 'pasti', 'tentu', 'memang',
            'justru', 'malah', 'saja', 'juga', 'pula', 'dong', 'deh', 'sih', 'kan',
            'nih', 'tuh', 'pun', 'ya', 'iya', 'tidak', 'nggak', 'gak', 'enggak',
            'kalau', 'kalo', 'karena', 'karna', 'sebab', 'supaya', 'agar', 'bahwa',
            'yang', 'untuk', 'dengan', 'pada', 'kepada', 'di', 'ke', 'dari', 'oleh',
            'dalam', 'tentang', 'seperti', 'bagai', 'sebagai', 'setelah', 'sebelum',
            'ketika', 'saat', 'sambil', 'sampai', 'sejak', 'hingga', 'walau',
            'meskipun', 'tapi', 'tetapi', 'namun', 'sedangkan', 'atau', 'dan',
            'kemudian', 'ini', 'itu', 'sini', 'situ', 'sana', 'siapa', 'apa',
            'kapan', 'mana', 'bagaimana', 'mengapa', 'kenapa', 'berapa', 'kemarin',
            'tadi', 'besok', 'nanti', 'sekarang', 'dulu', 'segera', 'akhirnya',
            'tiba', 'mari', 'ayo', 'biar', 'biarkan', 'coba', 'tolong', 'mohon',
            'silakan', 'halo', 'hai', 'hey', 'oh', 'ah', 'eh', 'wah', 'hm', 'hmm',
            'uh', 'sangat', 'terlalu', 'agak', 'cukup', 'lebih', 'paling', 'hanya',
            'cuma', 'cuman',
        ],
        // Kosong secara default: heuristik sufiks terlalu longgar dan menjadi
        // sumber utama false-positive ("sebenarnya saya" -> "sebenarnyaku").
        // Mekanisme tetap ada; user boleh mengisinya eksplisit.
        'nominal_suffixes' => [],
    ];
}

/**
 * Config efektif: file JSON menang per-key, key yang tidak ada pakai default.
 */
function naturalizer_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $defaults = naturalizer_defaults();
    $config = $defaults;

    $path = __DIR__ . '/../content/json/naturalize.json';
    if (is_file($path)) {
        $raw = @file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($data)) {
            if (array_key_exists('enabled', $data)) {
                $config['enabled'] = (bool) $data['enabled'];
            }
            $listKeys = ['prepositions', 'possessive_nouns', 'blocked_hosts', 'nominal_suffixes'];
            foreach ($listKeys as $key) {
                if (array_key_exists($key, $data) && is_array($data[$key])) {
                    $config[$key] = naturalizer_clean_list($data[$key]);
                }
            }
            if (array_key_exists('pronoun_groups', $data) && is_array($data['pronoun_groups'])) {
                $groups = [];
                foreach ($data['pronoun_groups'] as $group => $items) {
                    if (!is_array($items)) {
                        continue;
                    }
                    $clean = naturalizer_clean_list($items);
                    if (!empty($clean)) {
                        $groups[(int) $group] = $clean;
                    }
                }
                if (!empty($groups)) {
                    $config['pronoun_groups'] = $groups;
                }
            }
        }
    }

    // Saklar global di functions.php
    if (defined('ENABLE_PRONOUN_NATURALIZATION') && !ENABLE_PRONOUN_NATURALIZATION) {
        $config['enabled'] = false;
    }

    return $config;
}

// Normalisasi list config: string, lowercase, trim, unik
function naturalizer_clean_list(array $items): array
{
    $clean = [];
    foreach ($items as $item) {
        if (!is_string($item) && !is_numeric($item)) {
            continue;
        }
        $value = mb_strtolower(trim((string) $item), 'UTF-8');
        if ($value !== '' && !in_array($value, $clean, true)) {
            $clean[] = $value;
        }
    }
    return $clean;
}

// Normalisasi key kamus: spasi berlebih dirapatkan
function naturalizer_normalize_key(string $key): string
{
    $normalized = preg_replace('/\s+/u', ' ', trim($key));
    return is_string($normalized) ? $normalized : trim($key);
}

// Pola regex untuk satu key kamus (spasi di dalam key jadi \s+)
function naturalizer_key_pattern(string $key): string
{
    $parts = preg_split('/\s+/u', trim($key));
    if (!is_array($parts) || $parts === []) {
        return '';
    }
    $quoted = [];
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $quoted[] = preg_quote($part, '/');
    }
    return implode('\s+', $quoted);
}

/**
 * Adaptasi kapitalisasi replacement untuk match case-insensitive.
 */
function naturalizer_apply_case(string $matched, string $replacement): string
{
    if ($matched === '' || $replacement === '') {
        return $replacement;
    }
    // Match seluruhnya huruf besar -> replacement huruf besar
    if (mb_strlen($matched, 'UTF-8') > 1 && mb_strtoupper($matched, 'UTF-8') === $matched) {
        return mb_strtoupper($replacement, 'UTF-8');
    }
    $firstMatch = mb_substr($matched, 0, 1, 'UTF-8');
    $firstReplacement = mb_substr($replacement, 0, 1, 'UTF-8');
    $isUpper = $firstMatch !== mb_strtolower($firstMatch, 'UTF-8');
    $replacementLower = $firstReplacement === mb_strtolower($firstReplacement, 'UTF-8');
    if ($isUpper && $replacementLower) {
        return mb_strtoupper($firstReplacement, 'UTF-8') . mb_substr($replacement, 1, null, 'UTF-8');
    }
    return $replacement;
}

/**
 * Compile semua aturan jadi satu regex + lookup map (di-cache per dictionary+config).
 */
function naturalizer_compile(array $dictionary, array $config): array
{
    static $cache = [];
    $hash = md5(serialize([$dictionary, $config]));
    if (isset($cache[$hash])) {
        return $cache[$hash];
    }

    // --- 1. Entri dictionary, diurutkan panjang desc (stabil di PHP 8) ---
    $keys = [];
    foreach ($dictionary as $key => $value) {
        $key = (string) $key;
        if (trim($key) === '') {
            continue;
        }
        $keys[] = $key;
    }
    usort($keys, function ($a, $b) {
        return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
    });

    // Key yang punya varian huruf besar/kecil tidak perlu alternatif case-insensitive
    $lowerCount = [];
    foreach ($keys as $key) {
        $lower = mb_strtolower($key, 'UTF-8');
        $lowerCount[$lower] = ($lowerCount[$lower] ?? 0) + 1;
    }

    $dictMap = [];   // key ternormalisasi => replacement
    $ciMap = [];     // lowercase key => key ternormalisasi
    $csAlts = [];
    $ciAlts = [];
    foreach ($keys as $key) {
        $pattern = naturalizer_key_pattern($key);
        if ($pattern === '') {
            continue;
        }
        $normalized = naturalizer_normalize_key($key);
        $dictMap[$normalized] = (string) $dictionary[$key];
        $csAlts[] = $pattern;

        $lower = mb_strtolower($key, 'UTF-8');
        if (($lowerCount[$lower] ?? 0) === 1 && !isset($ciMap[$lower])) {
            $ciMap[$lower] = $normalized;
            $ciAlts[] = '(?i:' . $pattern . ')';
        }
    }

    $branches = [];
    $alternatives = array_values(array_unique(array_merge($csAlts, $ciAlts)));
    if (!empty($alternatives)) {
        $branches[] = '(?<dict>' . implode('|', $alternatives) . ')';
    }

    // --- 2. Data pronoun ---
    $pronounGroup = [];
    foreach ($config['pronoun_groups'] as $group => $items) {
        foreach ($items as $pronoun) {
            $pronoun = mb_strtolower((string) $pronoun, 'UTF-8');
            if ($pronoun !== '' && !isset($pronounGroup[$pronoun])) {
                $pronounGroup[$pronoun] = (int) $group;
            }
        }
    }
    $clitics = [1 => 'ku', 2 => 'mu', 3 => 'nya'];

    $pronouns = array_keys($pronounGroup);
    usort($pronouns, function ($a, $b) {
        return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
    });
    $pronounAlt = implode('|', array_map(function ($p) {
        return preg_quote($p, '/');
    }, $pronouns));

    $prepositions = $config['prepositions'];
    usort($prepositions, function ($a, $b) {
        return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
    });
    $prepositionAlt = implode('|', array_map(function ($p) {
        return preg_quote($p, '/');
    }, $prepositions));

    if (!empty($config['enabled']) && $pronounAlt !== '') {
        // Rule preposisi & possessive bersifat case-insensitive (modifier i lokal),
        // sementara entri dictionary tetap case-sensitive.
        if ($prepositionAlt !== '') {
            $branches[] = '(?i:(?<prep>' . $prepositionAlt . ')\s+(?<pronp>(?:' . $pronounAlt . ')))';
        }
        $branches[] = '(?i:(?<host>\p{L}[\p{L}\'’-]*)\s+(?<pronh>(?:' . $pronounAlt . ')))';
    }

    // Boundary Unicode (bukan \b) + kompatibilitas tag ASS "\N" (newline) dan "\h" (hard space).
    // Backslash dikecualikan agar "N"/"h" pada tag tidak ikut tertangkap sebagai
    // awal host ("\Nimpian saya" harus match host "impian", bukan "Nimpian").
    $prefix = '(?:(?<![\\\\\p{L}\p{N}])|(?<=\\\\[nNhH]))';
    $suffix = '(?![\p{L}\p{N}])';

    $pattern = null;
    if (!empty($branches)) {
        $pattern = '/' . $prefix . '(?:' . implode('|', $branches) . ')' . $suffix . '/u';
        // Pastikan regex valid sebelum dipakai
        if (@preg_match($pattern, '') === false) {
            $pattern = null;
        }
    }

    $compiled = [
        'pattern' => $pattern,
        'dict_map' => $dictMap,
        'ci_map' => $ciMap,
        'pronoun_group' => $pronounGroup,
        'clitics' => $clitics,
        'prepositions' => array_fill_keys($config['prepositions'], true),
        'possessive_nouns' => array_fill_keys($config['possessive_nouns'], true),
        'blocked_hosts' => array_fill_keys($config['blocked_hosts'], true),
        'nominal_suffixes' => $config['nominal_suffixes'],
    ];

    $cache[$hash] = $compiled;
    return $compiled;
}

/**
 * Host hanya boleh di-merge jika termasuk kata benda yang wajar.
 */
function naturalizer_is_possessive_host(string $host, array $compiled): bool
{
    $host = mb_strtolower(trim($host), 'UTF-8');
    if ($host === '') {
        return false;
    }
    if (isset($compiled['blocked_hosts'][$host])) {
        return false;
    }
    if (isset($compiled['prepositions'][$host])) {
        return false;
    }
    if (isset($compiled['pronoun_group'][$host])) {
        return false;
    }
    // Kata kerja imperatif/verba berakhiran -kan tidak di-merge
    if (mb_strlen($host, 'UTF-8') >= 3 && mb_substr($host, -3, null, 'UTF-8') === 'kan') {
        return false;
    }

    // Host yang didaftarkan eksplisit selalu dipercaya (melewati guard bentuk).
    $isExplicit = isset($compiled['possessive_nouns'][$host]);

    $hostLength = mb_strlen($host, 'UTF-8');
    // Terlalu pendek untuk dinilai aman (tidak ada pengecualian)
    if ($hostLength < 3) {
        return false;
    }
    if ($isExplicit) {
        return true;
    }
    // Bentuk ulang (jangan-jangan, kapan-kapan) bukan kata benda
    if (mb_strpos($host, '-') !== false) {
        return false;
    }
    // Kata yang sudah membawa -nya (sebenarnya, kelihatannya) bukan host klitik
    if (mb_substr($host, -3, null, 'UTF-8') === 'nya') {
        return false;
    }

    foreach ($compiled['nominal_suffixes'] as $suffix) {
        $suffix = (string) $suffix;
        $len = mb_strlen($suffix, 'UTF-8');
        if ($len > 0 && $hostLength >= $len && mb_substr($host, -$len, null, 'UTF-8') === $suffix) {
            return true;
        }
    }
    return false;
}

/**
 * Lookup satu token ke kamus (case-sensitive dulu, lalu case-insensitive).
 * Dipakai untuk fallback pronomina pada span yang ditolak.
 */
function naturalizer_dictionary_lookup(string $token, array $compiled): ?string
{
    if ($token === '') {
        return null;
    }
    $normalized = preg_replace('/\s+/u', ' ', $token);
    if (!is_string($normalized)) {
        $normalized = $token;
    }
    if (array_key_exists($normalized, $compiled['dict_map'])) {
        return $compiled['dict_map'][$normalized];
    }
    $lower = mb_strtolower($normalized, 'UTF-8');
    if (isset($compiled['ci_map'][$lower])) {
        return naturalizer_apply_case($token, $compiled['dict_map'][$compiled['ci_map'][$lower]]);
    }
    return null;
}

/**
 * Cari semua match non-overlapping, urut naik berdasarkan byte offset.
 *
 * @return array<int, array{start:int,length:int,original:string,replacement:string,type:string}>
 */
function naturalizer_matches(string $text, array $dictionary, ?array $config = null): array
{
    if ($text === '') {
        return [];
    }

    $config = $config ?? naturalizer_config();
    $compiled = naturalizer_compile($dictionary, $config);
    if ($compiled['pattern'] === null) {
        return [];
    }

    $matched = preg_match_all($compiled['pattern'], $text, $m, PREG_OFFSET_CAPTURE);
    if (!$matched) {
        return [];
    }

    $results = [];
    foreach ($m[0] as $index => $full) {
        $original = $full[0];
        $start = $full[1];
        if ($start < 0 || $original === '') {
            continue;
        }

        $replacement = null;
        $type = null;
        // Pronomina cadangan: jika span ditolak, pronomina-nya tetap boleh
        // mendapat penggantian kamus (tanpa merge klitik).
        $fallbackPronoun = null;

        // --- A. Entri dictionary ---
        if (isset($m['dict'][$index]) && $m['dict'][$index][1] >= 0) {
            $replacement = naturalizer_dictionary_lookup($original, $compiled);
            if ($replacement !== null) {
                $type = 'dictionary';
            }
        }

        // --- B. Preposisi + pronomina -> klitik ---
        if ($type === null && isset($m['prep'][$index]) && $m['prep'][$index][1] >= 0) {
            $pronoun = isset($m['pronp'][$index]) ? (string) $m['pronp'][$index][0] : '';
            $group = $compiled['pronoun_group'][mb_strtolower($pronoun, 'UTF-8')] ?? null;
            if ($group !== null && isset($compiled['clitics'][$group])) {
                // Kapitalisasi preposisi mengikuti teks asli
                $replacement = $m['prep'][$index][0] . $compiled['clitics'][$group];
                $type = 'preposition';
            } elseif (isset($m['pronp'][$index]) && $m['pronp'][$index][1] >= 0) {
                $fallbackPronoun = $m['pronp'][$index];
            }
        }

        // --- C. Kata benda + pronomina -> klitik ---
        if ($type === null && isset($m['host'][$index]) && $m['host'][$index][1] >= 0) {
            $pronoun = isset($m['pronh'][$index]) ? (string) $m['pronh'][$index][0] : '';
            $group = $compiled['pronoun_group'][mb_strtolower($pronoun, 'UTF-8')] ?? null;
            if ($group !== null && isset($compiled['clitics'][$group])
                && naturalizer_is_possessive_host($m['host'][$index][0], $compiled)) {
                $replacement = $m['host'][$index][0] . $compiled['clitics'][$group];
                $type = 'possessive';
            } elseif (isset($m['pronh'][$index]) && $m['pronh'][$index][1] >= 0) {
                $fallbackPronoun = $m['pronh'][$index];
            }
        }

        if ($type === null || $replacement === null) {
            // Span ditolak (tidak di-merge), tapi pronomina di dalamnya tetap
            // diproses kamus. Span penuh sudah dikonsumsi regex, jadi hanya
            // token pronomina yang dilaporkan.
            if ($fallbackPronoun !== null && $fallbackPronoun[1] >= 0) {
                $token = (string) $fallbackPronoun[0];
                $tokenReplacement = naturalizer_dictionary_lookup($token, $compiled);
                if ($tokenReplacement !== null && $tokenReplacement !== $token) {
                    $results[] = [
                        'start' => (int) $fallbackPronoun[1],
                        'length' => strlen($token),
                        'original' => $token,
                        'replacement' => $tokenReplacement,
                        'type' => 'dictionary',
                    ];
                }
            }
            continue;
        }

        $results[] = [
            'start' => $start,
            'length' => strlen($original),
            'original' => $original,
            'replacement' => $replacement,
            'type' => $type,
        ];
    }

    return $results;
}
