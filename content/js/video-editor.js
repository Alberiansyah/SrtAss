$(document).ready(function() {
    let parsedSubtitles = [];
    let currentSubtitleIndex = -1;
    let hasChanges = false;
    let videoDuration = 0;
    let showHighlights = false;

    const video = document.getElementById('videoPlayer');
    const subtitleOverlay = document.getElementById('subtitleOverlay');
    const subtitleList = document.getElementById('subtitleList');
    const timeline = document.getElementById('timeline');
    const timelineProgress = document.getElementById('timelineProgress');
    const timelinePlayhead = document.getElementById('timelinePlayhead');
    const subtitleMarkers = document.getElementById('subtitleMarkers');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const editForm = document.getElementById('editForm');
    const noSelection = document.getElementById('noSelection');

    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
        const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
        
        $('#toastContainer').append(`
            <div id="${toastId}" class="toast ${bgClass}" role="alert">
                <div class="toast-body d-flex align-items-center">
                    <i class="fas ${icon} me-2"></i>
                    ${message}
                </div>
            </div>
        `);
        const toast = new bootstrap.Toast(document.getElementById(toastId), { delay: 3000 });
        toast.show();
        document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    function formatTime(seconds, includeMs = false) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        if (includeMs) {
            const ms = Math.floor((seconds % 1) * 1000);
            return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')},${String(ms).padStart(3, '0')}`;
        }
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    function parseTime(timeStr) {
        const match = timeStr.match(/(\d+):(\d{2}):(\d{2})[,.](\d{1,3})/);
        if (match) {
            return parseInt(match[1]) * 3600 + parseInt(match[2]) * 60 + parseInt(match[3]) + parseInt(match[4].padEnd(3, '0')) / 1000;
        }
        return 0;
    }

    function parseSRT(content) {
        const blocks = content.trim().split(/\n\s*\n/);
        const parsed = [];
        blocks.forEach((block, idx) => {
            const lines = block.split('\n');
            if (lines.length >= 3) {
                const timeLine = lines[1];
                const timeMatch = timeLine.match(/(\d{2}):(\d{2}):(\d{2}),(\d{3})\s*-->\s*(\d{2}):(\d{2}):(\d{2}),(\d{3})/);
                if (timeMatch) {
                    parsed.push({
                        index: idx,
                        startTime: parseTime(timeMatch[0]),
                        endTime: parseTime(timeLine.split('-->')[1].trim()),
                        text: lines.slice(2).join('\n')
                    });
                }
            }
        });
        return parsed;
    }

    function parseASS(content) {
        const lines = content.split('\n');
        const parsed = [];
        lines.forEach((line, idx) => {
            if (line.startsWith('Dialogue:')) {
                const parts = line.substring(9).split(',');
                if (parts.length >= 10) {
                    parsed.push({
                        index: idx,
                        startTime: parseTime(parts[1].trim()),
                        endTime: parseTime(parts[2].trim()),
                        text: parts.slice(9).join(',').replace(/\{[^}]*\}/g, '').replace(/\\N/g, '\n').replace(/\\h/g, ' ')
                    });
                }
            }
        });
        return parsed;
    }

    function applyDictionaryReplace(text) {
        if (typeof fullDictionary === 'undefined' || !fullDictionary) return text;
        let result = text;
        Object.entries(fullDictionary).forEach(([key, value]) => {
            const regex = new RegExp(`(${key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            result = result.replace(regex, value);
        });
        return result;
    }

    function highlightDictionaryWords(text) {
        if (!showHighlights || typeof fullDictionary === 'undefined' || !fullDictionary) {
            return escapeHtml(text);
        }
        
        let result = text;
        let hasMatch = false;
        
        Object.entries(fullDictionary).forEach(([key, value]) => {
            const regex = new RegExp(`(${escapeRegex(key)})`, 'gi');
            if (regex.test(text)) {
                hasMatch = true;
            }
            result = result.replace(regex, '<span class="highlight-word" title="Original: $1 → Replace: ' + value + '">$1</span>');
        });
        
        if (!hasMatch) {
            return escapeHtml(text);
        }
        
        return result;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function getDisplayText(text) {
        if (!showHighlights || typeof fullDictionary === 'undefined' || !fullDictionary) {
            return escapeHtml(text);
        }
        
        let result = text;
        let hasReplacement = false;
        
        Object.entries(fullDictionary).forEach(([key, value]) => {
            const regex = new RegExp(`(${escapeRegex(key)})`, 'gi');
            if (regex.test(text)) {
                hasReplacement = true;
                result = result.replace(regex, '<span class="highlight-word">$1</span>');
            }
        });
        
        return hasReplacement ? result : escapeHtml(text);
    }

    function generateSRT() {
        let srt = '';
        parsedSubtitles.forEach((sub, i) => {
            srt += `${i + 1}\n${formatTime(sub.startTime, true)} --> ${formatTime(sub.endTime, true)}\n${sub.text}\n\n`;
        });
        return srt.trim();
    }

    function renderSubtitleMarkers() {
        if (!videoDuration) return;
        subtitleMarkers.innerHTML = parsedSubtitles.map((sub, i) => {
            const left = (sub.startTime / videoDuration) * 100;
            const width = ((sub.endTime - sub.startTime) / videoDuration) * 100;
            return `<div class="subtitle-marker ${i === currentSubtitleIndex ? 'active' : ''}" 
                        style="left: ${left}%; width: ${Math.max(width, 0.5)}%;" 
                        data-index="${i}"></div>`;
        }).join('');
    }

    function renderSubtitleList() {
        if (parsedSubtitles.length === 0) {
            subtitleList.innerHTML = `
                <div class="empty-list">
                    <i class="fas fa-closed-captioning fa-2x mb-2"></i>
                    <p>Upload subtitle untuk melihat list</p>
                </div>`;
            document.getElementById('subtitleCount').textContent = '0';
            return;
        }

        document.getElementById('subtitleCount').textContent = parsedSubtitles.length;
        subtitleList.innerHTML = parsedSubtitles.map((sub, i) => `
            <div class="subtitle-item ${i === currentSubtitleIndex ? 'active' : ''}" data-index="${i}">
                <div class="time">${formatTime(sub.startTime, true)} → ${formatTime(sub.endTime, true)}</div>
                <div class="text">${getDisplayText(sub.text)}</div>
            </div>
        `).join('');

        $('.subtitle-item').on('click', function() {
            const index = parseInt($(this).data('index'));
            selectSubtitle(index);
            video.currentTime = parsedSubtitles[index].startTime;
            video.play();
        });
    }

    function selectSubtitle(index) {
        currentSubtitleIndex = index;
        const sub = parsedSubtitles[index];
        
        if (!sub) return;

        noSelection.style.display = 'none';
        editForm.style.display = 'block';

        const startParts = formatTime(sub.startTime, true).split(/[:,]/);
        const endParts = formatTime(sub.endTime, true).split(/[:,]/);

        document.getElementById('startH').value = startParts[0];
        document.getElementById('startM').value = startParts[1];
        document.getElementById('startS').value = startParts[2];
        document.getElementById('startMs').value = startParts[3];
        document.getElementById('endH').value = endParts[0];
        document.getElementById('endM').value = endParts[1];
        document.getElementById('endS').value = endParts[2];
        document.getElementById('endMs').value = endParts[3];
        document.getElementById('subtitleText').value = sub.text;

        $('.subtitle-item').removeClass('active');
        $(`.subtitle-item[data-index="${index}"]`).addClass('active');
        renderSubtitleMarkers();
    }

    function updateCurrentSubtitle() {
        const currentTime = video.currentTime;
        let activeIndex = -1;

        for (let i = 0; i < parsedSubtitles.length; i++) {
            if (currentTime >= parsedSubtitles[i].startTime && currentTime <= parsedSubtitles[i].endTime) {
                activeIndex = i;
                break;
            }
        }

        if (activeIndex !== currentSubtitleIndex) {
            currentSubtitleIndex = activeIndex;
            if (activeIndex >= 0) {
                selectSubtitle(activeIndex);
                subtitleOverlay.innerHTML = `<div class="subtitle-text">${getDisplayText(parsedSubtitles[activeIndex].text)}</div>`;
            } else {
                subtitleOverlay.innerHTML = '';
                noSelection.style.display = 'block';
                editForm.style.display = 'none';
            }
        }
    }

    function updateTimeline() {
        if (!videoDuration) return;
        const progress = (video.currentTime / videoDuration) * 100;
        timelineProgress.style.width = progress + '%';
        timelinePlayhead.style.left = progress + '%';
    }

    function updateTimeDisplay() {
        document.getElementById('currentTime').textContent = formatTime(video.currentTime);
        document.getElementById('totalTime').textContent = formatTime(videoDuration);
    }

    uploadPrompt.addEventListener('click', () => {
        document.getElementById('videoFile').click();
    });

    document.getElementById('videoUpload').addEventListener('click', () => {
        document.getElementById('videoFile').click();
    });

    document.getElementById('subtitleUpload').addEventListener('click', () => {
        document.getElementById('subtitleFile').click();
    });

    document.getElementById('videoFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const url = URL.createObjectURL(file);
            video.src = url;
            uploadPrompt.classList.add('hidden');
            video.classList.add('loaded');
        }
    });

    document.getElementById('subtitleFile').addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (file) {
            const content = await file.text();
            const ext = file.name.split('.').pop().toLowerCase();
            parsedSubtitles = ext === 'ass' ? parseASS(content) : parseSRT(content);
            parsedSubtitles.sort((a, b) => a.startTime - b.startTime);
            renderSubtitleList();
            renderSubtitleMarkers();
            document.getElementById('btnSaveSubtitle').disabled = false;
            document.getElementById('btnExportSubtitle').disabled = false;
            showToast(`${parsedSubtitles.length} subtitles loaded!`, 'success');
        }
    });

    document.getElementById('showHighlights').addEventListener('change', function() {
        showHighlights = this.checked;
        renderSubtitleList();
        if (currentSubtitleIndex >= 0) {
            subtitleOverlay.innerHTML = `<div class="subtitle-text">${getDisplayText(parsedSubtitles[currentSubtitleIndex].text)}</div>`;
        }
        if (showHighlights) {
            showToast('Dictionary highlights enabled - showing words that will be replaced', 'info');
        }
    });

    video.addEventListener('loadedmetadata', function() {
        videoDuration = video.duration;
        updateTimeDisplay();
        renderSubtitleMarkers();
    });

    video.addEventListener('timeupdate', function() {
        updateTimeline();
        updateTimeDisplay();
        updateCurrentSubtitle();
    });

    timeline.addEventListener('click', function(e) {
        if (!videoDuration) return;
        const rect = timeline.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const percent = x / rect.width;
        video.currentTime = percent * videoDuration;
        video.play();
    });

    document.getElementById('btnPlayPause').addEventListener('click', function() {
        if (video.paused) {
            video.play();
            $(this).find('i').removeClass('fa-play').addClass('fa-pause');
        } else {
            video.pause();
            $(this).find('i').removeClass('fa-pause').addClass('fa-play');
        }
    });

    document.getElementById('btnBackward').addEventListener('click', function() {
        video.currentTime = Math.max(0, video.currentTime - 5);
    });

    document.getElementById('btnForward').addEventListener('click', function() {
        video.currentTime = Math.min(videoDuration, video.currentTime + 5);
    });

    document.getElementById('btnMute').addEventListener('click', function() {
        video.muted = !video.muted;
        $(this).find('i').toggleClass('fa-volume-up fa-volume-mute');
    });

    document.getElementById('volumeSlider').addEventListener('input', function() {
        video.volume = this.value;
    });

    document.getElementById('btnSetStart').addEventListener('click', function() {
        const time = formatTime(video.currentTime, true).split(/[,:]/);
        document.getElementById('startH').value = time[0];
        document.getElementById('startM').value = time[1];
        document.getElementById('startS').value = time[2];
        document.getElementById('startMs').value = time[3];
    });

    document.getElementById('btnSetEnd').addEventListener('click', function() {
        const time = formatTime(video.currentTime, true).split(/[,:]/);
        document.getElementById('endH').value = time[0];
        document.getElementById('endM').value = time[1];
        document.getElementById('endS').value = time[2];
        document.getElementById('endMs').value = time[3];
    });

    document.getElementById('btnApply').addEventListener('click', function() {
        if (currentSubtitleIndex < 0) return;

        const startH = parseInt(document.getElementById('startH').value) || 0;
        const startM = parseInt(document.getElementById('startM').value) || 0;
        const startS = parseInt(document.getElementById('startS').value) || 0;
        const startMs = parseInt(document.getElementById('startMs').value) || 0;
        
        const endH = parseInt(document.getElementById('endH').value) || 0;
        const endM = parseInt(document.getElementById('endM').value) || 0;
        const endS = parseInt(document.getElementById('endS').value) || 0;
        const endMs = parseInt(document.getElementById('endMs').value) || 0;

        const newStart = startH * 3600 + startM * 60 + startS + startMs / 1000;
        const newEnd = endH * 3600 + endM * 60 + endS + endMs / 1000;

        parsedSubtitles[currentSubtitleIndex].startTime = newStart;
        parsedSubtitles[currentSubtitleIndex].endTime = newEnd;
        parsedSubtitles[currentSubtitleIndex].text = document.getElementById('subtitleText').value;

        hasChanges = true;
        renderSubtitleList();
        renderSubtitleMarkers();
        showToast('Subtitle updated!', 'success');
    });

    document.getElementById('btnDelete').addEventListener('click', function() {
        if (currentSubtitleIndex < 0) return;
        var doDelete = function() {
            parsedSubtitles.splice(currentSubtitleIndex, 1);
            currentSubtitleIndex = -1;
            hasChanges = true;
            renderSubtitleList();
            renderSubtitleMarkers();
            noSelection.style.display = 'block';
            editForm.style.display = 'none';
            showToast('Subtitle deleted!', 'success');
        };
        if (typeof showConfirm === 'function') {
            showConfirm('Delete subtitle', 'Delete this subtitle?', doDelete);
        } else if (confirm('Delete this subtitle?')) {
            doDelete();
        }
    });
        currentSubtitleIndex = -1;
        hasChanges = true;
        renderSubtitleList();
        renderSubtitleMarkers();
        noSelection.style.display = 'block';
        editForm.style.display = 'none';
        showToast('Subtitle deleted!', 'success');
    });

    document.getElementById('btnSaveSubtitle').addEventListener('click', function() {
        const srtContent = generateSRT();
        const blob = new Blob([srtContent], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'edited_subtitles.srt';
        a.click();
        URL.revokeObjectURL(url);
        hasChanges = false;
        showToast('Subtitles saved!', 'success');
    });

    document.getElementById('btnExportSubtitle').addEventListener('click', function() {
        document.getElementById('btnSaveSubtitle').click();
    });

    document.getElementById('themeToggle').addEventListener('click', function() {
        const current = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        $('#themeIcon').toggleClass('fa-moon fa-sun');
    });

    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-bs-theme', savedTheme);
    if (savedTheme === 'light') {
        $('#themeIcon').removeClass('fa-moon').addClass('fa-sun');
    }

    $(document).on('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        switch(e.key) {
            case ' ':
                e.preventDefault();
                $('#btnPlayPause').click();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                $('#btnBackward').click();
                break;
            case 'ArrowRight':
                e.preventDefault();
                $('#btnForward').click();
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (currentSubtitleIndex > 0) {
                    selectSubtitle(currentSubtitleIndex - 1);
                }
                break;
            case 'ArrowDown':
                e.preventDefault();
                if (currentSubtitleIndex < parsedSubtitles.length - 1) {
                    selectSubtitle(currentSubtitleIndex + 1);
                }
                break;
            case 'Enter':
                if (e.ctrlKey && currentSubtitleIndex >= 0) {
                    e.preventDefault();
                    $('#btnApply').click();
                }
                break;
            case 'Delete':
                if (currentSubtitleIndex >= 0 && !e.target.matches('input, textarea')) {
                    e.preventDefault();
                    $('#btnDelete').click();
                }
                break;
        }
    });
});
