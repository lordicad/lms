# Project guidance for Claude / contributors

WeLearn LMS (MOE) — Laravel 12 + Blade + Tailwind + Alpine.js + Chart.js.
Deployed to lms-moe.weststar-dev.com (git push → GitHub webhook → deploy.sh).

---

## Icon policy (non-negotiable)

**Every icon is an inline SVG, never an emoji.** Use the project component:

```blade
<x-icon name="play" class="h-4 w-4" />        {{-- inherits currentColor, aria-hidden --}}
```

Icons come from `resources/views/components/icon.blade.php` (Tabler Icons, one
family, one stroke weight). They inherit `currentColor`, sit on the text baseline,
scale to any size, and stay out of the accessibility tree (every action already
carries a visible text label). Need a glyph that isn't there yet? Add its Tabler
path to that component — do not reach for an emoji.

### Why emoji are banned as icons

On iOS/WebKit a Unicode emoji is painted from **Apple Color Emoji**, a fixed
multicolour **bitmap** (sbix) font. As a result an emoji-as-icon:

- **ignores `color`, `fill`, and `font-weight`** — it cannot be tinted to match the
  UI, so it clashes in dark mode and with themed surfaces;
- **breaks the baseline** — it renders larger than the surrounding text and sits
  off-centre, so buttons and labels look misaligned;
- **is announced by VoiceOver** — “play button emoji” is read out even though the
  glyph is decorative and the button already has a text label;
- **looks different on every OS** — Android/Windows/iOS each ship their own artwork.

A ▶ play triangle, an ⬆/⬇ upload/download arrow, an ℹ️ info “i”, a ⚠️ warning sign,
a ⏰/⏳ clock, a ▸/▾ disclosure caret, a ✦ sparkle, a ♥ favourite — all of these are
**vector icons** and must be `<x-icon>`.

### What is *not* covered by this ban

Deliberately **colourful, decorative pictographs that are content, not UI chrome** —
podium medals (🥇🥈🥉), empty-state illustrations, achievement/role badges — are
allowed to stay emoji (they are *meant* to be colourful and are not standing in for a
monochrome vector icon). Prefer a PNG asset or an `<x-icon>` when practical, but these
are out of the lint’s scope. Likewise, plain text arrows/ticks used as **separators
inside a sentence** (`Subjek → Tahun → Bab`, a `✓` in prose) are text-presentation
glyphs, not emoji bitmaps, and are fine.

### Guard

`composer lint:emoji` (script: `scripts/lint-emoji.php`) fails the build if an
emoji-as-icon glyph appears under `resources/`. Run it before pushing; wire it into
CI. If you genuinely need strict zero-emoji, widen `$banned` in that script to include
the `U+1F000–1FAFF` plane.

---

## iOS / WebKit checklist

Mobile UI is tested against **iOS Safari (WebKit)**. WebKit differs from Blink in ways
that bite this app repeatedly — check these before shipping a mobile change:

- [ ] **No emoji-as-icons.** `composer lint:emoji` is green. (See the Icon policy above.)
- [ ] **Inputs are ≥16px font** (`app.css` enforces this) — anything smaller makes iOS
      Safari zoom the page on focus. Shrink the *placeholder* with `::placeholder`, not
      the input’s font-size.
- [ ] **Touch targets are ≥44×44pt.** Segmented controls, icon buttons, and pills must
      not drop below 44px tall on mobile.
- [ ] **No scroll jump when toggling panels.** WebKit has **no scroll anchoring**
      (`overflow-anchor` is unsupported). When show/hiding content changes document
      height, the viewport jumps. Fixes: keep both panels in one CSS grid cell so the
      box height never changes (see `cikgu/video/form.blade.php` — the “source” stack),
      and/or pin `window.scrollY` across the toggle with `$nextTick(() => scrollTo)`.
- [ ] **Contrast holds in both themes.** Never hard-code a light-mode ink colour on a
      control (e.g. `color:#28293F`); use the `--tp-*` / `--wl-*` tokens so dark mode
      recolours. UI component borders need ≥3:1 against their background; text ≥4.5:1.
- [ ] **`visibility:hidden` (not just `display:none`) removes an element from tab order
      and the a11y tree** — use it (or `inert`, if the Alpine build supports binding it)
      for panels that are laid out but inactive.
- [ ] **`-webkit-line-clamp`** needs `display:-webkit-box` + `-webkit-box-orient`.
- [ ] **`100vh`** includes the iOS toolbar; prefer `100svh`/`100dvh` for full-height UI.

---

## Layout notes

- **Student** shell/CSS: `resources/views/layouts/student.blade.php` (`.wl-*`,
  `--wl-*`, mobile `@media (max-width:900px)`).
- **Teacher (cikgu)** shell/CSS: `resources/views/components/cikgu-layout.blade.php`
  (`.tp-*`, `--tp-*`, mobile `@media (max-width:900px)`). Use `<x-cikgu-layout>`.
- Mobile-only tweaks go in those `@media (max-width:900px)` blocks; do not change
  desktop layout unless explicitly asked.
- Styled `<select>` enhancement lives in `resources/js/app.js` (`enhanceSelect`);
  changing it needs `npm run build`.
