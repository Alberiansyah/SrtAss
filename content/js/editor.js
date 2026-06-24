// ======= Main Editor JavaScript =======

document.addEventListener('DOMContentLoaded', function() {
    // ======= Initialize Variables =======
    let videoPlayer = null;
    let currentTime = 0;
    let duration = 0;
    let isPlaying = false;
    let selectedSubtitleIndex = null;
    let subtitles = [];
    let autoSaveInterval = null;
    
    // ======= DOM Elements =======
    const videoElement = document.getElementById('videoPlayer');
    const playBtn = document.getElementById('playBtn');
    const timelineSlider = document.getElementById('timelineSlider');
    const currentTimeDisplay = document.getElementById('currentTime');
    const totalTimeDisplay = document.getElementById('totalTime');
    const subtitleList = document.getElementById('subtitleList');
    const searchInput = document.getElementById('subtitleSearch');
    
    // ======= Video Player Controls =======
    if (videoElement) {
        videoPlayer = videoElement;
        
        videoElement.addEventListener('loadedmetadata', function() {
            duration = videoElement.duration;
            updateTimeDisplay();
            renderTimelineBlocks();
        });
        
        videoElement.addEventListener('timeupdate', function() {
            currentTime = videoElement.currentTime;
            updateTimeDisplay();
            updateSliderProgress();
            highlightCurrentSubtitle();
        });
        
        videoElement.addEventListener('ended', function() {
            isPlaying = false;
            updatePlayButton();
        });
        
        videoElement.addEventListener('play', function() {
            isPlaying = true;
            updatePlayButton();
        });
        
        videoElement.addEventListener('pause', function() {
            isPlaying = false;
            updatePlayButton();
        });
    }
    
    // ======= Play/Pause Button =======
    if (playBtn) {
        playBtn.addEventListener('click', togglePlayPause);
    }
    
    function togglePlayPause() {
        if (!videoPlayer) return;
        
        if (isPlaying) {
            videoPlayer.pause();
        } else {
            videoPlayer.play();
        }
    }
    
    function updatePlayButton() {
        if (playBtn) {
            const icon = playBtn.querySelector('i');
            if (icon) {
                icon.className = isPlaying ? 'fas fa-pause' : 'fas fa-play';
            }
        }
    }
    
    // ======= Time Display =======
    function updateTimeDisplay() {
        if (currentTimeDisplay) {
            currentTimeDisplay.textContent = formatTime(currentTime);
        }
        if (totalTimeDisplay && duration > 0) {
            totalTimeDisplay.textContent = formatTime(duration);
        }
    }
    
    function formatTime(seconds) {
        if (isNaN(seconds) || !isFinite(seconds)) return '00:00:00';
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        return [hours, minutes, secs]
            .map(v => v.toString().padStart(2, '0'))
            .join(':');
    }
    
    // ======= Timeline Slider =======
    if (timelineSlider) {
        timelineSlider.addEventListener('input', function() {
            if (!videoPlayer || !duration) return;
            const time = (this.value / 100) * duration;
            videoPlayer.currentTime = time;
            currentTime = time;
        });
        
        timelineSlider.addEventListener('change', function() {
            if (!videoPlayer) return;
            const time = (this.value / 100) * duration;
            videoPlayer.currentTime = time;
        });
    }
    
    function updateSliderProgress() {
        if (timelineSlider && duration > 0) {
            const progress = (currentTime / duration) * 100;
            timelineSlider.value = progress;
            
            // Update custom track
            const track = timelineSlider;
            track.style.setProperty('--progress', progress + '%');
        }
    }
    
    // ======= Timeline Blocks (Aegisub-style) =======
    function renderTimelineBlocks() {
        const blocksContainer = document.getElementById('subtitleBlocks');
        if (!blocksContainer || !duration) return;
        
        blocksContainer.innerHTML = '';
        
        const subtitleData = window.subtitleData || [];
        
        subtitleData.forEach((subtitle, index) => {
            const startTime = parseTimestamp(subtitle.start);
            const endTime = parseTimestamp(subtitle.end);
            
            const leftPercent = (startTime / duration) * 100;
            const widthPercent = ((endTime - startTime) / duration) * 100;
            
            const block = document.createElement('div');
            block.className = 'subtitle-block';
            block.style.left = leftPercent + '%';
            block.style.width = Math.max(widthPercent, 0.5) + '%';
            block.dataset.index = index;
            block.title = subtitle.text.substring(0, 50) + '...';
            
            block.addEventListener('click', function() {
                selectSubtitleByIndex(index);
                if (videoPlayer) {
                    videoPlayer.currentTime = startTime;
                }
            });
            
            blocksContainer.appendChild(block);
        });
    }
    
    function parseTimestamp(timestamp) {
        // Format: 00:00:00.00 or 00:00:00,000
        const parts = timestamp.replace(',', '.').split(':');
        if (parts.length !== 3) return 0;
        
        const hours = parseInt(parts[0]);
        const minutes = parseInt(parts[1]);
        const seconds = parseFloat(parts[2]);
        
        return hours * 3600 + minutes * 60 + seconds;
    }
    
    // ======= Subtitle List =======
    function renderSubtitleList(filter = '') {
        if (!subtitleList) return;
        
        const subtitleData = window.subtitleData || [];
        let filteredData = subtitleData;
        
        if (filter) {
            const lowerFilter = filter.toLowerCase();
            filteredData = subtitleData.filter(sub => 
                sub.text.toLowerCase().includes(lowerFilter)
            );
        }
        
        let html = '';
        
        filteredData.forEach((subtitle, index) => {
            const originalIndex = subtitleData.indexOf(subtitle);
            const isActive = originalIndex === selectedSubtitleIndex;
            const isSelected = isActive;
            
            html += `
                <div class="subtitle-row ${isActive ? 'active' : ''} ${isSelected ? 'selected' : ''}" 
                     data-index="${originalIndex}">
                    <div class="subtitle-index">${originalIndex + 1}</div>
                    <div class="subtitle-times">
                        <span class="time-start">${subtitle.start}</span>
                        <span class="time-end">${subtitle.end}</span>
                    </div>
                    <div class="subtitle-content" data-index="${originalIndex}">${subtitle.text}</div>
                    <div class="subtitle-actions">
                        <button class="play-from-btn" data-time="${subtitle.start}" title="Play from here">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        if (filteredData.length === 0) {
            html = `
                <div class="empty-state">
                    <i class="fas fa-film"></i>
                    <p>${filter ? 'No subtitles match your search' : 'No subtitles available'}</p>
                </div>
            `;
        }
        
        subtitleList.innerHTML = html;
        
        // Add click handlers
        document.querySelectorAll('.subtitle-row').forEach(row => {
            row.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                selectSubtitleByIndex(index);
            });
        });
        
        document.querySelectorAll('.play-from-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const time = parseTimestamp(this.dataset.time);
                if (videoPlayer) {
                    videoPlayer.currentTime = time;
                    videoPlayer.play();
                }
            });
        });
        
        document.querySelectorAll('.subtitle-content').forEach(content => {
            content.addEventListener('dblclick', function(e) {
                e.stopPropagation();
                const index = parseInt(this.dataset.index);
                enableInlineEdit(index);
            });
        });
    }
    
    function selectSubtitleByIndex(index) {
        selectedSubtitleIndex = index;
        
        // Update row selection
        document.querySelectorAll('.subtitle-row').forEach(row => {
            row.classList.remove('active', 'selected');
        });
        
        const activeRow = document.querySelector(`.subtitle-row[data-index="${index}"]`);
        if (activeRow) {
            activeRow.classList.add('active', 'selected');
            activeRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        // Update timeline block selection
        document.querySelectorAll('.subtitle-block').forEach((block, i) => {
            block.classList.toggle('active', i === index);
        });
        
        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('subtitleSelected', { detail: { index } }));
    }
    
    function highlightCurrentSubtitle() {
        if (!videoPlayer || !duration) return;
        
        let activeIndex = -1;
        const subtitleData = window.subtitleData || [];
        
        for (let i = 0; i < subtitleData.length; i++) {
            const start = parseTimestamp(subtitleData[i].start);
            const end = parseTimestamp(subtitleData[i].end);
            
            if (currentTime >= start && currentTime <= end) {
                activeIndex = i;
                break;
            }
        }
        
        if (activeIndex !== -1 && activeIndex !== selectedSubtitleIndex) {
            // Auto-scroll to current subtitle
            const row = document.querySelector(`.subtitle-row[data-index="${activeIndex}"]`);
            if (row) {
                row.classList.add('active');
                
                // Remove active from others
                document.querySelectorAll('.subtitle-row.active').forEach(r => {
                    if (r !== row) r.classList.remove('active');
                });
            }
        }
    }
    
    // ======= Search =======
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            renderSubtitleList(this.value);
        });
    }
    
    // ======= Inline Editing =======
    function enableInlineEdit(index) {
        const contentCell = document.querySelector(`.subtitle-content[data-index="${index}"]`);
        if (!contentCell) return;
        
        const currentText = contentCell.textContent;
        
        // Create textarea
        const textarea = document.createElement('textarea');
        textarea.value = currentText;
        textarea.className = 'edit-textarea';
        
        // Replace content with textarea
        contentCell.style.display = 'none';
        contentCell.parentNode.insertBefore(textarea, contentCell.nextSibling);
        textarea.style.display = 'block';
        textarea.focus();
        
        // Handle save on blur/enter
        const saveEdit = function() {
            const newText = textarea.value.trim();
            if (newText !== currentText) {
                // Send AJAX to update
                updateSubtitleText(index, newText);
            }
            textarea.remove();
            contentCell.style.display = 'block';
        };
        
        textarea.addEventListener('blur', saveEdit);
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                saveEdit();
            }
            if (e.key === 'Escape') {
                textarea.remove();
                contentCell.style.display = 'block';
            }
        });
    }
    
    function updateSubtitleText(index, newText) {
        const formData = new FormData();
        formData.append('index', index);
        formData.append('text', newText);
        
        fetch('update-subtitle.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Update the cell with highlighted text
            const contentCell = document.querySelector(`.subtitle-content[data-index="${index}"]`);
            if (contentCell) {
                contentCell.innerHTML = html;
            }
            showToast('Subtitle updated', 'success');
        })
        .catch(error => {
            console.error('Error updating subtitle:', error);
            showToast('Failed to update subtitle', 'error');
        });
    }
    
    // ======= Keyboard Shortcuts =======
    document.addEventListener('keydown', function(e) {
        // Ignore if typing in input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        switch(e.key) {
            case ' ':
                e.preventDefault();
                togglePlayPause();
                break;
            case 'ArrowLeft':
                if (videoPlayer) {
                    videoPlayer.currentTime = Math.max(0, currentTime - 5);
                }
                break;
            case 'ArrowRight':
                if (videoPlayer) {
                    videoPlayer.currentTime = Math.min(duration, currentTime + 5);
                }
                break;
            case 'ArrowUp':
                if (selectedSubtitleIndex !== null && selectedSubtitleIndex > 0) {
                    selectSubtitleByIndex(selectedSubtitleIndex - 1);
                }
                break;
            case 'ArrowDown':
                if (selectedSubtitleIndex !== null && selectedSubtitleIndex < (window.subtitleData?.length - 1)) {
                    selectSubtitleByIndex(selectedSubtitleIndex + 1);
                }
                break;
            case 'Enter':
                if (selectedSubtitleIndex !== null) {
                    const sub = window.subtitleData?.[selectedSubtitleIndex];
                    if (sub && videoPlayer) {
                        videoPlayer.currentTime = parseTimestamp(sub.start);
                        videoPlayer.play();
                    }
                }
                break;
            case 's':
                if (e.ctrlKey) {
                    e.preventDefault();
                    document.getElementById('downloadBtn')?.click();
                }
                break;
        }
    });
    
    // ======= Toast Notifications =======
    function showToast(message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-times-circle',
            warning: 'fas fa-exclamation-triangle'
        };
        
        toast.innerHTML = `
            <i class="${icons[type]}"></i>
            <p>${message}</p>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // ======= Panel Toggle =======
    document.querySelectorAll('.panel-header').forEach(header => {
        header.addEventListener('click', function() {
            this.closest('.panel-section').classList.toggle('collapsed');
        });
    });
    
    // ======= Auto Save =======
    function startAutoSave() {
        autoSaveInterval = setInterval(() => {
            // Save session state
            console.log('Auto-save: Session state saved');
        }, 60000); // Every minute
    }
    
    // ======= Drag and Drop Video =======
    const videoWrapper = document.querySelector('.video-wrapper');
    if (videoWrapper) {
        videoWrapper.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        videoWrapper.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });
        
        videoWrapper.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('video/')) {
                const url = URL.createObjectURL(files[0]);
                videoElement.src = url;
                showToast('Video loaded successfully', 'success');
            }
        });
    }
    
    // ======= Initialize =======
    if (typeof window.subtitleData !== 'undefined') {
        renderSubtitleList();
        renderTimelineBlocks();
    }
    
    startAutoSave();
    
    // ======= Export Functions =======
    window.editor = {
        play: () => videoPlayer?.play(),
        pause: () => videoPlayer?.pause(),
        seek: (time) => { if (videoPlayer) videoPlayer.currentTime = time; },
        getCurrentTime: () => currentTime,
        getSelectedIndex: () => selectedSubtitleIndex,
        selectSubtitle: selectSubtitleByIndex,
        showToast: showToast
    };
});