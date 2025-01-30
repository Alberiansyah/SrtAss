<?php
session_start();

require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handlePostRequest();
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
</head>

<body>
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="text-center">Subtitle Content</h2>
        <?php if (!empty($subtitles)): ?>
            <?php require __DIR__ . '/includes/dictionary_form.php'; ?>
            <?php require __DIR__ . '/includes/download_form.php'; ?>
    </div>
    <?php require __DIR__ . '/includes/dictionary_list.php'; ?>
    <?php require __DIR__ . '/includes/subtitle_table.php'; ?>
<?php else: ?>
    <p class="alert alert-warning text-center">No subtitle data found.</p>
<?php endif; ?>
</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('toggleDictionary').addEventListener('click', function() {
        const dictionaryGrid = document.getElementById('dictionaryGrid');
        if (dictionaryGrid.style.display === 'none') {
            dictionaryGrid.style.display = 'flex'; // Tampilkan grid
        } else {
            dictionaryGrid.style.display = 'none'; // Sembunyikan grid
        }
    });
</script>

</html>