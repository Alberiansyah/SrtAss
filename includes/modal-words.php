<div class="modal-overlay" id="nonIndonesianWordsModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Kata Tidak Dikenali</h3>
            <button class="close-btn" onclick="closeModal('nonIndonesianWordsModal'); $('.modal-backdrop').remove(); document.body.classList.remove('modal-open');">&times;</button>
        </div>
        <div class="modal-body p-0">
            <div class="sticky-top modal-tabs-wrapper">
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

            <div class="tab-content custom-tab-content p-3" style="max-height: 50vh; overflow-y: auto;">
                <?php if (isset($_SESSION['batch_files'])): ?>
                    <?php foreach ($_SESSION['batch_files'] as $index => $file): ?>
                        <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>"
                            id="nonIndonesian-content-<?= $index ?>"
                            role="tabpanel"
                            aria-labelledby="nonIndonesian-tab-<?= $index ?>">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="custom-table-header">
                                        <tr>
                                            <th>Baris</th>
                                            <th>Kata</th>
                                        </tr>
                                    </thead>
                                    <tbody id="nonIndonesianWordsList-<?= $index ?>">
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
                                <thead class="custom-table-header">
                                    <tr>
                                        <th>Baris</th>
                                        <th>Kata</th>
                                    </tr>
                                </thead>
                                <tbody id="nonIndonesianWordsList-single">
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('nonIndonesianWordsModal'); $('.modal-backdrop').remove(); document.body.classList.remove('modal-open');">Tutup</button>
            <button type="button" class="btn btn-primary" id="exportNonIndonesianWords">
                <i class="fas fa-download me-2"></i>Export ke TXT
            </button>
        </div>
    </div>
</div>

<style>
.modal-tabs-wrapper {
    background: var(--bg-surface, #1a1a24);
    padding: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

[data-theme="light"] .modal-tabs-wrapper {
    background: #f8f9fa;
    border-bottom: 1px solid #e2e8f0;
}

.custom-table-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.custom-table-header th {
    font-weight: 600;
    border: none;
    padding: 12px 15px;
}

.custom-table-header th:first-child {
    border-radius: 8px 0 0 0;
}

.custom-table-header th:last-child {
    border-radius: 0 8px 0 0;
}

[data-theme="light"] .custom-table-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

[data-theme="light"] .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(102, 126, 234, 0.05);
}

[data-theme="light"] .table-bordered {
    border-color: #e2e8f0;
}

[data-theme="light"] .table-bordered td,
[data-theme="light"] .table-bordered th {
    border-color: #e2e8f0;
    color: #1e293b;
}

[data-theme="light"] .custom-tab-content {
    background: white;
}

[data-theme="light"] .modal .nav-link {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 20px;
    font-weight: 500;
    color: #666;
    background: #f8f9fa;
    margin-right: 8px;
    transition: all 0.3s ease;
}

[data-theme="light"] .modal .nav-link:hover {
    border-color: #667eea;
    color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

[data-theme="light"] .modal .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
}

/* Dark theme table styles */
[data-theme="dark"] .table-bordered {
    border-color: rgba(255, 255, 255, 0.08);
}

[data-theme="dark"] .table-bordered td,
[data-theme="dark"] .table-bordered th {
    border-color: rgba(255, 255, 255, 0.08);
    color: #e5e7eb;
    background: #1f1f2a;
}

[data-theme="dark"] .table-striped tbody tr:nth-of-type(odd) td {
    background: rgba(255, 255, 255, 0.02);
}

[data-theme="dark"] .table-striped tbody tr:nth-of-type(even) td {
    background: #1a1a24;
}

[data-theme="dark"] .custom-tab-content {
    background: #1a1a24;
}

[data-theme="dark"] .modal .nav-link {
    border: 2px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 12px 20px;
    font-weight: 500;
    color: #9ca3af;
    background: rgba(255, 255, 255, 0.03);
    margin-right: 8px;
    transition: all 0.3s ease;
}

[data-theme="dark"] .modal .nav-link:hover {
    border-color: #667eea;
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

[data-theme="dark"] .modal .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('nonIndonesianWordsModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal('nonIndonesianWordsModal');
                $('.modal-backdrop').remove();
                document.body.classList.remove('modal-open');
            }
        });
    }
});
</script>
