class SingleEditor {
  constructor() {
    this.videoPlayer = null;
    this.isPlaying = false;
    this.currentTime = 0;
    this.duration = 0;
    this.selectedSubtitle = null;
    this.selectedRows = new Set();
    this.lastAnchor = null;
    this.undoStack = [];
    this.redoStack = [];
    this.MAX_UNDO = 50;
    this.mergeIndices = [];
    this.isVideoLarge = false;

    window.subtitleData = window.subtitleData || [];
    this._exposeGlobals();
  }

  init() {
    this.videoPlayer = $('#videoPlayer')[0];
    this._setupVideoEvents();
    this._setupUploadEvents();
    this._setupUIEvents();
    this._setupKeyboard();
  }

  // ===== Video =====
  _setupVideoEvents() {
    if (!this.videoPlayer) return;
    this.videoPlayer.addEventListener('loadedmetadata', () => {
      this.duration = this.videoPlayer.duration;
      this._updateTimeDisplay();
      this._renderTimelineBlocks();
    });
    this.videoPlayer.addEventListener('timeupdate', () => {
      this.currentTime = this.videoPlayer.currentTime;
      this._updateTimeDisplay();
      this._updateSlider();
      this._highlightCurrentSubtitle();
      this._updateVideoSubtitleOverlay();
    });
    this.videoPlayer.addEventListener('play', () => { this.isPlaying = true; this._updatePlayButton(); });
    this.videoPlayer.addEventListener('pause', () => { this.isPlaying = false; this._updatePlayButton(); });
  }

  _updateVideoSubtitleOverlay() {
    const overlay = document.getElementById('videoSubtitleOverlay');
    if (!overlay || !window.subtitleData) return;
    const ct = this.videoPlayer ? this.videoPlayer.currentTime : 0;
    let active = null;
    for (let i = 0; i < window.subtitleData.length; i++) {
      const sub = window.subtitleData[i];
      const start = EditorCore.parseTimestamp(sub.start);
      const end = EditorCore.parseTimestamp(sub.end);
      if (ct >= start && ct <= end) { active = EditorCore.assToHtml(sub.text).trim(); break; }
    }
    if (active) { overlay.innerHTML = '<div class="subtitle-display">' + active + '</div>'; overlay.style.display = 'block'; }
    else { overlay.innerHTML = ''; overlay.style.display = 'none'; }
  }

  // ===== Upload =====
  _setupUploadEvents() {
    const zone = $('#videoUploadZone');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => zone.on(evt, e => { e.preventDefault(); e.stopPropagation(); }));
    zone.on('dragenter dragover', function() { $(this).addClass('drag-over'); });
    zone.on('dragleave', function() { $(this).removeClass('drag-over'); });
    zone.on('drop', e => {
      $(e.target).removeClass('drag-over');
      const file = e.originalEvent.dataTransfer.files[0];
      if (file && file.type.startsWith('video/')) this._loadVideoFile(file);
    });
    $('#videoFileInput, #videoInput').on('change', e => {
      const file = e.target.files[0];
      if (file) this._loadVideoFile(file);
    });
    $('#videoWrapper').on('dragover', e => { e.preventDefault(); $(this).addClass('drag-over'); });
    $('#videoWrapper').on('dragleave', () => { $('#videoWrapper').removeClass('drag-over'); });
    $('#videoWrapper').on('drop', e => {
      e.preventDefault();
      $('#videoWrapper').removeClass('drag-over');
      const file = e.originalEvent.dataTransfer.files[0];
      if (file && file.type.startsWith('video/')) this._loadVideoFile(file);
    });
  }

  _loadVideoFile(file) {
    const url = URL.createObjectURL(file);
    this.videoPlayer.src = url;
    $('#videoPlayer').css('display', 'block');
    $('.video-placeholder').hide();
    this.videoPlayer.onloadedmetadata = () => this._generateSubtitleTrack();
    this.videoPlayer.onplay = () => this._enableSubtitles();
    EditorCore.showToast('Video loaded successfully', 'success');
  }

  _generateSubtitleTrack() {
    console.log('Subtitles will be displayed via overlay');
    EditorCore.showToast('Subtitles loaded to video', 'success');
  }

  _enableSubtitles() {
    if (!this.videoPlayer || !window.subtitleData) return;
    if (this.videoPlayer.textTracks.length > 0) {
      this.videoPlayer.textTracks[0].mode = 'showing';
      const track = this.videoPlayer.textTracks[0];
      while (track.cues && track.cues.length > 0) track.removeCue(track.cues[0]);
      window.subtitleData.forEach((sub) => {
        const start = EditorCore.parseTimestamp(sub.start);
        const end = EditorCore.parseTimestamp(sub.end);
        const cleanText = EditorCore.assToHtml(sub.text).trim();
        track.addCue(new VTTCue(start, end, cleanText));
      });
    }
    if (this.videoPlayer.currentTime > 0) this._updateVideoSubtitleOverlay();
  }

  // ===== Undo/Redo =====
  saveUndoState() {
    if (!window.subtitleData) return;
    this.undoStack.push(JSON.parse(JSON.stringify(window.subtitleData)));
    if (this.undoStack.length > this.MAX_UNDO) this.undoStack.shift();
    this.redoStack.length = 0;
  }

  undo() {
    if (this.undoStack.length === 0) return;
    this.redoStack.push(JSON.parse(JSON.stringify(window.subtitleData)));
    this._restoreState(this.undoStack.pop(), 'Undo');
  }

  redo() {
    if (this.redoStack.length === 0) return;
    this.undoStack.push(JSON.parse(JSON.stringify(window.subtitleData)));
    this._restoreState(this.redoStack.pop(), 'Redo');
  }

  _restoreState(state, label) {
    window.subtitleData = state;
    $.post(window.location.href, { restore_subtitles: JSON.stringify(state) }, resp => {
      if (resp && resp.success) { this.refreshSubtitleList(); EditorCore.showToast(label + ' successful', 'success'); }
      else { EditorCore.showToast(label + ' failed', 'error'); }
    }, 'json').fail(() => EditorCore.showToast(label + ' failed - server error', 'error'));
  }

  // ===== Display =====
  _updateTimeDisplay() {
    $('#currentTime').text(EditorCore.formatTime(this.currentTime));
    $('#totalTime').text(EditorCore.formatTime(this.duration));
  }

  _updateSlider() {
    if (this.duration > 0) {
      const p = (this.currentTime / this.duration) * 100;
      $('#timelineSlider').val(p);
      $('#timelineProgress').css('width', p + '%');
    }
  }

  _renderTimelineBlocks() {
    const container = $('#subtitleBlocks').empty();
    if (!this.duration || !window.subtitleData) return;
    window.subtitleData.forEach((sub, i) => {
      const start = EditorCore.parseTimestamp(sub.start);
      const end = EditorCore.parseTimestamp(sub.end);
      const left = (start / this.duration) * 100;
      const width = Math.max((end - start) / this.duration * 100, 0.5);
      const block = $('<div class="subtitle-block"></div>').css({ left: left + '%', width: width + '%' });
      block.on('click', () => { this.selectSubtitle(i); if (this.videoPlayer) this.videoPlayer.currentTime = start; });
      container.append(block);
    });
  }

  selectSubtitle(index) {
    this.selectedSubtitle = index;
    $('.subtitle-row').removeClass('active selected');
    $(`.subtitle-row[data-index="${index}"]`).addClass('active selected');
    $('.subtitle-block').removeClass('active');
    $($('.subtitle-block')[index]).addClass('active');
    const row = $(`.subtitle-row[data-index="${index}"]`);
    if (row.length) row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  prevSubtitle() {
    if (this.selectedSubtitle !== null && this.selectedSubtitle > 0) {
      this.selectSubtitle(this.selectedSubtitle - 1);
    }
  }

  nextSubtitle() {
    if (this.selectedSubtitle !== null && this.selectedSubtitle < window.subtitleData.length - 1) {
      this.selectSubtitle(this.selectedSubtitle + 1);
    }
  }

  _highlightCurrentSubtitle() {
    if (!this.videoPlayer || !this.duration || !window.subtitleData) return;
    for (let i = 0; i < window.subtitleData.length; i++) {
      const start = EditorCore.parseTimestamp(window.subtitleData[i].start);
      const end = EditorCore.parseTimestamp(window.subtitleData[i].end);
      if (this.currentTime >= start && this.currentTime <= end) {
        if (i !== this.selectedSubtitle) {
          $('.subtitle-row.active').removeClass('active');
          $(`.subtitle-row[data-index="${i}"]`).addClass('active');
        }
        break;
      }
    }
  }

  filterSubtitles(query) {
    const q = query.toLowerCase();
    $('.subtitle-row').each(function() {
      const orig = $(this).find('.subtitle-original').text().toLowerCase();
      const mod = $(this).find('.subtitle-modified').text().toLowerCase();
      $(this).toggle(orig.includes(q) || mod.includes(q));
    });
  }

  togglePlay() {
    if (!this.videoPlayer) return;
    this.isPlaying ? this.videoPlayer.pause() : this.videoPlayer.play();
  }

  _updatePlayButton() {
    $('#playBtn i').attr('class', this.isPlaying ? 'fas fa-pause' : 'fas fa-play');
  }

  skipBackward() {
    if (this.videoPlayer) this.videoPlayer.currentTime = Math.max(0, this.currentTime - 5);
  }

  skipForward() {
    if (this.videoPlayer) this.videoPlayer.currentTime = Math.min(this.duration, this.currentTime + 5);
  }

  // ===== Timeline =====
  goToSubtitle(index) {
    if (!window.subtitleData) { EditorCore.showToast('No subtitle data', 'error'); return; }
    if (index < 0 || index >= window.subtitleData.length) { EditorCore.showToast('Invalid line number: ' + index, 'error'); return; }

    const sub = window.subtitleData[index];
    const time = EditorCore.parseTimestamp(sub.start);

    $('.modal-overlay.active').removeClass('active');
    document.querySelectorAll('.modal').forEach(el => {
      const modal = bootstrap.Modal.getInstance(el);
      if (modal) modal.hide();
    });
    setTimeout(() => { $('.modal-backdrop').remove(); document.body.classList.remove('modal-open'); }, 100);

    this.selectSubtitle(index);
    setTimeout(() => {
      const row = $(`.subtitle-row[data-index="${index}"]`);
      if (row.length) row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
    setTimeout(() => {
      if (this.videoPlayer && this.videoPlayer.readyState >= 1) {
        this.videoPlayer.currentTime = time;
        this.videoPlayer.play().then(() => EditorCore.showToast('Playing line ' + (index + 1), 'success'))
          .catch(() => EditorCore.showToast('Loaded at line ' + (index + 1) + ' - click play', 'warning'));
      } else if (this.videoPlayer) {
        this.videoPlayer.currentTime = time;
        EditorCore.showToast('Video at line ' + (index + 1) + ' - click play', 'warning');
      } else {
        EditorCore.showToast('Line ' + (index + 1) + ' selected', 'success');
      }
    }, 200);
  }

  // ===== Refresh =====
  refreshSubtitleList() {
    this.clearSelection();
    $.get(window.location.href.split('?')[0] + '?t=' + Date.now(), html => {
      const $html = $(html);
      const newList = $html.find('#subtitleList').html();
      if (newList) $('#subtitleList').html(newList);
      const newCount = $html.find('#subtitleCount').text();
      if (newCount) $('#subtitleCount').text(newCount);
      const dataEl = $html.find('#subtitleDataStore');
      if (dataEl.length) {
        const raw = dataEl.val();
        if (raw) try { window.subtitleData = JSON.parse(raw); } catch (e) { console.warn('Failed to parse subtitle data', e); }
      }
      this._renderTimelineBlocks();
    });
  }

  // ===== Selection =====
  clearSelection() {
    this.selectedRows.clear();
    $('.subtitle-row').removeClass('multi-selected');
    this.lastAnchor = null;
    this._updateBatchToolbar();
  }

  _updateBatchToolbar() {
    const count = this.selectedRows.size;
    const $bar = $('#batchToolbar');
    if (count > 1) { $('#batchCount').text(count); $bar.addClass('show'); }
    else { $bar.removeClass('show'); }
  }

  _applyBatchFormat(cmd) {
    this.saveUndoState();
    const indices = Array.from(this.selectedRows);
    let updated = 0;
    indices.forEach(idx => {
      const original = $(`.subtitle-original[data-index="${idx}"]`);
      const modified = $(`.subtitle-modified[data-index="${idx}"]`);
      const text = original.text();
      const tagMap = { bold: ['b', '{\\b1}', '{\\b0}'], italic: ['i', '{\\i1}', '{\\i0}'], underline: ['u', '{\\u1}', '{\\u0}'], strike: ['s', '{\\s1}', '{\\s0}'] };
      const [tag, open, close] = tagMap[cmd] || [];
      if (!tag) return;
      let newText = text.indexOf(open) !== -1
        ? text.replace(new RegExp(open + '(.*?)' + close, 'g'), '$1').replace(new RegExp(open + '|' + close, 'g'), '')
        : open + text + close;
      if (newText !== text) {
        $.post('update-subtitle.php', { index: idx, text: newText }, response => modified.html(response));
        original.text(newText);
        if (window.subtitleData && window.subtitleData[idx]) window.subtitleData[idx].text = newText;
        updated++;
      }
    });
    if (updated > 0) EditorCore.showToast('Formatted ' + updated + ' line(s)', 'success');
    this.selectedRows.clear();
    $('.subtitle-row').removeClass('multi-selected');
    this._updateBatchToolbar();
  }

  _deleteSubtitle(index) {
    EditorCore.showConfirm('Delete subtitle #' + (parseInt(index) + 1) + '?', 'Are you sure you want to delete this subtitle? This action cannot be undone.', () => {
      this.saveUndoState();
      $.post('delete-subtitle.php', { index }, response => {
        if (response.success) { EditorCore.showToast('Deleted ' + response.deleted + ' subtitle(s)', 'success'); this.refreshSubtitleList(); }
        else { EditorCore.showToast('Delete failed', 'error'); }
      }, 'json').fail(() => EditorCore.showToast('Delete failed - server error', 'error'));
    });
  }

  _deleteSelectedSubtitles() {
    const count = this.selectedRows.size;
    if (count === 0) return;
    EditorCore.showConfirm('Delete ' + count + ' subtitles?', 'Are you sure you want to delete ' + count + ' selected subtitle(s)? This action cannot be undone.', () => {
      this.saveUndoState();
      $.post('delete-subtitle.php', { indices: JSON.stringify(Array.from(this.selectedRows)) }, response => {
        if (response.success) { EditorCore.showToast('Deleted ' + response.deleted + ' subtitle(s)', 'success'); this.refreshSubtitleList(); }
        else { EditorCore.showToast('Delete failed', 'error'); }
      }, 'json').fail(() => EditorCore.showToast('Delete failed - server error', 'error'));
    });
  }

  _mergeSelectedSubtitles() {
    const count = this.selectedRows.size;
    if (count < 2) { EditorCore.showToast('Select at least 2 subtitles to merge', 'warning'); return; }
    this.mergeIndices = Array.from(this.selectedRows).sort((a, b) => a - b);
    this._showMergeModal();
  }

  _showMergeModal() {
    const container = $('#mergeRowsList').empty();
    const first = this.mergeIndices[0];
    const last = this.mergeIndices[this.mergeIndices.length - 1];
    const firstSub = window.subtitleData[first];
    const lastSub = window.subtitleData[last];
    if (firstSub && lastSub) {
      $('#mergeSummary span').html('Select which subtitle text to <strong>keep</strong>. Start: <code class="merge-time start">' + EditorCore.escapeHtml(firstSub.start) + '</code> → End: <code class="merge-time end">' + EditorCore.escapeHtml(lastSub.end) + '</code>');
    }
    let html = '<div class="list-group">';
    this.mergeIndices.forEach(idx => {
      const sub = window.subtitleData[idx];
      if (!sub) return;
      const preview = sub.text.replace(/<[^>]*>/g, '').replace(/\n/g, ' ').substring(0, 100) + (sub.text.length > 100 ? '...' : '');
      html += '<label class="list-group-item merge-option"><div class="d-flex align-items-start gap-2"><input type="radio" name="mergeKeep" value="' + idx + '"><div class="flex-grow-1"><div class="d-flex align-items-center gap-2 flex-wrap"><span class="merge-line-badge">#' + (idx + 1) + '</span><span class="merge-time start">' + EditorCore.escapeHtml(sub.start) + '</span><span class="merge-time end">' + EditorCore.escapeHtml(sub.end) + '</span><span class="small" style="color:#6b7280">| ' + sub.text.length + ' chars</span></div><div class="merge-text-preview">' + EditorCore.escapeHtml(preview) + '</div></div></div></label>';
    });
    html += '</div>';
    container.html(html);
    container.find('input[type="radio"]').first().prop('checked', true);
    $('#mergeModal').addClass('active');
  }

  _doMerge() {
    this.saveUndoState();
    const keepIndex = parseInt($('#mergeRowsList input[name="mergeKeep"]:checked').val());
    if (isNaN(keepIndex)) { EditorCore.showToast('Select which text to keep', 'warning'); return; }
    $('#mergeModal').removeClass('active');
    $.post('merge-subtitle.php', { indices: JSON.stringify(this.mergeIndices), keep_index: keepIndex }, response => {
      if (response.success) { EditorCore.showToast('Merged ' + response.merged + ' subtitles into 1', 'success'); this.refreshSubtitleList(); }
      else { EditorCore.showToast('Merge failed', 'error'); }
    }, 'json').fail(() => EditorCore.showToast('Merge failed - server error', 'error'));
  }

  // ===== Inline Editing =====
  editSubtitle(index) {
    this.saveUndoState();
    const original = $(`.subtitle-original[data-index="${index}"]`);
    const modified = $(`.subtitle-modified[data-index="${index}"]`);
    const currentRaw = window.subtitleData?.[index]?.text ?? modified.text();
    const currentHtml = EditorCore.assToHtml(currentRaw);
    const row = $(`.subtitle-row[data-index="${index}"]`);
    if (row.hasClass('editing')) return;

    const toolbar = $(`<div class="edit-toolbar" data-index="${index}">
      <button type="button" class="et-btn et-bold" data-cmd="bold" title="Bold (Ctrl+B)"><b>B</b></button>
      <button type="button" class="et-btn et-italic" data-cmd="italic" title="Italic (Ctrl+I)"><i>I</i></button>
      <button type="button" class="et-btn et-underline" data-cmd="underline" title="Underline (Ctrl+U)"><u>U</u></button>
      <button type="button" class="et-btn et-strike" data-cmd="strikeThrough" title="Strike-through"><s>S</s></button>
      <span class="et-sep"></span>
      <button type="button" class="et-btn et-done" title="Save (Ctrl+Enter)">&#10003;</button>
      <button type="button" class="et-btn et-cancel" title="Cancel (Esc)">&#10007;</button>
    </div>`);
    const editor = $(`<div class="edit-editor" contenteditable="true" data-index="${index}">${currentHtml}</div>`);

    row.addClass('editing');
    row.find('.subtitle-text-container, .subtitle-actions, .subtitle-index, .subtitle-times').hide();
    row.find('.subtitle-text-container').after(toolbar);
    toolbar.after(editor);
    editor.css('grid-column', '1 / -1').focus();
    const sel = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(editor[0]);
    range.collapse(false);
    sel.removeAllRanges();
    sel.addRange(range);

    const finishEdit = () => {
      const newHtml = editor.html().trim();
      const newText = EditorCore.htmlToAss(newHtml);
      this._cleanupEdit(row, toolbar, editor);
      if (newText !== currentRaw) {
        $.post('update-subtitle.php', { index, text: newText }, response => {
          modified.html(response);
          if (window.subtitleData && window.subtitleData[index]) window.subtitleData[index].text = newText;
          EditorCore.showToast('Subtitle updated', 'success');
        });
      }
    };
    const cancelEdit = () => this._cleanupEdit(row, toolbar, editor);

    toolbar.on('click', '.et-btn[data-cmd]', function() {
      editor.focus();
      document.execCommand($(this).data('cmd'), false, null);
    });
    toolbar.on('click', '.et-done', finishEdit);
    toolbar.on('click', '.et-cancel', cancelEdit);
    editor.on('keydown', e => {
      if (e.key === 'Enter' && e.ctrlKey) { e.preventDefault(); finishEdit(); }
      if (e.key === 'Escape') { e.preventDefault(); cancelEdit(); }
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); document.execCommand('insertLineBreak', false, null); }
    });
  }

  _cleanupEdit(row, toolbar, editor) {
    toolbar.remove();
    editor.remove();
    row.removeClass('editing');
    row.find('.subtitle-text-container, .subtitle-actions, .subtitle-index, .subtitle-times').show();
  }

  // ===== Statistics =====
  showStatistics() {
    const subs = window.subtitleData;
    if (!subs || subs.length === 0) { EditorCore.showToast('No subtitle data', 'warning'); return; }
    let totalChars = 0, totalWords = 0, totalDuration = 0;
    const wordFreq = {};
    subs.forEach(s => {
      const text = s.text.replace(/<[^>]*>/g, '').replace(/\{[^}]*\}/g, '').trim();
      const words = text.match(/[\p{L}]+/gu) || [];
      totalChars += text.length;
      totalWords += words.length;
      words.forEach(w => { const k = w.toLowerCase(); wordFreq[k] = (wordFreq[k] || 0) + 1; });
      totalDuration += EditorCore.parseTimestamp(s.end) - EditorCore.parseTimestamp(s.start);
    });
    $('#statSubs').text(subs.length);
    $('#statDuration').text(Math.floor(totalDuration / 60) + 'm ' + Math.floor(totalDuration % 60) + 's');
    $('#statWords').text(totalWords);
    $('#statChars').text(totalChars);
    $('#statCPS').text((totalDuration > 0 ? totalChars / totalDuration : 0).toFixed(1));
    $('#statCPL').text((totalChars / subs.length).toFixed(1));
    $('#statWPL').text((totalWords / subs.length).toFixed(1));
    const sorted = Object.entries(wordFreq).sort((a, b) => b[1] - a[1]).slice(0, 10);
    const container = $('#statsTopWords').empty();
    sorted.forEach(e => container.append('<span class="tag"><span class="count">' + e[1] + 'x</span> ' + $('<span>').text(e[0]).html() + '</span>'));
    $('#statUnknown').text('...');
    $.get('includes/get-words.php', data => $('#statUnknown').text(data && Array.isArray(data) ? data.length : 0)).fail(() => $('#statUnknown').text('N/A'));
    $('#statisticsModal').addClass('active');
  }

  showTiming() {
    const subs = window.subtitleData;
    if (!subs || subs.length === 0) { EditorCore.showToast('No subtitle data', 'warning'); return; }
    let totalSec = 0;
    subs.forEach(s => { const end = EditorCore.parseTimestamp(s.end); if (end > totalSec) totalSec = end; });
    $('#timingCurrentDur').text(EditorCore.secondsToDuration(totalSec));
    $('#timingModal').addClass('active');
  }

  applyTimingShift() {
    const offsetMs = parseInt($('#timingOffset').val());
    if (isNaN(offsetMs) || offsetMs === 0) { EditorCore.showToast('Enter a valid offset (non-zero)', 'warning'); return; }
    this.saveUndoState();
    $.post(window.location.href, { shift_timing: offsetMs }, resp => {
      if (resp && resp.success) { window.subtitleData = resp.subtitles; this.refreshSubtitleList(); EditorCore.showToast('Timing shifted by ' + offsetMs + 'ms', 'success'); }
      else { EditorCore.showToast('Timing shift failed', 'error'); }
    }, 'json').fail(() => EditorCore.showToast('Timing shift failed - server error', 'error'));
  }

  applyTimingScale() {
    const input = $('#timingVideoDuration').val().trim();
    if (!input) { EditorCore.showToast('Enter target video duration', 'warning'); return; }
    const parts = input.split(':');
    if (parts.length !== 3) { EditorCore.showToast('Use format HH:MM:SS', 'warning'); return; }
    const targetSec = parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseFloat(parts[2]);
    if (isNaN(targetSec) || targetSec <= 0) { EditorCore.showToast('Invalid duration', 'warning'); return; }
    let currentDur = 0;
    window.subtitleData.forEach(s => { const end = EditorCore.parseTimestamp(s.end); if (end > currentDur) currentDur = end; });
    if (currentDur <= 0) { EditorCore.showToast('Cannot determine current duration', 'warning'); return; }
    this.saveUndoState();
    $.post(window.location.href, { scale_timing: targetSec / currentDur }, resp => {
      if (resp && resp.success) { window.subtitleData = resp.subtitles; this.refreshSubtitleList(); EditorCore.showToast('Timing scaled to ' + input, 'success'); }
      else { EditorCore.showToast('Scale failed', 'error'); }
    }, 'json').fail(() => EditorCore.showToast('Scale failed - server error', 'error'));
  }

  // ===== Unknown Words / Dictionary Changes =====
  showUnknownWords() {
    $.get('includes/get-words.php', data => {
      let html = '';
      if (Array.isArray(data) && data.length > 0) {
        html += '<div class="unknown-words-list">';
        for (let i = 0; i < data.length; i += 3) {
          html += '<div class="uw-row">';
          for (let j = i; j < i + 3 && j < data.length; j++) {
            const item = data[j];
            const lineIndex = parseInt(item.line) - 1;
            html += `<div class="unknown-word-item" onclick="goToSubtitle(${lineIndex})">
              <div class="uw-info"><span class="uw-line"><i class="fas fa-hashtag"></i> Line ${item.line}</span><span class="uw-word"><i class="fas fa-spell-check"></i> ${item.word}</span></div>
              <button class="uw-play-btn" onclick="event.stopPropagation(); goToSubtitle(${lineIndex})" title="Play from this line"><i class="fas fa-play"></i></button>
            </div>`;
          }
          html += '</div>';
        }
        html += '</div>';
      } else {
        html = '<div class="uw-empty"><i class="fas fa-check-circle"></i><p>No unknown words found</p><small>All words are recognized in the Indonesian dictionary</small></div>';
      }
      $('#unknownWordsList').html(html);
      $('#unknownWordsModal').addClass('active');
    }).fail(() => {
      $('#unknownWordsList').html('<div class="uw-empty text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load data</p></div>');
      $('#unknownWordsModal').addClass('active');
    });
  }

  showDictionaryChanges() {
    $.get('includes/get-dictionary-changes.php', data => {
      let html = '';
      if (Array.isArray(data) && data.length > 0) {
        html += '<div class="unknown-words-list">';
        for (let i = 0; i < data.length; i += 3) {
          html += '<div class="uw-row">';
          for (let j = i; j < i + 3 && j < data.length; j++) {
            const item = data[j];
            const lineIndex = item.line - 1;
            html += `<div class="unknown-word-item" onclick="goToSubtitle(${lineIndex})">
              <div class="uw-info"><span class="uw-line"><i class="fas fa-hashtag"></i> Line ${item.line}</span>
              <span class="uw-word dc-original">${item.original}</span><span class="dc-arrow"><i class="fas fa-arrow-right"></i></span>
              <span class="uw-word dc-replacement">${item.replacement}</span></div>
              <button class="uw-play-btn" onclick="event.stopPropagation(); goToSubtitle(${lineIndex})" title="Go to line"><i class="fas fa-arrow-right"></i></button>
            </div>`;
          }
          html += '</div>';
        }
        html += '</div>';
      } else {
        html = '<div class="uw-empty"><i class="fas fa-check-circle"></i><p>No dictionary changes</p><small>No words have been replaced by the dictionary</small></div>';
      }
      $('#dictionaryChangesList').html(html);
      $('#dictionaryChangesModal').addClass('active');
    }).fail(() => {
      $('#dictionaryChangesList').html('<div class="uw-empty text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load data</p></div>');
      $('#dictionaryChangesModal').addClass('active');
    });
  }

  // ===== Dictionary =====
  addDictionary() {
    const key = $('#dictKey').val().trim();
    const value = $('#dictValue').val().trim();
    if (!key || !value) { EditorCore.showToast('Please enter both words', 'warning'); return; }
    $.post('display.php', { add_to_dictionary: true, key, value }, () => location.reload());
  }

  removeDictionary(key) {
    $.post('display.php', { remove_from_dictionary: key }, () => location.reload());
  }

  // ===== Download =====
  downloadSubtitle(format) {
    format = format || $('#exportFormat').val();
    const subtitleType = $('#subtitleType').val();
    const form = $('<form method="post">');
    form.append('<input name="download">', $('<input name="format">').val(format), $('<input name="subtitle_type">').val(subtitleType));
    $('body').append(form);
    form.submit();
    form.remove();
  }

  // ===== Session =====
  clearSession() {
    EditorCore.showConfirm('Start new file?', 'All current changes will be lost. Are you sure?', () => {
      $.post('display.php', { clear_session: true }, () => { window.location.href = 'index.php'; });
    });
  }

  // ===== UI Events =====
  _setupUIEvents() {
    // Timeline slider
    $('#timelineSlider').on('input', function() {
      if (!this.videoPlayer || !this.duration) return;
      const time = (this.value / 100) * this.duration;
      this.videoPlayer.currentTime = time;
      this.currentTime = time;
    }.bind(this));

    // Search
    $('#subtitleSearch').on('input', e => this.filterSubtitles($(e.target).val()));

    // Row clicks
    $(document).on('click', '.subtitle-row', e => this._handleRowClick(e));

    // Play from
    $(document).on('click', '.play-from-btn', e => {
      e.stopPropagation();
      const time = EditorCore.parseTimestamp($(e.target).closest('.play-from-btn').data('time'));
      if (this.videoPlayer) { this.videoPlayer.currentTime = time; this.videoPlayer.play(); }
    });

    // Double click edit
    $(document).on('dblclick', '.subtitle-modified, .subtitle-original', e => {
      e.stopPropagation();
      this.editSubtitle($(e.target).closest('[data-index]').data('index'));
    });

    // Batch toolbar
    $('#batchToolbar').on('click', '.et-btn[data-cmd]', e => this._applyBatchFormat($(e.target).closest('.et-btn').data('cmd')));
    $('#batchClear').on('click', () => this.clearSelection());
    $('#batchDelete').on('click', () => this._deleteSelectedSubtitles());
    $('#batchMerge').on('click', () => this._mergeSelectedSubtitles());

    // Delete button
    $(document).on('click', '.delete-subtitle-btn', e => {
      e.stopPropagation();
      this._deleteSubtitle($(e.target).closest('.delete-subtitle-btn').data('index'));
    });

    // Merge modal
    $('#mergeModalClose, #mergeModalCancel').on('click', () => { $('#mergeModal').removeClass('active'); this.mergeIndices = []; });
    $('#mergeModalConfirm').on('click', () => this._doMerge());
  }

  _handleRowClick(e) {
    const index = $(e.currentTarget).data('index');
    if (e.shiftKey && (e.ctrlKey || e.metaKey)) {
      const activeRow = $('.subtitle-row.active');
      const activeIdx = activeRow.length ? activeRow.data('index') : null;
      const anchor = this.lastAnchor !== null ? this.lastAnchor : (activeIdx !== null ? activeIdx : index);
      const from = Math.min(anchor, index);
      const to = Math.max(anchor, index);
      this.selectedRows.clear();
      $('.subtitle-row').removeClass('multi-selected');
      for (let i = from; i <= to; i++) { this.selectedRows.add(i); $(`.subtitle-row[data-index="${i}"]`).addClass('multi-selected'); }
      this.lastAnchor = index;
      this._updateBatchToolbar();
    } else if (e.ctrlKey || e.metaKey) {
      const $row = $(e.currentTarget);
      $row.toggleClass('multi-selected');
      if ($row.hasClass('multi-selected')) {
        this.selectedRows.add(index);
        this.lastAnchor = index;
        if (this.selectedRows.size === 1) {
          $('.subtitle-row.active').each((_, el) => {
            const ai = $(el).data('index');
            if (ai !== undefined && !this.selectedRows.has(ai)) { this.selectedRows.add(ai); $(el).addClass('multi-selected'); }
          });
        }
      } else {
        this.selectedRows.delete(index);
      }
      this._updateBatchToolbar();
    } else {
      this.selectedRows.clear();
      $('.subtitle-row').removeClass('multi-selected');
      this.lastAnchor = null;
      this._updateBatchToolbar();
      this.selectSubtitle(index);
    }
  }

  // ===== Keyboard =====
  _setupKeyboard() {
    $(document).on('keydown', e => {
      if (['INPUT', 'TEXTAREA'].includes(e.target.tagName) || e.target.isContentEditable) return;
      switch (e.key) {
        case ' ': e.preventDefault(); this.togglePlay(); break;
        case 'ArrowLeft': if (this.videoPlayer) this.videoPlayer.currentTime = Math.max(0, this.currentTime - 5); break;
        case 'ArrowRight': if (this.videoPlayer) this.videoPlayer.currentTime = Math.min(this.duration, this.currentTime + 5); break;
        case 'ArrowUp': this.prevSubtitle(); break;
        case 'ArrowDown': this.nextSubtitle(); break;
        case 'Enter':
          if (this.selectedSubtitle !== null) {
            const sub = window.subtitleData[this.selectedSubtitle];
            if (sub && this.videoPlayer) { this.videoPlayer.currentTime = EditorCore.parseTimestamp(sub.start); this.videoPlayer.play(); }
          }
          break;
        case 's': if (e.ctrlKey) { e.preventDefault(); this.downloadSubtitle(); } break;
        case 'z': if (e.ctrlKey && !e.shiftKey) { e.preventDefault(); this.undo(); } break;
        case 'y': if (e.ctrlKey) { e.preventDefault(); this.redo(); } break;
      }
      if (e.ctrlKey && e.shiftKey && e.key === 'z') { e.preventDefault(); this.redo(); }
    });
  }

  // ===== Global exposure =====
  _exposeGlobals() {
    const instance = this;
    Object.assign(window, {
      SingleEditor: { instance },
      editSubtitle: i => instance.editSubtitle(i),
      addDictionary: () => instance.addDictionary(),
      removeDictionary: k => instance.removeDictionary(k),
      downloadSubtitle: f => instance.downloadSubtitle(f),
      togglePlay: () => instance.togglePlay(),
      toggleFullscreen: () => EditorCore.toggleFullscreen(),
      toggleSubtitlePosition: () => EditorCore.toggleSubtitlePosition(),
      togglePanel: h => EditorCore.togglePanel(h),
      toggleTheme: () => EditorCore.toggleTheme(),
      resizeVideoPanel: d => EditorCore.resizeVideoPanel(d),
      toggleDownloadMenu: e => EditorCore.toggleDownloadMenu(e),
      showShortcuts: () => $('#shortcutsModal').addClass('active'),
      showStatistics: () => instance.showStatistics(),
      showTiming: () => instance.showTiming(),
      applyTimingShift: () => instance.applyTimingShift(),
      applyTimingScale: () => instance.applyTimingScale(),
      showUnknownWords: () => instance.showUnknownWords(),
      showDictionaryChanges: () => instance.showDictionaryChanges(),
      goToSubtitle: i => instance.goToSubtitle(i),
      closeModal: id => EditorCore.closeModal(id),
      clearSession: () => instance.clearSession(),
      showConfirm: (t, m, c) => EditorCore.showConfirm(t, m, c),
      showToast: (m, t) => EditorCore.showToast(m, t),
      exportUnknownWords: () => EditorCore.exportUnknownWords(),
      selectSubtitle: i => instance.selectSubtitle(i),
      prevSubtitle: () => instance.prevSubtitle(),
      nextSubtitle: () => instance.nextSubtitle(),
      skipBackward: () => instance.skipBackward(),
      skipForward: () => instance.skipForward(),
      saveUndoState: () => instance.saveUndoState(),
      undo: () => instance.undo(),
      redo: () => instance.redo(),
      refreshSubtitleList: () => instance.refreshSubtitleList(),
      parseTimestamp: t => EditorCore.parseTimestamp(t),
      formatTimecode: s => EditorCore.formatTimecode(s),
      secondsToDuration: s => EditorCore.secondsToDuration(s),
      formatTime: s => EditorCore.formatTime(s),
      assToHtml: t => EditorCore.assToHtml(t),
      htmlToAss: h => EditorCore.htmlToAss(h),
      escapeHtml: t => EditorCore.escapeHtml(t),
      applyTheme: t => EditorCore.applyTheme(t),
    });
  }
}
