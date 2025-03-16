<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Subtitle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="container text-center" style="padding-bottom: 15.5vh;">

        <div class="container text-center">
            <h1>Upload Subtitle File (SRT or ASS)</h1>
            <form id="uploadForm" action="" method="post" enctype="multipart/form-data" class="mt-4">
                <div class="input-group">
                    <input type="file" class="form-control" id="fileInput" name="subtitle_files[]" accept=".srt,.ass" multiple required>
                    <button type="submit" name="upload" class="btn btn-outline-primary">Upload and Display</button>
                </div>
            </form>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

        <script>
            $('#uploadForm').on('submit', function(e) {
                var files = $('#fileInput')[0].files;
                if (files.length === 1) {
                    // Jika single file, ubah nama field menjadi "subtitle_file"
                    $('#fileInput').attr('name', 'subtitle_file');
                    this.action = 'display.php';
                } else if (files.length > 1) {
                    // Jika multiple file, pastikan nama field adalah "subtitle_files[]"
                    $('#fileInput').attr('name', 'subtitle_files[]');
                    this.action = 'display-batch.php';
                } else {
                    e.preventDefault();
                    alert('Please select at least one file.');
                }
            });
        </script>
    </div>
</body>

</html>