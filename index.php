<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SrtAss - Subtitle Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="content/css/editor.css">
    <link rel="stylesheet" href="content/css/theme.css">
    <link rel="stylesheet" href="content/css/modern.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        /* Default: Dark Mode (same as display.php) */
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
        
        /* Light Mode */
        [data-theme="light"] {
            --bg-body: #f8fafc;
            --bg-navbar: rgba(255, 255, 255, 0.95);
            --bg-surface: #ffffff;
            --bg-surface-hover: #f1f5f9;
            --bg-input: #ffffff;
            --border-color: #e2e8f0;
            --border-color-hover: #cbd5e1;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --text-inverse: #ffffff;
        }
        
        body {
            background: var(--bg-body);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-primary);
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        .navbar {
            background: var(--bg-navbar);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .navbar-brand i {
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 140px);
            padding: 2rem;
        }
        
        .hero {
            text-align: center;
            max-width: 600px;
            margin-bottom: 2.5rem;
        }
        
        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            line-height: 1.7;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            width: 100%;
            max-width: 900px;
        }
        
        .feature-card {
            background: var(--bg-surface);
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }
        
        .feature-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }
        
        .feature-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .feature-card p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
        }
        
        .upload-area {
            background: var(--bg-surface);
            border: 2px dashed var(--border-color-hover);
            border-radius: 16px;
            padding: 3rem 2rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .upload-area:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }
        
        .upload-area.drag-over {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            transform: scale(1.02);
        }
        
        .upload-area i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .upload-area h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .upload-area p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        .upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .selected-files {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .file-tag {
            background: var(--primary);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .file-tag .remove {
            cursor: pointer;
            opacity: 0.8;
        }
        
        .file-tag .remove:hover {
            opacity: 1;
        }
        
        .upload-btn {
            margin-top: 1.5rem;
            padding: 0.875rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .upload-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .upload-hint {
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        footer {
            background: var(--bg-navbar);
            border-top: 1px solid var(--border-color);
            padding: 1rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
        
        .theme-toggle {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .theme-toggle:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .theme-toggle i {
            font-size: 1rem;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hero, .features, .upload-area {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .features { animation-delay: 0.1s; }
        .upload-area { animation-delay: 0.2s; }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .features { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-closed-captioning"></i>
                SrtAss
            </a>
            <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <div class="main-content">
        <div class="hero">
            <h1>Subtitle Editor</h1>
            <p>Upload SRT or ASS subtitle files, edit them interactively, convert between formats, and manage your dictionary.</p>
        </div>

        <div class="features">
            <div class="feature-card">
                <i class="fas fa-file-import"></i>
                <h3>Import</h3>
                <p>Support SRT & ASS formats</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-edit"></i>
                <h3>Edit</h3>
                <p>Interactive subtitle editing</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-exchange-alt"></i>
                <h3>Convert</h3>
                <p>SRT to ASS or vice versa</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-book"></i>
                <h3>Dictionary</h3>
                <p>Custom word replacement</p>
            </div>
        </div>

        <div class="upload-area" id="dropZone">
            <i class="fas fa-cloud-upload-alt"></i>
            <h3>Drop subtitle files here</h3>
            <p>or click to browse</p>
            <input type="file" id="fileInput" accept=".srt,.ass" multiple>
        </div>
        
        <div class="selected-files" id="selectedFiles"></div>
        
        <button class="upload-btn" id="uploadBtn" disabled>
            <i class="fas fa-upload me-2"></i>Upload and Edit
        </button>
        
        <p class="upload-hint">Supported formats: .srt, .ass</p>
    </div>

    <footer>
        <p>&copy; 2026 SrtAss - Subtitle Editor Tool</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            const themeToggle = $('#themeToggle');
            const themeIcon = themeToggle.find('i');
            
            const savedTheme = localStorage.getItem('theme') || 'dark';
            applyTheme(savedTheme);

            themeToggle.on('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                applyTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                if (theme === 'dark') {
                    themeIcon.removeClass('fa-moon').addClass('fa-sun');
                } else {
                    themeIcon.removeClass('fa-sun').addClass('fa-moon');
                }
            }

            const fileInput = $('#fileInput');
            const dropZone = $('#dropZone');
            const selectedFiles = $('#selectedFiles');
            const uploadBtn = $('#uploadBtn');
            
            let files = [];
            
            // File selection
            fileInput.on('change', function(e) {
                handleFiles(e.target.files);
            });
            
            // Drag and drop
            dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });
            
            dropZone.on('dragleave', function() {
                $(this).removeClass('drag-over');
            });
            
            dropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                handleFiles(e.originalEvent.dataTransfer.files);
            });
            
            function handleFiles(newFiles) {
                Array.from(newFiles).forEach(file => {
                    if (file.name.match(/\.(srt|ass)$/i)) {
                        files.push(file);
                    }
                });
                updateSelectedFiles();
            }
            
            function updateSelectedFiles() {
                selectedFiles.empty();
                files.forEach((file, index) => {
                    selectedFiles.append(`
                        <span class="file-tag">
                            ${file.name}
                            <i class="fas fa-times remove" data-index="${index}"></i>
                        </span>
                    `);
                });
                
                uploadBtn.prop('disabled', files.length === 0);
                
                $('.remove').on('click', function() {
                    files.splice($(this).data('index'), 1);
                    updateSelectedFiles();
                });
            }
            
            // Upload button
            uploadBtn.on('click', function() {
                if (files.length === 0) return;
                
                if (files.length === 1) {
                    // Single file - redirect to display.php
                    const formData = new FormData();
                    formData.append('subtitle_file', files[0]);
                    
                    $.ajax({
                        url: 'display.php',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function() {
                            window.location.href = 'display.php';
                        }
                    });
                } else {
                    // Multiple files - redirect to display-batch.php
                    const formData = new FormData();
                    files.forEach(file => {
                        formData.append('subtitle_files[]', file);
                    });
                    
                    $.ajax({
                        url: 'display-batch.php',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function() {
                            window.location.href = 'display-batch.php';
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>