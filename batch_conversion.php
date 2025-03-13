<?php
session_start();
require __DIR__ . '/functions.php';

// Proses request POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$batchFiles = $_SESSION['batch_files'] ?? [];
$dictionary = $_SESSION['dictionary'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Subtitle Conversion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="content/css/css.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="content/js/js.js?v=<?= time() ?>"></script>
</head>

<body class="batchConversion">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg" style="background-color: red;">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="index.php">Subtitle App</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">Upload Subtitle</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white active" href="batch_conversion.php">Batch Convert</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (empty($batchFiles)): ?>
        <!-- Form unggah file untuk batch conversion -->
        <div class="container d-flex justify-content-center align-items-center" style="height: calc(100vh - 175px);">
            <div class="container text-center">
                <h1>Upload Batch Subtitle Files (SRT or ASS)</h1>
                <form action="batch_conversion.php" method="post" enctype="multipart/form-data" class="mt-4">
                    <div class="input-group">
                        <input type="file" class="form-control" name="subtitle_files[]" accept=".srt,.ass" multiple required>
                        <button type="submit" name="upload_batch" class="btn btn-outline-primary">Upload Batch</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>

        <div class="text-center mt-3 mb-4">
            <h1><span class="badge bg-primary">Batch Subtitle Conversion</span></h1>
        </div>
        <!-- Tampilan kamus dan download form (sama seperti display.php) -->
        <?php include __DIR__ . '/includes/dictionary_form.php'; ?>
        <?php include __DIR__ . '/includes/batch_download_form.php'; ?>
        <?php include __DIR__ . '/includes/dictionary_list.php'; ?>

        <!-- Tab untuk setiap file -->
        <ul class="nav custom-tabs justify-content-center" id="batchTabs" role="tablist">
            <?php foreach ($batchFiles as $fileIndex => $file): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $fileIndex === 0 ? 'active' : '' ?>" id="custom-tab-<?= $fileIndex ?>" data-bs-toggle="tab" data-bs-target="#custom-file-<?= $fileIndex ?>" type="button" role="tab" aria-controls="custom-file-<?= $fileIndex ?>" aria-selected="<?= $fileIndex === 0 ? 'true' : 'false' ?>">
                        <?= htmlspecialchars($file['uploaded_file_name']) ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="tab-content custom-tab-content">
            <?php foreach ($batchFiles as $fileIndex => $file): ?>
                <div class="tab-pane fade <?= $fileIndex === 0 ? 'show active' : '' ?>" id="custom-file-<?= $fileIndex ?>" role="tabpanel" aria-labelledby="custom-tab-<?= $fileIndex ?>">
                    <?php
                    $currentFileIndex = $fileIndex;
                    $subtitles = $file['subtitles'];
                    include __DIR__ . '/batch_subtitle_table.php';
                    ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Kirim data kamus ke JavaScript -->
        <script>
            const fullDictionary = <?= json_encode($dictionary) ?>;
        </script>
    <?php endif; ?>
</body>

</html>