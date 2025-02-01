<?php
function handlePostRequest()
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
        }
    }

    // Memastikan kamus default
    if (!isset($_SESSION['dictionary'])) {
        $_SESSION['dictionary'] = [
            'Saya' => 'Aku',
            'saya' => 'aku',
            'Kamu' => 'Kau',
            'kamu' => 'kau',
            'Anda' => 'kau',
            'anda' => 'kau',
        ];
    }

    // Memastikan data subtitle disimpan di session
    if (!isset($_SESSION['subtitles'])) {
        $_SESSION['subtitles'] = []; // Inisialisasi jika belum ada
    }

    if (isset($_POST['add_to_dictionary'])) {
        $key = trim($_POST['key']);
        $value = trim($_POST['value']);
        if (!empty($key) && !empty($value)) {
            $_SESSION['dictionary'][$key] = $value;
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
        }
    }

    if (isset($_POST['download'])) {
        $format = $_POST['format'];
        $subtitles = $_SESSION['subtitles'];
        $originalFileName = pathinfo($_SESSION['uploaded_file_name'], PATHINFO_FILENAME);

        if ($format === 'srt') {
            $content = convertToSrt($subtitles);
            $fileName = $originalFileName . '.srt';
            header('Content-Type: text/srt');
        } elseif ($format === 'ass') {
            $styles = $_SESSION['styles'] ?? [];
            $content = convertToAss($subtitles, $styles);
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
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
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

    $inStylesSection = false;
    foreach ($lines as $line) {
        // Memasukkan bagian Styles ke dalam array
        if (strpos($line, '[V4+ Styles]') !== false) {
            $inStylesSection = true;
        }
        if ($inStylesSection) {
            if (strpos($line, 'Style:') === 0) {
                // Ambil data style dan simpan dalam array styles
                $parts = explode(",", $line);
                $styleName = trim(explode(":", $parts[0])[1]);
                $styles[$styleName] = $line;
            }
        }

        // Memasukkan bagian subtitle (Dialogue)
        if (strpos($line, 'Dialogue:') === 0) {
            $parts = explode(',', $line, 10); // Split into dialogue parts
            $start = $parts[1];
            $end = $parts[2];
            $style = $parts[3]; // Menyimpan style yang digunakan
            $text = $parts[9];
            $subtitles[] = [
                'start' => $start,
                'end' => $end,
                'style' => $style, // Menyimpan style yang digunakan
                'text' => trim($text)
            ];
        }
    }

    return ['subtitles' => $subtitles, 'styles' => $styles];
}

// Proses penggantian kata berdasarkan kamus
function replaceWords($text, $applyHighlight = true)
{
    if (isset($_SESSION['dictionary'])) {
        // Urutkan kamus berdasarkan panjang kata kunci
        uksort($_SESSION['dictionary'], function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        // Ganti kata berdasarkan kamus
        foreach ($_SESSION['dictionary'] as $key => $value) {
            $pattern = '/\b' . preg_quote($key, '/') . '\b/';
            if ($applyHighlight) {
                // Tambahkan highlight jika diperlukan
                $text = preg_replace($pattern, '<span style="background-color: #00ff33;">' . $value . '</span>', $text);
            } else {
                // Ganti tanpa highlight
                $text = preg_replace($pattern, $value, $text);
            }
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

function convertToAss($subtitles, $styles = [])
{
    $ass = "[Script Info]\n";
    $ass .= "; Script generated by Aegisub 3.4.1\n";
    $ass .= "; http://www.aegisub.org/\n";
    $ass .= "Title: Default Aegisub file\n";
    $ass .= "ScriptType: v4.00+\n";
    $ass .= "WrapStyle: 0\n";
    $ass .= "ScaledBorderAndShadow: yes\n";

    $ass .= "[Aegisub Project Garbage]\n";

    $ass .= "[V4+ Styles]\n";
    if (!empty($styles)) {
        foreach ($styles as $style) {
            $ass .= $style . "\n";
        }
    } else {
        // Tambahkan style default jika tidak ada
        $ass .= "Style: Default,GosmickSans,75,&H00FFFFFF,&H000000FF,&H00000000,&H00000000,-1,0,0,0,100,100,0,0,1,2.5,2,2,15,15,55,1\n";
    }

    $ass .= "\n[Events]\n";
    $ass .= "Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n";
    foreach ($subtitles as $subtitle) {
        $start = convertTimeToAss($subtitle['start']);
        $end = convertTimeToAss($subtitle['end']);
        $style = $subtitle['style'] ?? 'Default';
        $text = replaceWords($subtitle['text'], false); // Tidak menerapkan highlight
        $text = str_replace("\n", "\\N", $text); // Ganti baris baru dengan \N
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
