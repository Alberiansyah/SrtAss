<?php
session_start();

require __DIR__ . '/functions.php';

if (!isset($_SESSION['non_indonesian_words'])) {
    $_SESSION['non_indonesian_words'] = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handleSingleRequest();
}

$subtitles = $_SESSION['subtitles'] ?? [];
$dictionary = $_SESSION['dictionary'] ?? [];
$fileName = $_SESSION['file_name'] ?? 'Untitled';
$styles = $_SESSION['styles'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor - <?= htmlspecialchars($fileName) ?></title>
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="content/css/theme.css">
    <link rel="stylesheet" href="content/css/editor.css?v=<?= time() ?>">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        [data-theme="light"] {
            --bg-body: #e2e6ec;
            --bg-navbar: rgba(234, 238, 245, 0.95);
            --bg-surface: #eef1f6;
            --bg-surface-hover: #e3e8ef;
            --bg-input: #ffffff;
            --border-color: #ced4de;
            --border-color-hover: #bcc4d0;
            --text-primary: #1a2433;
            --text-secondary: #536476;
            --text-muted: #7d8e9e;
            --text-inverse: #ffffff;
        }
        
        [data-theme="dark"] {
            --bg-body: #0f0f14;
            --bg-navbar: #18181f;
            --bg-surface: #1f1f2a;
            --bg-surface-hover: #252530;
            --bg-input: #0f0f14;
            --border-color: #2a2a35;
            --border-color-hover: #3a3a45;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --text-inverse: #0f0f14;
        }
        
        [data-theme="light"] .editor-container,
        [data-theme="light"] .editor-header,
        [data-theme="light"] .properties-panel,
        [data-theme="light"] .subtitle-list-panel,
        [data-theme="light"] .panel-header,
        [data-theme="light"] .subtitle-row {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .editor-container,
        [data-theme="light"] .editor-header,
        [data-theme="light"] .editor-main,
        [data-theme="light"] .properties-panel,
        [data-theme="light"] .subtitle-list-panel,
        [data-theme="light"] .subtitle-list-header {
            background: var(--bg-body);
        }
        
        [data-theme="light"] .editor-header .logo,
        [data-theme="light"] .editor-header .logo .divider,
        [data-theme="light"] .editor-header .logo .file-name,
        [data-theme="light"] .subtitle-times,
        [data-theme="light"] .subtitle-original,
        [data-theme="light"] .subtitle-modified,
        [data-theme="light"] .subtitle-search input,
        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group select,
        [data-theme="light"] .panel-header h4,
        [data-theme="light"] .time-display,
        [data-theme="light"] .timeline-btn,
        [data-theme="light"] .modal-content,
        [data-theme="light"] .toast p {
            color: var(--text-primary);
        }
        
        [data-theme="light"] .subtitle-search input,
        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group select {
            background: var(--bg-input);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .btn-secondary {
            background: var(--bg-surface);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .btn-secondary:hover {
            background: var(--bg-surface-hover);
        }
        
        [data-theme="light"] .editor-header .btn-secondary {
            background: var(--bg-surface);
            border-color: var(--border-color);
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .editor-header .btn-secondary:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        [data-theme="light"] .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        [data-theme="light"] .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        /* Modal Footer Buttons */
        [data-theme="light"] .modal-footer .btn-secondary {
            background: var(--bg-surface);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .modal-footer .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        /* Modal Theme Support */
        [data-theme="light"] .modal-content {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .modal-header {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .modal-header h3 {
            color: #0f172a;
        }
        
        [data-theme="light"] .modal-header h3::before {
            background: linear-gradient(to bottom, #6366f1, #8b5cf6);
        }
        
        [data-theme="light"] .modal-header .close-btn {
            background: var(--bg-surface-hover);
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .modal-header .close-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        [data-theme="light"] .modal-body {
            background: var(--bg-surface);
        }
        
        [data-theme="light"] .modal-footer {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .shortcut-item {
            background: var(--bg-surface-hover);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .shortcut-item:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.4);
        }
        
        [data-theme="light"] .shortcut-item span:first-child {
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .shortcut-key {
            background: var(--bg-surface-hover);
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        
        /* Subtitle List Theme Support */
        [data-theme="light"] .subtitle-row {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .subtitle-row:hover {
            background: var(--bg-surface-hover);
            border-color: var(--primary);
        }
        
        [data-theme="light"] .subtitle-row.active {
            background: var(--bg-surface-hover);
            border-color: var(--primary);
        }
        
        [data-theme="light"] .subtitle-row.selected {
            background: rgba(99, 102, 241, 0.1);
            border-color: var(--primary);
        }
        
        [data-theme="light"] .subtitle-row.editing {
            background: var(--bg-input);
        }
        
        [data-theme="light"] .subtitle-index {
            color: var(--text-muted);
        }
        
        [data-theme="light"] .subtitle-times {
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .subtitle-original {
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .subtitle-modified {
            color: var(--text-primary);
        }
        
        [data-theme="light"] .video-panel {
            background: #000;
        }
        
        [data-theme="light"] .timeline-panel {
            background: #f1f5f9;
            border-top-color: #e2e8f0;
        }
        
        [data-theme="light"] .timeline-btn {
            background: #e2e8f0;
            color: #475569;
        }
        
        [data-theme="light"] .timeline-btn:hover {
            background: #cbd5e1;
            color: #0f172a;
        }
        
        [data-theme="light"] .timeline-btn.play-btn {
            background: #6366f1;
            color: #fff;
        }
        
        [data-theme="light"] .timeline-btn.play-btn:hover {
            background: #4f46e5;
        }
        
        [data-theme="light"] .time-display {
            background: #fff;
            color: #1e293b;
            border-color: #e2e8f0;
        }
        
        [data-theme="light"] .subtitle-block {
            background: rgba(30, 41, 59, 0.5);
        }
        
        [data-theme="light"] .subtitle-block:hover {
            background: rgba(99, 102, 241, 0.8);
        }
        
        [data-theme="light"] .subtitle-block.active {
            background: rgba(99, 102, 241, 0.9);
        }
        
        [data-theme="light"] .properties-panel {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .panel-section {
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .panel-header {
            background: var(--bg-surface-hover);
        }
        
        [data-theme="light"] .panel-header h4 {
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .dictionary-entry {
            background: var(--bg-input);
        }
        
        [data-theme="light"] .subtitle-search input {
            background: var(--bg-input);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .subtitle-list-header {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .batch-toolbar {
            background: var(--bg-surface-hover);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .batch-toolbar .et-btn {
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .batch-toolbar .et-btn:hover {
            background: var(--bg-surface);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .subtitle-list-panel {
            background: var(--bg-body);
            border-color: var(--border-color);
        }
        
        /* Fix hover readability for subtitle text */
        [data-theme="light"] .subtitle-row:hover .subtitle-original {
            color: #334155;
        }
        
        [data-theme="light"] .subtitle-row:hover .subtitle-modified {
            color: #0f172a;
        }
        
        [data-theme="light"] .subtitle-row:hover .subtitle-index {
            color: #475569;
        }
        
        [data-theme="light"] .subtitle-row:hover .subtitle-times .time-start {
            color: #059669;
        }
        
        [data-theme="light"] .subtitle-row:hover .subtitle-times .time-end {
            color: #d97706;
        }
        
        /* Override hardcoded dark colors */
        [data-theme="light"] .editor-container {
            background: var(--bg-body);
        }
        
        [data-theme="light"] .editor-header {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .editor-header .logo,
        [data-theme="light"] .editor-header .logo .divider,
        [data-theme="light"] .editor-header .logo .file-name {
            color: var(--text-primary);
        }
        
        [data-theme="light"] .editor-header .logo i {
            color: var(--primary);
        }
        
        [data-theme="light"] .subtitle-list-panel {
            background: var(--bg-body);
        }
        
        [data-theme="light"] .subtitle-list-header {
            background: var(--bg-surface);
        }
        
        [data-theme="light"] .subtitle-row {
            background: var(--bg-surface);
        }
        
        [data-theme="light"] .subtitle-row.active {
            background: var(--bg-surface-hover);
        }
        
        [data-theme="light"] .subtitle-row.editing {
            background: var(--bg-input);
        }
        
        [data-theme="light"] .properties-panel {
            background: var(--bg-surface);
        }
        
        [data-theme="light"] .panel-header {
            background: var(--bg-surface-hover);
        }
        
        [data-theme="light"] .dictionary-entry {
            background: var(--bg-input);
        }
        
        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group select {
            background: var(--bg-input);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .edit-toolbar {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        
        [data-theme="light"] .edit-toolbar .et-btn {
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .edit-toolbar .et-btn:hover {
            background: var(--bg-surface-hover);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .edit-editor {
            background: var(--bg-input);
            border-color: var(--primary);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .btn-secondary {
            background: var(--bg-surface);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .btn-secondary:hover {
            background: var(--bg-surface-hover);
        }
        
        [data-theme="light"] .editor-header .btn-secondary {
            background: var(--bg-surface);
            border-color: var(--border-color);
            color: var(--text-secondary);
        }
        
        [data-theme="light"] .editor-header .btn-secondary:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        [data-theme="light"] .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        [data-theme="light"] .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        /* Modal Footer Buttons */
        [data-theme="light"] .modal-footer .btn-secondary {
            background: var(--bg-surface);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .modal-footer .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* Dictionary light theme overrides */
        [data-theme="light"] .dictionary-input input {
            background: var(--bg-input);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        [data-theme="light"] .dict-search {
            background: var(--bg-input);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        [data-theme="light"] .dict-search::placeholder {
            color: var(--text-muted);
        }

        [data-theme="light"] .dictionary-entry .original {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.08);
        }

        [data-theme="light"] .dictionary-entry .converted {
            color: var(--success);
            background: rgba(16, 185, 129, 0.08);
        }

        [data-theme="light"] .dictionary-empty {
            color: var(--text-muted);
        }

        [data-theme="light"] .btn-clear-session {
            border-color: var(--border-color);
            color: var(--danger);
        }

        [data-theme="light"] .btn-clear-session:hover {
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.05);
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="editor-container">
        <!-- Header -->
        <header class="editor-header">
            <div class="logo">
                <i class="fas fa-closed-captioning"></i>
                <span>SrtAss</span>
                <span class="divider">|</span>
                <span class="file-name"><?= htmlspecialchars($fileName) ?></span>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary btn-sm" onclick="window.location.href='index.php'" title="Home">
                    <i class="fas fa-home"></i>
                </button>
                <button id="themeToggle" class="btn btn-secondary btn-sm" onclick="toggleTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="showShortcuts()" title="Keyboard Shortcuts">
                    <i class="fas fa-keyboard"></i>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="showStatistics()" title="Statistics">
                    <i class="fas fa-chart-bar"></i>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="showTiming()" title="Timing Adjustment">
                    <i class="fas fa-clock"></i>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="showSettings()" title="Settings">
                    <i class="fas fa-cog"></i>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="clearSession()" title="New File">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="btn btn-primary btn-sm" onclick="toggleDownloadMenu(event)">
                    <i class="fas fa-download me-1"></i> Download
                </button>
                <div class="dropdown-menu" id="downloadDropdown">
                    <a class="dropdown-item" href="#" onclick="downloadSubtitle('ass')">Export as ASS</a>
                    <a class="dropdown-item" href="#" onclick="downloadSubtitle('srt')">Export as SRT</a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="editor-main">
            <div class="main-content">
                <!-- Video Panel -->
                <div class="video-panel" id="videoPanel">
                    <div class="video-wrapper" id="videoWrapper">
                        <div class="video-placeholder">
                            <i class="fas fa-film"></i>
                            <p>Drop video file here or click to load</p>
                        </div>
                        <video id="videoPlayer" playsinline crossorigin="anonymous" style="display: none;">
                        </video>
                        <div class="video-subtitle-overlay" id="videoSubtitleOverlay"></div>
                        <div class="video-controls">
                            <button onclick="document.getElementById('videoInput').click()" title="Load Video">
                                <i class="fas fa-folder-open"></i>
                            </button>
                            <button onclick="toggleFullscreen()" title="Fullscreen">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button onclick="resizeVideoPanel(-1)" title="Smaller">
                                <i class="fas fa-compress"></i>
                            </button>
                            <button onclick="resizeVideoPanel(1)" title="Larger">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                            <button onclick="toggleSubtitlePosition()" id="subtitlePosBtn" title="Subtitle position">
                                <i class="fas fa-chevron-down" id="subtitlePosIcon"></i>
                            </button>
                        </div>
                        <input type="file" id="videoInput" accept="video/*" style="display: none;">
                    </div>
                </div>

                <!-- Timeline Panel -->
                <div class="timeline-panel">
                    <div class="timeline-toolbar">
                        <button class="timeline-btn play-btn" id="playBtn" onclick="togglePlay()">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="timeline-btn" onclick="skipBackward()" title="Skip -5s">
                            <i class="fas fa-backward"></i>
                        </button>
                        <button class="timeline-btn" onclick="skipForward()" title="Skip +5s">
                            <i class="fas fa-forward"></i>
                        </button>
                        <div class="time-display">
                            <span id="currentTime">00:00:00</span>
                            <span> / </span>
                            <span id="totalTime">00:00:00</span>
                        </div>
                        <button class="timeline-btn" onclick="prevSubtitle()" title="Previous Subtitle">
                            <i class="fas fa-chevron-up"></i>
                        </button>
                        <button class="timeline-btn" onclick="nextSubtitle()" title="Next Subtitle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="timeline-slider-wrapper">
                            <div class="timeline-ruler">
                                <canvas id="timelineRuler"></canvas>
                            </div>
                            <div class="timeline-progress-container">
                                <div class="timeline-progress" id="timelineProgress"></div>
                                <div class="subtitle-blocks" id="subtitleBlocks"></div>
                                <input type="range" class="timeline-slider" id="timelineSlider" min="0" max="100" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="aegisub-info" id="aegisubInfo"></div>
                </div>

                <!-- Subtitle List Panel -->
                <div class="subtitle-list-panel">
                    <div class="subtitle-list-header">
                        <div class="subtitle-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="subtitleSearch" placeholder="Search subtitles...">
                        </div>
                        <span class="badge bg-primary" id="subtitleCount"><?= count($subtitles) ?> lines</span>
                        <label class="delete-warn-toggle" title="Aktif: tampilkan konfirmasi sebelum delete. Nonaktif: tombol delete langsung menghapus tanpa konfirmasi.">
                            <input type="checkbox" id="deleteWarnToggle"> Confirm delete
                        </label>
                        <div class="batch-toolbar" id="batchToolbar">
                            <span class="batch-label"><span id="batchCount">0</span> selected</span>
                            <span class="et-sep"></span>
                            <button type="button" class="et-btn" data-cmd="bold" title="Bold"><b>B</b></button>
                            <button type="button" class="et-btn" data-cmd="italic" title="Italic"><i>I</i></button>
                            <button type="button" class="et-btn" data-cmd="underline" title="Underline"><u>U</u></button>
                            <button type="button" class="et-btn" data-cmd="strike" title="Strike-through"><s>S</s></button>
                            <span class="et-sep"></span>
                            <button type="button" class="et-btn et-merge" id="batchMerge" title="Merge selected"><i class="fas fa-compress-alt"></i></button>
                            <button type="button" class="et-btn et-danger" id="batchDelete" title="Delete selected"><i class="fas fa-trash"></i></button>
                            <button type="button" class="et-btn et-cancel" id="batchClear" title="Clear selection">&#10007;</button>
                        </div>
                    </div>
                    <div class="subtitle-list" id="subtitleList">
                        <?php $_SESSION['non_indonesian_words']['single'] = []; ?>
                        <?php foreach ($subtitles as $index => $subtitle): ?>
                        <div class="subtitle-row" draggable="true" data-index="<?= $index ?>">
                            <div class="subtitle-index"><i class="fas fa-grip-lines drag-handle"></i> <?= $index + 1 ?></div>
                            <div class="subtitle-times">
                                <span class="time-start"><?= htmlspecialchars($subtitle['start']) ?></span>
                                <span class="time-end"><?= htmlspecialchars($subtitle['end']) ?></span>
                            </div>
                            <div class="subtitle-text-container">
                                <div class="subtitle-original" data-index="<?= $index ?>"><?= highlightIndonesiaWords($subtitle['text'], $index + 1) ?></div>
                                <div class="subtitle-modified" data-index="<?= $index ?>"><?= replaceWords(htmlspecialchars($subtitle['text'])) ?></div>
                            </div>
                            <div class="subtitle-warnings"></div>
                            <div class="subtitle-actions">
                                <button class="play-from-btn" data-time="<?= htmlspecialchars($subtitle['start'], ENT_QUOTES, 'UTF-8') ?>" title="Play from here">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="delete-subtitle-btn" data-index="<?= $index ?>" title="Delete subtitle">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <textarea id="subtitleDataStore" style="display:none"><?php
                        $subtitleData = array_map(function($s) {
                            $s['text'] = replaceWords($s['text'], false);
                            return $s;
                        }, $subtitles);
                        echo htmlspecialchars(json_encode($subtitleData), ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                </div>
            </div>

            <!-- Properties Panel (Right Sidebar) -->
            <div class="properties-panel">
                <!-- Video File Panel -->
                <div class="panel-section">
                    <div class="panel-header" onclick="togglePanel(this)">
                        <h4><i class="fas fa-video me-2"></i>Video</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="panel-content">
                        <div class="video-upload-zone" id="videoUploadZone">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Drop video here or click</span>
                            <input type="file" id="videoFileInput" accept="video/*">
                        </div>
                        <div class="form-group" style="margin-top:0.5rem">
                            <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;font-size:0.75rem">
                                <input type="checkbox" id="stylePreviewToggle"> ASS Style Preview
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Dictionary Panel -->
                <div class="panel-section collapsed">
                    <div class="panel-header" onclick="togglePanel(this)">
                        <h4><i class="fas fa-book me-2"></i>Dictionary <span class="dict-count" id="dictCount">(<?= count($dictionary) ?>)</span></h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="panel-content">
                        <div class="dictionary-input">
                            <input type="text" id="dictKey" placeholder="Original" title="Original word">
                            <input type="text" id="dictValue" placeholder="Replacement" title="Replacement word">
                            <button class="btn-add" onclick="addDictionary()" title="Add to dictionary">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <input type="text" id="dictSearch" class="dict-search" placeholder="Search dictionary..." title="Filter dictionary entries">
                        <div class="dictionary-list" id="dictionaryList">
                            <?php if (empty($dictionary)): ?>
                            <div class="dictionary-empty">
                                <i class="fas fa-book-open"></i>
                                No dictionary entries yet
                            </div>
                            <?php else: ?>
                            <?php foreach ($dictionary as $key => $value): ?>
                            <div class="dictionary-entry" data-key="<?= htmlspecialchars($key, ENT_QUOTES) ?>" data-value="<?= htmlspecialchars($value, ENT_QUOTES) ?>">
                                <div class="words">
                                    <span class="original"><?= htmlspecialchars($key) ?></span>
                                    <span class="arrow">→</span>
                                    <span class="converted"><?= htmlspecialchars($value) ?></span>
                                </div>
                                <button class="delete-btn" data-key="<?= htmlspecialchars($key, ENT_QUOTES) ?>" title="Remove entry">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form method="post">
                            <button type="submit" name="clear_session" class="btn-clear-session">
                                <i class="fas fa-trash-alt"></i> Clear Session
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Download Panel -->
                <div class="panel-section">
                    <div class="panel-header" onclick="togglePanel(this)">
                        <h4><i class="fas fa-download me-2"></i>Export</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="panel-content">
                        <div class="form-group">
                            <label>Format</label>
                            <select id="exportFormat">
                                <option value="ass" selected>ASS (Advanced SubStation Alpha)</option>
                                <option value="srt">SRT (SubRip)</option>
                                <option value="vtt">VTT (WebVTT)</option>
                                <option value="sbv">SBV (YouTube)</option>
                                <option value="txt">TXT (Plain Text)</option>
                            </select>
                            <div class="form-group" id="txtTimingOption" style="display:none;">
                                <label style="font-size:0.75rem;color:var(--text-secondary);display:flex;align-items:center;gap:0.4rem;cursor:pointer">
                                    <input type="checkbox" id="includeTiming" checked> Include timing timestamps
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Subtitle Type</label>
                            <select id="subtitleType">
                                <option value="anime">Anime</option>
                                <option value="movie">Movie</option>
                                <option value="none">Original Styles</option>
                            </select>
                        </div>
                        <button class="btn btn-success w-100" onclick="downloadSubtitle()">
                            <i class="fas fa-download me-2"></i>Download
                        </button>
                    </div>
                </div>

                <!-- Non-Indonesian Words Panel -->
                <?php if (ENABLE_WORD_HIGHLIGHT && ENABLE_NON_INDONESIAN_WORD_LOGGING): ?>
                <div class="panel-section">
                    <div class="panel-header" onclick="togglePanel(this)">
                        <h4><i class="fas fa-exclamation-triangle me-2"></i>Unknown Words</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="panel-content">
                        <button class="btn btn-warning btn-sm w-100" onclick="showUnknownWords()">
                            <i class="fas fa-list me-2"></i>View All
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Dictionary Changes Panel -->
                <div class="panel-section">
                    <div class="panel-header" onclick="togglePanel(this)">
                        <h4><i class="fas fa-exchange-alt me-2"></i>Dictionary Changes</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="panel-content">
                        <button class="btn btn-info btn-sm w-100" onclick="showDictionaryChanges()">
                            <i class="fas fa-list me-2"></i>View All
                        </button>
                    </div>
                </div>

                <!-- Readability Panel -->
                <div class="panel-section collapsed">
                    <div class="panel-header" onclick="togglePanel(this)">
                        <h4><i class="fas fa-eye me-2"></i>Readability <span id="readabilityCount" style="font-weight:400;font-size:0.7rem;color:var(--text-muted)"></span></h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="panel-content">
                        <div id="readabilitySummary" class="readability-summary"></div>
                        <button class="btn btn-warning btn-sm w-100" onclick="showReadabilityIssues()">
                            <i class="fas fa-list me-2"></i>View Issues
                        </button>
                    </div>
                </div>

                <!-- Version History Panel -->
                <div class="panel-section">
                    <div class="panel-header" onclick="togglePanel(this)">
                        <h4><i class="fas fa-history me-2"></i>History <span id="versionCount" style="font-weight:400;font-size:0.7rem;color:var(--text-muted)"></span></h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="panel-content">
                        <div style="display:flex;gap:0.3rem">
                            <button class="btn btn-sm btn-secondary" onclick="saveManualSnapshot()" style="flex:1;font-size:0.7rem">
                                <i class="fas fa-camera me-1"></i>Snapshot
                            </button>
                            <button class="btn btn-sm btn-info" onclick="showVersionHistory()" style="flex:1;font-size:0.7rem">
                                <i class="fas fa-list me-1"></i>Versions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Modal -->
    <div class="modal-overlay" id="statisticsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Subtitle Statistics</h3>
                <button class="close-btn" onclick="closeModal('statisticsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="stats-grid" id="statsContainer">
                    <div class="stat-card"><div class="stat-value" id="statSubs">0</div><div class="stat-label">Total Subtitles</div></div>
                    <div class="stat-card"><div class="stat-value" id="statDuration">00:00</div><div class="stat-label">Duration</div></div>
                    <div class="stat-card"><div class="stat-value" id="statWords">0</div><div class="stat-label">Total Words</div></div>
                    <div class="stat-card"><div class="stat-value" id="statChars">0</div><div class="stat-label">Total Characters</div></div>
                    <div class="stat-card"><div class="stat-value" id="statCPS">0.0</div><div class="stat-label">Avg Reading Speed (cps)</div></div>
                    <div class="stat-card"><div class="stat-value" id="statCPL">0.0</div><div class="stat-label">Avg Chars per Line</div></div>
                    <div class="stat-card"><div class="stat-value" id="statWPL">0.0</div><div class="stat-label">Avg Words per Line</div></div>
                    <div class="stat-card"><div class="stat-value" id="statUnknown">0</div><div class="stat-label">Unknown Words</div></div>
                </div>
                <div class="stats-section">
                    <h4 style="margin:1rem 0 0.5rem;font-size:0.95rem;color:var(--text-secondary)">Most Common Words</h4>
                    <div id="statsTopWords" class="stats-tags"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timing Adjustment Modal -->
    <div class="modal-overlay" id="timingModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3>Timing Adjustment</h3>
                <button class="close-btn" onclick="closeModal('timingModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom:1rem">
                    <label style="font-size:0.85rem;color:var(--text-secondary);display:block;margin-bottom:0.3rem">Shift offset (ms)</label>
                    <div style="display:flex;gap:0.5rem">
                        <input type="number" id="timingOffset" value="0" class="form-control" style="flex:1" placeholder="e.g. -500 or +1000">
                        <button class="btn btn-primary" onclick="applyTimingShift()">Apply</button>
                    </div>
                    <small style="color:var(--text-muted);font-size:0.75rem">Negative = earlier, Positive = later</small>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:var(--text-secondary);display:block;margin-bottom:0.3rem">Scale to fit video duration</label>
                    <div style="display:flex;gap:0.5rem">
                        <input type="text" id="timingVideoDuration" class="form-control" style="flex:1" placeholder="e.g. 02:30:00">
                        <button class="btn btn-primary" onclick="applyTimingScale()">Scale</button>
                    </div>
                    <small style="color:var(--text-muted);font-size:0.75rem">Current: <span id="timingCurrentDur">--:--:--</span></small>
                </div>
                <hr style="border-color:var(--border-color);margin:0.75rem 0">
                <div>
                    <label style="font-size:0.85rem;color:var(--text-secondary);display:block;margin-bottom:0.3rem">Auto-Timing</label>
                    <div style="display:flex;flex-direction:column;gap:0.4rem">
                        <div style="display:flex;align-items:center;gap:0.5rem">
                            <label style="font-size:0.75rem;color:var(--text-muted);min-width:70px">Target CPS:</label>
                            <input type="number" id="autoTargetCps" value="15" min="5" max="30" class="form-control" style="width:70px;padding:0.3rem;font-size:0.75rem">
                        </div>
                        <div style="display:flex;gap:0.3rem">
                            <button class="btn btn-sm btn-warning" onclick="autoNormalizeDuration()" style="flex:1;font-size:0.7rem;padding:0.3rem 0.4rem">
                                <i class="fas fa-clock me-1"></i>Normalize
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="autoFixOverlaps()" style="flex:1;font-size:0.7rem;padding:0.3rem 0.4rem">
                                <i class="fas fa-compress me-1"></i>Fix Overlaps
                            </button>
                        </div>
                    </div>
                    <small style="color:var(--text-muted);font-size:0.7rem">Adjusts duration based on reading speed. Detects overlap issues.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('timingModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Keyboard Shortcuts Modal -->
    <div class="modal-overlay" id="shortcutsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Keyboard Shortcuts</h3>
                <button class="close-btn" onclick="closeModal('shortcutsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="shortcuts-grid">
                    <div class="shortcut-item">
                        <span>Play/Pause</span>
                        <span class="shortcut-key">Space</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Skip Back</span>
                        <span class="shortcut-key">←</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Skip Forward</span>
                        <span class="shortcut-key">→</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Prev Subtitle</span>
                        <span class="shortcut-key">↑</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Next Subtitle</span>
                        <span class="shortcut-key">↓</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Go to Subtitle</span>
                        <span class="shortcut-key">Enter</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Save (Download)</span>
                        <span class="shortcut-key">Ctrl+S</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Edit Subtitle</span>
                        <span class="shortcut-key">Double Click</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Save Edit</span>
                        <span class="shortcut-key">Ctrl+Enter</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Select Row</span>
                        <span class="shortcut-key">Ctrl+Click</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Select Range</span>
                        <span class="shortcut-key">Ctrl+Shift+Click</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Undo</span>
                        <span class="shortcut-key">Ctrl+Z</span>
                    </div>
                    <div class="shortcut-item">
                        <span>Redo</span>
                        <span class="shortcut-key">Ctrl+Y</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Dialog Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-content" style="max-width: 420px;">
            <div class="modal-header">
                <h3 id="confirmTitle">Confirm</h3>
                <button class="close-btn" id="confirmClose">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage" style="margin:0;font-size:0.95rem;"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="confirmCancel">Cancel</button>
                <button class="btn btn-danger" id="confirmOk">
                    <i class="fas fa-check me-2"></i><span id="confirmOkText">OK</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Merge Subtitle Modal -->
    <div class="modal-overlay" id="mergeModal">
        <div class="modal-content" style="max-width: 620px;">
            <div class="modal-header">
                <h3><i class="fas fa-compress-alt" style="color:#6366f1;font-size:1rem;"></i> Merge Subtitles</h3>
                <button class="close-btn" id="mergeModalClose">&times;</button>
            </div>
            <div class="modal-body">
                <div class="merge-summary" id="mergeSummary">
                    <i class="fas fa-info-circle"></i>
                    <span>Select which subtitle text to <strong>keep</strong>. The first start time and last end time will be used.</span>
                </div>
                <div id="mergeRowsList"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="mergeModalCancel">Cancel</button>
                <button class="btn btn-primary" id="mergeModalConfirm">
                    <i class="fas fa-compress-alt me-2"></i>Merge
                </button>
            </div>
        </div>
    </div>

    <!-- Unknown Words Modal -->
    <div class="modal-overlay" id="unknownWordsModal">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h3>Unknown Words</h3>
                <button class="close-btn" onclick="closeModal('unknownWordsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="unknownWordsList"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('unknownWordsModal')">Close</button>
                <button class="btn btn-primary" onclick="exportUnknownWords()">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
        </div>
    </div>

    <!-- Readability Modal -->
    <div class="modal-overlay" id="readabilityModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3><i class="fas fa-eye me-2"></i>Readability Issues</h3>
                <button class="close-btn" onclick="closeModal('readabilityModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="readabilityIssuesList"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('readabilityModal')">Close</button>
                <button class="btn btn-warning" onclick="closeModal('readabilityModal')">
                    <i class="fas fa-check me-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <!-- Version History Modal -->
    <div class="modal-overlay" id="versionHistoryModal">
        <div class="modal-content" style="max-width: 750px;">
            <div class="modal-header">
                <h3><i class="fas fa-history me-2"></i>Version History</h3>
                <button class="close-btn" onclick="closeModal('versionHistoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="history-search">
                    <input type="text" id="historySearch" placeholder="Search snapshots..." oninput="filterHistory(this.value)">
                </div>
                <div id="versionHistoryList"></div>
                <div class="history-footer-bar">
                    <span class="total-info" id="historyTotalInfo"></span>
                    <button class="btn-danger" onclick="clearAllHistory()"><i class="fas fa-trash-alt me-1"></i>Clear All</button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('versionHistoryModal')">Close</button>
                <button class="btn btn-primary" onclick="saveManualSnapshot(); refreshHistoryList();">
                    <i class="fas fa-camera me-2"></i>Save Snapshot
                </button>
            </div>
        </div>
    </div>

    <!-- Diff Preview Modal -->
    <div class="modal-overlay" id="diffPreviewModal">
        <div class="modal-content" style="max-width: 750px;">
            <div class="modal-header">
                <h3><i class="fas fa-code-branch me-2"></i>Version Diff</h3>
                <button class="close-btn" onclick="closeModal('diffPreviewModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="diff-header" id="diffHeader"></div>
                <div class="diff-container" id="diffContainer"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('diffPreviewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Dictionary Changes Modal -->
    <div class="modal-overlay" id="dictionaryChangesModal">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h3><i class="fas fa-exchange-alt me-2"></i>Dictionary Changes</h3>
                <button class="close-btn" onclick="closeModal('dictionaryChangesModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="dictionaryChangesList"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('dictionaryChangesModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Subtitle data for JavaScript - make accessible globally
        window.subtitleData = <?= json_encode($subtitleData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        
        // ======= Video Player =======
        let videoPlayer = null;
        let isPlaying = false;
        let currentTime = 0;
        let duration = 0;

        function escapeHtml(text) {
            return $('<div>').text(text).html();
        }
        
        $(document).ready(function() {
            videoPlayer = $('#videoPlayer')[0];
            
            // Init checkbox konfirmasi delete (default nyala)
            $('#deleteWarnToggle').prop('checked', isDeleteConfirmEnabled());
            $('#deleteWarnToggle').on('change', function() {
                localStorage.setItem('deleteWarnConfirm', this.checked ? '1' : '0');
                showToast(this.checked ? 'Delete confirmation enabled' : 'Delete confirmation disabled - delete langsung tanpa konfirmasi', 'info');
            });
            
            // ======= Undo / Redo =======
            const undoStack = [];
            const redoStack = [];
            const MAX_UNDO = 50;
            
            window.saveUndoState = function() {
                if (!window.subtitleData) return;
                undoStack.push(JSON.parse(JSON.stringify(window.subtitleData)));
                if (undoStack.length > MAX_UNDO) undoStack.shift();
                redoStack.length = 0;
            };
            
            window.undo = function() {
                if (undoStack.length === 0) return;
                redoStack.push(JSON.parse(JSON.stringify(window.subtitleData)));
                const state = undoStack.pop();
                restoreState(state, 'Undo');
            };
            
            window.redo = function() {
                if (redoStack.length === 0) return;
                undoStack.push(JSON.parse(JSON.stringify(window.subtitleData)));
                const state = redoStack.pop();
                restoreState(state, 'Redo');
            };
            
            function restoreState(state, label) {
                window.subtitleData = state;
                $.post(window.location.href, { restore_subtitles: JSON.stringify(state) }, function(resp) {
                    if (resp && resp.success) {
                        refreshSubtitleList();
                        showToast(label + ' successful', 'success');
                    } else {
                        showToast(label + ' failed', 'error');
                    }
                }, 'json').fail(function() {
                    showToast(label + ' failed - server error', 'error');
                });
            }
            
            if (videoPlayer) {
                videoPlayer.addEventListener('loadedmetadata', function() {
                    duration = videoPlayer.duration;
                    resetFrameTracking();
                    startFrameTracking();
                    updateTimeDisplay();
                    renderTimelineBlocks();
                    // Terapkan posisi yang diklik sebelum video di-load (timeline: fraction, row subtitle: time)
                    if (window.pendingSeek && isFinite(duration) && duration > 0) {
                        if (typeof window.pendingSeek.time === 'number') {
                            videoPlayer.currentTime = window.pendingSeek.time;
                        } else if (typeof window.pendingSeek.fraction === 'number') {
                            videoPlayer.currentTime = window.pendingSeek.fraction * duration;
                        }
                        currentTime = videoPlayer.currentTime;
                        updateSlider();
                        updateTimeDisplay();
                        window.pendingSeek = null;
                    }
                });
                
                videoPlayer.addEventListener('timeupdate', function() {
                    currentTime = videoPlayer.currentTime;
                    updateTimeDisplay();
                    updateSlider();
                    highlightCurrentSubtitle();
                    updateVideoSubtitleOverlay();
                });
                
                videoPlayer.addEventListener('play', function() {
                    isPlaying = true;
                    updatePlayButton();
                    startFrameTracking();
                });
                
                videoPlayer.addEventListener('pause', function() {
                    isPlaying = false;
                    updatePlayButton();
                });
            }
            
            // Video upload zone drag-drop
            const uploadZone = $('#videoUploadZone');
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
                uploadZone.on(evt, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            uploadZone.on('dragenter', function() {
                $(this).addClass('drag-over');
            });
            uploadZone.on('dragover', function() {
                $(this).addClass('drag-over');
            });
            uploadZone.on('dragleave', function() {
                $(this).removeClass('drag-over');
            });
            uploadZone.on('drop', function(e) {
                $(this).removeClass('drag-over');
                const file = e.originalEvent.dataTransfer.files[0];
                if (file && file.type.startsWith('video/')) {
                    loadVideoFile(file);
                }
            });
            
            // Video file input (sidebar)
            $('#videoFileInput').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    loadVideoFile(file);
                }
            });
            
            // Video file input (overlay button)
            $('#videoInput').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    loadVideoFile(file);
                }
            });
            
            function loadVideoFile(file) {
                const url = URL.createObjectURL(file);
                videoPlayer.src = url;
                $('#videoPlayer').css('display', 'block');
                $('.video-placeholder').hide();
                
                // Generate subtitle track when video is ready
                videoPlayer.onloadedmetadata = function() {
                    generateVideoSubtitleTrack();
                };
                
                // Also try to enable subtitles on user interaction (play)
                videoPlayer.onplay = function() {
                    enableSubtitles();
                };
                
                showToast('Video loaded successfully', 'success');
            }
            
            function enableSubtitles() {
                // Try to enable text track
                if (videoPlayer.textTracks.length > 0) {
                    console.log('Enabling text track, mode:', videoPlayer.textTracks[0].mode);
                    videoPlayer.textTracks[0].mode = 'showing';
                }
                
                // Also add cues programmatically if track doesn't work
                if (videoPlayer.textTracks.length > 0) {
                    const track = videoPlayer.textTracks[0];
                    // Clear existing cues
                    while (track.cues && track.cues.length > 0) {
                        track.removeCue(track.cues[0]);
                    }
                    
                    // Add cues from subtitle data
                    const subtitles = window.subtitleData;
                    if (subtitles) {
                        subtitles.forEach((sub, index) => {
                            const start = parseTimestamp(sub.start);
                            const end = parseTimestamp(sub.end);
                            const cleanText = assToHtml(sub.text).trim();
                            const cue = new VTTCue(start, end, cleanText);
                            track.addCue(cue);
                        });
                    }
                }
                
                // Also make sure overlay shows subtitles
                if (videoPlayer.currentTime > 0) {
                    updateVideoSubtitleOverlay();
                }
            }
            
            function generateVideoSubtitleTrack() {
                // Using overlay-based subtitle display
                // No need to generate VTT track since we're using custom overlay
                console.log('Subtitles will be displayed via overlay');
                showToast('Subtitles loaded to video', 'success');
            }
            
            function convertToVTTTime(timeStr) {
                // Convert SRT/ASS time to VTT time (00:00:00.000)
                return timeStr.replace(',', '.');
            }
            
            function assToHtml(text, enableStylePreview) {
                // Use global assToHtml which supports style preview
                return window.assToHtml ? window.assToHtml(text, enableStylePreview) : text
                    .replace(/\\N/g, '\n')
                    .replace(/\\n/g, '\n')
                    .replace(/\{[^}]*\}/g, '')
                    .replace(/\n/g, '<br>');
            }

            function updateVideoSubtitleOverlay() {
                if (!window.subtitleData) return;
                
                const overlay = document.getElementById('videoSubtitleOverlay');
                if (!overlay) return;
                
                let currentTime = 0;
                if (videoPlayer) {
                    currentTime = videoPlayer.currentTime;
                }
                
                let activeSubtitle = null;
                
                // Find active subtitle
                for (let i = 0; i < window.subtitleData.length; i++) {
                    const sub = window.subtitleData[i];
                    const start = parseTimestamp(sub.start);
                    const end = parseTimestamp(sub.end);
                    
                    if (currentTime >= start && currentTime <= end) {
                        activeSubtitle = assToHtml(sub.text).trim();
                        break;
                    }
                }
                
                if (activeSubtitle) {
                    var useStylePreview = localStorage.getItem('srtass_style_preview') === 'true';
                    overlay.innerHTML = '<div class="subtitle-display' + (useStylePreview ? ' style-preview' : '') + '">' + assToHtml(activeSubtitle, useStylePreview) + '</div>';
                    overlay.style.display = 'block';
                } else {
                    overlay.innerHTML = '';
                    overlay.style.display = 'none';
                }
            }
            
            // Drag and drop video
            $('#videoWrapper').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });
            
            $('#videoWrapper').on('dragleave', function() {
                $(this).removeClass('drag-over');
            });
            
            $('#videoWrapper').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                const file = e.originalEvent.dataTransfer.files[0];
                if (file && file.type.startsWith('video/')) {
                    loadVideoFile(file);
                }
            });
            
            // Timeline slider
            $('#timelineSlider').on('input', function() {
                if (!videoPlayer || !duration) return;
                const time = (this.value / 100) * duration;
                videoPlayer.currentTime = time;
                currentTime = time;
            });
            
            // Timeline click & drag: 1x klik langsung menuju posisi yang diklik
            const timelineSeek = $('#timelineProgress').parent(); // .timeline-progress-container
            timelineSeek.css('cursor', 'pointer');
            window.pendingSeek = null; // posisi timeline yang diklik sebelum video di-load
            let timelineDragging = false;
            function seekFromEvent(e, snapToSubtitle) {
                const rect = timelineSeek[0].getBoundingClientRect();
                const fraction = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
                if (!videoPlayer || !duration) {
                    // Video belum di-load: simpan posisi, diterapkan saat video selesai di-load
                    window.pendingSeek = { fraction: fraction };
                    return;
                }
                let time = fraction * duration;
                // Klik di dalam range subtitle: lompat ke start time sub (presisi milidetik) agar sinkron
                if (snapToSubtitle && window.subtitleData && window.subtitleData.length) {
                    for (let i = 0; i < window.subtitleData.length; i++) {
                        const subStart = parseTimestamp(window.subtitleData[i].start);
                        const subEnd = parseTimestamp(window.subtitleData[i].end);
                        if (time >= subStart && time <= subEnd) {
                            selectSubtitle(i);
                            time = subStart;
                            break;
                        }
                    }
                }
                videoPlayer.currentTime = time;
                currentTime = videoPlayer.currentTime;
                updateSlider();
                updateTimeDisplay();
            }
            timelineSeek.on('mousedown', function(e) {
                timelineDragging = true;
                seekFromEvent(e, true);
            });
            $(document).on('mousemove', function(e) {
                if (timelineDragging) seekFromEvent(e, false);
            });
            $(document).on('mouseup', function() {
                timelineDragging = false;
            });
            
            // Search
            $('#subtitleSearch').on('input', function() {
                filterSubtitles($(this).val());
            });
            
            // Multi-selection set
            let selectedRows = new Set();
            
            function updateBatchToolbar() {
                const count = selectedRows.size;
                const $bar = $('#batchToolbar');
                if (count > 1) {
                    $('#batchCount').text(count);
                    $bar.addClass('show');
                } else {
                    $bar.removeClass('show');
                }
            }
            
            // ======= Drag & Drop Reorder =======
            let dragFromIndex = null;
            
            $('.subtitle-list').on('dragstart', '.subtitle-row', function(e) {
                dragFromIndex = $(this).data('index');
                $(this).addClass('dragging');
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                e.originalEvent.dataTransfer.setData('text/plain', dragFromIndex);
            });
            
            $('.subtitle-list').on('dragend', '.subtitle-row', function() {
                $('.subtitle-row').removeClass('dragging drag-over');
                dragFromIndex = null;
            });
            
            $('.subtitle-list').on('dragover', '.subtitle-row', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
            });
            
            $('.subtitle-list').on('dragenter', '.subtitle-row', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });
            
            $('.subtitle-list').on('dragleave', '.subtitle-row', function() {
                $(this).removeClass('drag-over');
            });
            
            $('.subtitle-list').on('drop', '.subtitle-row', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                const toIndex = $(this).data('index');
                if (dragFromIndex !== null && dragFromIndex !== toIndex) {
                    saveAutoSnapshot('Before reorder');
                    saveUndoState();
                    $.post('reorder-subtitle.php', { from_index: dragFromIndex, to_index: toIndex }, function(resp) {
                        if (resp.success) {
                            window.subtitleData = resp.subtitles;
                            refreshSubtitleList();
                            showToast('Subtitle moved', 'success');
                        } else {
                            showToast('Reorder failed', 'error');
                        }
                    }, 'json');
                }
                dragFromIndex = null;
            });
            
            function applyBatchFormat(cmd, openTag, closeTag) {
                window.saveUndoState();
                const indices = Array.from(selectedRows);
                let updated = 0;
                indices.forEach(function(idx) {
                    const original = $(`.subtitle-original[data-index="${idx}"]`);
                    const modified = $(`.subtitle-modified[data-index="${idx}"]`);
                    const text = original.text();
                    var newText = text;
                    if (cmd === 'bold') {
                        newText = text.indexOf('{\\b1}') !== -1
                            ? text.replace(/\{\\b1\}(.*?)\{\\b0\}/g, '$1').replace(/\{\\b1\}|\{\\b0\}/g, '')
                            : '{\\b1}' + text + '{\\b0}';
                    } else if (cmd === 'italic') {
                        newText = text.indexOf('{\\i1}') !== -1
                            ? text.replace(/\{\\i1\}(.*?)\{\\i0\}/g, '$1').replace(/\{\\i1\}|\{\\i0\}/g, '')
                            : '{\\i1}' + text + '{\\i0}';
                    } else if (cmd === 'underline') {
                        newText = text.indexOf('{\\u1}') !== -1
                            ? text.replace(/\{\\u1\}(.*?)\{\\u0\}/g, '$1').replace(/\{\\u1\}|\{\\u0\}/g, '')
                            : '{\\u1}' + text + '{\\u0}';
                    } else if (cmd === 'strike') {
                        newText = text.indexOf('{\\s1}') !== -1
                            ? text.replace(/\{\\s1\}(.*?)\{\\s0\}/g, '$1').replace(/\{\\s1\}|\{\\s0\}/g, '')
                            : '{\\s1}' + text + '{\\s0}';
                    }
                    if (newText !== text) {
                        $.post('update-subtitle.php', { index: idx, text: newText }, function(response) {
                            modified.html(response);
                        });
                        original.text(newText);
                        if (window.subtitleData && window.subtitleData[idx]) {
                            window.subtitleData[idx].text = newText;
                        }
                        updated++;
                    }
                });
                if (updated > 0) {
                    showToast('Formatted ' + updated + ' line(s)', 'success');
                }
                selectedRows.clear();
                $('.subtitle-row').removeClass('multi-selected');
                updateBatchToolbar();
            }
            
            function clearSelection() {
                selectedRows.clear();
                $('.subtitle-row').removeClass('multi-selected');
                lastAnchor = null;
                updateBatchToolbar();
            }
            
            function deleteSubtitle(index) {
                const doDelete = function() {
                    saveAutoSnapshot('Before delete #' + (parseInt(index) + 1));
                    saveUndoState();
                    $.post('delete-subtitle.php', { index: index }, function(response) {
                        if (response.success) {
                            showToast('Deleted ' + response.deleted + ' subtitle(s)', 'success');
                            refreshSubtitleList();
                        } else {
                            showToast('Delete failed', 'error');
                        }
                    }, 'json').fail(function() {
                        showToast('Delete failed - server error', 'error');
                    });
                };
                if (isDeleteConfirmEnabled()) {
                    showConfirm('Delete subtitle #' + (parseInt(index) + 1) + '?', 'Are you sure you want to delete this subtitle? This action cannot be undone.', doDelete);
                } else {
                    doDelete();
                }
            }
            
            function deleteSelectedSubtitles() {
                const count = selectedRows.size;
                if (count === 0) return;
                const doDelete = function() {
                    saveUndoState();
                    const indices = Array.from(selectedRows);
                    $.post('delete-subtitle.php', { indices: JSON.stringify(indices) }, function(response) {
                        if (response.success) {
                            showToast('Deleted ' + response.deleted + ' subtitle(s)', 'success');
                            refreshSubtitleList();
                        } else {
                            showToast('Delete failed', 'error');
                        }
                    }, 'json').fail(function() {
                        showToast('Delete failed - server error', 'error');
                    });
                };
                if (isDeleteConfirmEnabled()) {
                    showConfirm('Delete ' + count + ' subtitles?', 'Are you sure you want to delete ' + count + ' selected subtitle(s)? This action cannot be undone.', doDelete);
                } else {
                    doDelete();
                }
            }
            
            // ======= Merge Subtitles =======
            let mergeIndices = [];
            
            function mergeSelectedSubtitles() {
                const count = selectedRows.size;
                if (count < 2) {
                    showToast('Select at least 2 subtitles to merge', 'warning');
                    return;
                }
                mergeIndices = Array.from(selectedRows).sort(function(a, b) { return a - b; });
                // Re-check: show confirm if rows are not contiguous
                showMergeModal();
            }
            
            function showMergeModal() {
                const container = $('#mergeRowsList');
                const summary = $('#mergeSummary span');
                container.empty();
                const first = mergeIndices[0];
                const last = mergeIndices[mergeIndices.length - 1];
                const firstSub = window.subtitleData[first];
                const lastSub = window.subtitleData[last];
                if (firstSub && lastSub) {
                    summary.html('Select which subtitle text to <strong>keep</strong>. Start: <code class="merge-time start">' + escapeHtml(firstSub.start) + '</code> → End: <code class="merge-time end">' + escapeHtml(lastSub.end) + '</code>');
                }
                var html = '<div class="list-group">';
                mergeIndices.forEach(function(idx) {
                    const sub = window.subtitleData[idx];
                    if (!sub) return;
                    var textPreview = sub.text.replace(/<[^>]*>/g, '').replace(/\n/g, ' ').substring(0, 100);
                    if (textPreview.length >= 100) textPreview += '...';
                    html += '<label class="list-group-item merge-option">';
                    html += '<div class="d-flex align-items-start gap-2">';
                    html += '<input type="radio" name="mergeKeep" value="' + idx + '">';
                    html += '<div class="flex-grow-1">';
                    html += '<div class="d-flex align-items-center gap-2 flex-wrap">';
                    html += '<span class="merge-line-badge">#' + (idx + 1) + '</span>';
                    html += '<span class="merge-time start">' + escapeHtml(sub.start) + '</span>';
                    html += '<span class="merge-time end">' + escapeHtml(sub.end) + '</span>';
                    html += '<span class="small" style="color:#6b7280">| ' + sub.text.length + ' chars</span>';
                    html += '</div>';
                    html += '<div class="merge-text-preview">' + escapeHtml(textPreview) + '</div>';
                    html += '</div></div></label>';
                });
                html += '</div>';
                container.html(html);
                container.find('input[type="radio"]').first().prop('checked', true);
                $('#mergeModal').addClass('active');
            }
            
            function doMerge() {
                saveUndoState();
                const keepIndex = parseInt($('#mergeRowsList input[name="mergeKeep"]:checked').val());
                if (isNaN(keepIndex)) {
                    showToast('Select which text to keep', 'warning');
                    return;
                }
                $('#mergeModal').removeClass('active');
                $.post('merge-subtitle.php', { 
                    indices: JSON.stringify(mergeIndices), 
                    keep_index: keepIndex 
                }, function(response) {
                    if (response.success) {
                        showToast('Merged ' + response.merged + ' subtitles into 1', 'success');
                        refreshSubtitleList();
                    } else {
                        showToast('Merge failed', 'error');
                    }
                }, 'json').fail(function() {
                    showToast('Merge failed - server error', 'error');
                });
            }
            
            // ======= Refresh Subtitle List (no page reload) =======
            window.refreshSubtitleList = function refreshSubtitleList() {
                clearSelection();
                $.get(window.location.href.split('?')[0] + '?t=' + Date.now(), function(html) {
                    var $html = $(html);
                    var newList = $html.find('#subtitleList').html();
                    if (newList) {
                        $('#subtitleList').html(newList);
                    }
                    var newCount = $html.find('#subtitleCount').text();
                    if (newCount) {
                        $('#subtitleCount').text(newCount);
                    }
                    var dataEl = $html.find('#subtitleDataStore');
                    if (dataEl.length) {
                        var raw = dataEl.val();
                        if (raw) {
                            try { window.subtitleData = JSON.parse(raw); } catch(e) { console.warn('Failed to parse subtitle data', e); }
                        }
                    }
                    renderTimelineBlocks();
                    calculateReadability();
                });
            }
            
            // Subtitle row click with multi-select support
            let lastAnchor = null;
            $('.subtitle-list').on('click', '.subtitle-row', function(e) {
                const index = $(this).data('index');
                if (e.shiftKey && (e.ctrlKey || e.metaKey)) {
                    // Range select: from anchor to clicked row
                    const activeRow = $('.subtitle-row.active');
                    const activeIdx = activeRow.length ? activeRow.data('index') : null;
                    const anchor = lastAnchor !== null ? lastAnchor : (activeIdx !== null ? activeIdx : index);
                    const from = Math.min(anchor, index);
                    const to = Math.max(anchor, index);
                    // Clear current selection first
                    selectedRows.clear();
                    $('.subtitle-row').removeClass('multi-selected');
                    for (let i = from; i <= to; i++) {
                        selectedRows.add(i);
                        $(`.subtitle-row[data-index="${i}"]`).addClass('multi-selected');
                    }
                    lastAnchor = index;
                    updateBatchToolbar();
                } else if (e.ctrlKey || e.metaKey) {
                    // Toggle multi-select
                    $(this).toggleClass('multi-selected');
                    if ($(this).hasClass('multi-selected')) {
                        selectedRows.add(index);
                        lastAnchor = index;
                        // If this is the first multi-select, also include the active row
                        if (selectedRows.size === 1) {
                            $('.subtitle-row.active').each(function() {
                                const activeIdx = $(this).data('index');
                                if (activeIdx !== undefined && !selectedRows.has(activeIdx)) {
                                    selectedRows.add(activeIdx);
                                    $(this).addClass('multi-selected');
                                }
                            });
                        }
                    } else {
                        selectedRows.delete(index);
                    }
                    updateBatchToolbar();
                } else {
                    // Single select
                    selectedRows.clear();
                    $('.subtitle-row').removeClass('multi-selected');
                    lastAnchor = null;
                    updateBatchToolbar();
                    selectSubtitle(index);
                }
            });
            
            // Play from button (delegated so it works after refreshSubtitleList)
            $(document).on('click', '.play-from-btn', function(e) {
                e.stopPropagation();
                const time = parseTimestamp($(this).data('time'));
                if (videoPlayer) {
                    videoPlayer.currentTime = time;
                    videoPlayer.play();
                }
            });
            
            // Double click on modified text to edit
            $(document).on('dblclick', '.subtitle-modified', function(e) {
                e.stopPropagation();
                const index = $(this).data('index');
                editSubtitle(index);
            });
            
            // Also allow double-click on original
            $(document).on('dblclick', '.subtitle-original', function(e) {
                e.stopPropagation();
                const index = $(this).data('index');
                editSubtitle(index);
            });
            
            // Batch toolbar button clicks
            $('#batchToolbar').on('click', '.et-btn[data-cmd]', function() {
                applyBatchFormat($(this).data('cmd'));
            });
            $('#batchClear').on('click', function() {
                clearSelection();
            });
            
            // Single subtitle delete
            $(document).on('click', '.delete-subtitle-btn', function(e) {
                e.stopPropagation();
                const index = $(this).data('index');
                deleteSubtitle(index);
            });
            
            // Batch delete selected
            $('#batchDelete').on('click', function() {
                deleteSelectedSubtitles();
            });
            
            // Merge selected
            $('#batchMerge').on('click', function() {
                mergeSelectedSubtitles();
            });
            
            // Merge modal events
            $('#mergeModalClose, #mergeModalCancel').on('click', function() {
                $('#mergeModal').removeClass('active');
                mergeIndices = [];
            });
            $('#mergeModalConfirm').on('click', function() {
                doMerge();
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;
                
                switch(e.key) {
                    case ' ':
                        e.preventDefault();
                        togglePlay();
                        break;
                    case 'ArrowLeft':
                        if (videoPlayer) videoPlayer.currentTime = Math.max(0, currentTime - 5);
                        break;
                    case 'ArrowRight':
                        if (videoPlayer) videoPlayer.currentTime = Math.min(duration, currentTime + 5);
                        break;
                    case 'ArrowUp':
                        prevSubtitle();
                        break;
                    case 'ArrowDown':
                        nextSubtitle();
                        break;
                    case 'Enter':
                        if (selectedSubtitle !== null) {
                            const sub = subtitleData[selectedSubtitle];
                            if (sub && videoPlayer) {
                                videoPlayer.currentTime = parseTimestamp(sub.start);
                                videoPlayer.play();
                            }
                        }
                        break;
                    case 's':
                        if (e.ctrlKey) {
                            e.preventDefault();
                            downloadSubtitle();
                        }
                        break;
                    case 'z':
                        if (e.ctrlKey && !e.shiftKey) {
                            e.preventDefault();
                            window.undo();
                        }
                        break;
                    case 'y':
                        if (e.ctrlKey) {
                            e.preventDefault();
                            window.redo();
                        }
                        break;
                }
                // Ctrl+Shift+Z as alternative redo
                if (e.ctrlKey && e.shiftKey && e.key === 'z') {
                    e.preventDefault();
                    window.redo();
                }
            });
        });
        
        let selectedSubtitle = null;
        
        function togglePlay() {
            if (!videoPlayer) return;
            isPlaying ? videoPlayer.pause() : videoPlayer.play();
        }
        
        function updatePlayButton() {
            $('#playBtn i').attr('class', isPlaying ? 'fas fa-pause' : 'fas fa-play');
        }
        
        function skipBackward() {
            if (videoPlayer) videoPlayer.currentTime = Math.max(0, currentTime - 5);
        }
        
        function skipForward() {
            if (videoPlayer) videoPlayer.currentTime = Math.min(duration, currentTime + 5);
        }
        
        // === Info frame ala Aegisub: timestamp frame, nomor frame, offset ke baris aktif ===
        let frameSamples = [];      // [{f: presentedFrames, t: mediaTime}] dari requestVideoFrameCallback
        let fpsEstimate = null;
        let frameTrackingStarted = false;
        function pushFrameSample(md) {
            const last = frameSamples[frameSamples.length - 1];
            // Seek terdeteksi (mediaTime mundur / counter reset): buang map lama,
            // fpsEstimate dipertahankan (stabil utk CFR, dibangun ulang utk VFR)
            if (last && (md.mediaTime < last.t || md.presentedFrames <= last.f)) frameSamples = [];
            frameSamples.push({ f: md.presentedFrames, t: md.mediaTime });
            // Jaga agar map tidak membengkak: tipiskan separuh sampel jika terlalu banyak
            if (frameSamples.length > 600) {
                frameSamples = frameSamples.filter(function(s, i) { return i % 2 === 0; });
            }
            // Estimasi fps: median delta lokal (kebal lonjakan counter & seek)
            const s = frameSamples;
            if (s.length >= 2) {
                const rates = [];
                for (let i = s.length - 1; i >= Math.max(1, s.length - 24); i--) {
                    const dt = s[i].t - s[i - 1].t;
                    if (dt > 0) rates.push((s[i].f - s[i - 1].f) / dt);
                }
                if (rates.length) {
                    rates.sort(function(x, y) { return x - y; });
                    fpsEstimate = rates[Math.floor(rates.length / 2)];
                }
            }
        }
        function startFrameTracking() {
            if (frameTrackingStarted || !videoPlayer || !videoPlayer.requestVideoFrameCallback) return;
            frameTrackingStarted = true;
            const onFrame = function(now, metadata) {
                pushFrameSample(metadata);
                videoPlayer.requestVideoFrameCallback(onFrame);
            };
            videoPlayer.requestVideoFrameCallback(onFrame);
        }
        function resetFrameTracking() {
            frameSamples = [];
            fpsEstimate = null;
            frameTrackingStarted = false;
        }
        // Cari frame yang berisi waktu t:
        // - dalam cakupan map sampel: interpolasi lokal (akurat utk VFR, seperti timecodes Aegisub)
        // - di luar cakupan: frame = round(t x fps) ala CFR Aegisub
        function frameInfoAt(t) {
            const s = frameSamples;
            const n = s.length;
            if (!fpsEstimate && n < 2) return null;
            if (n >= 2 && t >= s[0].t && t <= s[n - 1].t) {
                let lo = 0, hi = n - 1;
                while (hi - lo > 1) {
                    const mid = (lo + hi) >> 1;
                    if (s[mid].t <= t) lo = mid; else hi = mid;
                }
                const a = s[lo], b = s[hi];
                const dt = b.t - a.t, df = b.f - a.f;
                if (dt > 0 && df !== 0) {
                    const frame = Math.round(a.f + (t - a.t) * df / dt);
                    const frameTime = a.t + (frame - a.f) * dt / df;
                    return { frame: frame, frameTime: frameTime };
                }
                return { frame: a.f, frameTime: a.t };
            }
            if (!fpsEstimate) return n ? { frame: s[n - 1].f, frameTime: s[n - 1].t } : null;
            const frame = Math.max(0, Math.round(t * fpsEstimate));
            return { frame: frame, frameTime: frame / fpsEstimate };
        }
        function formatFrameTime(seconds) {
            // Format ala Aegisub: 0:00:31.548 (jam tanpa leading zero, pemisah titik)
            const totalMs = Math.max(0, Math.round(seconds * 1000));
            const h = Math.floor(totalMs / 3600000);
            const m = Math.floor((totalMs % 3600000) / 60000);
            const s = Math.floor((totalMs % 60000) / 1000);
            const ms = totalMs % 1000;
            return h + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0') + '.' + String(ms).padStart(3, '0');
        }
        function updateAegisubInfo() {
            const el = document.getElementById('aegisubInfo');
            if (!el) return;
            if (!videoPlayer || !duration || !isFinite(duration) || duration <= 0) {
                el.textContent = '';
                return;
            }
            const fi = frameInfoAt(currentTime);
            if (!fi) {
                el.textContent = 'Frame: play video sebentar untuk kalibrasi fps...';
                return;
            }
            let offsets = '';
            if (selectedSubtitle !== null && subtitleData[selectedSubtitle]) {
                const startMs = Math.round(parseTimestamp(subtitleData[selectedSubtitle].start) * 1000);
                const endMs = Math.round(parseTimestamp(subtitleData[selectedSubtitle].end) * 1000);
                const ftMs = Math.round(fi.frameTime * 1000);
                const startOff = ftMs - startMs;
                const endOff = ftMs - endMs;
                offsets = ' (' + (startOff < 0 ? '-' : '+') + Math.abs(startOff) + 'ms; ' + (endOff < 0 ? '-' : '+') + Math.abs(endOff) + 'ms)';
            }
            el.textContent = formatFrameTime(fi.frameTime) + ' - frame ' + fi.frame + offsets;
        }

        function updateTimeDisplay() {
            $('#currentTime').text(formatTime(currentTime));
            $('#totalTime').text(formatTime(duration));
            updateAegisubInfo();
        }
        
        function formatTime(seconds) {
            if (isNaN(seconds) || !isFinite(seconds)) return '00:00:00,000';
            // Bulatkan total ms dulu supaya bebas dari floating-point error
            // (mis. 24.258 tersimpan sebagai 24.257999... -> floor memberi 257, bukan 258)
            const totalMs = Math.max(0, Math.round(seconds * 1000));
            const h = Math.floor(totalMs / 3600000);
            const m = Math.floor((totalMs % 3600000) / 60000);
            const s = Math.floor((totalMs % 60000) / 1000);
            const ms = totalMs % 1000;
            return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':') + ',' + String(ms).padStart(3, '0');
        }
        
        function parseTimestamp(ts) {
            const parts = ts.replace(',', '.').split(':');
            if (parts.length !== 3) return 0;
            return parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseFloat(parts[2]);
        }
        
        function updateSlider() {
            if (duration > 0) {
                const progress = (currentTime / duration) * 100;
                $('#timelineSlider').val(progress);
                $('#timelineProgress').css('width', progress + '%');
            }
        }
        
        function renderTimelineBlocks() {
            const container = $('#subtitleBlocks');
            container.empty();
            
            if (!duration) return;
            
            const subs = window.subtitleData;
            if (!subs) return;
            subs.forEach((sub, index) => {
                const start = parseTimestamp(sub.start);
                const end = parseTimestamp(sub.end);
                const left = (start / duration) * 100;
                const width = ((end - start) / duration) * 100;
                
                const block = $('<div class="subtitle-block"></div>');
                block.css({ left: left + '%', width: Math.max(width, 0.5) + '%' });
                block.on('click', function() {
                    selectSubtitle(index);
                    if (videoPlayer) videoPlayer.currentTime = start;
                });
                
                container.append(block);
            });
        }
        
        function selectSubtitle(index) {
            selectedSubtitle = index;
            
            $('.subtitle-row').removeClass('active selected');
            $(`.subtitle-row[data-index="${index}"]`).addClass('active selected');
            
            $('.subtitle-block').removeClass('active');
            $($('.subtitle-block')[index]).addClass('active');
            
            // Scroll to view
            const row = $(`.subtitle-row[data-index="${index}"]`);
            if (row.length) {
                row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            // Pindahkan video/timeline ke start time subtitle yang dipilih
            if (videoPlayer && window.subtitleData && window.subtitleData[index]) {
                const time = parseTimestamp(window.subtitleData[index].start);
                if (isFinite(time)) {
                    if (!videoPlayer.src) {
                        // Video belum di-load: simpan target, diterapkan saat video selesai di-load
                        window.pendingSeek = { time: time };
                    } else {
                        videoPlayer.currentTime = time;
                        currentTime = videoPlayer.currentTime;
                        updateSlider();
                        updateTimeDisplay();
                    }
                }
            }
        }
        
        function prevSubtitle() {
            if (selectedSubtitle !== null && selectedSubtitle > 0) {
                selectSubtitle(selectedSubtitle - 1);
            }
        }
        
        function nextSubtitle() {
            if (selectedSubtitle !== null && selectedSubtitle < subtitleData.length - 1) {
                selectSubtitle(selectedSubtitle + 1);
            }
        }
        
        function highlightCurrentSubtitle() {
            if (!videoPlayer || !duration) return;
            
            for (let i = 0; i < subtitleData.length; i++) {
                const start = parseTimestamp(subtitleData[i].start);
                const end = parseTimestamp(subtitleData[i].end);
                
                if (currentTime >= start && currentTime <= end) {
                    if (i !== selectedSubtitle) {
                        $('.subtitle-row.active').removeClass('active');
                        $(`.subtitle-row[data-index="${i}"]`).addClass('active');
                    }
                    break;
                }
            }
        }
        
        function filterSubtitles(query) {
            const lowerQuery = query.toLowerCase();
            
            $('.subtitle-row').each(function() {
                const original = $(this).find('.subtitle-original').text().toLowerCase();
                const modified = $(this).find('.subtitle-modified').text().toLowerCase();
                $(this).toggle(original.includes(lowerQuery) || modified.includes(lowerQuery));
            });
        }
        
        function assToHtml(text, enableStylePreview) {
            var result = text;
            
            if (enableStylePreview) {
                // Extended ASS tag parsing for style preview
                // Order matters: specific → general
                
                // Font color: {\c&HBBGGRR&} (BGR format)
                result = result.replace(/\{\\c&H([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})([0-9A-Fa-f]{2})&?\}/g, function(m, b, g, r) {
                    return '<span style="color:#' + r + g + b + '">';
                });
                result = result.replace(/\{\\c\}/g, '</span>');
                
                // Font size: {\fs20}
                result = result.replace(/\{\\fs(\d+)\}/g, '<span style="font-size:$1px">');
                
                // Font name: {\fnArial}
                result = result.replace(/\{\\fn([^}]+)\}/g, '<span style="font-family:\'$1\'">');
                
                // Bold: {\b1}
                result = result.replace(/\{\\b(\d+)\}/g, function(m, v) {
                    return v === '1' ? '<b>' : '</b>';
                });
                
                // Italic
                result = result.replace(/\{\\i(\d+)\}/g, function(m, v) {
                    return v === '1' ? '<i>' : '</i>';
                });
                
                // Underline
                result = result.replace(/\{\\u(\d+)\}/g, function(m, v) {
                    return v === '1' ? '<u>' : '</u>';
                });
                
                // Strikethrough
                result = result.replace(/\{\\s(\d+)\}/g, function(m, v) {
                    return v === '1' ? '<s>' : '</s>';
                });
                
                // Border/outline: {\bord2}
                result = result.replace(/\{\\bord(\d+(?:\.\d+)?)\}/g, function(m, v) {
                    return '<span style="text-shadow:0 0 ' + (parseFloat(v) * 2) + 'px #000">';
                });
                
                // Shadow: {\shad2}
                result = result.replace(/\{\\shad(\d+(?:\.\d+)?)\}/g, function(m, v) {
                    var offset = parseFloat(v);
                    return '<span style="text-shadow:' + offset + 'px ' + offset + 'px ' + (offset * 2) + 'px #000">';
                });
                
                // Alignment: {\an8} (1=bottom-left, 2=bottom-center, 3=bottom-right, 7=top-left, 8=top-center, 9=top-right)
                result = result.replace(/\{\\an(\d+)\}/g, function(m, v) {
                    var alignMap = {'1':'left','2':'center','3':'right','4':'left','5':'center','6':'right','7':'left','8':'center','9':'right'};
                    var valignMap = {'1':'','2':'','3':'','4':'','5':'','6':'','7':'','8':'','9':''};
                    return '<span style="display:block;text-align:' + (alignMap[v] || 'center') + '">';
                });
            }
            
            // Bold (always convert for basic formatting)
            result = result.replace(/\{\\b1\}([\s\S]*?)\{\\b0\}/g, '<b>$1</b>');
            result = result.replace(/\{\\i1\}([\s\S]*?)\{\\i0\}/g, '<i>$1</i>');
            result = result.replace(/\{\\u1\}([\s\S]*?)\{\\u0\}/g, '<u>$1</u>');
            result = result.replace(/\{\\s1\}([\s\S]*?)\{\\s0\}/g, '<s>$1</s>');
            
            // Remove any remaining ASS tags
            result = result.replace(/\{[^}]*\}/g, '');
            
            // Close any unclosed tags from style preview
            if (enableStylePreview) {
                // Count open spans and close them
                var openCount = (result.match(/<span/g) || []).length;
                var closeCount = (result.match(/<\/span>/g) || []).length;
                for (var i = closeCount; i < openCount; i++) {
                    result += '</span>';
                }
            }
            
            return result.replace(/\n/g, '<br>');
        }
        
        function htmlToAss(html) {
            var text = html
                .replace(/<br\s*\/?>/gi, '\n')
                .replace(/<b>(.*?)<\/b>/gi, '{\\b1}$1{\\b0}')
                .replace(/<i>(.*?)<\/i>/gi, '{\\i1}$1{\\i0}')
                .replace(/<u>(.*?)<\/u>/gi, '{\\u1}$1{\\u0}')
                .replace(/<s>(.*?)<\/s>/gi, '{\\s1}$1{\\s0}')
                .replace(/<[^>]*>/g, '')
                .replace(/&nbsp;/g, ' ');
            return $('<div>').html(text).text().trim();
        }
        
        function editSubtitle(index) {
            window.saveUndoState();
            const original = $(`.subtitle-original[data-index="${index}"]`);
            const modified = $(`.subtitle-modified[data-index="${index}"]`);
            const currentRaw = modified.text();
            const currentHtml = assToHtml(currentRaw);
            
            const row = $(`.subtitle-row[data-index="${index}"]`);
            if (row.hasClass('editing')) return;
            
            const toolbar = $(`
                <div class="edit-toolbar" data-index="${index}">
                    <button type="button" class="et-btn" data-cmd="bold" title="Bold (Ctrl+B)"><b>B</b></button>
                    <button type="button" class="et-btn" data-cmd="italic" title="Italic (Ctrl+I)"><i>I</i></button>
                    <button type="button" class="et-btn" data-cmd="underline" title="Underline (Ctrl+U)"><u>U</u></button>
                    <button type="button" class="et-btn" data-cmd="strikeThrough" title="Strike-through"><s>S</s></button>
                    <span class="et-sep"></span>
                    <button type="button" class="et-btn et-done" title="Save (Ctrl+Enter)">&#10003;</button>
                    <button type="button" class="et-btn et-cancel" title="Cancel (Esc)">&#10007;</button>
                </div>
            `);
            
            const editor = $(`<div class="edit-editor" contenteditable="true" data-index="${index}">${currentHtml}</div>`);
            
            row.addClass('editing');
            row.find('.subtitle-text-container').hide();
            row.find('.subtitle-warnings').hide();
            row.find('.subtitle-actions').hide();
            row.find('.subtitle-index').hide();
            row.find('.subtitle-times').hide();
            
            row.find('.subtitle-text-container').after(toolbar);
            toolbar.after(editor);
            editor.css('grid-column', '1 / -1');
            
            editor.focus();
            // Place cursor at end
            var sel = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents(editor[0]);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
            
            // Toolbar button clicks
            toolbar.on('click', '.et-btn[data-cmd]', function() {
                var cmd = $(this).data('cmd');
                editor.focus();
                document.execCommand(cmd, false, null);
            });
            
            toolbar.on('click', '.et-done', function() { finishEdit(); });
            toolbar.on('click', '.et-cancel', function() { cancelEdit(); });
            
                    var finishEdit = function() {
                        var newHtml = editor.html().trim();
                var newText = htmlToAss(newHtml);
                cleanup();
                if (newText !== currentRaw) {
                    $.post('update-subtitle.php', { index: index, text: newText }, function(response) {
                        modified.html(response);
                        if (window.subtitleData && window.subtitleData[index]) {
                            window.subtitleData[index].text = newText;
                        }
                        showToast('Subtitle updated', 'success');
                    });
                }
            };
            
            var cancelEdit = function() {
                cleanup();
            };
            
            var cleanup = function() {
                toolbar.remove();
                editor.remove();
                row.removeClass('editing');
                row.find('.subtitle-text-container').show();
                row.find('.subtitle-warnings').show();
                row.find('.subtitle-actions').show();
                row.find('.subtitle-index').show();
                row.find('.subtitle-times').show();
            };
            
            editor.on('keydown', function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    finishEdit();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEdit();
                }
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.execCommand('insertLineBreak', false, null);
                }
            });
        }
        
        // ======= Dictionary =======
        function addDictionary() {
            const key = $('#dictKey').val().trim();
            const value = $('#dictValue').val().trim();
            
            if (!key || !value) {
                showToast('Please enter both words', 'warning');
                return;
            }
            
            $.post('display.php', { add_to_dictionary: true, key: key, value: value }, function() {
                location.reload();
            });
        }
        
        function removeDictionary(key) {
            $.post('display.php', { remove_from_dictionary: key }, function() {
                location.reload();
            });
        }
        $(document).on('click', '.dictionary-entry .delete-btn', function() {
            const key = $(this).data('key');
            if (key) removeDictionary(key);
        });
        
        // Dictionary search filter
        $('#dictSearch').on('input', function() {
            const q = $(this).val().toLowerCase();
            const $entries = $('#dictionaryList').children('.dictionary-entry');
            let visible = 0;
            $entries.each(function() {
                const key = $(this).data('key').toLowerCase();
                const val = $(this).data('value').toLowerCase();
                const match = !q || key.indexOf(q) !== -1 || val.indexOf(q) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });
            $('#dictCount').text('(' + visible + '/' + $entries.length + ')');
        });
        
        // ======= Download =======
        function downloadSubtitle(format) {
            format = format || $('#exportFormat').val() || 'ass';
            const subtitleType = $('#subtitleType').val() || 'anime';
            
            const form = $('<form method="post">');
            form.append($('<input name="download">'));
            form.append($('<input name="format">').val(format));
            form.append($('<input name="subtitle_type">').val(subtitleType));
            if (format === 'txt') {
                form.append($('<input name="include_timing">').val($('#includeTiming').is(':checked') ? '1' : '0'));
            }
            
            $('body').append(form);
            form.submit();
            form.remove();
        }
        
        // Show/hide TXT timing option on format change
        $(document).ready(function() {
            $('#exportFormat').on('change', function() {
                $('#txtTimingOption').toggle($(this).val() === 'txt');
            });
            
            // Style preview toggle
            var stylePreviewEnabled = localStorage.getItem('srtass_style_preview') === 'true';
            $('#stylePreviewToggle').prop('checked', stylePreviewEnabled);
            $('#stylePreviewToggle').on('change', function() {
                localStorage.setItem('srtass_style_preview', $(this).is(':checked') ? 'true' : 'false');
                showToast('Style preview ' + ($(this).is(':checked') ? 'enabled' : 'disabled'), 'info');
            });
        });
        
        function toggleFullscreen() {
            const videoWrapper = document.getElementById('videoWrapper');
            const videoPanel = document.getElementById('videoPanel');
            if (videoWrapper) {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                    videoWrapper.classList.remove('fullscreen-mode');
                    if (videoPanel) videoPanel.classList.remove('fullscreen-mode');
                } else {
                    videoWrapper.requestFullscreen().catch(e => {
                        console.log('Fullscreen failed:', e);
                        videoWrapper.style.position = 'fixed';
                        videoWrapper.style.top = '0';
                        videoWrapper.style.left = '0';
                        videoWrapper.style.width = '100vw';
                        videoWrapper.style.height = '100vh';
                        videoWrapper.style.zIndex = '9999';
                        videoWrapper.classList.add('fullscreen-mode');
                        if (videoPanel) videoPanel.classList.add('fullscreen-mode');
                    });
                }
            }
        }
        
        document.addEventListener('fullscreenchange', function() {
            const videoWrapper = document.getElementById('videoWrapper');
            const videoPanel = document.getElementById('videoPanel');
            if (!document.fullscreenElement && videoWrapper) {
                videoWrapper.classList.remove('fullscreen-mode');
                if (videoPanel) videoPanel.classList.remove('fullscreen-mode');
                videoWrapper.style.position = '';
                videoWrapper.style.top = '';
                videoWrapper.style.left = '';
                videoWrapper.style.width = '';
                videoWrapper.style.height = '';
                videoWrapper.style.zIndex = '';
            }
        });
        
        // Resize video panel with better UX
        let isVideoLarge = false;
        function resizeVideoPanel(delta) {
            const panel = document.getElementById('videoPanel');
            const defaultHeight = 280;
            const largeHeight = 500;
            const minHeight = 150;
            
            if (delta > 0) {
                // Toggle between default and large
                if (!isVideoLarge) {
                    panel.style.minHeight = largeHeight + 'px';
                    panel.style.height = largeHeight + 'px';
                    isVideoLarge = true;
                } else {
                    panel.style.minHeight = defaultHeight + 'px';
                    panel.style.height = defaultHeight + 'px';
                    isVideoLarge = false;
                }
            } else {
                // Shrink
                const currentHeight = parseInt(panel.style.minHeight) || defaultHeight;
                const newHeight = Math.max(minHeight, currentHeight - 50);
                panel.style.minHeight = newHeight + 'px';
                panel.style.height = newHeight + 'px';
                isVideoLarge = false;
            }
        }
        
        // ======= Subtitle Position Toggle =======
        function toggleSubtitlePosition() {
            const overlay = document.getElementById('videoSubtitleOverlay');
            const icon = document.getElementById('subtitlePosIcon');
            overlay.classList.toggle('top');
            const isTop = overlay.classList.contains('top');
            icon.className = isTop ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
            localStorage.setItem('subtitlePosition', isTop ? 'top' : 'bottom');
        }
        
        // ======= Panels =======
        function togglePanel(header) {
            header.closest('.panel-section').classList.toggle('collapsed');
        }
        
        // ======= Modals =======
        function showShortcuts() {
            $('#shortcutsModal').addClass('active');
        }
        
        function showStatistics() {
            const subs = window.subtitleData;
            if (!subs || subs.length === 0) {
                showToast('No subtitle data', 'warning');
                return;
            }
            
            let totalChars = 0, totalWords = 0, totalDuration = 0;
            const wordFreq = {};
            
            subs.forEach(function(s) {
                const text = s.text.replace(/<[^>]*>/g, '').replace(/\{[^}]*\}/g, '').trim();
                const words = text.match(/[\p{L}]+/gu) || [];
                totalChars += text.length;
                totalWords += words.length;
                words.forEach(function(w) {
                    const key = w.toLowerCase();
                    wordFreq[key] = (wordFreq[key] || 0) + 1;
                });
                const start = parseTimestamp(s.start);
                const end = parseTimestamp(s.end);
                totalDuration += (end - start);
            });
            
            const avgDuration = totalDuration / subs.length;
            const avgCPS = totalDuration > 0 ? totalChars / totalDuration : 0;
            
            $('#statSubs').text(subs.length);
            
            // Format duration
            const durMin = Math.floor(totalDuration / 60);
            const durSec = Math.floor(totalDuration % 60);
            $('#statDuration').text(durMin + 'm ' + durSec + 's');
            
            $('#statWords').text(totalWords);
            $('#statChars').text(totalChars);
            $('#statCPS').text(avgCPS.toFixed(1));
            $('#statCPL').text((totalChars / subs.length).toFixed(1));
            $('#statWPL').text((totalWords / subs.length).toFixed(1));
            
            // Top 10 most common words
            const sorted = Object.entries(wordFreq).sort(function(a, b) { return b[1] - a[1]; }).slice(0, 10);
            const container = $('#statsTopWords');
            container.empty();
            sorted.forEach(function(entry) {
                container.append('<span class="tag"><span class="count">' + entry[1] + 'x</span> ' + $('<span>').text(entry[0]).html() + '</span>');
            });
            
            // Unknown words count via AJAX
            var unknownCount = '...';
            $('#statUnknown').text(unknownCount);
            $.get('includes/get-words.php', function(data) {
                const count = data && Array.isArray(data) ? data.length : 0;
                $('#statUnknown').text(count);
            }).fail(function() {
                $('#statUnknown').text('N/A');
            });
            
            $('#statisticsModal').addClass('active');
        }
        
        function formatTimecode(seconds) {
            const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const s = String(Math.floor(seconds % 60)).padStart(2, '0');
            const ms = String(Math.round((seconds % 1) * 1000)).padStart(3, '0');
            return h + ':' + m + ':' + s + ',' + ms;
        }
        
        function secondsToDuration(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            return h + 'h ' + m + 'm ' + s + 's';
        }
        
        function showTiming() {
            const subs = window.subtitleData;
            if (!subs || subs.length === 0) {
                showToast('No subtitle data', 'warning');
                return;
            }
            let totalSec = 0;
            subs.forEach(function(s) {
                const end = parseTimestamp(s.end);
                if (end > totalSec) totalSec = end;
            });
            $('#timingCurrentDur').text(secondsToDuration(totalSec));
            $('#timingModal').addClass('active');
        }
        
        function applyTimingShift() {
            const offsetMs = parseInt($('#timingOffset').val());
            if (isNaN(offsetMs) || offsetMs === 0) {
                showToast('Enter a valid offset (non-zero)', 'warning');
                return;
            }
            saveUndoState();
            $.post(window.location.href, { shift_timing: offsetMs }, function(resp) {
                if (resp && resp.success) {
                    window.subtitleData = resp.subtitles;
                    refreshSubtitleList();
                    showToast('Timing shifted by ' + offsetMs + 'ms', 'success');
                } else {
                    showToast('Timing shift failed', 'error');
                }
            }, 'json').fail(function() {
                showToast('Timing shift failed - server error', 'error');
            });
        }
        
        function applyTimingScale() {
            const input = $('#timingVideoDuration').val().trim();
            if (!input) {
                showToast('Enter target video duration', 'warning');
                return;
            }
            const parts = input.split(':');
            if (parts.length !== 3) {
                showToast('Use format HH:MM:SS', 'warning');
                return;
            }
            const targetSec = parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseFloat(parts[2]);
            if (isNaN(targetSec) || targetSec <= 0) {
                showToast('Invalid duration', 'warning');
                return;
            }
            let currentDur = 0;
            const subs = window.subtitleData;
            subs.forEach(function(s) {
                const end = parseTimestamp(s.end);
                if (end > currentDur) currentDur = end;
            });
            if (currentDur <= 0) {
                showToast('Cannot determine current duration', 'warning');
                return;
            }
            saveUndoState();
            $.post(window.location.href, { scale_timing: targetSec / currentDur }, function(resp) {
                if (resp && resp.success) {
                    window.subtitleData = resp.subtitles;
                    refreshSubtitleList();
                    showToast('Timing scaled to ' + input, 'success');
                } else {
                    showToast('Scale failed', 'error');
                }
            }, 'json').fail(function() {
                showToast('Scale failed - server error', 'error');
            });
        }
        
        function showSettings() {
            showToast('Settings coming soon', 'warning');
        }
        
        function showUnknownWords() {
            $.get('includes/get-words.php', function(data) {
                console.log('Unknown words data:', data);
                let html = '';
                if (Array.isArray(data) && data.length > 0) {
                    html += '<div class="unknown-words-list">';
                    // Group by line number: 2 columns side by side
                    for (let i = 0; i < data.length; i += 3) {
                        html += '<div class="uw-row">';
                        for (let j = i; j < i + 3 && j < data.length; j++) {
                            const item = data[j];
                            const lineIndex = parseInt(item.line) - 1;
                            html += `<div class="unknown-word-item" onclick="goToSubtitle(${lineIndex})">
                                <div class="uw-info">
                                    <span class="uw-line"><i class="fas fa-hashtag"></i> Line ${item.line}</span>
                                    <span class="uw-word"><i class="fas fa-spell-check"></i> ${item.word}</span>
                                </div>
                                <button class="uw-play-btn" onclick="event.stopPropagation(); goToSubtitle(${lineIndex})" title="Play from this line">
                                    <i class="fas fa-play"></i>
                                </button>
                            </div>`;
                        }
                        html += '</div>';
                    }
                    html += '</div>';
                } else {
                    html = `<div class="uw-empty">
                        <i class="fas fa-check-circle"></i>
                        <p>No unknown words found</p>
                        <small>All words are recognized in the Indonesian dictionary</small>
                    </div>`;
                }
                $('#unknownWordsList').html(html);
                $('#unknownWordsModal').addClass('active');
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Failed to load unknown words:', textStatus, errorThrown);
                $('#unknownWordsList').html('<div class="uw-empty text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load data</p></div>');
                $('#unknownWordsModal').addClass('active');
            });
        }
        
        function showDictionaryChanges() {
            $.get('includes/get-dictionary-changes.php', function(data) {
                let html = '';
                if (Array.isArray(data) && data.length > 0) {
                    html += '<div class="unknown-words-list">';
                    for (let i = 0; i < data.length; i += 3) {
                        html += '<div class="uw-row">';
                        for (let j = i; j < i + 3 && j < data.length; j++) {
                            const item = data[j];
                            const lineIndex = item.line - 1;
                            html += `<div class="unknown-word-item" onclick="goToSubtitle(${lineIndex})">
                                <div class="uw-info">
                                    <span class="uw-line"><i class="fas fa-hashtag"></i> Line ${item.line}</span>
                                    <span class="uw-word dc-original">${escapeHtml(item.original)}</span>
                                    <span class="dc-arrow"><i class="fas fa-arrow-right"></i></span>
                                    <span class="uw-word dc-replacement">${escapeHtml(item.replacement)}</span>
                                </div>
                                <button class="uw-play-btn" onclick="event.stopPropagation(); goToSubtitle(${lineIndex})" title="Go to line">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>`;
                        }
                        html += '</div>';
                    }
                    html += '</div>';
                } else {
                    html = `<div class="uw-empty">
                        <i class="fas fa-check-circle"></i>
                        <p>No dictionary changes</p>
                        <small>No words have been replaced by the dictionary</small>
                    </div>`;
                }
                $('#dictionaryChangesList').html(html);
                $('#dictionaryChangesModal').addClass('active');
            }).fail(function() {
                $('#dictionaryChangesList').html('<div class="uw-empty text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load data</p></div>');
                $('#dictionaryChangesModal').addClass('active');
            });
        }
        
        function goToSubtitle(index) {
            console.log('goToSubtitle called with index:', index);
            
            if (!window.subtitleData) {
                showToast('No subtitle data', 'error');
                return;
            }
            
            if (index < 0 || index >= window.subtitleData.length) {
                showToast('Invalid line number: ' + index, 'error');
                return;
            }
            
            const sub = window.subtitleData[index];
            const time = parseTimestamp(sub.start);
            
            console.log('Target time:', time, 'subtitle:', sub.text.substring(0, 30));
            
            // Close any active modal
            $('.modal-overlay.active').removeClass('active');
            
            // Force close any Bootstrap modals (Bootstrap 5 native API)
            document.querySelectorAll('.modal').forEach(function(el) {
                var modal = bootstrap.Modal.getInstance(el);
                if (modal) modal.hide();
            });
            
            // Remove any modal backdrops
            setTimeout(() => {
                $('.modal-backdrop').remove();
                document.body.classList.remove('modal-open');
            }, 100);
            
            // Select and scroll to subtitle
            selectSubtitle(index);
            
            // Scroll to row after selection
            setTimeout(() => {
                const row = $(`.subtitle-row[data-index="${index}"]`);
                if (row.length) {
                    row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
            
            // Try to play video
            setTimeout(() => {
                console.log('Video player check:', {
                    exists: !!videoPlayer,
                    src: videoPlayer ? videoPlayer.currentSrc : 'no video',
                    readyState: videoPlayer ? videoPlayer.readyState : 'N/A'
                });
                
                if (videoPlayer && videoPlayer.readyState >= 1) {
                    videoPlayer.currentTime = time;
                    videoPlayer.play().then(() => {
                        showToast('Playing line ' + (index + 1), 'success');
                    }).catch(e => {
                        console.log('Play error:', e);
                        showToast('Loaded at line ' + (index + 1) + ' - click play', 'warning');
                    });
                } else if (videoPlayer) {
                    videoPlayer.currentTime = time;
                    showToast('Video at line ' + (index + 1) + ' - click play', 'warning');
                } else {
                    showToast('Line ' + (index + 1) + ' selected', 'success');
                }
            }, 200);
        }
        
        // ======= Auto-Split Long Lines =======
        function exportUnknownWords() {
            window.location.href = 'includes/export-words.php';
        }
        
        // ======= Readability Warnings =======
        function calculateReadability() {
            let total = 0, cpsIssues = 0, cplIssues = 0, durIssues = 0;
            const issues = [];
            
            $('.subtitle-row').each(function() {
                const $row = $(this);
                const startStr = $row.find('.time-start').text().trim();
                const endStr = $row.find('.time-end').text().trim();
                const text = $row.find('.subtitle-modified').text().trim();
                const index = $row.data('index');
                
                if (!startStr || !endStr) return;
                
                const start = parseTime(startStr);
                const end = parseTime(endStr);
                const duration = end - start;
                const cleanText = text.replace(/\{[^}]*\}/g, '').replace(/\\[nN]/g, ' ');
                const charCount = cleanText.length;
                const cps = duration > 0 ? charCount / duration : 0;
                const cpl = charCount;
                
                // Remove existing warnings
                $row.removeClass('has-warning has-danger');
                $row.find('.warning-badge').remove();
                
                const rowWarnings = [];
                
                if (cps > 20) {
                    rowWarnings.push({type:'cps', severity:'danger', msg:'CPS ' + cps.toFixed(1)});
                    cpsIssues++;
                } else if (cps > 15) {
                    rowWarnings.push({type:'cps', severity:'warning', msg:'CPS ' + cps.toFixed(1)});
                    cpsIssues++;
                }
                if (cpl > 42) {
                    rowWarnings.push({type:'cpl', severity:'warning', msg:cpl + ' chars'});
                    cplIssues++;
                }
                if (duration < 1) {
                    rowWarnings.push({type:'duration', severity:'danger', msg:(duration).toFixed(1) + 's'});
                    durIssues++;
                } else if (duration < 1.5) {
                    rowWarnings.push({type:'duration', severity:'warning', msg:(duration).toFixed(1) + 's'});
                    durIssues++;
                }
                
                if (rowWarnings.length > 0) {
                    total++;
                    const hasDanger = rowWarnings.some(w => w.severity === 'danger');
                    $row.addClass(hasDanger ? 'has-danger' : 'has-warning');
                    
                    // Add warning badge
                    const $warnings = $row.find('.subtitle-warnings');
                    $warnings.empty();
                    rowWarnings.forEach(function(w) {
                        $warnings.append('<span class="warning-badge badge-' + w.type + '" title="' + w.msg + '"><i class="fas fa-exclamation-' + (w.severity === 'danger' ? 'circle' : 'triangle') + '"></i> ' + w.msg + '</span>');
                    });
                    
                    issues.push({index: index + 1, warnings: rowWarnings});
                }
            });
            
            // Update sidebar summary
            const $summary = $('#readabilitySummary');
            $('#readabilityCount').text('(' + total + ' issues)');
            
            if (total === 0) {
                $summary.html('<div style="text-align:center;padding:0.5rem;color:var(--success);font-size:0.8rem"><i class="fas fa-check-circle"></i> No issues</div>');
            } else {
                $summary.html(
                    '<div class="r-item"><div class="r-value" style="color:' + (cpsIssues > 0 ? '#ef4444' : '#10b981') + '">' + cpsIssues + '</div><div class="r-label">High CPS</div></div>' +
                    '<div class="r-item"><div class="r-value" style="color:' + (cplIssues > 0 ? '#f59e0b' : '#10b981') + '">' + cplIssues + '</div><div class="r-label">Long Lines</div></div>' +
                    '<div class="r-item"><div class="r-value" style="color:' + (durIssues > 0 ? '#ef4444' : '#10b981') + '">' + durIssues + '</div><div class="r-label">Short Dur.</div></div>' +
                    '<div class="r-item"><div class="r-value">' + total + '</div><div class="r-label">Total</div></div>'
                );
            }
            
            window._readabilityIssues = issues;
        }
        
        function showReadabilityIssues() {
            const issues = window._readabilityIssues || [];
            const $list = $('#readabilityIssuesList');
            
            if (issues.length === 0) {
                $list.html('<div class="uw-empty"><i class="fas fa-check-circle" style="color:var(--success)"></i><p>No readability issues found!</p></div>');
            } else {
                let html = '';
                issues.forEach(function(issue) {
                    const icons = issue.warnings.map(function(w) {
                        return '<span class="warning-badge badge-' + w.type + '"><i class="fas fa-exclamation-' + (w.severity === 'danger' ? 'circle' : 'triangle') + '"></i> ' + w.msg + '</span>';
                    }).join(' ');
                    html += '<div class="ri-row" onclick="navigateToLine(' + (issue.index - 1) + ')">' +
                        '<span class="ri-line">#' + issue.index + '</span>' +
                        '<span class="ri-msg">' + icons + '</span>' +
                        '<i class="fas fa-arrow-right ri-icon" style="color:var(--text-muted)"></i>' +
                    '</div>';
                });
                $list.html(html);
            }
            
            openModal('readabilityModal');
        }
        
        // ======= Auto-Timing =======
        function autoNormalizeDuration() {
            var cps = parseInt($('#autoTargetCps').val()) || 15;
            saveAutoSnapshot('Before normalize duration');
            saveUndoState();
            showToast('Normalizing durations...', 'warning');
            $.post('auto-timing.php', { action: 'normalize', target_cps: cps }, function(resp) {
                if (resp.success) {
                    window.subtitleData = resp.subtitles;
                    refreshSubtitleList();
                    showToast('Adjusted ' + (resp.adjusted || 0) + ' subtitle(s)', 'success');
                } else {
                    showToast('Normalize failed: ' + (resp.error || 'unknown'), 'error');
                }
            }, 'json').fail(function() {
                showToast('Normalize failed - server error', 'error');
            });
        }
        
        function autoFixOverlaps() {
            saveAutoSnapshot('Before fix overlaps');
            saveUndoState();
            showToast('Fixing overlaps...', 'warning');
            $.post('auto-timing.php', { action: 'fix_overlaps' }, function(resp) {
                if (resp.success) {
                    window.subtitleData = resp.subtitles;
                    refreshSubtitleList();
                    showToast('Fixed ' + (resp.fixed || 0) + ' overlap(s)', 'success');
                } else {
                    showToast('Fix overlaps failed: ' + (resp.error || 'unknown'), 'error');
                }
            }, 'json').fail(function() {
                showToast('Fix overlaps failed - server error', 'error');
            });
        }
        
        function parseTime(str) {
            str = str.replace(',', '.');
            const parts = str.split(':');
            if (parts.length === 3) {
                return parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseFloat(parts[2]);
            }
            return 0;
        }
        
        function navigateToLine(index) {
            closeModal('readabilityModal');
            const $row = $('.subtitle-row[data-index="' + index + '"]');
            if ($row.length) {
                $row.click();
                $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        // ======= Version History =======
        function saveManualSnapshot() {
            var label = prompt('Snapshot name:', 'Snapshot ' + new Date().toLocaleTimeString());
            if (!label) return;
            $.post('version-history.php', { action: 'snapshot', label: label }, function(resp) {
                if (resp.success) {
                    showToast('Snapshot saved', 'success');
                    updateVersionCount();
                }
            }, 'json');
        }
        
        function saveAutoSnapshot(label) {
            $.post('version-history.php', { action: 'snapshot', label: label }, function(resp) {
                if (resp.success) {
                    updateVersionCount();
                }
            }, 'json');
        }
        
        var _historySearchTerm = '';
        
        function showVersionHistory() {
            _historySearchTerm = '';
            $('#historySearch').val('');
            refreshHistoryList();
            openModal('versionHistoryModal');
        }
        
        function filterHistory(term) {
            _historySearchTerm = term;
            refreshHistoryList();
        }
        
        function refreshHistoryList() {
            var $list = $('#versionHistoryList');
            $list.html('<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></div>');
            
            $.get('version-history.php?action=list&search=' + encodeURIComponent(_historySearchTerm) + '&limit=200&t=' + Date.now(), function(resp) {
                if (!resp.success || !resp.versions || resp.versions.length === 0) {
                    $list.html('<div class="uw-empty"><i class="fas fa-history"></i><p>No versions saved</p><small>Use "Snapshot" to save the current state</small></div>');
                    $('#historyTotalInfo').text('');
                    return;
                }
                var html = '';
                resp.versions.forEach(function(v) {
                    var typeLabel = v.type === 'auto' ? '<span style="color:var(--text-muted);font-size:0.65rem">[auto]</span>' : '';
                    html += '<div class="ri-row">' +
                        '<span class="ri-line">#' + v.id + '</span>' +
                        '<span class="ri-msg" style="flex:1"><strong>' + escapeHtml(v.label) + '</strong> ' + typeLabel + '<br><small style="color:var(--text-muted)">' + v.timestamp + ' &middot; ' + v.count + ' lines</small></span>' +
                        '<span class="history-actions">' +
                            '<button class="btn-diff" onclick="event.stopPropagation(); showDiff(' + v.id + ')" title="Compare with current"><i class="fas fa-code-branch"></i></button>' +
                            '<button class="btn-delete" onclick="event.stopPropagation(); deleteHistoryEntry(' + v.id + ')" title="Delete"><i class="fas fa-trash-alt"></i></button>' +
                            '<button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); restoreVersion(' + v.id + ')" style="font-size:0.7rem;padding:0.2rem 0.5rem"><i class="fas fa-undo"></i> Restore</button>' +
                        '</span>' +
                    '</div>';
                });
                $list.html(html);
                $('#historyTotalInfo').text(resp.total + ' snapshot(s)');
            }, 'json').fail(function() {
                $list.html('<div class="uw-empty"><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i><p>Failed to load history</p></div>');
            });
        }
        
        function restoreVersion(id) {
            showConfirm('Restore Version #' + id, 'Restore this version? Current state will be saved as a snapshot.', function() {
                $.post('version-history.php', { action: 'restore', id: id }, function(resp) {
                    if (resp.success) {
                        window.subtitleData = resp.subtitles;
                        refreshSubtitleList();
                        showToast('Restored version #' + id, 'success');
                        updateVersionCount();
                        closeModal('versionHistoryModal');
                    } else {
                        showToast('Restore failed: ' + (resp.error || 'unknown'), 'error');
                    }
                }, 'json');
            });
        }
        
        function deleteHistoryEntry(id) {
            showConfirm('Delete Snapshot #' + id, 'Are you sure you want to delete this snapshot? This cannot be undone.', function() {
                $.post('version-history.php', { action: 'delete', id: id }, function(resp) {
                    if (resp.success) {
                        showToast('Snapshot #' + id + ' deleted', 'success');
                        refreshHistoryList();
                        updateVersionCount();
                    } else {
                        showToast('Delete failed: ' + (resp.error || 'unknown'), 'error');
                    }
                }, 'json');
            });
        }
        
        function clearAllHistory() {
            showConfirm('Clear All History', 'Delete ALL snapshots? This cannot be undone.', function() {
                $.post('version-history.php', { action: 'clear' }, function(resp) {
                    if (resp.success) {
                        showToast('All history cleared', 'success');
                        refreshHistoryList();
                        updateVersionCount();
                    }
                }, 'json');
            });
        }
        
        function showDiff(id) {
            var $container = $('#diffContainer');
            var $header = $('#diffHeader');
            $container.html('<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading diff...</p></div>');
            $header.html('');
            openModal('diffPreviewModal');
            
            $.get('version-history.php?action=diff&id1=0&id2=' + id + '&t=' + Date.now(), function(resp) {
                if (!resp.success) {
                    $container.html('<div class="diff-empty">' + escapeHtml(resp.error || 'Unknown error') + '</div>');
                    return;
                }
                $header.html(
                    '<span><strong>Current</strong> (working state)</span>' +
                    '<span><i class="fas fa-arrow-right"></i></span>' +
                    '<span><strong>#' + resp.v2.id + '</strong> ' + escapeHtml(resp.v2.label) + '<br>' + resp.v2.timestamp + '</span>'
                );
                
                if (resp.total_changes === 0) {
                    $container.html('<div class="diff-empty"><i class="fas fa-check-circle" style="color:var(--success);font-size:2rem;display:block;margin-bottom:0.5rem"></i>No differences between current state and this version</div>');
                    return;
                }
                
                var html = '<div class="diff-changes-count"><strong>' + resp.total_changes + '</strong> change(s)</div>';
                
                if (resp.added.length > 0) {
                    html += '<div class="diff-section-title"><i class="fas fa-plus-circle" style="color:#34d399"></i> Added (' + resp.added.length + ')</div>';
                    resp.added.forEach(function(item) {
                        html += '<div class="diff-line added"><span class="diff-index">#' + item.index + '</span>' + escapeHtml(item.text) + '</div>';
                    });
                }
                
                if (resp.removed.length > 0) {
                    html += '<div class="diff-section-title"><i class="fas fa-minus-circle" style="color:#f87171"></i> Removed (' + resp.removed.length + ')</div>';
                    resp.removed.forEach(function(item) {
                        html += '<div class="diff-line removed"><span class="diff-index">#' + item.index + '</span>' + escapeHtml(item.text) + '</div>';
                    });
                }
                
                if (resp.modified.length > 0) {
                    html += '<div class="diff-section-title"><i class="fas fa-edit" style="color:#fbbf24"></i> Modified (' + resp.modified.length + ')</div>';
                    resp.modified.forEach(function(item) {
                        html += '<div class="diff-line removed"><span class="diff-index">#' + item.index + '</span>' + escapeHtml(item.old) + '</div>';
                        html += '<div class="diff-line added"><span class="diff-index">#' + item.index + '</span>' + escapeHtml(item.new) + '</div>';
                    });
                }
                
                $container.html(html);
            }, 'json').fail(function() {
                $container.html('<div class="diff-empty"><i class="fas fa-exclamation-triangle" style="color:var(--danger);font-size:2rem;display:block;margin-bottom:0.5rem"></i>Failed to load diff</div>');
            });
        }
        
        function updateVersionCount() {
            $.get('version-history.php?action=list&limit=1&t=' + Date.now(), function(resp) {
                if (resp.success) {
                    $('#versionCount').text('(' + (resp.total || 0) + ')');
                }
            }, 'json');
        }
        
        function closeModal(id) {
            $('#' + id).removeClass('active');
        }
        
        function openModal(id) {
            $('#' + id).addClass('active');
        }
        
        function clearSession() {
            showConfirm('Start new file?', 'All current changes will be lost. Are you sure?', function() {
                $.post('display.php', { clear_session: true }, function() {
                    window.location.href = 'index.php';
                });
            });
        }
        
        // ======= Confirm Dialog =======
        let _confirmCallback = null;
        function showConfirm(title, message, callback) {
            $('#confirmTitle').text(title);
            $('#confirmMessage').text(message);
            $('#confirmOkText').text(title);
            _confirmCallback = callback;
            $('#confirmModal').addClass('active');
            $('#confirmModal #confirmOk').off('click').on('click', function() {
                $('#confirmModal').removeClass('active');
                if (_confirmCallback) { _confirmCallback(); _confirmCallback = null; }
            });
            $('#confirmModal #confirmCancel, #confirmModal #confirmClose').off('click').on('click', function() {
                $('#confirmModal').removeClass('active');
                _confirmCallback = null;
            });
        }

        // ======= Delete Confirm Toggle =======
        // Default nyala (true). Pilihan disimpan di localStorage.
        function isDeleteConfirmEnabled() {
            return localStorage.getItem('deleteWarnConfirm') !== '0';
        }
        // Close on overlay click
        $(document).on('click', '#confirmModal', function(e) {
            if ($(e.target).is('#confirmModal')) {
                $('#confirmModal').removeClass('active');
                _confirmCallback = null;
            }
        });

        // ======= Toast =======
        function showToast(message, type = 'success') {
            let container = $('.toast-container');
            if (!container.length) {
                container = $('<div class="toast-container"></div>');
                $('body').append(container);
            }
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            const toast = $(`<div class="toast ${type}">
                <i class="fas ${icons[type]}"></i>
                <p>${message}</p>
            </div>`);
            
            container.append(toast);
            
            setTimeout(function() {
                toast.remove();
            }, 3000);
        }
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!e.target.closest('.header-actions')) {
                $('.dropdown-menu').removeClass('show');
            }
        });
        
        function toggleDownloadMenu(e) {
            e.stopPropagation();
            $('#downloadDropdown').toggleClass('show');
        }
        
        // ======= Theme Toggle =======
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        }
        
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            const iconBtn = document.querySelector('#themeToggle i');
            if (iconBtn) {
                if (theme === 'dark') {
                    iconBtn.classList.remove('fa-moon');
                    iconBtn.classList.add('fa-sun');
                } else {
                    iconBtn.classList.remove('fa-sun');
                    iconBtn.classList.add('fa-moon');
                }
            }
        }

        // Initialize theme on load
        $(document).ready(function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            applyTheme(savedTheme);
            
            const pos = localStorage.getItem('subtitlePosition');
            if (pos === 'top') {
                const overlay = document.getElementById('videoSubtitleOverlay');
                const icon = document.getElementById('subtitlePosIcon');
                if (overlay && icon) {
                    overlay.classList.add('top');
                    icon.className = 'fas fa-chevron-up';
                }
            }
            calculateReadability();
            updateVersionCount();
        });
    </script>
    <?php include __DIR__ . '/includes/modal-words.php'; ?>
</body>

</html>