<?php
session_start();
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handleBatchRequest();
}

if (isset($_SESSION['non_indonesian_words'])) {
    foreach ($_SESSION['non_indonesian_words'] as $fileKey => $words) {
        $uniqueWords = [];
        $seenWords = [];

        foreach ($words as $uniqueKey => $wordData) {
            $wordKey = $wordData['word'] . '|' . $wordData['line'];
            if (!isset($seenWords[$wordKey])) {
                $uniqueWords[$uniqueKey] = $wordData;
                $seenWords[$wordKey] = true;
            }
        }

        $_SESSION['non_indonesian_words'][$fileKey] = $uniqueWords;
    }
}

$batchFiles = $_SESSION['batch_files'] ?? [];
$dictionary = $_SESSION['dictionary'] ?? [];
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
                                        <div class="subtitle-original" data-file="<?= $fileIndex ?>" data-index="<?= $index ?>"><?= highlightIndonesiaWords($subtitle['text'], $index + 1, null, $fileIndex) ?></div>
                                        <div class="subtitle-modified" data-file="<?= $fileIndex ?>" data-index="<?= $index ?>"><?= replaceWords(htmlspecialchars($subtitle['text'])) ?></div>
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
                                <option value="srt">SRT (SubRip)</option>
                                <option value="ass">ASS (Advanced)</option>
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

    <script>
        // Batch data - make accessible globally
        window.batchFiles = <?= json_encode($batchData) ?>;
        let currentFileIndex = 0;
        let selectedSubtitle = null;
        let videoPlayer = null;
        let isPlaying = false;
        let currentTime = 0;
        let duration = 0;

        $(document).ready(function() {
            videoPlayer = $('#videoPlayer')[0];
            
            if (videoPlayer) {
                videoPlayer.addEventListener('loadedmetadata', function() {
                    duration = videoPlayer.duration;
                    updateTimeDisplay();
                    renderTimelineBlocks();
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
            
            // Video input (sidebar)
            $('#videoFileInput').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    loadVideoFile(file);
                }
            });
            
            // Video input (overlay button)
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
                
                // Generate subtitle track for current file
                generateVideoSubtitleTrack(currentFileIndex);
                
                showToast('Video loaded successfully', 'success');
            }
            
            function generateVideoSubtitleTrack(fileIndex) {
                // Using overlay-based subtitle display
                console.log('Subtitles will be displayed via overlay');
                showToast('Subtitles loaded to video', 'success');
            }
            
            function convertToVTTTime(timeStr) {
                return timeStr.replace(',', '.');
            }
            
            function updateVideoSubtitleOverlay() {
                if (!window.batchFiles) return;
                
                const overlay = document.getElementById('videoSubtitleOverlay');
                if (!overlay) return;
                
                let currentTime = 0;
                if (videoPlayer) {
                    currentTime = videoPlayer.currentTime;
                }
                
                const batchFiles = window.batchFiles;
                const subs = batchFiles[currentFileIndex]?.subtitles;
                if (!subs) {
                    overlay.style.display = 'none';
                    return;
                }
                
                let activeSubtitle = null;
                
                for (let i = 0; i < subs.length; i++) {
                    const sub = subs[i];
                    const start = parseTimestamp(sub.start);
                    const end = parseTimestamp(sub.end);
                    
                    if (currentTime >= start && currentTime <= end) {
                        activeSubtitle = sub.text.replace(/<[^>]*>/g, '').trim();
                        break;
                    }
                }
                
                if (activeSubtitle) {
                    overlay.innerHTML = '<div class="subtitle-display">' + activeSubtitle + '</div>';
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
            
            // Search
            $('#subtitleSearch').on('input', function() {
                filterSubtitles($(this).val());
            });
            
            // Multi-selection set (batch)
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
            
            function clearSelection() {
                selectedRows.clear();
                $('.subtitle-row').removeClass('multi-selected');
                lastAnchor = null;
                updateBatchToolbar();
            }
            
            function deleteSubtitleBatch(fileIndex, index) {
                showConfirm('Delete subtitle #' + (parseInt(index) + 1) + '?', 'Are you sure you want to delete this subtitle? This action cannot be undone.', function() {
                    $.post('delete-subtitle-batch.php', { file_index: fileIndex, index: index }, function(response) {
                        if (response.success) {
                            showToast('Deleted ' + response.deleted + ' subtitle(s)', 'success');
                            refreshBatchSubtitleList();
                        } else {
                            showToast('Delete failed', 'error');
                        }
                    }, 'json').fail(function() {
                        showToast('Delete failed - server error', 'error');
                    });
                });
            }
            
            function deleteSelectedSubtitlesBatch() {
                const count = selectedRows.size;
                if (count === 0) return;
                const currentFile = currentFileIndex;
                var indices = Array.from(selectedRows)
                    .filter(function(item) { return item.file === currentFile; })
                    .map(function(item) { return item.index; });
                if (indices.length === 0) {
                    showToast('No subtitles selected in current file', 'warning');
                    return;
                }
                showConfirm('Delete ' + indices.length + ' subtitles?', 'Are you sure you want to delete ' + indices.length + ' selected subtitle(s)? This action cannot be undone.', function() {
                    $.post('delete-subtitle-batch.php', { file_index: currentFile, indices: JSON.stringify(indices) }, function(response) {
                        if (response.success) {
                            showToast('Deleted ' + response.deleted + ' subtitle(s)', 'success');
                            refreshBatchSubtitleList();
                        } else {
                            showToast('Delete failed', 'error');
                        }
                    }, 'json').fail(function() {
                        showToast('Delete failed - server error', 'error');
                    });
                });
            }
            
            // ======= Merge Subtitles (Batch) =======
            let mergeIndices = [];
            
            function mergeSelectedSubtitlesBatch() {
                const count = selectedRows.size;
                if (count < 2) {
                    showToast('Select at least 2 subtitles to merge', 'warning');
                    return;
                }
                const currentFile = currentFileIndex;
                const indices = Array.from(selectedRows)
                    .filter(function(item) { return item.file === currentFile; })
                    .map(function(item) { return item.index; })
                    .sort(function(a, b) { return a - b; });
                if (indices.length < 2) {
                    showToast('Select at least 2 subtitles in the current file', 'warning');
                    return;
                }
                mergeIndices = indices;
                showMergeModal();
            }
            
            function showMergeModal() {
                const container = $('#mergeRowsList');
                const summary = $('#mergeSummary span');
                container.empty();
                const subs = window.batchFiles[currentFileIndex].subtitles;
                const first = mergeIndices[0];
                const last = mergeIndices[mergeIndices.length - 1];
                const firstSub = subs[first];
                const lastSub = subs[last];
                if (firstSub && lastSub) {
                    summary.html('Select which subtitle text to <strong>keep</strong>. Start: <code class="merge-time start">' + escapeHtml(firstSub.start) + '</code> → End: <code class="merge-time end">' + escapeHtml(lastSub.end) + '</code>');
                }
                var html = '<div class="list-group">';
                mergeIndices.forEach(function(idx) {
                    const sub = subs[idx];
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
            
            function doMergeBatch() {
                const keepIndex = parseInt($('#mergeRowsList input[name="mergeKeep"]:checked').val());
                if (isNaN(keepIndex)) {
                    showToast('Select which text to keep', 'warning');
                    return;
                }
                $('#mergeModal').removeClass('active');
                $.post('merge-subtitle-batch.php', { 
                    file_index: currentFileIndex, 
                    indices: JSON.stringify(mergeIndices), 
                    keep_index: keepIndex 
                }, function(response) {
                    if (response.success) {
                        showToast('Merged ' + response.merged + ' subtitles into 1', 'success');
                        refreshBatchSubtitleList();
                    } else {
                        showToast('Merge failed', 'error');
                    }
                }, 'json').fail(function() {
                    showToast('Merge failed - server error', 'error');
                });
            }
            
            function escapeHtml(text) {
                return $('<div>').text(text).html();
            }
            
            // ======= Refresh Subtitle List (no reload) =======
            function refreshBatchSubtitleList() {
                clearSelection();
                $.get(window.location.href.split('?')[0] + '?t=' + Date.now(), function(html) {
                    var $html = $(html);
                    var newList = $html.find('#subtitleList-' + currentFileIndex).html();
                    if (newList) {
                        $('#subtitleList-' + currentFileIndex).html(newList);
                    }
                    var dataEl = $html.find('#batchDataStore');
                    if (dataEl.length) {
                        var raw = dataEl.val();
                        if (raw) {
                            try { window.batchFiles = JSON.parse(raw); } catch(e) { console.warn('Failed to parse batch data', e); }
                        }
                    }
                    renderTimelineBlocks();
                });
            }
            
            function applyBatchFormat(cmd) {
                const currentFile = currentFileIndex;
                const indices = Array.from(selectedRows).filter(function(idx) {
                    return idx.file === currentFile;
                }).map(function(idx) {
                    return idx.index;
                });
                let updated = 0;
                indices.forEach(function(index) {
                    const original = $(`.subtitle-original[data-file="${currentFile}"][data-index="${index}"]`);
                    const modified = $(`.subtitle-modified[data-file="${currentFile}"][data-index="${index}"]`);
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
                        $.post('update-subtitle-batch.php', {
                            file_index: currentFile,
                            index: index,
                            text: newText
                        }, function(response) {
                            if (response.success) {
                                modified.html(response.html);
                                original.text(newText);
                                window.batchFiles[currentFile].subtitles[index].text = newText;
                            }
                        }, 'json');
                        updated++;
                    }
                });
                if (updated > 0) {
                    showToast('Formatted ' + updated + ' line(s)', 'success');
                }
                clearSelection();
            }
            
            // Subtitle row click with multi-select
            let lastAnchor = null; // { file, index }
            $(document).on('click', '.subtitle-row', function(e) {
                const fileIndex = $(this).data('file');
                const index = $(this).data('index');
                const key = fileIndex + '-' + index;
                if (e.shiftKey && (e.ctrlKey || e.metaKey)) {
                    // Range select within same file
                    if (!lastAnchor) {
                        const activeRow = $('.subtitle-row.active');
                        const af = activeRow.length ? activeRow.data('file') : null;
                        const ai = activeRow.length ? activeRow.data('index') : null;
                        if (af !== null && ai !== null && af === fileIndex) {
                            lastAnchor = { file: af, index: ai, key: af + '-' + ai };
                        }
                    }
                    if (lastAnchor && lastAnchor.file === fileIndex) {
                        const from = Math.min(lastAnchor.index, index);
                        const to = Math.max(lastAnchor.index, index);
                        selectedRows.clear();
                        $('.subtitle-row').removeClass('multi-selected');
                        for (let i = from; i <= to; i++) {
                            const ik = fileIndex + '-' + i;
                            selectedRows.add({ file: fileIndex, index: i, key: ik });
                            $(`.subtitle-row[data-file="${fileIndex}"][data-index="${i}"]`).addClass('multi-selected');
                        }
                        lastAnchor = { file: fileIndex, index: index };
                    } else {
                        // No anchor or different file, just select this row
                        selectedRows.clear();
                        $('.subtitle-row').removeClass('multi-selected');
                        selectedRows.add({ file: fileIndex, index: index, key: key });
                        $(this).addClass('multi-selected');
                        lastAnchor = { file: fileIndex, index: index };
                    }
                    updateBatchToolbar();
                } else if (e.ctrlKey || e.metaKey) {
                    $(this).toggleClass('multi-selected');
                    if ($(this).hasClass('multi-selected')) {
                        selectedRows.add({ file: fileIndex, index: index, key: key });
                        lastAnchor = { file: fileIndex, index: index };
                        // If first multi-select, also include the active row
                        if (selectedRows.size === 1) {
                            $('.subtitle-row.active').each(function() {
                                const af = $(this).data('file');
                                const ai = $(this).data('index');
                                if (af !== undefined && ai !== undefined) {
                                    const ak = af + '-' + ai;
                                    let found = false;
                                    selectedRows.forEach(function(item) { if (item.key === ak) found = true; });
                                    if (!found) {
                                        selectedRows.add({ file: af, index: ai, key: ak });
                                        $(this).addClass('multi-selected');
                                    }
                                }
                            });
                        }
                    } else {
                        selectedRows.forEach(function(item) {
                            if (item.key === key) selectedRows.delete(item);
                        });
                    }
                    updateBatchToolbar();
                } else {
                    selectedRows.clear();
                    $('.subtitle-row').removeClass('multi-selected');
                    lastAnchor = null;
                    updateBatchToolbar();
                    selectSubtitle(fileIndex, index);
                }
            });
            
            // Batch toolbar button clicks
            $('#batchToolbar').on('click', '.et-btn[data-cmd]', function() {
                applyBatchFormat($(this).data('cmd'));
            });
            $('#batchClear').on('click', function() {
                clearSelection();
            });
            
            // Single subtitle delete (batch mode)
            $(document).on('click', '.delete-subtitle-btn', function(e) {
                e.stopPropagation();
                const fileIndex = $(this).data('file');
                const index = $(this).data('index');
                deleteSubtitleBatch(fileIndex, index);
            });
            
            // Batch delete selected
            $('#batchDelete').on('click', function() {
                deleteSelectedSubtitlesBatch();
            });
            
            // Merge selected
            $('#batchMerge').on('click', function() {
                mergeSelectedSubtitlesBatch();
            });
            
            // Merge modal events
            $('#mergeModalClose, #mergeModalCancel').on('click', function() {
                $('#mergeModal').removeClass('active');
                mergeIndices = [];
            });
            $('#mergeModalConfirm').on('click', function() {
                doMergeBatch();
            });
            
            // Play from button
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
                const fileIndex = $(this).data('file');
                const index = $(this).data('index');
                editSubtitle(fileIndex, index);
            });
            
            // Also allow double-click on original
            $(document).on('dblclick', '.subtitle-original', function(e) {
                e.stopPropagation();
                const fileIndex = $(this).data('file');
                const index = $(this).data('index');
                editSubtitle(fileIndex, index);
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
                    case 's':
                        if (e.ctrlKey) {
                            e.preventDefault();
                            downloadBatch();
                        }
                        break;
                }
            });
            
            // Initialize first file
            if (batchFiles.length > 0) {
                renderCurrentFileSubtitles();
            }
            
            window.switchBatchFile = function(index) {
                currentFileIndex = index;
                
                // Activate tab button
                document.querySelectorAll('.nav-pills .nav-link').forEach(function(btn) {
                    btn.classList.remove('active');
                });
                var tabBtn = document.getElementById('tab-' + index);
                if (tabBtn) tabBtn.classList.add('active');
                
                // Activate tab panel
                document.querySelectorAll('.tab-content .tab-pane').forEach(function(pane) {
                    pane.classList.remove('show', 'active');
                });
                var panel = document.getElementById('file-' + index);
                if (panel) panel.classList.add('show', 'active');
                
                renderTimelineBlocks();
                
                if (videoPlayer && videoPlayer.src) {
                    generateVideoSubtitleTrack(currentFileIndex);
                    updateVideoSubtitleOverlay();
                }
            };
        });
        
        function renderCurrentFileSubtitles() {
            const container = $(`#subtitleList-${currentFileIndex}`);
            // Subtitles already rendered server-side, just ensure visible
            $(`#subtitleList-${currentFileIndex} .subtitle-row`).show();
        }
        
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
        
        function updateTimeDisplay() {
            $('#currentTime').text(formatTime(currentTime));
            $('#totalTime').text(formatTime(duration));
        }
        
        function formatTime(seconds) {
            if (isNaN(seconds) || !isFinite(seconds)) return '00:00:00';
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
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
            
            if (!duration || !window.batchFiles[currentFileIndex]) return;
            
            const subs = window.batchFiles[currentFileIndex].subtitles;
            if (!subs) return;
            subs.forEach((sub, index) => {
                const start = parseTimestamp(sub.start);
                const end = parseTimestamp(sub.end);
                const left = (start / duration) * 100;
                const width = ((end - start) / duration) * 100;
                
                const block = $('<div class="subtitle-block"></div>');
                block.css({ left: left + '%', width: Math.max(width, 0.5) + '%' });
                block.on('click', function() {
                    selectSubtitle(currentFileIndex, index);
                    if (videoPlayer) videoPlayer.currentTime = start;
                });
                
                container.append(block);
            });
        }
        
        function selectSubtitle(fileIndex, index) {
            selectedSubtitle = { file: fileIndex, index: index };
            
            $(`.subtitle-row`).removeClass('active selected');
            $(`.subtitle-row[data-file="${fileIndex}"][data-index="${index}"]`).addClass('active selected');
            
            // Scroll to view
            const row = $(`.subtitle-row[data-file="${fileIndex}"][data-index="${index}"]`);
            if (row.length) {
                row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        
        function prevSubtitle() {
            if (!selectedSubtitle) return;
            if (selectedSubtitle.index > 0) {
                selectSubtitle(selectedSubtitle.file, selectedSubtitle.index - 1);
            }
        }
        
        function nextSubtitle() {
            if (!selectedSubtitle) return;
            const max = batchFiles[selectedSubtitle.file].subtitles.length - 1;
            if (selectedSubtitle.index < max) {
                selectSubtitle(selectedSubtitle.file, selectedSubtitle.index + 1);
            }
        }
        
        function highlightCurrentSubtitle() {
            if (!videoPlayer || !duration || !batchFiles[currentFileIndex]) return;
            
            const subs = batchFiles[currentFileIndex].subtitles;
            
            for (let i = 0; i < subs.length; i++) {
                const start = parseTimestamp(subs[i].start);
                const end = parseTimestamp(subs[i].end);
                
                if (currentTime >= start && currentTime <= end) {
                    $(`.subtitle-row[data-file="${currentFileIndex}"].active`).removeClass('active');
                    $(`.subtitle-row[data-file="${currentFileIndex}"][data-index="${i}"]`).addClass('active');
                    break;
                }
            }
        }
        
        function filterSubtitles(query) {
            const lowerQuery = query.toLowerCase();
            
            $(`.subtitle-row`).each(function() {
                const original = $(this).find('.subtitle-original').text().toLowerCase();
                const modified = $(this).find('.subtitle-modified').text().toLowerCase();
                $(this).toggle(original.includes(lowerQuery) || modified.includes(lowerQuery));
            });
        }
        
        function assToHtml(text) {
            return text
                .replace(/\{\\b1\}([\s\S]*?)\{\\b0\}/g, '<b>$1</b>')
                .replace(/\{\\i1\}([\s\S]*?)\{\\i0\}/g, '<i>$1</i>')
                .replace(/\{\\u1\}([\s\S]*?)\{\\u0\}/g, '<u>$1</u>')
                .replace(/\{\\s1\}([\s\S]*?)\{\\s0\}/g, '<s>$1</s>')
                .replace(/\{[^}]*\}/g, '')
                .replace(/\n/g, '<br>');
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
        
        function editSubtitle(fileIndex, index) {
            const original = $(`.subtitle-original[data-file="${fileIndex}"][data-index="${index}"]`);
            const modified = $(`.subtitle-modified[data-file="${fileIndex}"][data-index="${index}"]`);
            const currentRaw = modified.text();
            const currentHtml = assToHtml(currentRaw);
            
            const row = $(`.subtitle-row[data-file="${fileIndex}"][data-index="${index}"]`);
            if (row.hasClass('editing')) return;
            
            const toolbar = $(`
                <div class="edit-toolbar" data-file="${fileIndex}" data-index="${index}">
                    <button type="button" class="et-btn" data-cmd="bold" title="Bold (Ctrl+B)"><b>B</b></button>
                    <button type="button" class="et-btn" data-cmd="italic" title="Italic (Ctrl+I)"><i>I</i></button>
                    <button type="button" class="et-btn" data-cmd="underline" title="Underline (Ctrl+U)"><u>U</u></button>
                    <button type="button" class="et-btn" data-cmd="strikeThrough" title="Strike-through"><s>S</s></button>
                    <span class="et-sep"></span>
                    <button type="button" class="et-btn et-done" title="Save (Ctrl+Enter)">&#10003;</button>
                    <button type="button" class="et-btn et-cancel" title="Cancel (Esc)">&#10007;</button>
                </div>
            `);
            
            const editor = $(`<div class="edit-editor" contenteditable="true" data-file="${fileIndex}" data-index="${index}">${currentHtml}</div>`);
            
            row.addClass('editing');
            row.find('.subtitle-text-container').hide();
            row.find('.subtitle-actions').hide();
            row.find('.subtitle-index').hide();
            row.find('.subtitle-times').hide();
            
            row.find('.subtitle-text-container').after(toolbar);
            toolbar.after(editor);
            editor.css('grid-column', '1 / -1');
            
            editor.focus();
            var sel = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents(editor[0]);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
            
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
                    $.post('update-subtitle-batch.php', { 
                        file_index: fileIndex,
                        index: index, 
                        text: newText 
                    }, function(response) {
                        if (response.success) {
                            modified.html(response.html);
                            original.text(newText);
                            window.batchFiles[fileIndex].subtitles[index].text = newText;
                            showToast('Subtitle updated', 'success');
                        } else {
                            showToast('Update failed: ' + response.error, 'error');
                        }
                    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                        showToast('Update failed: ' + textStatus, 'error');
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
        
        // Dictionary functions
        function addDictionary() {
            const key = $('#dictKey').val().trim();
            const value = $('#dictValue').val().trim();
            
            if (!key || !value) {
                showToast('Please enter both words', 'warning');
                return;
            }
            
            $.post('display-batch.php', { add_to_dictionary: true, key: key, value: value }, function() {
                location.reload();
            });
        }
        
        function removeDictionary(key) {
            $.post('display-batch.php', { remove_from_dictionary: key }, function() {
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
        
        // Download
        function downloadBatch() {
            const format = $('#exportFormat').val();
            const subtitleType = $('#subtitleType').val();
            
            const form = $('<form method="post">');
            form.append($('<input name="batch_download">').val('1'));
            form.append($('<input name="format">').val(format));
            form.append($('<input name="subtitle_type">').val(subtitleType));
            
            $('body').append(form);
            form.submit();
            form.remove();
        }
        
        function toggleFullscreen() {
            const videoWrapper = document.getElementById('videoWrapper');
            if (videoWrapper) {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                    videoWrapper.classList.remove('fullscreen-mode');
                } else {
                    videoWrapper.requestFullscreen().catch(e => {
                        console.log('Fullscreen failed:', e);
                        // Fallback: just make video wrapper larger
                        videoWrapper.style.position = 'fixed';
                        videoWrapper.style.top = '0';
                        videoWrapper.style.left = '0';
                        videoWrapper.style.width = '100vw';
                        videoWrapper.style.height = '100vh';
                        videoWrapper.style.zIndex = '9999';
                        videoWrapper.classList.add('fullscreen-mode');
                    });
                }
            }
        }
        
        // Listen for fullscreen changes to clean up
        document.addEventListener('fullscreenchange', function() {
            const videoWrapper = document.getElementById('videoWrapper');
            if (!document.fullscreenElement && videoWrapper) {
                videoWrapper.classList.remove('fullscreen-mode');
                videoWrapper.style.position = '';
                videoWrapper.style.top = '';
                videoWrapper.style.left = '';
                videoWrapper.style.width = '';
                videoWrapper.style.height = '';
                videoWrapper.style.zIndex = '';
            }
        });
        
        function resizeVideoPanel(delta) {
            const panel = document.getElementById('videoPanel');
            const currentHeight = parseInt(panel.style.minHeight) || 100;
            const newHeight = Math.max(80, Math.min(400, currentHeight + (delta * 30)));
            panel.style.minHeight = newHeight + 'px';
            panel.style.height = newHeight + 'px';
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
        
        function togglePanel(header) {
            header.closest('.panel-section').classList.toggle('collapsed');
        }
        
        function showShortcuts() {
            $('#shortcutsModal').addClass('active');
        }
        
        function showUnknownWords() {
            $.get('includes/get-words.php', { batch_mode: 1 }, function(data) {
                console.log('Batch unknown words data:', data);

                const tabsContainer = document.getElementById('unknownWordsTabs');
                const tabContentContainer = document.getElementById('unknownWordsTabContent');
                tabsContainer.innerHTML = '';
                tabContentContainer.innerHTML = '';

                const batchFiles = window.batchFiles;

                if (batchFiles && batchFiles.length > 0) {
                    batchFiles.forEach(function(bf, fileIdx) {
                        const fileData = data ? data.find(f => f.file_index === fileIdx) : null;
                        const words = fileData && fileData.words ? fileData.words : [];

                        const savedTab = localStorage.getItem('uwActiveTab');
                        const isActive = savedTab !== null ? parseInt(savedTab) === fileIdx : fileIdx === currentFileIndex;
                        const tabBtn = document.createElement('button');
                        tabBtn.className = 'nav-link' + (isActive ? ' active' : '');
                        tabBtn.id = 'uw-tab-' + fileIdx;
                        tabBtn.type = 'button';
                        tabBtn.innerHTML = bf.file_name + (words.length > 0 ? ` <span class="badge bg-warning">${words.length}</span>` : '');
                        tabsContainer.appendChild(tabBtn);

                        const tabPane = document.createElement('div');
                        tabPane.className = 'tab-pane fade' + (isActive ? ' show active' : '');
                        tabPane.id = 'uw-pane-' + fileIdx;

                        if (words.length > 0) {
                            let wordsHtml = '<div class="unknown-words-list">';
                            for (let w = 0; w < words.length; w += 3) {
                                wordsHtml += '<div class="uw-row">';
                                for (let wj = w; wj < w + 3 && wj < words.length; wj++) {
                                    const item = words[wj];
                                    const lineIndex = parseInt(item.line) - 1;
                                    wordsHtml += `<div class="unknown-word-item" onclick="goToSubtitle(${fileIdx}, ${lineIndex})">
                                        <div class="uw-info">
                                            <span class="uw-line"><i class="fas fa-hashtag"></i> Line ${item.line}</span>
                                            <span class="uw-word"><i class="fas fa-spell-check"></i> ${item.word}</span>
                                        </div>
                                        <button class="uw-play-btn" onclick="event.stopPropagation(); goToSubtitle(${fileIdx}, ${lineIndex})" title="Play from this line">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </div>`;
                                }
                                wordsHtml += '</div>';
                            }
                            wordsHtml += '</div>';
                            tabPane.innerHTML = wordsHtml;
                        } else {
                            tabPane.innerHTML = `<div class="uw-empty">
                                <i class="fas fa-check-circle"></i>
                                <p>No unknown words found</p>
                                <small>All words are recognized in the Indonesian dictionary</small>
                            </div>`;
                        }

                        tabContentContainer.appendChild(tabPane);
                    });
                }

                // Manual tab switching with localStorage save
                tabsContainer.querySelectorAll('button.nav-link').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const idx = this.id.replace('uw-tab-', '');
                        // Save scroll for current tab before switching
                        const scrollKey = 'uwScroll_' + idx;
                        const contentEl = document.getElementById('unknownWordsTabContent');
                        if (contentEl) {
                            localStorage.setItem(scrollKey, contentEl.scrollTop);
                        }
                        tabsContainer.querySelectorAll('button.nav-link').forEach(function(b) {
                            b.classList.remove('active');
                        });
                        tabContentContainer.querySelectorAll('.tab-pane').forEach(function(p) {
                            p.classList.remove('show', 'active');
                        });
                        this.classList.add('active');
                        const pane = document.getElementById('uw-pane-' + idx);
                        if (pane) {
                            pane.classList.add('show', 'active');
                        }
                        localStorage.setItem('uwActiveTab', idx);
                    });
                });
                
                // Restore scroll position for the active tab after rendering
                setTimeout(function() {
                    const activeIdx = localStorage.getItem('uwActiveTab') || '0';
                    const scrollKey = 'uwScroll_' + activeIdx;
                    const contentEl = document.getElementById('unknownWordsTabContent');
                    if (contentEl) {
                        const savedScroll = localStorage.getItem(scrollKey);
                        if (savedScroll !== null) {
                            contentEl.scrollTop = parseInt(savedScroll);
                        }
                    }
                }, 100);

                $('#unknownWordsModal').addClass('active');
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Failed to load unknown words:', textStatus, errorThrown);
                $('#unknownWordsTabContent').html('<div class="uw-empty text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load data</p></div>');
                $('#unknownWordsModal').addClass('active');
            });
        }
        
        function goToSubtitle(fileIndex, lineIndex) {
            console.log('goToSubtitle batch called:', fileIndex, lineIndex);
            
            const batchFiles = window.batchFiles;
            if (!batchFiles || !batchFiles[fileIndex]) {
                showToast('File not found', 'error');
                return;
            }
            
            const subtitles = batchFiles[fileIndex].subtitles;
            if (!subtitles || lineIndex < 0 || lineIndex >= subtitles.length) {
                showToast('Subtitle not found', 'error');
                return;
            }
            
            const sub = subtitles[lineIndex];
            const time = parseTimestamp(sub.start);
            
            console.log('Batch target time:', time, 'subtitle:', sub.text.substring(0, 30));
            
            // Save active tab before closing
            const activeTab = document.querySelector('#unknownWordsTabs .nav-link.active');
            if (activeTab) {
                const tabIdx = activeTab.id.replace('uw-tab-', '');
                localStorage.setItem('uwActiveTab', tabIdx);
                const contentEl = document.getElementById('unknownWordsTabContent');
                if (contentEl) {
                    localStorage.setItem('uwScroll_' + tabIdx, contentEl.scrollTop);
                }
            }

            // Close modal
            $('#unknownWordsModal').removeClass('active');
            
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
            
            // Switch to the file tab using Bootstrap Tab
            switchBatchFile(fileIndex);
            
            // Wait longer for tab switch, then select and play
            setTimeout(() => {
                selectSubtitle(fileIndex, lineIndex);
                
                // Scroll to row
                const row = $(`.subtitle-row[data-file="${fileIndex}"][data-index="${lineIndex}"]`);
                if (row.length) {
                    row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                // Seek video and play
                setTimeout(() => {
                    console.log('Batch video player check:', {
                        exists: !!videoPlayer,
                        src: videoPlayer ? videoPlayer.currentSrc : 'no video',
                        readyState: videoPlayer ? videoPlayer.readyState : 'N/A'
                    });
                    
                    if (videoPlayer && videoPlayer.readyState >= 1) {
                        videoPlayer.currentTime = time;
                        videoPlayer.play().then(() => {
                            showToast('Playing line ' + (lineIndex + 1), 'success');
                        }).catch(e => {
                            console.log('Batch play error:', e);
                            showToast('Loaded at line ' + (lineIndex + 1) + ' - click play', 'warning');
                        });
                    } else if (videoPlayer) {
                        videoPlayer.currentTime = time;
                        showToast('Video at line ' + (lineIndex + 1) + ' - click play', 'warning');
                    } else {
                        showToast('Line ' + (lineIndex + 1) + ' selected', 'success');
                    }
                }, 300);
            }, 300);
        }
        
        function exportUnknownWords() {
            window.location.href = 'includes/export-words.php?batch_mode=1';
        }
        
        function closeModal(id) {
            if (id === 'unknownWordsModal') {
                const activeTab = document.querySelector('#unknownWordsTabs .nav-link.active');
                if (activeTab) {
                    const idx = activeTab.id.replace('uw-tab-', '');
                    localStorage.setItem('uwActiveTab', idx);
                    // Save scroll position from the shared tab-content container
                    const contentEl = document.getElementById('unknownWordsTabContent');
                    if (contentEl) {
                        localStorage.setItem('uwScroll_' + idx, contentEl.scrollTop);
                    }
                }
            }
            $('#' + id).removeClass('active');
        }
        
        function clearSession() {
            showConfirm('Start new files?', 'All current changes will be lost. Are you sure?', function() {
                $.post('display-batch.php', { clear_session: true }, function() {
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
        $(document).on('click', '#confirmModal', function(e) {
            if ($(e.target).is('#confirmModal')) {
                $('#confirmModal').removeClass('active');
                _confirmCallback = null;
            }
        });

        function showToast(message, type = 'success') {
            let container = $('.toast-container');
            if (!container.length) {
                container = $('<div class="toast-container"></div>');
                $('body').append(container);
            }
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                warning: 'fa-exclamation-triangle'
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
        });
    </script>
    <?php include __DIR__ . '/includes/modal-words.php'; ?>
</body>

</html>