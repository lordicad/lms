# LMS MOE — Design Brief

A complete description of the system, for a design revamp. Read this top to bottom
before proposing new screens; it explains **what the product is, who uses it, every
screen that exists, and the design system in place today** so a redesign stays faithful
to how the product actually works.

---

## 1. What this is

**LMS MOE** is a learning platform for **Malaysian government primary schools** (MOE =
Ministry of Education). Think of it as a **"Netflix for classroom recordings"**: teachers
upload lesson videos, worksheets and quizzes; students watch, download and take quizzes,
all organised by **Subject → Year → Chapter**.

- **Language:** The UI is primarily **Bahasa Melayu (BM)**, with a live **BM ⇄ English**
  toggle. All labels below are given as `BM (English)`. A BM→EN glossary is in §7.
- **Audience:** Primary-school pupils (**Tahun 1–6**, ages ~7–12), their teachers, and
  MOE administrators.
- **Stack (for context, not for redesign):** Laravel 12 + Blade + Tailwind CSS + Alpine.js.
  Server-rendered, no SPA. Light **and** dark themes, both first-class.
- **Platforms:** Responsive web (mobile-first for students), plus a companion mobile app
  that hits a JSON API. The web app is the focus of this brief.

The product's core organising idea: **content is a library**, browsed the way a streaming
service is browsed — rails of cards, "Continue Watching", favourites, search — not a
traditional list-heavy LMS.

---

## 2. The three roles

The app is really **three distinct products** sharing one design language. A user's role is
fixed at their account and decides which they see.

### 2a. Murid (Student) — the streaming experience
The largest, most-designed surface. Kid-facing, warm, mobile-first.

- **Layout:** Left **sidebar** on desktop (collapsible to icons); **bottom tab bar** on
  mobile with a "Lagi" (More) sheet for overflow items.
- **Nav items:** Utama (Home), Subjek (Subjects), Kegemaran (Favourites), Sambung
  Menonton (Continue Watching), Simpanan Offline (Offline Saves), Papan Ranking
  (Leaderboard), Kuiz (Quizzes). Plus a persistent **search** bar and a **Tahun (Year)
  switcher**.
- **Journey:** land on Home → browse rails by subject → open a **video** → watch (resume
  from where they left off) → favourite it / download for offline → take the **quiz** →
  see their **result** → climb the **leaderboard** (points, ranked within their own Year).

### 2b. Cikgu (Teacher) — the content studio
A productivity tool. Cleaner, denser, desktop-first. Uses a **top nav bar** (not the
student sidebar).

- **Nav items:** Papan Pemuka (Dashboard), Video, Bahan (Materials), Kuiz (Quizzes), Bab
  (Chapters), Ranking, Bakat (Talent). Plus a prominent **"Video Baharu" (New Video)** CTA.
- **Journey:** organise the curriculum into **Bab (Chapters)** → upload **Video** (device
  upload *or* a YouTube link from a channel they've verified they own via OAuth) → attach
  **Bahan** (slides, PDF, DOCX, PPTX worksheets) → build **Kuiz** (file-based or interactive
  self-marking) → watch their **Bakat (Talent) score** and per-lesson stats.

### 2c. Admin (MOE) — oversight only
Read-only supervision. Same top-nav shell as the teacher. **Cannot edit content** — only
audits it.

- **Nav items:** Kandungan (Content) dropdown → Video / Bahan / Kuiz; Skor Bakat (Talent
  Scores); Murid (Students).
- **Journey:** audit every teacher's library behind a shared **Subjek/Tahun** filter →
  review **Talent scores** across all teachers and drill into one → **deactivate /
  reactivate** a teacher's sign-in → oversee the student roster.
- **Important nuance the redesign must preserve:** when an admin previews content, it must
  **not** count a view or a download — the numbers they audit must stay clean. Previews
  open in a **modal**, never by navigating into the student watch page.

---

## 3. The domain model (what the content *is*)

The hierarchy every screen is built around:

```
Subjek (Subject)  ─ e.g. Matematik, Sains, Bahasa Melayu, English
  └─ Tahun (Year/Grade)  ─ Tahun 1 … Tahun 6
       └─ Bab (Chapter)  ─ a teacher-defined unit within a subject+year
            ├─ Video (Lesson)   ─ uploaded mp4 OR embedded YouTube
            ├─ Bahan (Material) ─ downloadable file: PDF / DOCX / PPTX / image
            └─ Kuiz (Quiz)      ─ file-based OR interactive self-marking
```

- **Each Subject has its own accent colour** used as an identity tint on tiles and icons
  (never as the primary UI colour — see §5).
- **Lessons** track: view count, watch progress per student (for resume + Continue
  Watching), favourites, and whether they're published.
- **Quizzes** produce **QuizAttempts** with scored answers → feed the student's points and
  leaderboard rank.

### The "Bakat" (Talent) score — a signature, unusual feature
A **transparent** score (0–100) that rates a **teacher's** content on **four visible
sub-scores** — not a black box. The redesign should treat this as a hero data-viz surface:

| Sub-score | BM label | What it measures |
|---|---|---|
| Engagement | Penglibatan | Unique student views + favourites |
| Quality | Kualiti | Favourite rate per viewer |
| Outcome | Hasil Pembelajaran | Quiz-mark lift of viewers vs the chapter average |
| Breadth | Keluasan | Number of chapters contributed |

Shown as a big headline number `NN/100` with four labelled progress bars, each with a
plain-language hint. If a teacher has too few engaged students, it shows **"Data belum
mencukupi" (Not enough data yet)** instead of a misleading number. A disclaimer always
accompanies it. Teachers see their own; admins see everyone's and can export.

---

## 4. Complete screen inventory

Group these when redesigning. `*` marks the highest-traffic / most-important screens.

### Public / Auth
- **Landing** * — marketing page: hero ("Belajar di mana-mana, bila-bila masa." / "Learn
  anywhere, anytime."), three-step explainer (Watch → Try Quiz → Climb Leaderboard), a
  "For teachers" section, stat counters, and a final CTA. Students self-register; teachers
  need a school code.
- **Login**, **Register**, **Forgot / Reset password**, **Confirm password** — students can
  log in with just a username; teachers register with a code.

### Student (Murid)
- **Utama / Home** * — the streaming dashboard: hero + horizontal **rails** of lesson cards
  ("Continue Watching", per-subject rows, etc.).
- **Subjek — index** — grid of subject tiles (each in its subject colour).
- **Subjek — browse** (`/belajar/{subject}/{grade}`) — a subject's chapters & lessons.
- **Bab — browse** (`/bab/{chapter}`) — one chapter's videos, materials, quiz.
- **Tonton / Watch** * — the video player page: player, resume, favourite heart, related,
  download-for-offline, link into the quiz.
- **Kegemaran / Favourites**, **Sambung Menonton / Continue Watching**, **Simpanan /
  Offline Saves** — three "my library" list screens.
- **Cari / Search** — search results for videos.
- **Kuiz intro** → **Jawab / Take quiz** * → **Keputusan / Result** * — the quiz-taking
  flow (intro banner, question runner, scored results screen). Plus **Kuiz-saya / My
  Quizzes** (history) and a **fail** (locked/unavailable) state.
- **Papan Ranking / Leaderboard** * — points, ranked within the student's own Tahun.
- **Profil / Profile** — edit name, avatar, language, theme; delete account.

### Teacher (Cikgu)
- **Papan Pemuka / Dashboard** * — overview of their content and signals.
- **Video** — index (list of their lessons, publish toggle) + create/edit **form** *.
- **Bahan / Materials** — index + upload **form**.
- **Kuiz / Quizzes** — index → **mod (mode picker: file vs interactive)** → create/edit
  **form** → **soalan (question builder)** * → **statistik (per-quiz stats)**.
- **Bab / Chapters** — index with **inline add** (no separate create screen) + edit form.
- **Ranking** — teacher-facing ranking view.
- **Bakat / Talent** * — the four-sub-score scorecard, per-lesson breakdown with ownership
  badges, a YouTube-connect card, and the disclaimer.
- **YouTube OAuth connect/disconnect** — verify channel ownership (read-only scope).

### Admin (MOE)
- **Skor Bakat / Talent Scores** * — table of all teachers' scores; **drill-down** per
  teacher; **export**.
- **Murid / Students** — the student roster.
- **Kandungan / Content — Video / Bahan / Kuiz** — three read-only oversight tables behind
  a shared Subjek/Tahun filter, with totals that follow the filter, and **modal previews**
  (PDF/images render inline; Office files offer download, never a fake render).
- **Teacher activate/deactivate** — toggle a teacher's sign-in (content stays published).

---

## 5. Current design system (the starting point)

The existing design is already considered and token-driven. **Keep what works; the ask is a
refresh, not a teardown.** Everything below is defined as CSS custom properties so **one
token set drives both light and dark** — markup never uses `dark:` variants.

### Colour tokens
Colours are the semantic layer — redesign by **retuning tokens**, not hard-coding hex.

| Token | Role | Light | Dark |
|---|---|---|---|
| `bg` | page background (never pure white) | `#F7F8FA` | `#0A0E12` |
| `surface` | cards, panels, sidebar | `#FFFFFF` | `#111820` |
| `surface-2` | hover, inputs, skeletons | `#F1F3F6` | `#18212B` |
| `surface-3` | pressed, dividers, popovers | `#E7EAEF` | `#212C38` |
| `ink` | primary text | `#0F172A` | `#EDF2F8` |
| `ink-2` | muted text | `#5B6675` | `#94A3B8` |
| `brand` | **the one accent — teal** | `#0F766E` | `#2DD4BF` |
| `brand-soft` | accent wash (active nav, highlights) | `#E6F5F2` | dark teal |
| `success` / `warn` / `danger` | status (each with a `-soft` fill) | — | — |
| `subject` | **per-subject identity tint** (`--sc`) | injected inline | — |

**Colour philosophy — important:** there is exactly **one brand accent (teal)**. Subject
colours are for **identity only** (a tile tint, an icon), never for primary actions. Active
nav is an **accent wash + a 3px inset left-bar**, *never* a solid filled block. Hierarchy
comes from **weight, scale and spacing** far more than from colour. Every pairing was
checked for **WCAG AA** — keep it that way.

### Typography
Two self-hosted, freely-licensed variable fonts:
- **Geist** — modern grotesk for UI/display (nav, cards, buttons, headings). Scoped to the
  **student** surface (`.type-student`), one step larger, line-height 1.6. Carries the
  "not-a-template" look via weight/scale contrast.
- **Nunito** — warmer, kid-friendly; used for the **teacher** UI and for **reading copy**
  (descriptions, quiz text) everywhere.
- Headings: bold, tight tracking, `text-wrap: balance`.

### Shape, depth, motion
- **Radii:** controls `10px`, cards `14px`, panels `20px`, hero `28px`.
- **Depth** comes from **soft layered shadows** (`card` / `lift` / `hero`), not heavy
  borders. Borders are low-alpha hairlines that read over any surface.
- **Motion:** one easing curve `cubic-bezier(.2,.8,.2,1)`; buttons `active:scale-[0.98]`;
  respect `prefers-reduced-motion` (shimmer/animations disabled).
- **Touch:** buttons/controls **min 44px** tall — this is a kids' + mobile product.

### Signature components (reuse the vocabulary)
- **Buttons:** `btn-primary` (teal), `btn-secondary`, `btn-ghost`, `btn-danger`, `btn-sm`.
- **Cards:** `card` + `card-pad`; **lesson-card** with metadata *below* the thumbnail
  (no text-on-photo).
- **Thumbnails:** gently desaturated at rest, ease to full colour + `1.03` zoom on hover,
  inset hairline ring so a white slide doesn't dissolve into a white card; **skeleton
  shimmer** while decoding.
- **Rails:** horizontal scroll-snap tracks, hidden scrollbar, arrow buttons + swipe.
- **Glass pills:** on-image controls (duration chip, favourite heart) with backdrop blur.
- **Nav:** desktop teacher nav has a **shared sliding highlight pill** that glides between
  tabs. Student sidebar uses the inset-left-bar active state.
- Others: avatar (with initials fallback), dropdown, alerts (success/warn/danger),
  chips, empty states, subject tiles/icons, favourite-button, talent-scorecard, theme &
  language toggles.

---

## 6. Design goals for the revamp

What a redesign should aim for (fill in / adjust with the team):

1. **Keep the three-surface split** — student (playful, streaming, mobile-first),
   teacher (efficient studio), admin (calm oversight) — but make them feel like one family.
2. **Preserve the token architecture and AA contrast** in both themes. Retune, don't
   hard-code.
3. **Make the student Home and Watch pages more delightful** without adding weight — this
   is where kids live.
4. **Elevate "Bakat" into a proper data-viz moment** — it's the product's most novel idea
   and currently reads as plain bars.
5. **Keep one teal accent + subject tints as identity only.** Resist rainbow UI.
6. **Respect the constraints:** 44px touch targets, reduced-motion, self-hosted fonts,
   no text baked onto photos, modal previews for admins, bilingual labels that don't break
   layout when they get longer in English.

---

## 7. BM → English glossary (so labels are understood)

| Bahasa Melayu | English |
|---|---|
| Belajar | Learn |
| Murid | Student |
| Cikgu / Guru | Teacher |
| Subjek | Subject |
| Tahun | Year (grade level, Tahun 1–6) |
| Bab | Chapter |
| Video | Video / lesson |
| Bahan | Materials (files) |
| Kuiz | Quiz |
| Soalan | Questions |
| Kandungan | Content |
| Kegemaran | Favourites |
| Sambung Menonton | Continue Watching |
| Simpanan Offline | Offline Saves |
| Papan Ranking | Leaderboard |
| Papan Pemuka | Dashboard |
| Tonton | Watch |
| Keputusan | Result |
| Muat Turun | Download |
| Muat Naik | Upload |
| Bakat / Skor Bakat | Talent / Talent Score |
| Penglibatan | Engagement |
| Kualiti | Quality |
| Hasil Pembelajaran | Learning Outcome |
| Keluasan | Breadth |
| Utama | Home |
| Cari | Search |
| Profil | Profile |
| Log Masuk / Log Keluar | Log In / Log Out |
| Daftar | Register |
| Terbit | Publish |
| Data belum mencukupi | Not enough data yet |

---

*This brief describes the system as it exists today. Use it as the source of truth for
what each screen does and how the design language works — then revamp within those rules.*
