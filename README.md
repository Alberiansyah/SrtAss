# SrtAss

**SrtAss** is a web-based subtitle editor for `SRT`, `ASS`, `VTT`, `SBV`, and `TXT` formats. Supports dictionary-based word replacement, batch multi-file processing, Indonesian language word detection with Sastrawi stemming, video preview with synchronized subtitle overlay, inline WYSIWYG editing, dark/light theme, readability analysis, version history, drag-and-drop reorder, and auto-timing.

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

### Drag & Drop Reorder
- **Drag subtitle rows** to reorder in both single and batch mode
- **Auto-snapshot** before reorder for undo protection
- **AJAX persistence** — no full page reload

### Version History
- **Persistent file storage** — history saved to `data/version-history.json` (not session)
- **Auto-snapshots** before destructive actions (reorder, timing changes)
- **Manual snapshots** — save current state at any time
- **Restore** — click to restore any previous version (auto-saves current state first)
- **Diff preview** — compare any version with current state before restoring
- **Delete individual snapshots** — remove unwanted entries
- **Clear all history** — per-file or global
- **Search & filter** — filter snapshots by label
- **Per-file history** in batch mode
- **Max 100 versions** kept, auto-pruned

### Readability Analysis
- **CPS** (characters per second) — warning >15, danger >20
- **CPL** (characters per line) — warning >42 chars
- **Duration** — warning <1.5s, danger <1s
- **Per-row badges** in subtitle list (color-coded)
- **Summary panel** with issue counts
- **Modal viewer** with grouped issue listing

### Dictionary Changes Tracking
- **Per-file tracking** of which dictionary replacements were applied
- **Modal viewer** in both single and batch mode
- **Click to jump** — selects the subtitle line

### Auto-Timing
- **Normalize duration** — set target CPS for all subtitles
- **Fix overlaps** — automatically resolve overlapping time ranges
- **Auto-snapshot** before timing changes

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
- **ASS style fallback** — When source has no styles (e.g., SRT converted to ASS), a default `GosmickSans 75pt` style is auto-generated
- **Subtitle type validation** — Only `anime`, `movie`, `none` accepted; invalid values fall back to `anime`
- **VTT** — WebVTT format with stripped ASS tags
- **SBV** — YouTube-compatible format
- **TXT** — Plain text with optional timing prefix
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
├── reorder-subtitle.php         AJAX reorder (single & batch)
├── version-history.php          Snapshot/restore version history
├── auto-timing.php              Normalize duration & fix overlaps
├── data/
│   └── version-history.json       Persistent version history (auto-created)
├── .gitignore
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

### Bug Fixes & Hardening

- **Fatal JS syntax errors fixed** — removed orphaned duplicate code blocks in `content/js/js.js` and `content/js/video-editor.js` that broke the whole script (and `video-player.php`) on parse
- **Stored XSS fixed** — dictionary replacement values are now HTML-escaped in the highlight path (`replaceWords()`); raw values are preserved for SRT/ASS/VTT/SBV/TXT export so output stays faithful
- **Client-side XSS fixed** — dictionary-changes modal now escapes `original`/`replacement` via `escapeHtml()` in both single and batch mode
- **JSON script-breakout fixed** — `window.subtitleData` / `window.batchFiles` now use `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT`
- **Attribute-injection XSS fixed** — subtitle `data-time` attributes are now `htmlspecialchars()`-escaped
- **Open redirect fixed** — `export-dictionary.php` redirects to a fixed path instead of the request Referer
- **Dictionary import hardening** — 5 MB size cap, `UPLOAD_ERR_OK` check, and per-entry string key/value validation (`is_string()`) before merge
- **Regex safety in `replaceWords()`** — added `/u` flag; replacement values are no longer treated as backreferences; placeholder isolation prevents re-matching inside freshly inserted markup
- **Timestamp math corrected** — `secondsToSrtTimestamp()` can no longer emit an invalid 4-digit ms field; `srtTimestampToSeconds()` preserves ASS centiseconds; `convertTimeToAss()` rollover now cascades correctly through seconds → minutes → hours
- **Version history atomic writes** — snapshot/restore/delete/clear now run their full read-modify-write under an exclusive lock (`updateHistory()`), preventing lost writes and duplicate IDs
- **Version history diff fixed** — comparison now keys by subtitle position (previously keyed by a non-existent `index` field, collapsing every line to key `0`)
- **Batch auto-timing fixed** — `auto-timing.php` now accepts `file_index` and operates on `batch_files[...]['subtitles']`; batch normalize/fix-overlaps buttons work again
- **Normalize no longer creates overlaps** — end time is clamped before the next subtitle's start; `fix_overlaps` iterates until stable
- **CWD-independent paths** — dictionary and log paths now use `__DIR__`, fixing split-brain state when endpoints are called from `includes/`
- **Validation & guards** — unvalidated batch `format`, restore-payload schema validation, timing null/array guards, dictionary decode guard, `clear_session` ordering, `showToast('info')` icon, dead `=== true` comparison

### Features

- **Format converter** — VTT, SBV, TXT export support added
- **Readability warnings** — CPS/CPL/duration badges, panel, and modal in both modes
- **Version history optimization** — file-based persistent storage (not session), diff preview, delete/clear actions, search filter, improved ID generation (no conflicts), faster batch restore
- **Drag & drop reorder** — reorder subtitle rows without page reload
- **ASS style preview** — toggle inline ASS tag rendering in subtitle list
- **Auto-timing** — normalize CPS duration and fix overlapping timecodes
- **Dictionary changes tracking** — per-file log of applied dictionary replacements
- **Batch mode parity** — all features ported to batch mode with per-file isolation
- **Scrollbar styling** — themed thin scrollbars matching dark/light mode
- **Light/Dark theme** — full CSS variable migration, no hardcoded colors
- **Video preview sync** — overlay shows dictionary-modified text; editing updates preview
- **Unknown words modal** — per-file tabs, localStorage persistence for tab and scroll, fixed tab bar layout
- **Session freshness** — unknown words session cleared before each render to prevent stale data
- **Batch tabs** — manual DOM switching (no Bootstrap interference), scope fix for global functions
- **Global refresh function** — `window.refreshBatchSubtitleList` for cross-window batch subtitle list refresh
- **Default export values** — format defaults to `ass`, subtitle type defaults to `anime` when not set
- **Subtitle type validation** — only `anime`, `movie`, `none` accepted; invalid values fall back to `anime`
- **ASS style fallback** — auto-generates default `GosmickSans 75pt` style when source has no styles
- **Keyboard handling** — Space key skips when editing contenteditable fields
- **CSS architecture** — all colors use `var(--...)` from `theme.css`

## License

Open-source. Fork and modify as needed.

## Natural Pronoun Merging (Clitic Rules)

Word-by-word dictionary replacement produces grammatically awkward Indonesian, because pronouns that should attach as clitics are left as separate words. The engine now has a normalization layer that resolves dictionary entries and clitic merges in a **single pass**, so the two never chain into each other.

### The Problem

| Before (word-by-word) | After (naturalized) |
|-----------------------|---------------------|
| `dengan Anda` | `denganmu` |
| `dari saya` | `dariku` |
| `kepada dia` | `kepadanya` |
| `milik saya` | `milikku` |
| `impian saya` | `impianku` |
| `rumah kamu` | `rumahmu` |
| `nama saya` | `namaku` |
| `rumah dia` | `rumahnya` |

End-to-end example:

```
Input : Menjadi advokat publik adalah impian saya. Bagaimana dengan Anda?
Output: Menjadi advokat publik adalah impianku. Bagaimana denganmu?
```

### How the Rules Work

Two merge rules are applied, both driven by pronoun groups:

| Group | Pronouns | Clitic |
|-------|----------|--------|
| 1 | `saya`, `aku` | `-ku` |
| 2 | `kamu`, `kau`, `anda` | `-mu` |
| 3 | `dia`, `ia` | `-nya` |

- **Preposition + pronoun** — a pronoun directly following a configured preposition is attached to that preposition (`dengan Anda` → `denganmu`). Only prepositions that are unambiguous as hosts are configured; `seperti`, `punya`, and `tanpa` are deliberately excluded because they also introduce clauses (`seperti ia katakan` = "as he said") or act as verbs (`Saya punya kamu`).
- **Possessive noun + pronoun** — a pronoun following a noun listed in `possessive_nouns` is attached to that noun (`impian saya` → `impianku`). Only listed nouns are hosts, so random words are never merged.
- **Ambiguous cases are intentionally left untouched** to avoid introducing new errors. For example `Aku cinta kamu.`, `Tolong, jangan lupa sama aku.`, and `bantu aku` are **not** merged. `blocked_hosts` (e.g. `jangan`, `kemarin`, `tolong`) can never act as a host word, and reduplicated forms (`jangan-jangan`, `kapan-kapan`) plus `-nya` adverbs (`sebenarnya`, `padahal`, `katanya`) are rejected as hosts.

Precision is preferred over coverage: the layer only merges patterns it can resolve safely. When a candidate merge is rejected, the pronoun still falls back to the plain dictionary rule, so `hubungi saya` → `hubungi aku` rather than being skipped.

### Configuration

Rules live in `content/json/naturalize.json`. The file is authoritative — if the file or a key is missing, the built-in defaults are used.

```json
{
  "enabled": true,
  "prepositions": ["dengan", "dari", "kepada", "milik"],
  "pronoun_groups": {
    "1": ["saya", "aku"],
    "2": ["kamu", "kau", "anda"],
    "3": ["dia", "ia"]
  },
  "possessive_nouns": ["impian", "rumah", "nama"],
  "blocked_hosts": ["jangan", "kemarin", "tolong"],
  "nominal_suffixes": []
}
```

- `enabled` — turn the whole layer on or off.
- `prepositions` — words that may host a following pronoun.
- `pronoun_groups` — group number → pronoun list, mapped to `-ku` / `-mu` / `-nya`.
- `possessive_nouns` — nouns allowed to host a possessive pronoun.
- `blocked_hosts` — words that must never become a host.
- `nominal_suffixes` — optional suffixes treated as nominal endings when validating a host. Empty by default: the suffix heuristic proved too loose (`sebenarnya saya` would become `sebenarnyaku`), so only explicit `possessive_nouns` are trusted out of the box. Fill this in only if you want broader coverage and are willing to accept false positives.

### Dictionary Priority & Overriding

Multi-word dictionary entries always win — they are applied first and have the highest priority. This means the naturalization layer can be overridden per phrase through the Dictionary UI, e.g. an entry `impian saya` → any free text you choose will take effect instead of `impianku`.

### Disabling the Feature

Either switch the constant off in `functions.php`:

```php
define('ENABLE_PRONOUN_NATURALIZATION', false);
```

or set `"enabled": false` in `content/json/naturalize.json`.

### Related Engine Changes

- **Single-pass matching** — replacements are no longer chained, so no cascade re-replacement occurs.
- **Unicode-aware word boundaries** — entries that end with punctuation (e.g. `Muncrat!`) now match correctly.
- **Whitespace tolerance** — double spaces inside a multi-word phrase still match.
- **Case safety** — uppercase/lowercase input is handled without breaking matches.
- **ASS tag awareness** — `\N`, `\n`, `\h`, and `\H` are treated as boundaries, so `\Nimpian saya` becomes `\Nimpianku`.
- **Accurate change log** — `getDictionaryChanges()` (the "dictionary changes" modal) now reports the actual final result, including clitic merges.
