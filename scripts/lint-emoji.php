<?php

/**
 * lint:emoji — guards against Unicode emoji used where a vector icon belongs.
 *
 * WHY THIS EXISTS
 * On iOS/WebKit an emoji codepoint is painted from Apple Color Emoji — a fixed
 * multicolour bitmap (sbix). It ignores `color`/`fill`/`font-weight`, sits off the
 * text baseline, and is announced by VoiceOver. So an emoji standing in for a UI
 * icon (a play triangle, an upload arrow, an info "i", a warning sign) renders as a
 * broken, uncontrollable blob on iPhone/iPad. Every icon in this app must instead be
 * an inline SVG via <x-icon name="..."> (Tabler, currentColor, aria-hidden).
 * See the "Icon policy" section in CLAUDE.md.
 *
 * WHAT IT FLAGS (the emoji-as-icon signal — and ONLY this):
 *   • U+FE0F / U+FE0E  — the variation selectors that force a plain symbol into
 *                         emoji presentation. Their presence is the clearest sign a
 *                         glyph is being used as a coloured icon (⚠️ ℹ️ ⬇️ ❤️ …).
 *   • The monochrome symbols routinely misused as icons: media triangles, block
 *     arrows, info/warning, clock/hourglass, sparkle. Listed explicitly below.
 *
 * WHAT IT DELIBERATELY ALLOWS:
 *   • Genuinely decorative, meant-to-be-colourful pictographs that are content, not
 *     UI chrome — podium medals, empty-state illustrations, achievement badges. These
 *     are supposed to be colourful and are not substituting for a vector icon, so they
 *     are out of scope. (They live in the U+1F000–1FAFF plane, which this lint does
 *     NOT scan.) If you ever want strict zero-emoji, widen $banned below.
 *   • Plain text arrows/ticks used as separators inside sentences (→, ←, ✓) — these
 *     are text-presentation glyphs, not emoji bitmaps, and read fine on iOS.
 *
 * Exit code 0 = clean, 1 = offending glyph(s) found (details printed).
 */

$root = dirname(__DIR__);
$scanDirs = ['resources'];

// Codepoints that signal "emoji being used as an icon".
$banned = [
    0xFE0F, 0xFE0E,                 // emoji / text variation selectors
    0x25B6, 0x25C0, 0x25B2, 0x25BC, // ▶ ◀ ▲ ▼  media / caret triangles
    0x25B8, 0x25BE, 0x25BA, 0x25C2, // ▸ ▾ ► ◂  disclosure / caret triangles
    0x2B06, 0x2B07, 0x2B05, 0x27A1, // ⬆ ⬇ ⬅ ➡  block arrows
    0x2139,                         // ℹ  information
    0x26A0, 0x26D4,                 // ⚠ ⛔ warning / no-entry
    0x23F0, 0x23F1, 0x23F2, 0x23F3, 0x231A, 0x231B, // ⏰ ⏱ ⏲ ⏳ ⌚ ⌛ clock / timer
    0x23E9, 0x23EA, 0x23EB, 0x23EC, 0x23ED, 0x23EE, 0x23EF, // ⏩ ⏪ … transport
    0x2726, 0x2727, 0x2728,         // ✦ ✧ ✨ sparkle
    0x2705, 0x274C, 0x274E,         // ✅ ❌ ❎ box-emoji tick / cross
    0x2795, 0x2796, 0x2797,         // ➕ ➖ ➗ heavy math
    0x2B50, 0x2B55,                 // ⭐ ⭕ star / circle emoji
];

$class = implode('', array_map(fn ($cp) => sprintf('\\x{%X}', $cp), $banned));
$pattern = '/[' . $class . ']/u';

$exts = ['blade.php', 'php', 'js', 'ts', 'vue', 'css'];
$skip = ['/build/', '/vendor/', '/node_modules/'];

$hits = [];

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . DIRECTORY_SEPARATOR . 'resources', FilesystemIterator::SKIP_DOTS)
);

foreach ($rii as $file) {
    if (! $file->isFile()) {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    foreach ($skip as $s) {
        if (str_contains($path, $s)) {
            continue 2;
        }
    }
    $name = $file->getFilename();
    $ok = false;
    foreach ($exts as $ext) {
        if (str_ends_with($name, '.' . $ext)) {
            $ok = true;
            break;
        }
    }
    if (! $ok) {
        continue;
    }

    $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
    foreach ($lines as $n => $line) {
        if (preg_match_all($pattern, $line, $m)) {
            foreach ($m[0] as $glyph) {
                $cp = mb_ord($glyph, 'UTF-8');
                $rel = ltrim(str_replace(str_replace('\\', '/', $root), '', $path), '/');
                $hits[] = sprintf('%s:%d  U+%04X  %s', $rel, $n + 1, $cp, $glyph);
            }
        }
    }
}

if ($hits) {
    fwrite(STDERR, "\n  emoji-as-icon found (use <x-icon name=\"...\"> instead):\n\n");
    foreach ($hits as $h) {
        fwrite(STDERR, '    ' . $h . "\n");
    }
    fwrite(STDERR, "\n  " . count($hits) . " offending glyph(s). See the Icon policy in CLAUDE.md.\n\n");
    exit(1);
}

fwrite(STDOUT, "lint:emoji — clean (no emoji-as-icon glyphs under resources/).\n");
exit(0);
