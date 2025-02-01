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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .editable {
            position: relative;
        }

        .text-display {
            cursor: pointer;
        }

        .text-edit {
            width: 100%;
            box-sizing: border-box;
        }

        .card {
            border: none;
            border-radius: 10px;
        }

        .card.shadow-sm {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-lg {
            padding: 10px 20px;
            font-size: 1.1rem;
        }

        .form-select,
        .form-control {
            border-radius: 5px;
            padding: 10px;
        }
    </style>
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

        <div class="text-center mt-3">
            <?php if (isset($_SESSION['file_name'])) : ?>
                <h1><span class="badge bg-primary"><?= $_SESSION['file_name'] ?></span></h1>
            <?php else : ?>
            <?php endif; ?>
        </div>
        <h2 class="text-center mt-4 mb-4">Subtitle Content</h2>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.getElementById('toggleDictionary').addEventListener('click', function() {
        const dictionaryGrid = document.getElementById('dictionaryGrid');
        if (dictionaryGrid.style.display === 'none') {
            dictionaryGrid.style.display = 'flex'; // Tampilkan grid
        } else {
            dictionaryGrid.style.display = 'none'; // Sembunyikan grid
        }
    });

    $(document).ready(function() {
        // Handle double-click to show input field
        $('.text-display').on('dblclick', function() {
            const $editable = $(this).closest('.editable');
            $editable.find('.text-display').hide();
            $editable.find('.text-edit').show().focus();
        });

        // Handle blur event to save changes
        $('.text-edit').on('blur', function() {
            const $editable = $(this).closest('.editable');
            const newText = $(this).val();
            const index = $editable.data('index');

            // Send AJAX request to save changes
            $.ajax({
                url: 'update_subtitle.php',
                method: 'POST',
                data: {
                    index: index,
                    text: newText
                },
                success: function(response) {
                    // Update the display text with the highlighted response
                    $editable.find('.text-display').html(response).show();
                    $editable.find('.text-edit').hide();
                    console.log('Text updated successfully!');
                },
                error: function() {
                    console.log('Failed to update text.');
                }
            });
        });

        // Handle Enter key to save changes
        $('.text-edit').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                $(this).blur(); // Trigger blur event to save changes
            }
        });
    });
</script>

</html>