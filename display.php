<?php
session_start();

require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handleSingleRequest();
}

$subtitles = $_SESSION['subtitles'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subtitle Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- <link rel="stylesheet" href="content/css/core.css">
    <link rel="stylesheet" href="content/css/theme-default.css">
    <link rel="stylesheet" href="content/css/demo.css"> -->
    <link rel="stylesheet" href="content/css/css.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="content/js/js.js?v=<?= time() ?>"></script>

</head>

<body>
    <nav class="navbar navbar-expand-lg" style="background-color: red;">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="index.php">Subtitle App</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <div class="text-center mt-3 mb-4">
        <?php if (isset($_SESSION['file_name'])) : ?>
            <h1><span class="badge bg-primary"><?= $_SESSION['file_name'] ?></span></h1>
        <?php else : ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($subtitles)): ?>
        <?php require __DIR__ . '/includes/dictionary-form.php'; ?>
        <?php require __DIR__ . '/includes/download-form.php'; ?>
        <?php require __DIR__ . '/includes/dictionary-list.php'; ?>
        <?php require __DIR__ . '/includes/subtitle-table.php'; ?>
    <?php else: ?>
        <div class="container">
            <p class="alert alert-warning text-center">No subtitle data found.</p>
        </div>
    <?php endif; ?>
    </div>
</body>
<script>
    // Simpan seluruh dictionary di JavaScript
    const fullDictionary = <?= json_encode($dictionary) ?>;
</script>

</html>