<?php
require __DIR__ . '/vendor/autoload.php';

\App\Core\Config::init();
\App\Core\Session::start();

require __DIR__ . '/functions.php';

$editor = new \App\Controllers\BatchEditorController();
$editor->handleRequest();

$batchFiles = \App\Core\Session::get('batch_files', []);
$batchData = $editor->getBatchData();
$dictionary = \App\Core\Session::get('dictionary', []);

if (empty($dictionary)) {
    $dictModel = new \App\Models\Dictionary();
    $dictionary = $dictModel->all();
    \App\Core\Session::set('dictionary', $dictionary);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Editor - SrtAss</title>
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="content/css/theme.css">
    <link rel="stylesheet" href="content/css/editor.css">
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
        
        /* Nav tabs styling for light mode */
        [data-theme="light"] .nav-pills {
            gap: 0.25rem;
        }
        
        [data-theme="light"] .nav-pills .nav-link {
            background: var(--bg-surface-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        
        [data-theme="light"] .nav-pills .nav-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        [data-theme="light"] .nav-pills .nav-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        [data-theme="light"] .tab-content {
            background: var(--bg-body);
        }
        
        [data-theme="light"] .tab-pane {
            background: var(--bg-body);
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
    <script src="content/js/EditorCore.js"></script>
    <script src="content/js/BatchEditor.js"></script>
</head>

<body class="batchConversion">
    <div class="editor-container">
        <!-- Header -->
        <header class="editor-header">
            <div class="logo">
                <i class="fas fa-closed-captioning"></i>
                <span>SrtAss</span>
                <span class="divider">|</span>
                <span class="file-name">Batch Editor (<?= count($batchFiles) ?> files)</span>
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
                <button class="btn btn-secondary btn-sm" onclick="clearSession()" title="New File">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="btn btn-primary btn-sm" onclick="downloadBatch()">
                    <i class="fas fa-download me-1"></i> Download All
                </button>
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
                </div>

                <!-- Subtitle Tabs and List -->
                <div class="subtitle-list-panel">
                    <div class="subtitle-list-header">
                        <!-- Batch File Tabs -->
                        <ul class="nav nav-pills" id="batchTabs" role="tablist">
                            <?php foreach ($batchFiles as $fileIndex => $file): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $fileIndex === 0 ? 'active' : '' ?>"
                                    id="tab-<?= $fileIndex ?>"
                                    type="button"
                                    role="tab"
                                    onclick="switchBatchFile(<?= $fileIndex ?>)">
                                    <?= htmlspecialchars($file['file_name']) ?>
                                </button>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="subtitle-search" style="margin-left: auto;">
                            <i class="fas fa-search"></i>
                            <input type="text" id="subtitleSearch" placeholder="Search subtitles...">
                        </div>
                        <div class="batch-toolbar" id="batchToolbar">
                            <span class="batch-label"><span id="batchCount">0</span> selected</span>
                            <span class="et-sep"></span>
                            <button type="button" class="et-btn et-bold" data-cmd="bold" title="Bold"><b>B</b></button>
                            <button type="button" class="et-btn et-italic" data-cmd="italic" title="Italic"><i>I</i></button>
                            <button type="button" class="et-btn et-underline" data-cmd="underline" title="Underline"><u>U</u></button>
                            <button type="button" class="et-btn et-strike" data-cmd="strike" title="Strike-through"><s>S</s></button>
                            <span class="et-sep"></span>
                            <button type="button" class="et-btn et-merge" id="batchMerge" title="Merge selected"><i class="fas fa-compress-alt"></i></button>
                            <button type="button" class="et-btn et-danger" id="batchDelete" title="Delete selected"><i class="fas fa-trash"></i></button>
                            <button type="button" class="et-btn et-cancel" id="batchClear" title="Clear selection">&#10007;</button>
                        </div>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" style="flex: 1; overflow-y: auto; padding: 0.5rem;">
                        <?php foreach ($batchFiles as $fileIndex => $file): ?>
                        <?php $_SESSION['non_indonesian_words']['file_' . $fileIndex] = []; ?>
                        <div class="tab-pane fade <?= $fileIndex === 0 ? 'show active' : '' ?>"
                            id="file-<?= $fileIndex ?>"
                            role="tabpanel">
                            <div class="subtitle-list" id="subtitleList-<?= $fileIndex ?>">
                                <?php foreach ($file['subtitles'] as $index => $subtitle): ?>
                                <div class="subtitle-row" data-file="<?= $fileIndex ?>" data-index="<?= $index ?>">
                                    <div class="subtitle-index"><?= $index + 1 ?></div>
                                    <div class="subtitle-times">
                                        <span class="time-start"><?= htmlspecialchars($subtitle['start']) ?></span>
                                        <span class="time-end"><?= htmlspecialchars($subtitle['end']) ?></span>
                                    </div>
                                    <div class="subtitle-text-container">
                                        <?php $isAssFormat = ($file['format'] ?? 'srt') === 'ass'; ?>
                                        <div class="subtitle-original" data-file="<?= $fileIndex ?>" data-index="<?= $index ?>"><?= $isAssFormat ? highlightIndonesiaWords($subtitle['text'], $index + 1, null, $fileIndex) : assToHtmlTags(highlightIndonesiaWords(srtTagsToAss($subtitle['text']), $index + 1, null, $fileIndex)) ?></div>
                                        <div class="subtitle-modified" data-file="<?= $fileIndex ?>" data-index="<?= $index ?>"><?= $isAssFormat ? replaceWords(htmlspecialchars($subtitle['text'])) : assToHtmlTags(replaceWords(htmlspecialchars(srtTagsToAss($subtitle['text'])))) ?></div>
                                    </div>
                                    <div class="subtitle-actions">
                                        <button class="play-from-btn" data-time="<?= $subtitle['start'] ?>" title="Play from here">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="delete-subtitle-btn" data-file="<?= $fileIndex ?>" data-index="<?= $index ?>" title="Delete subtitle">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <textarea id="batchDataStore" style="display:none"><?php
                        $batchData = array_map(function($f) {
                            $modified = array_map(function($s) {
                                $s['text'] = replaceWords($s['text'], false);
                                return $s;
                            }, $f['subtitles']);
                            return ['subtitles' => $modified, 'file_name' => $f['file_name']];
                        }, $batchFiles);
                        echo htmlspecialchars(json_encode($batchData), ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                </div>
            </div>

            <!-- Properties Panel -->
            <div class="properties-panel">
                <!-- Video Panel -->
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
                                <option value="ass" selected>ASS (Advanced)</option>
                                <option value="srt">SRT (SubRip)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subtitle Type</label>
                            <select id="subtitleType">
                                <option value="anime">Anime</option>
                                <option value="movie">Movie</option>
                                <option value="none">Original</option>
                            </select>
                        </div>
                        <button class="btn btn-success w-100" onclick="downloadBatch()">
                            <i class="fas fa-download me-2"></i>Download ZIP
                        </button>
                    </div>
                </div>

                <!-- Unknown Words Panel -->
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
                        <span>Save</span>
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
                <!-- Tabs for batch files -->
                <ul class="nav nav-pills mb-3" id="unknownWordsTabs" role="tablist"></ul>
                <div class="tab-content" id="unknownWordsTabContent"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('unknownWordsModal')">Close</button>
                <button class="btn btn-primary" onclick="exportUnknownWords()">
                    <i class="fas fa-download me-2"></i>Export
                </button>
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
                <ul class="nav nav-pills mb-3" id="dicChangesTabs" role="tablist"></ul>
                <div class="tab-content" id="dicChangesTabContent"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('dictionaryChangesModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        window.batchFiles = <?= json_encode($batchData) ?>;

        $(document).ready(function() {
            if (typeof EditorCore === 'undefined') {
                console.error('EditorCore not loaded');
                return;
            }
            const editor = new BatchEditor();
            window.editor = editor;
            editor.init();
        });
    </script>
    <?php include __DIR__ . '/includes/modal-words.php'; ?>
</body>

</html>