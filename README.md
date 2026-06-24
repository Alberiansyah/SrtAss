# SrtAss

**SrtAss** is a web-based subtitle editor for `SRT` and `ASS` formats. Supports dictionary-based word replacement, batch multi-file processing, Indonesian language word detection with Sastrawi stemming, video preview with synchronized subtitle overlay, inline WYSIWYG editing, dark/light theme, and various export options.

## Features

### File Upload & Parsing
- **Single & batch upload** — drag-drop or click-to-browse for `.srt` and `.ass` files
- **SRT parser** — parses sequential subtitle number, timecode, multi-line text
- **ASS parser** — parses `[Script Info]`, `[Aegisub Project Garbage]`, `[V4+ Styles]`, `[Events] Dialoque`
- **Route to editor** — single file → `display.php`, multiple files → `display-batch.php`

### Video Player
- **HTML5 video player** with drag-drop video loading
- **Synchronized subtitle overlay** — shows current subtitle on the video frame
- **Subtitle position toggle** — overlay top/bottom, saved to localStorage
- **Fullscreen mode** with CSS fallback
- **Play/Pause** — button or Space key
- **Skip ±5 seconds** — buttons or Arrow keys
- **Current/total time display** — `00:00:00` format
- **Resizable video panel** — toggle between default (280px) and large (500px)

### Timeline
- **Visual timeline ruler** with canvas rendering
- **Subtitle duration blocks** — clickable to seek
- **Progress bar** with gradient and glow effect
- **Range slider** for fine seeking
- **Previous/Next subtitle** — chevron buttons or ArrowUp/ArrowDown

### Subtitle List
- **Index, start/end time, original & modified text** per row
- **Real-time search** — filters original and modified text
- **Subtitle count badge**
- **Single click** — select subtitle (blue border highlight)
- **Ctrl+Click** — toggle multi-select for batch operations
- **Ctrl+Shift+Click** — range select contiguous rows
- **Active row auto-highlight** during video playback
- **Scroll-into-view** on selection
- **Play-from-this-line button** per row
- **Delete subtitle button** per row

### Inline Subtitle Editor
- **Double-click** to edit original or modified text
- **ContentEditable WYSIWYG editor**
- **Formatting toolbar** per subtitle — Bold, Italic, Underline, Strikethrough
- **Save** (Ctrl+Enter) / Cancel (Escape)
- **Enter** inserts line break without `<div>` tags
- **AJAX save** — updates server and refreshes list

### Batch Operations (Multi-Select)
- **Bold / Italic / Underline / Strikethrough** — apply formatting to all selected rows
- **Merge selected** — opens merge dialog (choose which text to keep, combines time range)
- **Delete selected** — confirmation then batch delete
- **Clear selection**

### Dictionary System
- **Persistent storage** — `content/json/dictionary.json` (sorted, case-insensitive)
- **Add entry** — key/value pair via sidebar form
- **Remove entry** — per-entry delete, restores original subtitle text
- **Dictionary search** — filter by original or replacement word
- **Entry count** — visible / total in panel header
- **Import** — from JSON or TXT (`key=value`) files
- **Export** — as JSON or TXT
- **Pre-populated** — ~370 word replacements (informal → formal Indonesian)
- **Auto-replace** — `replaceWords()` applied to all subtitle text on load
- **Highlighted replacements** — wrapped in `<span class="highlight">`

### Indonesian Language Detection
- **112,651-word Indonesian dictionary** (KBBI-derived)
- **Sastrawi stemming** — stems each word before dictionary lookup
- **Unknown word highlighting** — `<span class="non-indonesian-word">` (yellow)
- **Session logging** — stores unknown words per file, deduplicated by word+line
- **File logging** — `content/logs/*.log`
- **Configurable** — `ENABLE_WORD_HIGHLIGHT` and `ENABLE_NON_INDONESIAN_WORD_LOGGING` constants
- **Handles ASS tags** — preserves `\N`, `\h`, `\R`, `{...}` during detection

### Unknown Words Modal
- **Single mode** — grouped 3-column layout with line number and word
- **Batch mode** — per-file tabs with word count badge
- **Click to jump** — switches file tab, selects subtitle, seeks video, plays
- **Play button per word**
- **Export** — TXT (single) or ZIP with per-file TXT (batch)
- **Tab persistence** — active tab saved to localStorage
- **Scroll persistence** — scroll position saved per tab
- **Fixed tab bar** — tabs stay visible while content scrolls
- **Empty state** — checkmark when no unknown words

### Format Export
- **SRT** — auto-increment numbering, `00:00:00,000` timecodes, HTML→ASS tag conversion
- **ASS** — Script Info header, Aegisub garbage, V4+ Styles, Events
  - **Anime**: 5 styles (Default, Atas, Red PK, Signs, Outline — GosmickSans 75pt)
  - **Movie**: Default — Panefresco 800wt 50pt
  - **Original**: Preserved from source
- **Batch ZIP** — timestamped archive with all files converted

### Theme System
- **Dark mode** (default) — backgrounds #0f0f14, surfaces #1f1f2a, text #e5e7eb
- **Light mode** — backgrounds #e2e6ec, surfaces #eef1f6, text #1a2433
- **CSS variables** — all colors via `--bg-body`, `--text-primary`, etc.
- **localStorage persistence** — theme choice saved across sessions
- **Toggle button** — moon/sun icon in header

### Notification System
- **Toast notifications** — slide-in from right, auto-dismiss 3s
- **Three types** — Success (green), Error (red), Warning (yellow)
- **Icons** — check-circle, times-circle, exclamation-triangle

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| **Space** | Play / Pause (disabled when editing contenteditable text) |
| **←** | Skip backward 5 seconds |
| **→** | Skip forward 5 seconds |
| **↑** | Previous subtitle |
| **↓** | Next subtitle |
| **Enter** | Go to selected subtitle and play (single mode only) |
| **Ctrl+S** | Save / Download |
| **Ctrl+Enter** | Save inline edit |
| **Esc** | Cancel inline edit |
| **Enter** (in editor) | Insert line break (without closing editor) |
| **Ctrl+Click** | Toggle multi-select row |
| **Ctrl+Shift+Click** | Range select rows |
| **Double-click** | Edit subtitle inline |

## File Structure

```
├── index.php                    Landing page / upload
├── display.php                  Single-file subtitle editor
├── display-batch.php            Multi-file batch editor
├── functions.php                Core parsing, export, dictionary, word detection
├── video-player.php             Standalone video + subtitle player
├── merge-subtitle.php           AJAX merge (single)
├── merge-subtitle-batch.php     AJAX merge (batch)
├── delete-subtitle.php          AJAX delete (single)
├── delete-subtitle-batch.php    AJAX delete (batch)
├── update-subtitle.php          AJAX update (single)
├── update-subtitle-batch.php    AJAX update (batch)
├── content/
│   ├── css/
│   │   ├── theme.css            CSS variables for dark/light theme
│   │   ├── editor.css           Main editor styles
│   │   ├── modern.css           Legacy styles
│   │   └── video-editor.css     Styles for video-player.php
│   ├── js/
│   │   ├── editor.js            Auto-save, modals, interactions
│   │   ├── js.js                Utility functions
│   │   ├── video-editor.js      Video player logic
│   │   └── video-player.js      Standalone player logic
│   ├── json/
│   │   ├── dictionary.json      Word replacement pairs
│   │   └── id-words.txt         Indonesian dictionary (~112k words)
│   └── logs/                    Non-Indonesian word logs
├── includes/
│   ├── get-words.php            JSON endpoint for unknown words
│   ├── export-words.php         TXT/ZIP export of unknown words
│   ├── export-dictionary.php    JSON/TXT dictionary export
│   ├── import-dictionary.php    JSON/TXT dictionary import
│   ├── dictionary-form.php      Add entry form
│   ├── dictionary-list.php      Expandable dictionary grid
│   ├── subtitle-table.php       AJAX-loaded subtitle table
│   └── ...                      Modal partials (download, merge, confirm, shortcuts)
├── vendor/sastrawi/             Indonesian stemmer library
└── backup/                      Archived CSS
```

## Setup

1. **Dictionary file**: Create `content/json/dictionary.json`:
   ```json
   { "Hey how's it going": "Good morning" }
   ```

2. **Upload**: Drag-drop `.srt` or `.ass` files on the upload page.

3. **Edit**: Double-click any subtitle to edit inline, or use the formatting toolbar.

4. **Replace**: Add dictionary entries via the sidebar form — replacements apply in real-time.

5. **Export**: Choose SRT or ASS format and download. Batch files download as ZIP.

## Troubleshooting

- **Dictionary not applied**: Clear session (`New Session` button) and re-upload.
- **Missing dictionary file**: App works without replacements; upload one via Import.
- **Unknown words show wrong lines**: Session was stale — already fixed (auto-cleared on render).
- **Batch download fails**: Ensure all files are valid `.srt` or `.ass`.
- **Video won't load**: Ensure browser supports the format (.mp4, .webm recommended).

## Technologies

- **PHP 8+** — server-side logic, session handling
- **jQuery** — AJAX, DOM manipulation
- **Bootstrap 5.3** — tabs, modals, nav, table, forms
- **Font Awesome 6** — icons
- **Sastrawi** — Indonesian word stemming
- **Web Storage API** — theme, subtitle position, unknown words tab persistence
- **ZipArchive** — batch downloads

## Recent Updates

- **Light/Dark theme** — full CSS variable migration, no hardcoded colors
- **Video preview sync** — overlay shows dictionary-modified text; editing updates preview
- **Unknown words modal** — per-file tabs, localStorage persistence for tab and scroll, fixed tab bar layout
- **Session freshness** — unknown words session cleared before each render to prevent stale data
- **Batch tabs** — manual DOM switching (no Bootstrap interference), scope fix for global functions
- **Keyboard handling** — Space key skips when editing contenteditable fields
- **CSS architecture** — all colors use `var(--...)` from `theme.css`

## License

Open-source. Fork and modify as needed.
