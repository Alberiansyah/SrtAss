<div class="modal fade" id="nonIndonesianWordsModal" tabindex="-1" aria-labelledby="nonIndonesianWordsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info sticky-top">
                <h5 class="modal-title" id="nonIndonesianWordsModalLabel">Kata Tidak Dikenali</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Tab navigasi dengan sticky -->
                <div class="sticky-top bg-white" style="z-index: 1;">
                    <ul class="nav custom-tabs justify-content-center" id="nonIndonesianWordsTabs" role="tablist">
                        <?php if (isset($_SESSION['batch_files'])): ?>
                            <?php foreach ($_SESSION['batch_files'] as $index => $file): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $index === 0 ? 'active' : '' ?>"
                                        id="nonIndonesian-tab-<?= $index ?>"
                                        data-bs-toggle="tab"
                                        data-bs-target="#nonIndonesian-content-<?= $index ?>"
                                        type="button"
                                        role="tab"
                                        aria-controls="nonIndonesian-content-<?= $index ?>"
                                        aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                                        <?= htmlspecialchars($file['uploaded_file_name']) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active"
                                    id="nonIndonesian-tab-single"
                                    data-bs-toggle="tab"
                                    data-bs-target="#nonIndonesian-content-single"
                                    type="button"
                                    role="tab"
                                    aria-controls="nonIndonesian-content-single"
                                    aria-selected="true">
                                    <?= $_SESSION['file_name'] ?? 'File' ?>
                                </button>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Konten tab dengan scroll -->
                <div class="tab-content custom-tab-content p-3" style="max-height: 60vh; overflow-y: auto;">
                    <?php if (isset($_SESSION['batch_files'])): ?>
                        <?php foreach ($_SESSION['batch_files'] as $index => $file): ?>
                            <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>"
                                id="nonIndonesian-content-<?= $index ?>"
                                role="tabpanel"
                                aria-labelledby="nonIndonesian-tab-<?= $index ?>">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Baris</th>
                                                <th>Kata</th>
                                            </tr>
                                        </thead>
                                        <tbody id="nonIndonesianWordsList-<?= $index ?>">
                                            <!-- Konten akan diisi oleh JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="tab-pane fade show active"
                            id="nonIndonesian-content-single"
                            role="tabpanel"
                            aria-labelledby="nonIndonesian-tab-single">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Baris</th>
                                            <th>Kata</th>
                                        </tr>
                                    </thead>
                                    <tbody id="nonIndonesianWordsList-single">
                                        <!-- Konten akan diisi oleh JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="exportNonIndonesianWords">Export ke TXT</button>
            </div>
        </div>
    </div>
</div>