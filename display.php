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

        ::-webkit-scrollbar {
            width: 7.5px;
        }

        ::-webkit-scrollbar-thumb {

            background: #007bff;
            /* Warna biru */
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 10px;

        }

        ::-webkit-scrollbar-track {
            background: #e9ecef;
            /* Warna abu-abu muda */
            border-radius: 10px;
        }

        #searchInput {
            border-radius: 25px;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }

        #searchInput:focus {
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.25);
        }

        .dictionary-item {
            transition: all 0.3s ease;
        }

        .dictionary-item:hover {
            transform: translateX(5px);
        }

        .highlight {
            background-color: #ffeb3b;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
        }

        #searchResults .list-group-item {
            border-radius: 0.25rem;
            margin-bottom: 5px;
        }

        #clearSearch {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
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
    // Toggle dictionary grid
    $('#toggleDictionary').click(function() {
        $('#dictionaryGrid').toggle();
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

        const $originalGrid = $('#dictionaryGrid');
        const $searchResults = $('#searchResults');

        // Fungsi pencarian
        $('#searchInput').on('input', function() {
            const query = $(this).val().trim().toLowerCase();
            $searchResults.empty().hide();
            $originalGrid.show();

            if (query) {
                $originalGrid.hide();
                $searchResults.show();

                // Cari di semua item
                $('.dictionary-item').each(function() {
                    const $item = $(this);
                    const text = $item.find('.dictionary-text').text().toLowerCase();

                    if (text.includes(query)) {
                        // Clone item asli dan tambahkan highlight
                        const $clone = $item.clone();
                        const highlightedText = $clone.find('.dictionary-text').html()
                            .replace(new RegExp(`(${query})`, 'gi'), '<span class="highlight">$1</span>');

                        $clone.find('.dictionary-text').html(highlightedText);
                        $searchResults.append(
                            `<div class="col-12">` +
                            `<ul class="list-group">${$clone.prop('outerHTML')}</ul>` +
                            `</div>`
                        );
                    }
                });
            } else {
                $originalGrid.show();
                $searchResults.hide();
            }
        });

        // Tombol clear search
        $('#clearSearch').click(function() {
            $('#searchInput').val('').trigger('input');
        });
    });
</script>

</html>