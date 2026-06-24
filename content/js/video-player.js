$.fn.scrollTo = function(target, options) {
    if (typeof target === 'number') {
        options = options || {};
        options.scrollTop = target;
    } else {
        options = target || {};
    }
    return this.scrollTo(options);
};

$(document).ready(function() {
    let subtitles = [];
    let currentSubtitleIndex = -1;
    let parsedSubtitles = [];

    const video = document.getElementById('videoPlayer');
    const subtitleOverlay = document.getElementById('subtitleOverlay');
    const subtitleList = document.getElementById('subtitleList');
    const previewBox = document.getElementById('previewBox');
    const uploadPrompt = document.getElementById('uploadPrompt');

    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
        const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
        
        const toastHtml = `
            <div id="${toastId}" class="toast ${bgClass}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-body d-flex align-items-center">
                    <i class="fas ${icon} me-2"></i>
                    ${message}
                </div>
            </div>
        `;
        
        $('#toastContainer').append(toastHtml);
        const toastEl = new bootstrap.Toast(document.getElementById(toastId), { delay: 3000 });
        toastEl.show();
        toastEl._element.addEventListener('hidden.bs.toast', () => toastEl._element.remove());
    }

    function parseSRT(content) {
        const blocks = content.trim().split(/\n\s*\n/);
        const parsed = [];
        
        blocks.forEach(block => {
            const lines = block.split('\n');
            if (lines.length >= 3) {
                const timeLine = lines[1];
                const timeMatch = timeLine.match(/(\d{2}):(\d{2}):(\d{2}),(\d{3})\s*-->\s*(\d{2}):(\d{2}):(\d{2}),(\d{3})/);
                
                if (timeMatch) {
                    const startTime = parseInt(timeMatch[1]) * 3600 + parseInt(timeMatch[2]) * 60 + parseInt(timeMatch[3]) + parseInt(timeMatch[4]) / 1000;
                    const endTime = parseInt(timeMatch[5]) * 3600 + parseInt(timeMatch[6]) * 60 + parseInt(timeMatch[7]) + parseInt(timeMatch[8]) / 1000;
                    const text = lines.slice(2).join('\n').replace(/<[^>]*>/g, '');
                    
                    parsed.push({ startTime, endTime, text, index: parsed.length });
                }
            }
        });
        
        return parsed;
    }

    function parseASS(content) {
        const lines = content.split('\n');
        const parsed = [];
        
        lines.forEach(line => {
            if (line.startsWith('Dialogue:')) {
                const parts = line.substring(9).split(',');
                if (parts.length >= 10) {
                    const startTime = parseTimeCode(parts[1].trim());
                    const endTime = parseTimeCode(parts[2].trim());
                    const text = parts[9].replace(/\{[^}]*\}/g, '').replace(/\\N/g, '\n').replace(/\\h/g, ' ').replace(/<[^>]*>/g, '');
                    
                    parsed.push({ startTime, endTime, text, index: parsed.length });
                }
            }
        });
        
        return parsed;
    }

    function parseTimeCode(timecode) {
        const match = timecode.match(/(\d+):(\d{2}):(\d{2})\.(\d{2})/);
        if (match) {
            return parseInt(match[1]) * 3600 + parseInt(match[2]) * 60 + parseInt(match[3]) + parseInt(match[4]) / 100;
        }
        return 0;
    }

    function formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }

    function highlightDictionaryWords(text) {
        if (typeof fullDictionary === 'undefined') return text;
        
        let result = text;
        Object.entries(fullDictionary).forEach(([key, value]) => {
            const regex = new RegExp(`(${key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            result = result.replace(regex, '<span class="highlight-word">$1</span>');
        });
        return result;
    }

    function renderSubtitleList() {
        if (parsedSubtitles.length === 0) {
            subtitleList.innerHTML = `
                <div class="empty-state text-center text-muted py-4">
                    <i class="fas fa-closed-captioning fa-2x mb-2"></i>
                    <p>Upload subtitle to see list</p>
                </div>
            `;
            return;
        }

        subtitleList.innerHTML = parsedSubtitles.map((sub, index) => `
            <div class="subtitle-item" data-index="${index}">
                <div class="time">
                    <i class="fas fa-clock me-1"></i>${formatTime(sub.startTime)} - ${formatTime(sub.endTime)}
                </div>
                <div class="text">${highlightDictionaryWords(sub.text)}</div>
            </div>
        `).join('');

        $('.subtitle-item').on('click', function() {
            const index = parseInt($(this).data('index'));
            goToSubtitle(index);
        });
    }

    function goToSubtitle(index) {
        if (index >= 0 && index < parsedSubtitles.length) {
            video.currentTime = parsedSubtitles[index].startTime;
            video.play();
            updateCurrentSubtitle(index);
        }
    }

    function updateCurrentSubtitle(index) {
        currentSubtitleIndex = index;
        
        $('.subtitle-item').removeClass('active');
        $(`.subtitle-item[data-index="${index}"]`).addClass('active');
        
        if ($('#autoScroll').is(':checked')) {
            const activeItem = $(`.subtitle-item[data-index="${index}"]`);
            if (activeItem.length) {
                subtitleList.scrollTo(activeItem, {
                    offset: -100,
                    duration: 300
                });
            }
        }
        
        if (parsedSubtitles[index]) {
            previewBox.innerHTML = `<div class="subtitle-preview-text">${highlightDictionaryWords(parsedSubtitles[index].text)}</div>`;
        }
    }

    function updateSubtitle() {
        const currentTime = video.currentTime;
        let activeSubtitle = null;
        let activeIndex = -1;
        
        for (let i = 0; i < parsedSubtitles.length; i++) {
            if (currentTime >= parsedSubtitles[i].startTime && currentTime <= parsedSubtitles[i].endTime) {
                activeSubtitle = parsedSubtitles[i];
                activeIndex = i;
                break;
            }
        }
        
        if (activeSubtitle && activeIndex !== currentSubtitleIndex) {
            subtitleOverlay.innerHTML = `<div class="subtitle-text">${highlightDictionaryWords(activeSubtitle.text)}</div>`;
            updateCurrentSubtitle(activeIndex);
        } else if (!activeSubtitle) {
            subtitleOverlay.innerHTML = '';
            currentSubtitleIndex = -1;
            $('.subtitle-item').removeClass('active');
        }
    }

    $('#videoFile').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const url = URL.createObjectURL(file);
            video.src = url;
            video.load();
            uploadPrompt.style.display = 'none';
            video.classList.add('loaded');
            showToast('Video loaded successfully!', 'success');
        }
    });

    $('#subtitleFile').on('change', async function(e) {
        const file = e.target.files[0];
        if (file) {
            const content = await file.text();
            const extension = file.name.split('.').pop().toLowerCase();
            
            if (extension === 'srt') {
                parsedSubtitles = parseSRT(content);
            } else if (extension === 'ass') {
                parsedSubtitles = parseASS(content);
            }
            
            renderSubtitleList();
            showToast(`${parsedSubtitles.length} subtitles loaded!`, 'success');
        }
    });

    video.addEventListener('timeupdate', updateSubtitle);
    
    video.addEventListener('loadedmetadata', function() {
        showToast('Video ready to play!', 'info');
    });

    $('#prevSubtitle').on('click', function() {
        const newIndex = currentSubtitleIndex > 0 ? currentSubtitleIndex - 1 : 0;
        goToSubtitle(newIndex);
    });

    $('#nextSubtitle').on('click', function() {
        const newIndex = currentSubtitleIndex < parsedSubtitles.length - 1 ? currentSubtitleIndex + 1 : parsedSubtitles.length - 1;
        goToSubtitle(newIndex);
    });

    $(document).on('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            $('#prevSubtitle').click();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            $('#nextSubtitle').click();
        } else if (e.key === ' ') {
            e.preventDefault();
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        }
    });
});
