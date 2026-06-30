class EditorCore {
  static assToHtml(text) {
    return text
      .replace(/<i>(.*?)<\/i>/g, '{\\i1}$1{\\i0}')
      .replace(/<b>(.*?)<\/b>/g, '{\\b1}$1{\\b0}')
      .replace(/<u>(.*?)<\/u>/g, '{\\u1}$1{\\u0}')
      .replace(/<s>(.*?)<\/s>/g, '{\\s1}$1{\\s0}')
      .replace(/<[^>]*>/g, '')
      .replace(/\\N/g, '\n').replace(/\\n/g, '\n')
      .replace(/\{\\b1\}([\s\S]*?)\{\\b0\}/g, '<b>$1</b>')
      .replace(/\{\\i1\}([\s\S]*?)\{\\i0\}/g, '<i>$1</i>')
      .replace(/\{\\u1\}([\s\S]*?)\{\\u0\}/g, '<u>$1</u>')
      .replace(/\{\\s1\}([\s\S]*?)\{\\s0\}/g, '<s>$1</s>')
      .replace(/\{[^}]*\}/g, '').replace(/\n/g, '<br>');
  }

  static htmlToAss(html) {
    const text = html
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/<b>(.*?)<\/b>/gi, '{\\b1}$1{\\b0}')
      .replace(/<i>(.*?)<\/i>/gi, '{\\i1}$1{\\i0}')
      .replace(/<u>(.*?)<\/u>/gi, '{\\u1}$1{\\u0}')
      .replace(/<s>(.*?)<\/s>/gi, '{\\s1}$1{\\s0}')
      .replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ');
    return $('<div>').html(text).text().trim();
  }

  static showToast(message, type = 'success') {
    let container = $('.toast-container');
    if (!container.length) {
      container = $('<div class="toast-container"></div>');
      $('body').append(container);
    }
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle' };
    const toast = $(`<div class="toast ${type}"><i class="fas ${icons[type]}"></i><p>${message}</p></div>`);
    container.append(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  static showConfirm(title, message, callback) {
    $('#confirmTitle').text(title);
    $('#confirmMessage').text(message);
    $('#confirmOkText').text(title);
    this._confirmCallback = callback;
    $('#confirmModal').addClass('active');
    $('#confirmModal #confirmOk').off('click').on('click', () => {
      $('#confirmModal').removeClass('active');
      if (this._confirmCallback) { this._confirmCallback(); this._confirmCallback = null; }
    });
    $('#confirmModal #confirmCancel, #confirmModal #confirmClose').off('click').on('click', () => {
      $('#confirmModal').removeClass('active');
      this._confirmCallback = null;
    });
  }

  static closeModal(id) {
    // Save unknown words scroll state before closing (batch mode)
    if (id === 'unknownWordsModal') {
      const activeTab = document.querySelector('#unknownWordsTabs .nav-link.active');
      if (activeTab) {
        const idx = activeTab.id.replace('uw-tab-', '');
        localStorage.setItem('uwActiveTab', idx);
        const contentEl = document.getElementById('unknownWordsTabContent');
        if (contentEl) localStorage.setItem('uwScroll_' + idx, contentEl.scrollTop);
      }
    }
    $('#' + id).removeClass('active');
  }

  static toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    const next = current === 'light' ? 'dark' : 'light';
    localStorage.setItem('theme', next);
    this.applyTheme(next);
  }

  static applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const iconBtn = document.querySelector('#themeToggle i');
    if (iconBtn) {
      const isDark = theme === 'dark';
      iconBtn.classList.toggle('fa-moon', !isDark);
      iconBtn.classList.toggle('fa-sun', isDark);
    }
  }

  static parseTimestamp(ts) {
    const parts = ts.replace(',', '.').split(':');
    if (parts.length !== 3) return 0;
    return parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseFloat(parts[2]);
  }

  static formatTime(seconds) {
    if (isNaN(seconds) || !isFinite(seconds)) return '00:00:00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
  }

  static formatTimecode(seconds) {
    const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
    const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
    const s = String(Math.floor(seconds % 60)).padStart(2, '0');
    const ms = String(Math.round((seconds % 1) * 1000)).padStart(3, '0');
    return h + ':' + m + ':' + s + ',' + ms;
  }

  static secondsToDuration(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    return h + 'h ' + m + 'm ' + s + 's';
  }

  static escapeHtml(text) {
    return $('<div>').text(text).html();
  }

  static toggleFullscreen(wrapperId = 'videoWrapper') {
    const wrapper = document.getElementById(wrapperId);
    if (!wrapper) return;
    if (document.fullscreenElement) {
      document.exitFullscreen();
      wrapper.classList.remove('fullscreen-mode');
    } else {
      wrapper.requestFullscreen().catch(() => {
        wrapper.style.position = 'fixed';
        wrapper.style.top = '0';
        wrapper.style.left = '0';
        wrapper.style.width = '100vw';
        wrapper.style.height = '100vh';
        wrapper.style.zIndex = '9999';
        wrapper.classList.add('fullscreen-mode');
      });
    }
  }

  static toggleSubtitlePosition() {
    const overlay = document.getElementById('videoSubtitleOverlay');
    const icon = document.getElementById('subtitlePosIcon');
    if (!overlay || !icon) return;
    overlay.classList.toggle('top');
    const isTop = overlay.classList.contains('top');
    icon.className = isTop ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
    localStorage.setItem('subtitlePosition', isTop ? 'top' : 'bottom');
  }

  static togglePanel(header) {
    header.closest('.panel-section').classList.toggle('collapsed');
  }

  static resizeVideoPanel(delta) {
    const panel = document.getElementById('videoPanel');
    if (!panel) return;
    const defaultHeight = 280;
    const largeHeight = 500;
    const minHeight = 150;
    if (delta > 0) {
      const isLarge = panel.style.minHeight === largeHeight + 'px';
      panel.style.minHeight = isLarge ? defaultHeight + 'px' : largeHeight + 'px';
      panel.style.height = panel.style.minHeight;
    } else {
      const current = parseInt(panel.style.minHeight) || defaultHeight;
      panel.style.minHeight = Math.max(minHeight, current - 50) + 'px';
      panel.style.height = panel.style.minHeight;
    }
  }

  static toggleDownloadMenu(e) {
    e.stopPropagation();
    $('#downloadDropdown').toggleClass('show');
  }

  static exportUnknownWords(batchMode = false) {
    window.location.href = 'includes/export-words.php' + (batchMode ? '?batch_mode=1' : '');
  }
}

$(document).on('click', '#confirmModal', function(e) {
  if ($(e.target).is('#confirmModal')) {
    $('#confirmModal').removeClass('active');
    EditorCore._confirmCallback = null;
  }
});

document.addEventListener('fullscreenchange', () => {
  const wrapper = document.getElementById('videoWrapper');
  if (!document.fullscreenElement && wrapper) {
    wrapper.classList.remove('fullscreen-mode');
    wrapper.style.position = wrapper.style.top = wrapper.style.left = '';
    wrapper.style.width = wrapper.style.height = wrapper.style.zIndex = '';
  }
});

$(document).on('click', function(e) {
  if (!e.target.closest('.header-actions')) {
    $('.dropdown-menu').removeClass('show');
  }
});

$(document).on('click', '.dictionary-entry .delete-btn', function() {
  const key = $(this).data('key');
  if (key) {
    const mode = window.location.pathname.includes('display-batch') ? 'display-batch.php' : 'display.php';
    $.post(mode, { remove_from_dictionary: key }, () => location.reload());
  }
});

$(document).on('input', '#dictSearch', function() {
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

$(document).ready(() => {
  const savedTheme = localStorage.getItem('theme') || 'dark';
  EditorCore.applyTheme(savedTheme);
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

window.EditorCore = EditorCore;
