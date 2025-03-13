<!-- batch_download_form.php -->
<h3 class="text-center mt-2 mb-4">Download Batch Subtitles</h3>
<div class="container">
    <div class="row justify-content-center">
        <!-- Form Download ASS -->
        <div class="col-md-5 text-center mb-3">
            <form method="post" class="download-batch" action="batch_conversion.php">
                <!-- Opsi subtitle type -->
                <div class="d-flex justify-content-left mb-3">
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="subtitle_type" value="anime" id="anime" checked>
                        <label class="form-check-label" for="anime">Anime</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="subtitle_type" value="movie" id="movie">
                        <label class="form-check-label" for="movie">Movie</label>
                    </div>
                </div>
                <input type="hidden" name="format" value="ass">
                <button type="submit" name="batch_download" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-download mx-1"></i> Download ASS Batch
                </button>
            </form>
        </div>

        <!-- Form Download SRT -->
        <div class="col-md-5 text-center mb-3">
            <div class="d-flex justify-content-center mb-3">
                <div class="form-check form-check-inline">
                </div>
            </div>
            <form method="post" class="download-batch" action="batch_conversion.php">
                <input type="hidden" name="format" value="srt">
                <button type="submit" name="batch_download" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-download mx-1"></i> Download SRT Batch
                </button>
            </form>
        </div>
    </div>
</div>