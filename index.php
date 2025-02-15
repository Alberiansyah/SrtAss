<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Subtitle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="wp-content/css/core.css">
    <link rel="stylesheet" href="wp-content/css/theme-default.css">
</head>

<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="container text-center" style="padding-bottom: 17.5vh;">

        <div class="container text-center">
            <h1>Upload Subtitle File (SRT or ASS)</h1>
            <form action="display.php" method="post" enctype="multipart/form-data" class="mt-4">
                <div class="input-group">
                    <input type="file" class="form-control" name="subtitle_file" accept=".srt,.ass" required>
                    <input type="hidden" name="file_name" id="file_name">
                    <button type="submit" name="upload" class="btn btn-outline-primary">Upload and Display</button>
                </div>
            </form>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

        <script>
            $('input[type="file"]').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $('#file_name').val(fileName);
            });
        </script>
    </div>

</body>

</html>