<?php
session_start();
require __DIR__ . '/functions.php';

$dictionary = $_SESSION['dictionary'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Subtitle Editor - SrtAss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="content/css/video-editor.css?v=<?= time() ?>">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-gradient">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center text-white" href="index.php">
                <i class="fas fa-arrow-left me-2"></i>
                <i class="fas fa-closed-captioning me-2"></i>
                <span class="brand-text">Subtitle Editor</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light btn-sm" id="btnSaveSubtitle" disabled>
                    <i class="fas fa-save me-1"></i>Save
                </button>
                <button class="btn btn-light btn-sm" id="btnExportSubtitle" disabled>
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <a href="display.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-list me-1"></i>List
                </a>
            </div>
        </div>
    </nav>

    <div class="editor-container">
        <div class="video-section">
            <div class="video-wrapper" id="videoWrapper">
                <div class="upload-prompt" id="uploadPrompt">
                    <div class="text-center">
                        <i class="fas fa-film fa-4x mb-3"></i>
                        <h4>Upload Video & Subtitle</h4>
                        <p class="text-muted mb-0">Drag & drop atau klik untuk upload</p>
                    </div>
                </div>
                <video id="videoPlayer" class="video-player"></video>
                <div class="subtitle-overlay" id="subtitleOverlay"></div>
            </div>

            <div class="video-controls">
                <div class="timeline-container">
                    <div class="timeline" id="timeline">
                        <div class="timeline-progress" id="timelineProgress"></div>
                        <div class="timeline-playhead" id="timelinePlayhead"></div>
                        <div class="subtitle-markers" id="subtitleMarkers"></div>
                    </div>
                </div>
                <div class="controls-row">
                    <div class="time-display">
                        <span id="currentTime">00:00:00</span>
                        <span class="text-muted">/</span>
                        <span id="totalTime">00:00:00</span>
                    </div>
                    <div class="playback-controls">
                        <button class="btn-control" id="btnBackward" title="Backward 5s (←)">
                            <i class="fas fa-backward"></i>
                        </button>
                        <button class="btn-control btn-play" id="btnPlayPause" title="Play/Pause (Space)">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn-control" id="btnForward" title="Forward 5s (→)">
                            <i class="fas fa-forward"></i>
                        </button>
                    </div>
                    <div class="volume-control">
                        <button class="btn-control" id="btnMute" title="Mute">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <input type="range" class="volume-slider" id="volumeSlider" min="0" max="1" step="0.1" value="1">
                    </div>
                </div>
            </div>

                <div class="upload-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="upload-box" id="videoUpload">
                                <i class="fas fa-video fa-2x mb-2"></i>
                                <span>Video</span>
                                <input type="file" id="videoFile" accept=".mp4,.webm,.mkv,.avi,.mov">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="upload-box" id="subtitleUpload">
                                <i class="fas fa-closed-captioning fa-2x mb-2"></i>
                                <span>Subtitle</span>
                                <input type="file" id="subtitleFile" accept=".srt,.ass">
                            </div>
                        </div>
                    </div>
                    <div class="form-check mt-3 d-flex justify-content-center">
                        <input class="form-check-input" type="checkbox" id="showHighlights">
                        <label class="form-check-label ms-2" for="showHighlights">
                            Show dictionary highlights (Original → Replaced)
                        </label>
                    </div>
                </div>
        </div>

        <div class="edit-section">
            <div class="edit-panel">
                <div class="panel-header">
                    <h5><i class="fas fa-edit me-2"></i>Edit Subtitle</h5>
                </div>
                <div class="panel-body">
                    <div class="no-selection" id="noSelection">
                        <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                        <p>Pilih subtitle dari list atau timeline</p>
                    </div>
                    
                    <div class="edit-form" id="editForm" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-clock me-1"></i>Start Time
                            </label>
                            <div class="time-input-group">
                                <input type="number" class="form-control time-input" id="startH" min="0" max="99" value="00">
                                <span class="time-separator">:</span>
                                <input type="number" class="form-control time-input" id="startM" min="0" max="59" value="00">
                                <span class="time-separator">:</span>
                                <input type="number" class="form-control time-input" id="startS" min="0" max="59" value="00">
                                <span class="time-separator">,</span>
                                <input type="number" class="form-control time-input ms-0" id="startMs" min="0" max="999" step="100" value="000">
                            </div>
                            <button class="btn btn-sm btn-outline-primary mt-1 w-100" id="btnSetStart">
                                <i class="fas fa-flag me-1"></i>Set to Current Time
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-clock me-1"></i>End Time
                            </label>
                            <div class="time-input-group">
                                <input type="number" class="form-control time-input" id="endH" min="0" max="99" value="00">
                                <span class="time-separator">:</span>
                                <input type="number" class="form-control time-input" id="endM" min="0" max="59" value="00">
                                <span class="time-separator">:</span>
                                <input type="number" class="form-control time-input" id="endS" min="0" max="59" value="00">
                                <span class="time-separator">,</span>
                                <input type="number" class="form-control time-input ms-0" id="endMs" min="0" max="999" step="100" value="000">
                            </div>
                            <button class="btn btn-sm btn-outline-primary mt-1 w-100" id="btnSetEnd">
                                <i class="fas fa-flag me-1"></i>Set to Current Time
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-align-left me-1"></i>Text
                            </label>
                            <textarea class="form-control subtitle-textarea" id="subtitleText" rows="3" placeholder="Enter subtitle text..."></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-success flex-grow-1" id="btnApply">
                                <i class="fas fa-check me-1"></i>Apply
                            </button>
                            <button class="btn btn-outline-danger" id="btnDelete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="subtitle-list-panel">
                <div class="panel-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Subtitles</h5>
                    <span class="badge bg-secondary" id="subtitleCount">0</span>
                </div>
                <div class="subtitle-list" id="subtitleList">
                    <div class="empty-list">
                        <i class="fas fa-closed-captioning fa-2x mb-2"></i>
                        <p>Upload subtitle untuk melihat list</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h6 class="modal-title" id="confirmTitle">Confirm</h6>
                    <button type="button" class="btn-close btn-close-white" id="confirmClose" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer border-secondary">
                    <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger btn-sm" id="confirmOk"><i class="fas fa-check me-1"></i>OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const fullDictionary = <?= json_encode($dictionary) ?>;
        let _confirmCallback = null;
        function showConfirm(title, message, callback) {
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            _confirmCallback = callback;
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('confirmOk').onclick = function() {
                modal.hide();
                if (_confirmCallback) { _confirmCallback(); _confirmCallback = null; }
            };
            modal.show();
        }
    </script>
    <script src="content/js/video-editor.js?v=<?= time() ?>"></script>
</body>

</html>
