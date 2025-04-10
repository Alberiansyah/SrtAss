<h3 class="text-center mt-2 mb-4">Download Subtitle</h3>
<div class="container">
    <div class="row justify-content-center">
        <!-- Form untuk ASS Download -->
        <div class="col-md-5 text-center mb-3">
            <form method="post">
                <!-- Subtitle Type Selection (Anime / Movie) -->
                <div class="d-flex justify-content-left mb-3">
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="subtitle_type" value="anime" id="anime" checked>
                        <label class="form-check-label" for="anime">Anime</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="subtitle_type" value="movie" id="movie">
                        <label class="form-check-label" for="movie">Movie</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="subtitle_type" value="none" id="none">
                        <label class="form-check-label" for="none">None</label>
                    </div>
                </div>
                <!-- ASS Download Button -->
                <input type="hidden" name="format" value="ass">
                <button type="submit" name="download" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-download mx-1"></i> Download ASS
                </button>
            </form>
        </div>

        <!-- Form untuk SRT Download -->
        <div class="col-md-5 text-center mb-3">

            <div class="d-flex justify-content-center mb-3">
                <div class="form-check form-check-inline">
                </div>
            </div>
            <form method="post">
                <input type="hidden" name="format" value="srt">
                <button type="submit" name="download" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-download mx-1"></i> Download SRT
                </button>
            </form>
        </div>
    </div>
</div>