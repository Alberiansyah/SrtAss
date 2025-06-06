<?php
require_once 'vendor/autoload.php'; // Tambahkan di awal file

define('ENABLE_WORD_HIGHLIGHT', true); // Kontrol highlight
define('ENABLE_NON_INDONESIAN_WORD_LOGGING', true); // Kontrol logging

function handleSingleRequest()
{
    if (isset($_FILES['subtitle_file']) && $_FILES['subtitle_file']['error'] == UPLOAD_ERR_OK) {
        $fileName = $_FILES['subtitle_file']['name'];
        $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
        $_SESSION['file_name'] = $fileNameWithoutExtension;
        $_SESSION['uploaded_file_name'] = $fileName;

        $fileContent = file_get_contents($_FILES['subtitle_file']['tmp_name']);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if ($extension == 'srt') {
            $_SESSION['subtitles'] = parseSrt($fileContent);
            $_SESSION['styles'] = [];
        } elseif ($extension == 'ass') {
            $parsedAss = parseAss($fileContent);
            $_SESSION['subtitles'] = $parsedAss['subtitles'];
            $_SESSION['styles'] = $parsedAss['styles'];
            $_SESSION['scriptInfo'] = $parsedAss['scriptInfo']; // Simpan Script Info
            $_SESSION['projectGarbage'] = $parsedAss['projectGarbage']; // Simpan Project Garbage
        }
    }

    // Memastikan kamus default
    if (!isset($_SESSION['dictionary'])) {
        $_SESSION['dictionary'] = loadDictionaryFromJson();
    }

    if (isset($_POST['add_to_dictionary'])) {
        $key = trim($_POST['key']);
        $value = trim($_POST['value']);
        if (!empty($key) && !empty($value)) {
            $_SESSION['dictionary'][$key] = $value;
            saveDictionaryToJson($_SESSION['dictionary']); // Simpan ke JSON
        }
    }

    if (isset($_POST['remove_from_dictionary'])) {
        $key_to_remove = $_POST['remove_from_dictionary'];
        if (array_key_exists($key_to_remove, $_SESSION['dictionary'])) {
            $value_to_restore = $_SESSION['dictionary'][$key_to_remove];
            foreach ($_SESSION['subtitles'] as &$subtitle) {
                $subtitle['text'] = str_replace($value_to_restore, $key_to_remove, $subtitle['text']);
            }
            unset($_SESSION['dictionary'][$key_to_remove]);
            saveDictionaryToJson($_SESSION['dictionary']); // Simpan ke JSON
        }
    }

    if (isset($_POST['download'])) {
        $format = $_POST['format'];
        $subtitles = $_SESSION['subtitles'];
        $originalFileName = pathinfo($_SESSION['uploaded_file_name'], PATHINFO_FILENAME);
        $subtitleType = $_POST['subtitle_type'] ?? 'anime';  // Default to anime if not set

        if ($format === 'srt') {
            $content = convertToSrt($subtitles);
            $fileName = $originalFileName . '.srt';
            header('Content-Type: text/srt');
        } elseif ($format === 'ass') {
            $styles = $_SESSION['styles'] ?? [];
            $scriptInfo = $_SESSION['scriptInfo'] ?? '';
            $projectGarbage = $_SESSION['projectGarbage'] ?? '';
            $content = convertToAss($subtitles, $styles, $scriptInfo, $projectGarbage, $subtitleType); // Pass subtitle type
            $fileName = $originalFileName . '.ass';
            header('Content-Type: application/x-ansi');
        }

        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    if (isset($_POST['clear_session'])) {
        $_SESSION = [];
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit;
    }
}

function handleBatchRequest()
{
    // Proses unggahan banyak file
    if (isset($_FILES['subtitle_files'])) {
        $batchFiles = [];
        foreach ($_FILES['subtitle_files']['error'] as $index => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $fileName = $_FILES['subtitle_files']['name'][$index];
                $tmpName = $_FILES['subtitle_files']['tmp_name'][$index];
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $fileContent = file_get_contents($tmpName);

                if ($extension == 'srt') {
                    $subtitles = parseSrt($fileContent);
                    $batchFiles[] = [
                        'uploaded_file_name' => $fileName,
                        'file_name'          => pathinfo($fileName, PATHINFO_FILENAME),
                        'extension'          => $extension,
                        'subtitles'          => $subtitles,
                        'styles'             => [],
                        'format'             => 'srt'
                    ];
                } elseif ($extension == 'ass') {
                    $parsedAss = parseAss($fileContent);
                    $batchFiles[] = [
                        'uploaded_file_name' => $fileName,
                        'file_name'          => pathinfo($fileName, PATHINFO_FILENAME),
                        'extension'          => $extension,
                        'subtitles'          => $parsedAss['subtitles'],
                        'styles'             => $parsedAss['styles'],
                        'scriptInfo'         => $parsedAss['scriptInfo'],
                        'projectGarbage'     => $parsedAss['projectGarbage'],
                        'format'             => 'ass'
                    ];
                }
            }
        }
        $_SESSION['batch_files'] = $batchFiles;

        foreach ($_SESSION['batch_files'] as &$file) {
            $file['log_file'] = 'content/logs/' . preg_replace(['/[^\w\s\-+\[\]]/', '/\s+\./'], [' ', '.'], $file['file_name']) . '.log';
        }
        unset($file); // Hapus reference
    }

    // Proses download batch (mengemas file-file hasil konversi ke ZIP)
    if (isset($_POST['batch_download'])) {
        $selectedFormat = $_POST['format']; // 'srt' atau 'ass'
        $subtitleType = $_POST['subtitle_type'] ?? 'anime';
        $batchFiles = $_SESSION['batch_files'] ?? [];
        if (!empty($batchFiles)) {
            $zip = new ZipArchive();

            // Nama file zip yang benar-benar unik
            $zipFileName = 'batch_converted_' . time() . '.zip';

            // Buat file temporer
            $tmpFile = tempnam(sys_get_temp_dir(), 'zip');
            if ($zip->open($tmpFile, ZipArchive::CREATE) === TRUE) {
                foreach ($batchFiles as $file) {
                    $originalFileName = $file['file_name'];
                    $subtitles = $file['subtitles'];
                    if ($selectedFormat === 'srt') {
                        $content = convertToSrt($subtitles);
                        $ext = 'srt';
                    } elseif ($selectedFormat === 'ass') {
                        $styles = $file['styles'] ?? [];
                        $scriptInfo = $file['scriptInfo'] ?? '';
                        $projectGarbage = $file['projectGarbage'] ?? '';
                        $content = convertToAss($subtitles, $styles, $scriptInfo, $projectGarbage, $subtitleType);
                        $ext = 'ass';
                    }
                    $fileName = $originalFileName . '.' . $ext;
                    $zip->addFromString($fileName, $content);
                }
                $zip->close();

                // Pastikan belum ada output HTML sebelum header
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
                header('Content-Length: ' . filesize($tmpFile));
                readfile($tmpFile);
                unlink($tmpFile);
                exit;
            }
        }
    }

    // Pastikan kamus default
    if (!isset($_SESSION['dictionary'])) {
        $_SESSION['dictionary'] = loadDictionaryFromJson();
    }

    // Proses penambahan/menghapus data kamus
    if (isset($_POST['add_to_dictionary'])) {
        $key = trim($_POST['key']);
        $value = trim($_POST['value']);
        if (!empty($key) && !empty($value)) {
            $_SESSION['dictionary'][$key] = $value;
            saveDictionaryToJson($_SESSION['dictionary']);
        }
    }

    if (isset($_POST['remove_from_dictionary'])) {
        $key_to_remove = $_POST['remove_from_dictionary'];
        if (array_key_exists($key_to_remove, $_SESSION['dictionary'])) {
            $value_to_restore = $_SESSION['dictionary'][$key_to_remove];
            if (isset($_SESSION['batch_files'])) {
                foreach ($_SESSION['batch_files'] as &$file) {
                    foreach ($file['subtitles'] as &$subtitle) {
                        $subtitle['text'] = str_replace($value_to_restore, $key_to_remove, $subtitle['text']);
                    }
                }
            }
            unset($_SESSION['dictionary'][$key_to_remove]);
            saveDictionaryToJson($_SESSION['dictionary']);
        }
    }

    if (isset($_POST['clear_session'])) {
        $_SESSION = [];
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit;
    }
}

function saveDictionaryToJson($dictionary, $filename = 'dictionary.json')
{
    $folder = 'content/json/';

    // Pastikan folder ada
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    // Salin dictionary untuk di-sort tanpa mengubah data asli di session
    $sortedDictionary = $dictionary;

    // Urutkan berdasarkan key secara ascending
    ksort($sortedDictionary, SORT_STRING | SORT_FLAG_CASE);

    // Simpan ke JSON
    $jsonData = json_encode($sortedDictionary, JSON_PRETTY_PRINT);
    file_put_contents($folder . $filename, $jsonData);
}

function loadDictionaryFromJson($filename = 'dictionary.json')
{
    $folder = 'content/json/'; // Folder tempat file JSON disimpan
    $filePath = $folder . $filename;
    if (file_exists($filePath)) {
        $jsonData = file_get_contents($filePath);
        return json_decode($jsonData, true);
    }
    return [];
}

function parseSrt($content)
{
    $lines = explode("\n", $content);
    $subtitles = [];
    $index = 0;

    while ($index < count($lines)) {
        $index++; // Skip number line
        if (isset($lines[$index]) && preg_match('/(\d{2}:\d{2}:\d{2},\d{3}) --> (\d{2}:\d{2}:\d{2},\d{3})/', $lines[$index], $matches)) {
            $start = $matches[1];
            $end = $matches[2];
            $index++;
            $text = '';
            while (isset($lines[$index]) && trim($lines[$index]) !== '') {
                $text .= $lines[$index] . "\n";
                $index++;
            }
            $subtitles[] = [
                'start' => $start,
                'end' => $end,
                'text' => trim($text)
            ];
        }
        $index++;
    }
    return $subtitles;
}

function parseAss($content)
{
    $lines = explode("\n", $content);
    $subtitles = [];
    $styles = [];
    $scriptInfo = [];
    $projectGarbage = [];

    $inScriptInfoSection = false;
    $inProjectGarbageSection = false;
    $inStylesSection = false;

    foreach ($lines as $line) {
        // Memasukkan bagian Script Info
        if (strpos($line, '[Script Info]') !== false) {
            $inScriptInfoSection = true;
            $inProjectGarbageSection = false;
            $inStylesSection = false;
            continue;
        }

        // Memasukkan bagian Aegisub Project Garbage
        if (strpos($line, '[Aegisub Project Garbage]') !== false) {
            $inScriptInfoSection = false;
            $inProjectGarbageSection = true;
            $inStylesSection = false;
            continue;
        }

        // Memasukkan bagian Styles
        if (strpos($line, '[V4+ Styles]') !== false) {
            $inScriptInfoSection = false;
            $inProjectGarbageSection = false;
            $inStylesSection = true;
            continue;
        }

        if ($inScriptInfoSection) {
            $scriptInfo[] = $line;
        }

        if ($inProjectGarbageSection) {
            $projectGarbage[] = $line;
        }

        if ($inStylesSection && strpos($line, 'Style:') === 0) {
            $styles[] = $line;
        }

        // Memasukkan bagian subtitle (Dialogue)
        if (strpos($line, 'Dialogue:') === 0) {
            $parts = explode(',', $line, 10);
            $start = $parts[1];
            $end = $parts[2];
            $style = $parts[3];
            $text = $parts[9];
            $subtitles[] = [
                'start' => $start,
                'end' => $end,
                'style' => $style,
                'text' => trim($text)
            ];
        }
    }

    return [
        'subtitles' => $subtitles,
        'styles' => $styles,
        'scriptInfo' => implode("\n", $scriptInfo),
        'projectGarbage' => implode("\n", $projectGarbage)
    ];
}

// Proses penggantian kata berdasarkan kamus
function replaceWords($text, $applyHighlight = true)
{
    if (isset($_SESSION['dictionary'])) {
        // Urutkan kamus berdasarkan panjang kata kunci (terpanjang dulu)
        uksort($_SESSION['dictionary'], function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        // Ganti kata berdasarkan kamus
        foreach ($_SESSION['dictionary'] as $key => $value) {
            // Pola untuk kata biasa
            $text = preg_replace(
                '/\b' . preg_quote($key, '/') . '\b/',
                $applyHighlight ? '<span style="background-color: #00ff33;">' . $value . '</span>' : $value,
                $text
            );

            // Pola untuk kasus \n atau \N di awal kata (pertahankan newline)
            $text = preg_replace(
                '/(\\\\[nN])' . preg_quote($key, '/') . '\b/',
                '$1' . ($applyHighlight ? '<span style="background-color: #00ff33;">' . $value . '</span>' : $value),
                $text
            );
        }
    }
    return $text;
}

// Fungsi untuk mengonversi subtitle menjadi format SRT
function convertToSrt($subtitles)
{
    $srt = "";
    foreach ($subtitles as $index => $subtitle) {
        $srt .= ($index + 1) . "\n";
        $start = convertTimeToSrt($subtitle['start']);
        $end = convertTimeToSrt($subtitle['end']);
        $textWithReplacements = replaceWords($subtitle['text'], false); // Tidak menerapkan highlight

        // Tambahkan penggantian tag italic dari ASS ke format HTML yang sesuai untuk SRT
        $textWithReplacements = str_replace('{\\i1}', '<i>', $textWithReplacements);
        $textWithReplacements = str_replace('{\\i0}', '</i>', $textWithReplacements);
        $textWithReplacements = str_replace('{\\i}', '</i>', $textWithReplacements); // Menangani kasus {\i} yang seharusnya menjadi </i>

        $srt .= $start . ' --> ' . $end . "\n";
        $srt .= $textWithReplacements . "\n\n";
    }
    return trim($srt);
}

function convertTimeToSrt($time)
{
    // Jika waktu berasal dari ASS (format: 0:00:06.15)
    if (strpos($time, '.') !== false) {
        $timeParts = explode('.', $time);
        $timeInSec = $timeParts[0]; // Ambil bagian jam:menit:detik
        $hundredths = isset($timeParts[1]) ? (int)$timeParts[1] : 0; // Ambil hundredths, default 0
        $milliseconds = $hundredths * 10; // Konversi hundredths ke milidetik (15 hundredths = 150 milidetik)

        // Tambahkan digit 0 di depan jam jika hanya ada satu digit
        if (strlen($timeInSec) < 8) { // Format jam:menit:detik harus memiliki 8 karakter (misal: 00:00:00)
            $timeInSec = '0' . $timeInSec;
        }

        return $timeInSec . ',' . str_pad($milliseconds, 3, '0', STR_PAD_LEFT); // Kembalikan dalam format SRT
    }

    // Jika waktu sudah dalam format SRT (00:00:01,000), kembalikan langsung
    return $time;
}

function convertToAss($subtitles, $styles = [], $scriptInfo = '', $projectGarbage = '', $subtitleType)
{
    $ass = "[Script Info]\n";

    // Check if styles exist and include script info
    if (!empty($styles)) {
        $ass .= $scriptInfo . "\n";  // Include the Script Info from the original file
    } else {
        // Anime-specific header information (when no styles are present)
        if ($subtitleType === 'anime') {
            $ass .= "; Script generated by Aegisub 3.4.2\n";
            $ass .= "; http://www.aegisub.org/\n";
            $ass .= "Title: Default Aegisub file\n";
            $ass .= "ScriptType: v4.00+\n";
            $ass .= "WrapStyle: 0\n";
            $ass .= "ScaledBorderAndShadow: yes\n\n";
        } elseif ($subtitleType === 'movie') {
            // Movie-specific header information
            $ass .= "; Script generated by Aegisub 3.4.2\n";
            $ass .= "; http://www.aegisub.org/\n";
            $ass .= "Title: Default Aegisub file\n";
            $ass .= "ScriptType: v4.00+\n";
            $ass .= "WrapStyle: 0\n";
            $ass .= "ScaledBorderAndShadow: yes\n";
            $ass .= "YCbCr Matrix: TV.709\n"; // Movie-specific info
            $ass .= "PlayResX: 1280\n";      // Movie-specific resolution
            $ass .= "PlayResY: 720\n\n";    // Movie-specific resolution
        }
    }

    $ass .= "[Aegisub Project Garbage]\n";
    $ass .= $projectGarbage . "\n"; // Use project garbage info from the file

    $ass .= "[V4+ Styles]\n";
    $ass .= "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding\n";
    // Default style if no styles are present
    if ($subtitleType === 'anime') {
        $ass .= "Style: Default,GosmickSans,75,&H00FFFFFF,&H000000FF,&H00000000,&H00000000,-1,0,0,0,100,100,0,0,1,2.5,2,2,15,15,55,1\n";
        $ass .= "Style: Atas,GosmickSans,75,&H00FFFFFF,&H000000FF,&H00000000,&H00000000,-1,0,0,0,100,100,0,0,1,2.5,2,8,15,15,55,1\n";
        $ass .= "Style: Red PK,GosmickSans,75,&H000000DD,&H000000FF,&H00000000,&H00000000,-1,0,0,0,100,100,0,0,1,2.5,2,8,15,15,55,1\n";
        $ass .= "Style: Signs,GosmickSans,75,&H00323232,&H000000FF,&H00EEEEEE,&H00000000,-1,0,0,0,100,100,0,0,1,2.5,2,5,10,10,55,1\n";
        $ass .= "Style: Outline,GosmickSans,75,&H00FFFFFF,&H000000FF,&H000000D7,&H00000000,-1,0,0,0,100,100,0,0,1,2.5,2,2,10,10,55,1\n";
    } elseif ($subtitleType === 'movie') {
        $ass .= "Style: Default,Panefresco 800wt,50,&H00FFFFFF,&H000000FF,&H00000000,&H00000000,-1,0,0,0,100,100,0,0,1,2,1.5,2,15,15,55,1\n";
    } elseif ($subtitleType === 'none') {
        if (!empty($styles)) {
            foreach ($styles as $style) {
                $ass .= $style . "\n";
            }
        }
    }

    $ass .= "\n[Events]\n";
    $ass .= "Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n";
    foreach ($subtitles as $subtitle) {
        $start = convertTimeToAss($subtitle['start']);
        $end = convertTimeToAss($subtitle['end']);
        $style = $subtitle['style'] ?? 'Default';
        $text = replaceWords($subtitle['text'], false); // No highlight applied

        // Replace <i> with {\i1} and </i> with {\i}
        $text = preg_replace('/<i>(.*?)<\/i>/', '{\i1}$1{\i}', $text);

        // Replace newlines with \N and ensure text stays on one line
        $text = str_replace("\n", "\\N", $text);
        $text = str_replace("\r", "", $text); // Remove carriage return characters if any

        $ass .= "Dialogue: 0,$start,$end,$style,,0,0,0,,$text\n";
    }

    return $ass;
}

function convertTimeToAss($time)
{
    // Jika waktu berasal dari SRT (format: 00:00:01,000)
    if (strpos($time, ',') !== false) {
        list($timePart, $milliseconds) = explode(',', $time);
        $milliseconds = (int)$milliseconds;
        // Parse waktu menjadi jam, menit, detik
        list($hours, $minutes, $seconds) = explode(':', $timePart);
        // Konversi milidetik ke centidetik dengan pembulatan
        $centiseconds = round($milliseconds / 10);
        // Handle centiseconds >= 100 (rollover ke detik)
        if ($centiseconds >= 100) {
            $seconds += floor($centiseconds / 100);
            $centiseconds = $centiseconds % 100;
        }
        // Format ulang waktu dengan rollover
        $timeInSec = sprintf(
            "%01d:%02d:%02d", // Format: 0:00:00 (1 digit jam, 2 digit menit/detik)
            $hours,
            $minutes,
            $seconds
        );
        return "$timeInSec." . str_pad($centiseconds, 2, '0', STR_PAD_LEFT);
    }
    // Jika sudah dalam format ASS, kembalikan langsung
    return $time;
}

function loadIndonesianDictionary()
{
    $filePath = 'content/json/id-words.txt';

    // Jika file tidak ditemukan, return null khusus
    if (!file_exists($filePath)) {
        error_log("Kamus bahasa Indonesia tidak ditemukan di: " . realpath(dirname($filePath)));
        return null; // Kembalikan null bukan array kosong
    }

    $words = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($words)) {
        error_log("Kamus bahasa Indonesia kosong atau tidak valid");
        return null;
    }

    return array_flip($words);
}

function highlightIndonesiaWords($text, $lineNumber = null, $logFile = null, $currentFileIndex = null)
{
    // Jika highlight dinonaktifkan, kembalikan teks asli
    if (!ENABLE_WORD_HIGHLIGHT || !ENABLE_NON_INDONESIAN_WORD_LOGGING) {
        return htmlspecialchars($text);
    }
    // Di bagian atas functions.php, tambahkan:
    $currentFileIndex = $_SESSION['current_file_index'] ?? null;

    static $stemmer = null;
    static $indonesianWords = null;
    static $dictionaryExists = false;

    if ($stemmer === null) {
        $stemmer = new \Sastrawi\Stemmer\StemmerFactory();
        $stemmer = $stemmer->createStemmer();
        $indonesianWords = loadIndonesianDictionary();
        $dictionaryExists = ($indonesianWords !== null);
    }

    // Jika kamus tidak ada, kembalikan teks asli tanpa highlight
    if (!$dictionaryExists) {
        return htmlspecialchars($text);
    }

    // Inisialisasi session untuk menyimpan kata tidak dikenal
    if (!isset($_SESSION['non_indonesian_words'])) {
        $_SESSION['non_indonesian_words'] = [];
    }

    $specialCharsPattern = '/(\\\\[NnHh]|\\R|\{\\.*?\})/';
    $parts = preg_split($specialCharsPattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    $result = '';

    foreach ($parts as $part) {
        if (preg_match($specialCharsPattern, $part)) {
            $result .= $part;
            continue;
        }

        $tokens = preg_split('/([^\p{L}\p{N}]+)/u', $part, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($tokens as $token) {
            if (empty(trim($token))) {
                $result .= $token;
                continue;
            }

            if (preg_match('/^\p{N}+$/u', $token)) {
                $result .= $token;
                continue;
            }

            if (preg_match('/^\p{L}+$/u', $token)) {
                $lowerToken = mb_strtolower($token, 'UTF-8');
                $stemmed = $stemmer->stem($lowerToken);

                if (!isset($indonesianWords[$stemmed]) && !isset($indonesianWords[$lowerToken])) {
                    $result .= '<span class="non-indonesian-word" title="Kata tidak dikenali">' .
                        htmlspecialchars($token) . '</span>';

                    // Simpan kata tidak dikenal dengan key yang unik
                    if ($lineNumber !== null) {
                        $fileKey = isset($GLOBALS['currentFileIndex']) ? 'file_' . $GLOBALS['currentFileIndex'] : 'single';
                        if (!isset($_SESSION['non_indonesian_words'][$fileKey])) {
                            $_SESSION['non_indonesian_words'][$fileKey] = [];
                        }

                        // Gunakan kombinasi unik untuk key
                        $uniqueKey = $token . '|' . $lineNumber . '|' . $fileKey;
                        $_SESSION['non_indonesian_words'][$fileKey][$uniqueKey] = [
                            'line' => $lineNumber,
                            'word' => $token,
                            'file_name' => isset($GLOBALS['currentFileIndex']) ?
                                $_SESSION['batch_files'][$GLOBALS['currentFileIndex']]['file_name'] : ($_SESSION['file_name'] ?? 'unknown')
                        ];
                    }
                } else {
                    $result .= htmlspecialchars($token);
                }
            } else {
                $result .= htmlspecialchars($token);
            }
        }
    }

    return $result;
}

function logIndonesiaWords($text, $lineNumber, $logFile = null)
{
    // Jika logging disabled ATAU highlight disabled, langsung return
    if ((defined('ENABLE_NON_INDONESIAN_WORD_LOGGING') && !ENABLE_NON_INDONESIAN_WORD_LOGGING) ||
        (defined('ENABLE_WORD_HIGHLIGHT') && !ENABLE_WORD_HIGHLIGHT)
    ) {
        return;
    }

    static $stemmer = null;
    static $indonesianWords = null;
    static $dictionaryExists = false;
    static $loggedEntries = [];

    if ($stemmer === null) {
        $stemmer = new \Sastrawi\Stemmer\StemmerFactory();
        $stemmer = $stemmer->createStemmer();
        $indonesianWords = loadIndonesianDictionary();
        $dictionaryExists = ($indonesianWords !== null);
    }

    // Jika kamus tidak ada, jangan lakukan logging
    if (!$dictionaryExists) {
        return;
    }

    // Jika tidak ada nama file log spesifik, gunakan default
    if ($logFile === null) {
        $fileName = $_SESSION['file_name'] ?? 'unknown';
        $logFile = 'content/logs/' . preg_replace(['/[^\w\s\-+\[\]]/', '/\s+\./'], [' ', '.'], $fileName) . '.log';
    }

    // Baca log yang sudah ada
    if (file_exists($logFile)) {
        $loggedEntries = array_flip(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }

    $specialCharsPattern = '/(\\\\[NnHh]|\\R|\{\\.*?\})/';
    $parts = preg_split($specialCharsPattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    $newEntries = [];

    foreach ($parts as $part) {
        if (preg_match($specialCharsPattern, $part)) {
            continue;
        }

        $tokens = preg_split('/([^\p{L}\p{N}]+)/u', $part, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($tokens as $token) {
            if (empty(trim($token)) || preg_match('/^\p{N}+$/u', $token)) {
                continue;
            }

            if (preg_match('/^\p{L}+$/u', $token)) {
                $stemmed = $stemmer->stem(mb_strtolower($token, 'UTF-8'));

                if (
                    !isset($indonesianWords[$stemmed]) &&
                    !isset($indonesianWords[mb_strtolower($token, 'UTF-8')])
                ) {

                    $entry = "[LINE:$lineNumber] $token";
                    if (!isset($loggedEntries[$entry])) {
                        $newEntries[$entry] = true;
                    }
                }
            }
        }
    }

    // Simpan entri baru ke log file
    if (!empty($newEntries)) {
        $logDir = 'content/logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents(
            $logFile,
            implode("\n", array_keys($newEntries)) . "\n",
            FILE_APPEND
        );
    }
}
