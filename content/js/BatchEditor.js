class BatchEditor {
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
    this.currentFileIndex = 0;

    window.batchFiles = window.batchFiles || [];
    this._exposeGlobals();
  }

  init() {
    this.videoPlayer = $('#videoPlayer')[0];
    this._setupVideoEvents();
    this._setupUploadEvents();
    this._setupUIEvents();
    this._setupKeyboard();

    if (window.batchFiles.length > 0) {
      this.renderCurrentFileSubtitles();
    }
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
    if (!overlay || !window.batchFiles) return;
    const ct = this.videoPlayer ? this.videoPlayer.currentTime : 0;
    const subs = window.batchFiles[this.currentFileIndex]?.subtitles;
    if (!subs) { overlay.style.display = 'none'; return; }
    let active = null;
    for (let i = 0; i < subs.length; i++) {
      const sub = subs[i];
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
    $('#videoWrapper').on('dragover', e => { e.preventDefault(); $('#videoWrapper').addClass('drag-over'); });
    $('#videoWrapper').on('dragleave', () => $('#videoWrapper').removeClass('drag-over'));
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
    this._generateSubtitleTrack(this.currentFileIndex);
    EditorCore.showToast('Video loaded successfully', 'success');
  }

  _generateSubtitleTrack(fileIndex) {
    console.log('Subtitles will be displayed via overlay');
    EditorCore.showToast('Subtitles loaded to video', 'success');
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
    if (!this.duration || !window.batchFiles[this.currentFileIndex]) return;
    const subs = window.batchFiles[this.currentFileIndex].subtitles;
    if (!subs) return;
    subs.forEach((sub, i) => {
      const start = EditorCore.parseTimestamp(sub.start);
      const end = EditorCore.parseTimestamp(sub.end);
      const left = (start / this.duration) * 100;
      const width = Math.max((end - start) / this.duration * 100, 0.5);
      const block = $('<div class="subtitle-block"></div>').css({ left: left + '%', width: width + '%' });
      block.on('click', () => { this.selectSubtitle(this.currentFileIndex, i); if (this.videoPlayer) this.videoPlayer.currentTime = start; });
      container.append(block);
    });
  }

  renderCurrentFileSubtitles() {
    $(`#subtitleList-${this.currentFileIndex} .subtitle-row`).show();
  }

  selectSubtitle(fileIndex, index) {
    this.selectedSubtitle = { file: fileIndex, index };
    $('.subtitle-row').removeClass('active selected');
    $(`.subtitle-row[data-file="${fileIndex}"][data-index="${index}"]`).addClass('active selected');
    const row = $(`.subtitle-row[data-file="${fileIndex}"][data-index="${index}"]`);
    if (row.length) row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  prevSubtitle() {
    if (!this.selectedSubtitle) return;
    if (this.selectedSubtitle.index > 0) {
      this.selectSubtitle(this.selectedSubtitle.file, this.selectedSubtitle.index - 1);
    }
  }

  nextSubtitle() {
    if (!this.selectedSubtitle) return;
    const max = window.batchFiles[this.selectedSubtitle.file].subtitles.length - 1;
    if (this.selectedSubtitle.index < max) {
      this.selectSubtitle(this.selectedSubtitle.file, this.selectedSubtitle.index + 1);
    }
  }

  _highlightCurrentSubtitle() {
    if (!this.videoPlayer || !this.duration || !window.batchFiles[this.currentFileIndex]) return;
    const subs = window.batchFiles[this.currentFileIndex].subtitles;
    for (let i = 0; i < subs.length; i++) {
      const start = EditorCore.parseTimestamp(subs[i].start);
      const end = EditorCore.parseTimestamp(subs[i].end);
      if (this.currentTime >= start && this.currentTime <= end) {
        $(`.subtitle-row[data-file="${this.currentFileIndex}"].active`).removeClass('active');
        $(`.subtitle-row[data-file="${this.currentFileIndex}"][data-index="${i}"]`).addClass('active');
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

  switchBatchFile(index) {
    this.currentFileIndex = index;
    document.querySelectorAll('.nav-pills .nav-link').forEach(b => b.classList.remove('active'));
    const tabBtn = document.getElementById('tab-' + index);
    if (tabBtn) tabBtn.classList.add('active');
    document.querySelectorAll('.tab-content .tab-pane').forEach(p => p.classList.remove('show', 'active'));
    const panel = document.getElementById('file-' + index);
    if (panel) panel.classList.add('show', 'active');
    this._renderTimelineBlocks();
    if (this.videoPlayer && this.videoPlayer.src) {
      this._generateSubtitleTrack(this.currentFileIndex);
      this._updateVideoSubtitleOverlay();
    }
  }

  // ===== Undo/Redo =====
  saveUndoState() {
    const subs = window.batchFiles?.[this.currentFileIndex]?.subtitles;
    if (!subs) return;
    this.undoStack.push(JSON.parse(JSON.stringify(subs)));
    if (this.undoStack.length > this.MAX_UNDO) this.undoStack.shift();
    this.redoStack.length = 0;
  }

  undo() {
    if (this.undoStack.length === 0) return;
    const current = window.batchFiles?.[this.currentFileIndex]?.subtitles;
    if (current) this.redoStack.push(JSON.parse(JSON.stringify(current)));
    this._restoreState(this.undoStack.pop(), 'Undo');
  }

  redo() {
    if (this.redoStack.length === 0) return;
    const current = window.batchFiles?.[this.currentFileIndex]?.subtitles;
    if (current) this.undoStack.push(JSON.parse(JSON.stringify(current)));
    this._restoreState(this.redoStack.pop(), 'Redo');
  }

  _restoreState(state, label) {
    if (!window.batchFiles?.[this.currentFileIndex]) return;
    window.batchFiles[this.currentFileIndex].subtitles = state;
    $.post(window.location.href, { restore_subtitles_batch: JSON.stringify(state), file_index: this.currentFileIndex }, resp => {
      if (resp && resp.success) { this.refreshBatchSubtitleList(); EditorCore.showToast(label + ' successful', 'success'); }
      else { EditorCore.showToast(label + ' failed', 'error'); }
    }, 'json').fail(() => EditorCore.showToast(label + ' failed - server error', 'error'));
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

  _deleteSubtitleBatch(fileIndex, index) {
    EditorCore.showConfirm('Delete subtitle #' + (parseInt(index) + 1) + '?', 'Are you sure?', () => {
      this.saveUndoState();
      $.post('delete-subtitle-batch.php', { file_index: fileIndex, index }, response => {
        if (response.success) { EditorCore.showToast('Deleted ' + response.deleted + ' subtitle(s)', 'success'); this.refreshBatchSubtitleList(); }
        else { EditorCore.showToast('Delete failed', 'error'); }
      }, 'json').fail(() => EditorCore.showToast('Delete failed - server error', 'error'));
    });
  }

  _deleteSelectedSubtitlesBatch() {
    const count = this.selectedRows.size;
    if (count === 0) return;
    const cf = this.currentFileIndex;
    const indices = Array.from(this.selectedRows).filter(i => i.file === cf).map(i => i.index);
    if (indices.length === 0) { EditorCore.showToast('No subtitles selected in current file', 'warning'); return; }
    EditorCore.showConfirm('Delete ' + indices.length + ' subtitles?', 'Are you sure?', () => {
      this.saveUndoState();
      $.post('delete-subtitle-batch.php', { file_index: cf, indices: JSON.stringify(indices) }, response => {
        if (response.success) { EditorCore.showToast('Deleted ' + response.deleted + ' subtitle(s)', 'success'); this.refreshBatchSubtitleList(); }
        else { EditorCore.showToast('Delete failed', 'error'); }
      }, 'json').fail(() => EditorCore.showToast('Delete failed - server error', 'error'));
    });
  }

  _mergeSelectedSubtitlesBatch() {
    const count = this.selectedRows.size;
    if (count < 2) { EditorCore.showToast('Select at least 2 subtitles to merge', 'warning'); return; }
    const cf = this.currentFileIndex;
    const indices = Array.from(this.selectedRows).filter(i => i.file === cf).map(i => i.index).sort((a, b) => a - b);
    if (indices.length < 2) { EditorCore.showToast('Select at least 2 subtitles in the current file', 'warning'); return; }
    this.mergeIndices = indices;
    this._showMergeModal();
  }

  _showMergeModal() {
    const container = $('#mergeRowsList').empty();
    const subs = window.batchFiles[this.currentFileIndex].subtitles;
    const first = this.mergeIndices[0];
    const last = this.mergeIndices[this.mergeIndices.length - 1];
    const firstSub = subs[first];
    const lastSub = subs[last];
    if (firstSub && lastSub) {
      $('#mergeSummary span').html('Select which subtitle text to <strong>keep</strong>. Start: <code class="merge-time start">' + EditorCore.escapeHtml(firstSub.start) + '</code> → End: <code class="merge-time end">' + EditorCore.escapeHtml(lastSub.end) + '</code>');
    }
    let html = '<div class="list-group">';
    this.mergeIndices.forEach(idx => {
      const sub = subs[idx];
      if (!sub) return;
      const preview = sub.text.replace(/<[^>]*>/g, '').replace(/\n/g, ' ').substring(0, 100) + (sub.text.length > 100 ? '...' : '');
      html += '<label class="list-group-item merge-option"><div class="d-flex align-items-start gap-2"><input type="radio" name="mergeKeep" value="' + idx + '"><div class="flex-grow-1"><div class="d-flex align-items-center gap-2 flex-wrap"><span class="merge-line-badge">#' + (idx + 1) + '</span><span class="merge-time start">' + EditorCore.escapeHtml(sub.start) + '</span><span class="merge-time end">' + EditorCore.escapeHtml(sub.end) + '</span><span class="small" style="color:#6b7280">| ' + sub.text.length + ' chars</span></div><div class="merge-text-preview">' + EditorCore.escapeHtml(preview) + '</div></div></div></label>';
    });
    html += '</div>';
    container.html(html);
    container.find('input[type="radio"]').first().prop('checked', true);
    $('#mergeModal').addClass('active');
  }

  _doMergeBatch() {
    this.saveUndoState();
    const keepIndex = parseInt($('#mergeRowsList input[name="mergeKeep"]:checked').val());
    if (isNaN(keepIndex)) { EditorCore.showToast('Select which text to keep', 'warning'); return; }
    $('#mergeModal').removeClass('active');
    $.post('merge-subtitle-batch.php', { file_index: this.currentFileIndex, indices: JSON.stringify(this.mergeIndices), keep_index: keepIndex }, response => {
      if (response.success) { EditorCore.showToast('Merged ' + response.merged + ' subtitles into 1', 'success'); this.refreshBatchSubtitleList(); }
      else { EditorCore.showToast('Merge failed', 'error'); }
    }, 'json').fail(() => EditorCore.showToast('Merge failed - server error', 'error'));
  }

  // ===== Refresh =====
  refreshBatchSubtitleList() {
    this.clearSelection();
    $.get(window.location.href.split('?')[0] + '?t=' + Date.now(), html => {
      const $html = $(html);
      const newList = $html.find('#subtitleList-' + this.currentFileIndex).html();
      if (newList) $('#subtitleList-' + this.currentFileIndex).html(newList);
      const dataEl = $html.find('#batchDataStore');
      if (dataEl.length) {
        const raw = dataEl.val();
        if (raw) try { window.batchFiles = JSON.parse(raw); } catch (e) { console.warn('Failed to parse batch data', e); }
      }
      this._renderTimelineBlocks();
    });
  }

  // ===== Inline Editing =====
  editSubtitle(fileIndex, index) {
    this.saveUndoState();
    const original = $(`.subtitle-original[data-file="${fileIndex}"][data-index="${index}"]`);
    const modified = $(`.subtitle-modified[data-file="${fileIndex}"][data-index="${index}"]`);
    const currentRaw = window.batchFiles?.[fileIndex]?.subtitles?.[index]?.text ?? modified.text();
    const currentHtml = EditorCore.assToHtml(currentRaw);
    const row = $(`.subtitle-row[data-file="${fileIndex}"][data-index="${index}"]`);
    if (row.hasClass('editing')) return;

    const toolbar = $(`<div class="edit-toolbar" data-file="${fileIndex}" data-index="${index}">
      <button type="button" class="et-btn et-bold" data-cmd="bold" title="Bold"><b>B</b></button>
      <button type="button" class="et-btn et-italic" data-cmd="italic" title="Italic"><i>I</i></button>
      <button type="button" class="et-btn et-underline" data-cmd="underline" title="Underline"><u>U</u></button>
      <button type="button" class="et-btn et-strike" data-cmd="strikeThrough" title="Strike-through"><s>S</s></button>
      <span class="et-sep"></span>
      <button type="button" class="et-btn et-done" title="Save (Ctrl+Enter)">&#10003;</button>
      <button type="button" class="et-btn et-cancel" title="Cancel (Esc)">&#10007;</button>
    </div>`);
    const editor = $(`<div class="edit-editor" contenteditable="true" data-file="${fileIndex}" data-index="${index}">${currentHtml}</div>`);

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
        $.post('update-subtitle-batch.php', { file_index: fileIndex, index, text: newText }, response => {
          if (response.success) { modified.html(response.html); original.text(newText); window.batchFiles[fileIndex].subtitles[index].text = newText; EditorCore.showToast('Subtitle updated', 'success'); }
          else { EditorCore.showToast('Update failed: ' + response.error, 'error'); }
        }, 'json').fail((jq, status) => EditorCore.showToast('Update failed: ' + status, 'error'));
      }
    };
    const cancelEdit = () => this._cleanupEdit(row, toolbar, editor);

    toolbar.on('click', '.et-btn[data-cmd]', function() { editor.focus(); document.execCommand($(this).data('cmd'), false, null); });
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

  // ===== Batch Format =====
  _applyBatchFormat(cmd) {
    this.saveUndoState();
    const cf = this.currentFileIndex;
    const indices = Array.from(this.selectedRows).filter(i => i.file === cf).map(i => i.index);
    let updated = 0;
    const tagMap = { bold: ['b', '{\\b1}', '{\\b0}'], italic: ['i', '{\\i1}', '{\\i0}'], underline: ['u', '{\\u1}', '{\\u0}'], strike: ['s', '{\\s1}', '{\\s0}'] };
    const [tag, open, close] = tagMap[cmd] || [];
    if (!tag) return;
    indices.forEach(index => {
      const original = $(`.subtitle-original[data-file="${cf}"][data-index="${index}"]`);
      const modified = $(`.subtitle-modified[data-file="${cf}"][data-index="${index}"]`);
      const text = original.text();
      let newText = text.indexOf(open) !== -1
        ? text.replace(new RegExp(open + '(.*?)' + close, 'g'), '$1').replace(new RegExp(open + '|' + close, 'g'), '')
        : open + text + close;
      if (newText !== text) {
        $.post('update-subtitle-batch.php', { file_index: cf, index, text: newText }, response => { if (response.success) { modified.html(response.html); original.text(newText); window.batchFiles[cf].subtitles[index].text = newText; } }, 'json');
        updated++;
      }
    });
    if (updated > 0) EditorCore.showToast('Formatted ' + updated + ' line(s)', 'success');
    this.clearSelection();
  }

  // ===== Statistics / Timing =====
  showStatistics() {
    const subs = window.batchFiles?.[this.currentFileIndex]?.subtitles;
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
    $.get('includes/get-words.php', { batch_mode: 1 }, data => {
      let count = 0;
      if (data) data.forEach(f => { if (f.words) count += f.words.length; });
      $('#statUnknown').text(count);
    }).fail(() => $('#statUnknown').text('N/A'));
    $('#statisticsModal').addClass('active');
  }

  showTiming() {
    const subs = window.batchFiles?.[this.currentFileIndex]?.subtitles;
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
    $.post(window.location.href, { shift_timing: offsetMs, file_index: this.currentFileIndex }, resp => {
      if (resp && resp.success) { window.batchFiles[this.currentFileIndex].subtitles = resp.subtitles; this.renderCurrentFileSubtitles(); EditorCore.showToast('Timing shifted by ' + offsetMs + 'ms', 'success'); }
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
    const subs = window.batchFiles?.[this.currentFileIndex]?.subtitles;
    if (!subs) return;
    subs.forEach(s => { const end = EditorCore.parseTimestamp(s.end); if (end > currentDur) currentDur = end; });
    if (currentDur <= 0) { EditorCore.showToast('Cannot determine current duration', 'warning'); return; }
    this.saveUndoState();
    $.post(window.location.href, { scale_timing: targetSec / currentDur, file_index: this.currentFileIndex }, resp => {
      if (resp && resp.success) { window.batchFiles[this.currentFileIndex].subtitles = resp.subtitles; this.renderCurrentFileSubtitles(); EditorCore.showToast('Timing scaled to ' + input, 'success'); }
      else { EditorCore.showToast('Scale failed', 'error'); }
    }, 'json').fail(() => EditorCore.showToast('Scale failed - server error', 'error'));
  }

  // ===== Unknown Words / Dictionary Changes =====
  showUnknownWords() {
    $.get('includes/get-words.php', { batch_mode: 1 }, data => {
      const tabsContainer = document.getElementById('unknownWordsTabs');
      const tabContentContainer = document.getElementById('unknownWordsTabContent');
      tabsContainer.innerHTML = '';
      tabContentContainer.innerHTML = '';
      const batchFiles = window.batchFiles;
      if (batchFiles && batchFiles.length > 0) {
        batchFiles.forEach((bf, fileIdx) => {
          const fileData = data ? data.find(f => f.file_index === fileIdx) : null;
          const words = fileData && fileData.words ? fileData.words : [];
          const savedTab = localStorage.getItem('uwActiveTab');
          const isActive = savedTab !== null ? parseInt(savedTab) === fileIdx : fileIdx === this.currentFileIndex;
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
                  <div class="uw-info"><span class="uw-line"><i class="fas fa-hashtag"></i> Line ${item.line}</span><span class="uw-word"><i class="fas fa-spell-check"></i> ${item.word}</span></div>
                  <button class="uw-play-btn" onclick="event.stopPropagation(); goToSubtitle(${fileIdx}, ${lineIndex})" title="Play"><i class="fas fa-play"></i></button>
                </div>`;
              }
              wordsHtml += '</div>';
            }
            wordsHtml += '</div>';
            tabPane.innerHTML = wordsHtml;
          } else {
            tabPane.innerHTML = '<div class="uw-empty"><i class="fas fa-check-circle"></i><p>No unknown words found</p><small>All words are recognized in the Indonesian dictionary</small></div>';
          }
          tabContentContainer.appendChild(tabPane);
        });
      }
      tabsContainer.querySelectorAll('button.nav-link').forEach(btn => {
        btn.addEventListener('click', function() {
          const idx = this.id.replace('uw-tab-', '');
          const scrollKey = 'uwScroll_' + idx;
          const contentEl = document.getElementById('unknownWordsTabContent');
          if (contentEl) localStorage.setItem(scrollKey, contentEl.scrollTop);
          tabsContainer.querySelectorAll('button.nav-link').forEach(b => b.classList.remove('active'));
          tabContentContainer.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
          this.classList.add('active');
          const pane = document.getElementById('uw-pane-' + idx);
          if (pane) pane.classList.add('show', 'active');
          localStorage.setItem('uwActiveTab', idx);
        });
      });
      setTimeout(() => {
        const activeIdx = localStorage.getItem('uwActiveTab') || '0';
        const scrollKey = 'uwScroll_' + activeIdx;
        const contentEl = document.getElementById('unknownWordsTabContent');
        if (contentEl) {
          const savedScroll = localStorage.getItem(scrollKey);
          if (savedScroll !== null) contentEl.scrollTop = parseInt(savedScroll);
        }
      }, 100);
      $('#unknownWordsModal').addClass('active');
    }).fail(() => {
      $('#unknownWordsTabContent').html('<div class="uw-empty text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load data</p></div>');
      $('#unknownWordsModal').addClass('active');
    });
  }

  showDictionaryChanges() {
    $.get('includes/get-dictionary-changes.php', { batch_mode: 1 }, data => {
      const tabsContainer = document.getElementById('dicChangesTabs');
      const tabContentContainer = document.getElementById('dicChangesTabContent');
      tabsContainer.innerHTML = '';
      tabContentContainer.innerHTML = '';
      const batchFiles = window.batchFiles;
      if (batchFiles && batchFiles.length > 0) {
        batchFiles.forEach((bf, fileIdx) => {
          const fileData = data ? data.find(f => f.file_index === fileIdx) : null;
          const changes = fileData && fileData.changes ? fileData.changes : [];
          const isActive = fileIdx === 0;
          const tabBtn = document.createElement('button');
          tabBtn.className = 'nav-link' + (isActive ? ' active' : '');
          tabBtn.id = 'dc-tab-' + fileIdx;
          tabBtn.type = 'button';
          tabBtn.innerHTML = bf.file_name + (changes.length > 0 ? ` <span class="badge bg-info">${changes.length}</span>` : '');
          tabsContainer.appendChild(tabBtn);
          const tabPane = document.createElement('div');
          tabPane.className = 'tab-pane fade' + (isActive ? ' show active' : '');
          tabPane.id = 'dc-pane-' + fileIdx;
          if (changes.length > 0) {
            let html = '<div class="unknown-words-list">';
            for (let c = 0; c < changes.length; c += 3) {
              html += '<div class="uw-row">';
              for (let cj = c; cj < c + 3 && cj < changes.length; cj++) {
                const item = changes[cj];
                const lineIndex = item.line - 1;
                html += `<div class="unknown-word-item" onclick="goToSubtitle(${fileIdx}, ${lineIndex})">
                  <div class="uw-info"><span class="uw-line"><i class="fas fa-hashtag"></i> Line ${item.line}</span>
                  <span class="uw-word dc-original">${item.original}</span><span class="dc-arrow"><i class="fas fa-arrow-right"></i></span>
                  <span class="uw-word dc-replacement">${item.replacement}</span></div>
                  <button class="uw-play-btn" onclick="event.stopPropagation(); goToSubtitle(${fileIdx}, ${lineIndex})" title="Go"><i class="fas fa-arrow-right"></i></button>
                </div>`;
              }
              html += '</div>';
            }
            html += '</div>';
            tabPane.innerHTML = html;
          } else {
            tabPane.innerHTML = '<div class="uw-empty"><i class="fas fa-check-circle"></i><p>No dictionary changes</p><small>No words have been replaced by the dictionary</small></div>';
          }
          tabContentContainer.appendChild(tabPane);
        });
      }
      tabsContainer.querySelectorAll('button.nav-link').forEach(btn => {
        btn.addEventListener('click', function() {
          const idx = this.id.replace('dc-tab-', '');
          tabsContainer.querySelectorAll('button.nav-link').forEach(b => b.classList.remove('active'));
          tabContentContainer.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
          this.classList.add('active');
          const pane = document.getElementById('dc-pane-' + idx);
          if (pane) pane.classList.add('show', 'active');
        });
      });
      $('#dictionaryChangesModal').addClass('active');
    }).fail(() => {
      $('#dicChangesTabContent').html('<div class="uw-empty text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load data</p></div>');
      $('#dictionaryChangesModal').addClass('active');
    });
  }

  // ===== Navigation =====
  goToSubtitle(fileIndex, lineIndex) {
    const batchFiles = window.batchFiles;
    if (!batchFiles || !batchFiles[fileIndex]) { EditorCore.showToast('File not found', 'error'); return; }
    const subtitles = batchFiles[fileIndex].subtitles;
    if (!subtitles || lineIndex < 0 || lineIndex >= subtitles.length) { EditorCore.showToast('Subtitle not found', 'error'); return; }
    const sub = subtitles[lineIndex];
    const time = EditorCore.parseTimestamp(sub.start);

    const activeTab = document.querySelector('#unknownWordsTabs .nav-link.active');
    if (activeTab) {
      const tabIdx = activeTab.id.replace('uw-tab-', '');
      localStorage.setItem('uwActiveTab', tabIdx);
      const contentEl = document.getElementById('unknownWordsTabContent');
      if (contentEl) localStorage.setItem('uwScroll_' + tabIdx, contentEl.scrollTop);
    }

    $('.modal-overlay.active').removeClass('active');
    document.querySelectorAll('.modal').forEach(el => {
      const modal = bootstrap.Modal.getInstance(el);
      if (modal) modal.hide();
    });
    setTimeout(() => { $('.modal-backdrop').remove(); document.body.classList.remove('modal-open'); }, 100);

    this.switchBatchFile(fileIndex);
    setTimeout(() => {
      this.selectSubtitle(fileIndex, lineIndex);
      const row = $(`.subtitle-row[data-file="${fileIndex}"][data-index="${lineIndex}"]`);
      if (row.length) row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => {
        if (this.videoPlayer && this.videoPlayer.readyState >= 1) {
          this.videoPlayer.currentTime = time;
          this.videoPlayer.play().then(() => EditorCore.showToast('Playing line ' + (lineIndex + 1), 'success'))
            .catch(() => EditorCore.showToast('Loaded at line ' + (lineIndex + 1) + ' - click play', 'warning'));
        } else if (this.videoPlayer) {
          this.videoPlayer.currentTime = time;
          EditorCore.showToast('Video at line ' + (lineIndex + 1) + ' - click play', 'warning');
        } else {
          EditorCore.showToast('Line ' + (lineIndex + 1) + ' selected', 'success');
        }
      }, 300);
    }, 300);
  }

  // ===== Dictionary =====
  addDictionary() {
    const key = $('#dictKey').val().trim();
    const value = $('#dictValue').val().trim();
    if (!key || !value) { EditorCore.showToast('Please enter both words', 'warning'); return; }
    $.post('display-batch.php', { add_to_dictionary: true, key, value }, () => location.reload());
  }

  removeDictionary(key) {
    $.post('display-batch.php', { remove_from_dictionary: key }, () => location.reload());
  }

  // ===== Download =====
  downloadBatch() {
    const format = $('#exportFormat').val();
    const subtitleType = $('#subtitleType').val();
    const form = $('<form method="post">');
    form.append('<input name="batch_download" value="1">', $('<input name="format">').val(format), $('<input name="subtitle_type">').val(subtitleType));
    $('body').append(form);
    form.submit();
    form.remove();
  }

  // ===== Session =====
  clearSession() {
    EditorCore.showConfirm('Start new files?', 'All current changes will be lost. Are you sure?', () => {
      $.post('display-batch.php', { clear_session: true }, () => { window.location.href = 'index.php'; });
    });
  }

  // ===== UI Events =====
  _setupUIEvents() {
    $('#timelineSlider').on('input', function() {
      if (!this.videoPlayer || !this.duration) return;
      const time = (this.value / 100) * this.duration;
      this.videoPlayer.currentTime = time;
      this.currentTime = time;
    }.bind(this));

    $('#subtitleSearch').on('input', e => this.filterSubtitles($(e.target).val()));

    $(document).on('click', '.subtitle-row', e => this._handleRowClick(e));

    $(document).on('click', '.play-from-btn', e => {
      e.stopPropagation();
      const time = EditorCore.parseTimestamp($(e.target).closest('.play-from-btn').data('time'));
      if (this.videoPlayer) { this.videoPlayer.currentTime = time; this.videoPlayer.play(); }
    });

    $(document).on('dblclick', '.subtitle-modified, .subtitle-original', e => {
      e.stopPropagation();
      const $el = $(e.target).closest('[data-file]');
      this.editSubtitle($el.data('file'), $el.data('index'));
    });

    $('#batchToolbar').on('click', '.et-btn[data-cmd]', e => this._applyBatchFormat($(e.target).closest('.et-btn').data('cmd')));
    $('#batchClear').on('click', () => this.clearSelection());
    $('#batchDelete').on('click', () => this._deleteSelectedSubtitlesBatch());
    $('#batchMerge').on('click', () => this._mergeSelectedSubtitlesBatch());

    $(document).on('click', '.delete-subtitle-btn', e => {
      e.stopPropagation();
      const $btn = $(e.target).closest('.delete-subtitle-btn');
      this._deleteSubtitleBatch($btn.data('file'), $btn.data('index'));
    });

    $('#mergeModalClose, #mergeModalCancel').on('click', () => { $('#mergeModal').removeClass('active'); this.mergeIndices = []; });
    $('#mergeModalConfirm').on('click', () => this._doMergeBatch());
  }

  _handleRowClick(e) {
    const $row = $(e.currentTarget);
    const fileIndex = $row.data('file');
    const index = $row.data('index');
    const key = fileIndex + '-' + index;
    if (e.shiftKey && (e.ctrlKey || e.metaKey)) {
      if (!this.lastAnchor) {
        const activeRow = $('.subtitle-row.active');
        const af = activeRow.length ? activeRow.data('file') : null;
        const ai = activeRow.length ? activeRow.data('index') : null;
        if (af !== null && ai !== null && af === fileIndex) this.lastAnchor = { file: af, index: ai, key: af + '-' + ai };
      }
      if (this.lastAnchor && this.lastAnchor.file === fileIndex) {
        const from = Math.min(this.lastAnchor.index, index);
        const to = Math.max(this.lastAnchor.index, index);
        this.selectedRows.clear();
        $('.subtitle-row').removeClass('multi-selected');
        for (let i = from; i <= to; i++) {
          const ik = fileIndex + '-' + i;
          this.selectedRows.add({ file: fileIndex, index: i, key: ik });
          $(`.subtitle-row[data-file="${fileIndex}"][data-index="${i}"]`).addClass('multi-selected');
        }
        this.lastAnchor = { file: fileIndex, index };
      } else {
        this.selectedRows.clear();
        $('.subtitle-row').removeClass('multi-selected');
        this.selectedRows.add({ file: fileIndex, index, key });
        $row.addClass('multi-selected');
        this.lastAnchor = { file: fileIndex, index };
      }
      this._updateBatchToolbar();
    } else if (e.ctrlKey || e.metaKey) {
      $row.toggleClass('multi-selected');
      if ($row.hasClass('multi-selected')) {
        this.selectedRows.add({ file: fileIndex, index, key });
        this.lastAnchor = { file: fileIndex, index };
        if (this.selectedRows.size === 1) {
          $('.subtitle-row.active').each((_, el) => {
            const af = $(el).data('file');
            const ai = $(el).data('index');
            if (af !== undefined && ai !== undefined) {
              const ak = af + '-' + ai;
              let found = false;
              this.selectedRows.forEach(item => { if (item.key === ak) found = true; });
              if (!found) { this.selectedRows.add({ file: af, index: ai, key: ak }); $(el).addClass('multi-selected'); }
            }
          });
        }
      } else {
        this.selectedRows.forEach(item => { if (item.key === key) this.selectedRows.delete(item); });
      }
      this._updateBatchToolbar();
    } else {
      this.selectedRows.clear();
      $('.subtitle-row').removeClass('multi-selected');
      this.lastAnchor = null;
      this._updateBatchToolbar();
      this.selectSubtitle(fileIndex, index);
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
        case 's': if (e.ctrlKey) { e.preventDefault(); this.downloadBatch(); } break;
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
      BatchEditor: { instance },
      editSubtitle: (fi, i) => instance.editSubtitle(fi, i),
      addDictionary: () => instance.addDictionary(),
      removeDictionary: k => instance.removeDictionary(k),
      downloadBatch: () => instance.downloadBatch(),
      togglePlay: () => instance.togglePlay(),
      toggleFullscreen: () => EditorCore.toggleFullscreen(),
      toggleSubtitlePosition: () => EditorCore.toggleSubtitlePosition(),
      togglePanel: h => EditorCore.togglePanel(h),
      toggleTheme: () => EditorCore.toggleTheme(),
      resizeVideoPanel: d => EditorCore.resizeVideoPanel(d),
      showShortcuts: () => $('#shortcutsModal').addClass('active'),
      showStatistics: () => instance.showStatistics(),
      showTiming: () => instance.showTiming(),
      applyTimingShift: () => instance.applyTimingShift(),
      applyTimingScale: () => instance.applyTimingScale(),
      showUnknownWords: () => instance.showUnknownWords(),
      showDictionaryChanges: () => instance.showDictionaryChanges(),
      goToSubtitle: (fi, li) => instance.goToSubtitle(fi, li),
      closeModal: id => EditorCore.closeModal(id),
      clearSession: () => instance.clearSession(),
      showConfirm: (t, m, c) => EditorCore.showConfirm(t, m, c),
      showToast: (m, t) => EditorCore.showToast(m, t),
      exportUnknownWords: () => EditorCore.exportUnknownWords(true),
      selectSubtitle: (fi, i) => instance.selectSubtitle(fi, i),
      prevSubtitle: () => instance.prevSubtitle(),
      nextSubtitle: () => instance.nextSubtitle(),
      skipBackward: () => instance.skipBackward(),
      skipForward: () => instance.skipForward(),
      saveUndoState: () => instance.saveUndoState(),
      undo: () => instance.undo(),
      redo: () => instance.redo(),
      refreshSubtitleList: () => instance.refreshBatchSubtitleList(),
      parseTimestamp: t => EditorCore.parseTimestamp(t),
      formatTimecode: s => EditorCore.formatTimecode(s),
      secondsToDuration: s => EditorCore.secondsToDuration(s),
      formatTime: s => EditorCore.formatTime(s),
      assToHtml: t => EditorCore.assToHtml(t),
      htmlToAss: h => EditorCore.htmlToAss(h),
      escapeHtml: t => EditorCore.escapeHtml(t),
      applyTheme: t => EditorCore.applyTheme(t),
      switchBatchFile: i => instance.switchBatchFile(i),
    });
  }
}
