<?php
session_start();
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handleBatchRequest();
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
        </div>
    </nav>

    <?php if (!empty($batchFiles)): ?>
        <div class="text-center mt-3 mb-4">
            <h1><span class="badge bg-primary">Batch Subtitle Conversion</span></h1>
        </div>
        <!-- Tampilan kamus dan download form (sama seperti display.php) -->
        <?php include __DIR__ . '/includes/dictionary-form.php'; ?>
        <?php include __DIR__ . '/includes/download-form-batch.php'; ?>
        <?php include __DIR__ . '/includes/dictionary-list.php'; ?>

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
                    include __DIR__ . '/includes/subtitle-table.php';
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="container mt-4">
            <p class="alert alert-warning text-center">No subtitle data found.</p>
        </div>
    <?php endif; ?>
</body>
<!-- Kirim data kamus ke JavaScript -->
<script>
    const fullDictionary = <?= json_encode($dictionary) ?>;
</script>

</html>