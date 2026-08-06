# Admin Home Page — Design Brief

A complete specification of **one screen**: the WeLearn admin landing page (`/admin`, route
`admin.dashboard`, view `resources/views/admin/dashboard.blade.php`). Everything needed to
redesign it is here — what the product is, who this user is, every section and its real data,
the existing design tokens, all empty/edge states, and the constraints that must survive a
revamp.

---

## 1. Product context (the minimum you need)

**WeLearn / LMS MOE** is a learning platform for **Malaysian government primary schools**
(MOE = Ministry of Education). Teachers upload lesson videos, worksheets and quizzes;
students (**Tahun 1–6**, ages ~7–12) watch, download and take quizzes. Content is organised
**Subject → Year → Chapter**.

- **Language:** UI is **Bahasa Melayu** with a live **BM ⇄ EN** toggle. Labels below are
  `BM (English)`. Glossary in §9.
- **Themes:** Light **and** dark, both first-class and equally designed.
- **Stack:** Laravel + Blade + Chart.js + Alpine.js. Server-rendered, full page loads. Not an SPA.

There are three user types — **Murid** (student), **Cikgu** (teacher), **Admin** (MOE).
**This brief covers the Admin's home page only.**

---

## 2. Who this screen is for

An **MOE administrator** — a ministry/district officer overseeing the whole platform. Not a
teacher, not a school user.

**Their job on this page:** open it in the morning and answer, at a glance —
> *"Is the platform healthy? Who's contributing? What's working? What needs my attention?"*

Then, once a month, **export a report** (PDF/Word) for a superior.

**Critical characterisation — this is a calm, read-only overview.** The admin role is
**oversight, not editing**. From this page they can do exactly two things: change the
reporting period, and export a report. Every other element is a *number to read* or a *link
to somewhere else*. Deliberately, there is **no ability to edit or delete content here** —
deleting a teacher would cascade through their lessons, materials and quizzes and take
students' history with it, so that action does not exist.

**Design temperature:** professional, trustworthy, government-appropriate. Denser and more
serious than the kid-facing student surface — but not cold or bureaucratic. Currently the
page is calm and data-dense; **the risk to avoid in a revamp is making it feel like a
consumer analytics SaaS dashboard.** It should feel like a reliable ministry instrument.

---

## 3. The page shell (surrounds the content — context, not the redesign target)

The screen lives inside `x-admin-layout`, a **fixed two-column shell**:

```
┌────────────┬────────────────────────────────────────────────┐
│  SIDEBAR   │  HEADER: h1 + subtitle · [BM|EN] · ☾ · 🔔      │
│  236px     ├────────────────────────────────────────────────┤
│  sticky    │                                                │
│            │  MAIN — max-width 1240px, padding 28px 40px,   │
│  ▸ Utama   │  vertical rhythm gap: 22px between sections    │
│  ▸ Pengguna│                                                │
│  ▸ Kandungan  ←── THE 7 SECTIONS IN §4 GO HERE ──→         │
│  ▸ Cikgu   │                                                │
│  ▸ Murid   │                                                │
│  ▸ Tetapan │                                                │
│            │                                                │
│  ─────────  │                                                │
│  avatar    │                                                │
│  name/role │                                                │
│  ⏻ logout  │                                                │
└────────────┴────────────────────────────────────────────────┘
```

- **Sidebar:** white surface, `1px` right border, sticky full-height, `20px 14px` padding.
  Brand = **WeLearn logo (42×42) + "WeLearn" / "Portal Admin"**. Six nav items, each an
  icon (Feather, 1.8 stroke, 21px) + label, `48px` min-height, `12px` radius.
  **Active state = soft teal wash background `#E6F5F1` + teal text `#0F7A68`** — never a
  solid filled block. Bottom: avatar (initials, 42px circle), name, "Admin MOE", red logout.
- **Header row:** `h1` (Geist 24px/800) + muted subtitle, then a pill **BM|EN** language
  toggle (active pill = solid teal), a **theme toggle** icon button, and a **notification
  bell** with a red dot. Icon buttons are `46×46`, `12px` radius, `1px` border.
- **On this page specifically:**
  - `h1` = **"Selamat datang, {name}"** ("Welcome, {name}") — personalised.
  - subtitle = **"Gambaran keseluruhan platform WeLearn"** ("Overview of the WeLearn platform").
- **Responsive:** below `900px` the grid collapses to one column and the sidebar becomes a
  horizontal wrapping bar. Main padding drops to `20px`. *(The mobile story is currently
  weak — see §8.)*

---

## 4. The seven sections, in current top-to-bottom order

All content sits in a single column, `22px` gap. Cards share one recipe:
`background: surface; border: 1px solid line; border-radius: 18px; padding: 22px;
box-shadow: 0 2px 10px rgba(46,44,80,.04)`.

### ① Period selector + export actions
A single row, space-between, wrapping.

- **Left:** label `"Tempoh laporan:"` ("Reporting period:") then **three pills** —
  `7 hari` / `30 hari` / `12 bulan` (7 days / 30 days / 12 months). Active pill = solid teal
  `#17907B`, white text; inactive = `--tp-input` fill, muted text. `38px` min-height, fully
  rounded. **These are links** (full page reload with `?period=`), not JS tabs.
- **Right:** two outline buttons — **📄 Jana PDF** ("Generate PDF") and **📝 Jana Word**
  ("Generate Word"). They carry the current period through.
- **Scope note that matters:** the period **only** drives section ④ (the activity chart).
  Every other section is all-time or fixed-window. This is currently ambiguous and a redesign
  should make the relationship explicit.

### ② 🏅 Penyumbang Teratas (Top Contributors)
Card. Header row: `h2` + an outline button **"Lihat semua penyumbang"** ("View all
contributors") → separate page. Below the heading, a one-line explainer:
> "Sumbangan = bilangan Video + Bahan + Kuiz yang dicipta."
> *(Contribution = number of Videos + Materials + Quizzes created.)*

**Exactly 3 teachers**, in an auto-fit grid (`minmax(220px, 1fr)`, 14px gap). Each tile
(`1px` border, `14px` radius, `16px` padding) holds:
- A `40×40` rounded-square badge, `12px` radius, in a rotating pastel tint, containing
  **🥇 / 🥈 / 🥉**.
- Teacher **name** (Geist 800, truncated) and **school name** below (muted, truncated,
  optional).
- Big number: **`N` sumbangan** (contributions) — Geist 22px/800.
- A breakdown row: **🎬 videos · 📄 materials · 📝 quizzes**.

**Ranking is transparent and fully deterministic:** total contributions, tie-broken by
videos → materials → quizzes → name → id. Preserve that honesty.

### ③ ⭐ Kandungan Berprestasi Tinggi (Top-Performing Content)
Card. Three tiles in an auto-fit grid (`minmax(240px, 1fr)`). **Each tile is a link** into
the corresponding Kandungan (Content) oversight page.

| Tile | Label (BM / EN) | Metric |
|---|---|---|
| 🎬 | Video paling ditonton / Most-watched video | `tontonan` (views) |
| 📄 | Bahan paling dimuat turun / Most-downloaded material | `muat turun` (downloads) |
| 📝 | Kuiz paling dicuba / Most-attempted quiz | `percubaan selesai` (completed attempts) |

Each shows: micro-label with emoji → **content title** (Geist 15px/800) → **"Cikgu: {name}"**
(teacher attribution, muted) → the **count in teal** (Geist 18px/800) with its unit in muted
small text.

**Empty state:** an item with a zero count renders as *"Tiada data lagi."* ("No data yet.")
— the tile is never dropped, so the trio stays balanced.

### ④ 📊 Aktiviti Platform (Platform Activity) — the interactive chart
Card, and **the most complex element on the page**. Heading includes the active period as a
muted suffix (e.g. "· 7 hari").

- **Four series toggles** above the chart — real checkboxes with `accent-color` set to the
  series colour, plus an `11×11` rounded colour swatch and a label:

  | Series | BM label | EN | Colour |
  |---|---|---|---|
  | views | Tontonan video | Video views | `#17907B` teal |
  | completed | Kuiz selesai | Quizzes completed | `#4276AE` blue |
  | passed | Kuiz lulus | Quizzes passed | `#E3A31C` amber |
  | uploads | Muat naik | Uploads | `#B84A75` pink |

- **The chart:** a Chart.js line chart in a `300px`-tall canvas, `role="img"` with an
  aria-label. Toggling a checkbox shows/hides that series live (Alpine `activityChart`).
- **X axis:** 7 or 30 daily buckets (`d/m`), or 12 monthly buckets (`M Y`). **Zero-value
  periods are included** so the axis is continuous — never gap-collapsed.
- **Below it:** a `<details>` disclosure — **"Lihat data sebagai jadual"** ("View data as a
  table") — revealing a server-rendered `<table>` of the same numbers. This is the
  accessible, no-JS fallback. **It must survive any redesign** (see §8).

### ⑤ Pendaftaran (7 hari lalu) — Registrations, last 7 days
A card with `overflow:hidden` and **no padding** — a header strip then flush list rows.

- **Header strip** (`18px 22px`, bottom border): `h2` + a sub-line
  *":count akaun baharu sejak :date"* (":count new accounts since :date"). If there are more
  registrations than the 10 shown, an outline **"Lihat Semua"** ("View All") button →
  Pengguna (Users) page.
- **Up to 10 rows**, newest first. Each row: a `36×36` rounded-square avatar with **initials**
  in a rotating pastel tint → **name** + a muted meta line (`Tahun N · email/username` for
  students) → a **role pill** (Cikgu = teal `#DCF2EE`/`#0F7A68`, Murid = blue
  `#E4EEF9`/`#2E6CA8`) → a **relative date** (`Hari ini` / `Semalam` / `j M` = Today /
  Yesterday / date).
- **Empty state:** *"Tiada pendaftaran dalam 7 hari lalu."* ("No registrations in the last 7 days.")
- This window is **fixed at 7 days** and does *not* follow the period selector.

### ⑥ ⏳ Tindakan Menunggu (Pending Actions)
Card, vertical stack of alert rows (`12px 14px`, `12px` radius, tinted background, `12px` gap).
Each row: emoji → **bold title** → muted description.

**These are real, computed signals — never invented.** Only non-empty ones appear:

| Condition | Icon / tint | Title (BM) | Description |
|---|---|---|---|
| Deactivated teacher accounts | 🚫 `#FDE7E0` | ":count akaun cikgu dinyahaktifkan" | "Kandungan mereka kekal untuk murid. Semak di halaman Cikgu." |
| Unpublished draft videos | 📼 `#FEF0CE` | ":count video belum diterbitkan" | "Draf yang belum kelihatan kepada murid." |
| Teachers with zero content | 🧑‍🏫 `#E4EEF9` | ":count cikgu belum menyumbang kandungan" | "Belum memuat naik sebarang video, bahan atau kuiz." |

**All-clear state:** if nothing is pending, exactly one row shows — ✅ `#DCF2EE`
**"Tiada tindakan menunggu"** / "Semua cikgu aktif dan menyumbang. Platform teratur."
("No pending actions" / "All teachers active and contributing. The platform is tidy.")

**Note:** these rows are **not currently clickable**, though every one of them describes
something the admin would want to go fix. That is a real weakness — see §8.

### ⑦ All-time summary cards
An auto-fit grid (`minmax(200px, 1fr)`, 16px gap) of **four stat cards** (`16px` radius,
`20px 22px`). Each: a `40×40` tinted rounded-square with an emoji, a muted label, then the
number in Geist 28px/800.

| | Label (BM / EN) | Tint |
|---|---|---|
| 👨‍🎓 | Jumlah murid / Total students | `#E4EEF9` |
| 🧑‍🏫 | Jumlah cikgu / Total teachers | `#DCF2EE` |
| 🎬 | Jumlah video / Total videos | `#FEF0CE` |
| 📝 | Jumlah kuiz / Total quizzes | `#FBE4ED` |

**These are all-time totals and they currently sit at the very bottom of the page** — the
most summary-level numbers are the last thing the admin sees. Flagged in §8.

---

## 5. Design tokens in place today

Scoped under a `.tp` class. **Same token names in both themes**, so recolouring is free —
any redesign should keep this discipline and retune values rather than hard-code hex.

| Token | Role | Light | Dark |
|---|---|---|---|
| `--tp-page` | page background | `#F7F6F2` (warm off-white) | `#0E1116` |
| `--tp-surface` | cards, sidebar | `#FFFFFF` | `#171E27` |
| `--tp-surface-2` | row hover | `#FAF9F5` | `#1E2731` |
| `--tp-hover` / `--tp-chip` | hover fill / chips | `#F1F0E8` / `#EFEDE6` | `#232D38` |
| `--tp-input` | input + inactive pill fill | `#F6F5F0` | `#1E2731` |
| `--tp-ink` | headings | `#28293F` | `#EDF2F8` |
| `--tp-body` | body text | `#2D2F44` | `#C9D2DC` |
| `--tp-muted` / `--tp-muted-2` | muted / secondary | `#8B8AA3` / `#6C6F87` | `#8A94A3` / `#A6AFBC` |
| `--tp-line` | hairline border | `rgba(46,44,80,.08)` | `rgba(255,255,255,.09)` |
| `--tp-teal` / `--tp-teal-hover` | **the one accent** | `#17907B` / `#2BB39B` | `#2DD4BF` / `#5EEAD4` |
| `--tp-active-bg` / `--tp-active-fg` | active nav | `#E6F5F1` / `#0F7A68` | `#123029` / `#5EEAD4` |
| `--tp-shadow` | card shadow | `0 2px 10px rgba(46,44,80,.04)` | deeper, darker |

**The warm neutral is deliberate.** The page background is `#F7F6F2` — a warm off-white, not
a cold grey — and chips/inputs follow it. That warmth is what stops this from feeling like a
generic admin template. Keep it.

**Colour philosophy:** exactly **one accent — teal**. The pastel pairs
(`#DCF2EE`/`#0F7A68` teal, `#E4EEF9`/`#2E6CA8` blue, `#FBE4ED`/`#B84A75` pink,
`#FEF0CE`/`#8A6A12` amber, `#FDE7E0`/`#C24936` clay) are used only as **rotating identity
tints** for avatars, badges and category strips — never for primary actions. Resist rainbow UI.

**Type:** two self-hosted variable fonts.
- **Geist** — all headings, numbers, buttons, nav, labels. Weight 800 almost everywhere;
  hierarchy comes from **size + weight**, not colour.
- **Nunito** — body/base font, set on `.tp`.
- Scale in use: `h1` 24px · `h2` 17px · big stat 28px · mid stat 22px · body 13–14.5px ·
  micro-label 12–12.5px. Numbers are consistently the largest, boldest thing in any card.

**Shape & depth:** radii `999px` pills · `11–12px` buttons/inputs · `14px` inner tiles ·
`16px` stat cards · `18px` section cards · `20px` empty states. Depth comes from **one soft
shadow**, not heavy borders. Borders are low-alpha hairlines.

**Controls:** buttons `.tp-btn` (solid teal), `.tp-btn-outline`, `.tp-btn-ghost`,
`.tp-linkbtn`. Min-height `42–46px`. Focus ring is `3px rgba(43,179,155,.2)` with a teal
border. Transitions `.15s`, and **all animation is disabled under `prefers-reduced-motion`**.

---

## 6. Real data shape

Every number is a genuine server-side aggregate from `AdminReportService` — the same service
feeds the PDF/Word exports, so **the page and the report always agree**. Nothing is mocked.

```php
period          // '7d' | '30d' | '12m'  — drives ONLY the chart
topContributors // 3 × { name, school, videos, materials, quizzes, total }
topContent      // { video|material|quiz : { title, teacher, count } | null }
activity        // { labels: string[], series: { views[], completed[], passed[], uploads[] } }
registrations   // ≤10 User models (last 7 days, newest first)
registrationsCount // full count in that window
pending         // [ { icon, bg, title, desc } ]  — 1..3 real items, or 1 all-clear
totals          // { students, teachers, videos, quizzes }  — all-time
```

Design for realistic magnitudes: **totals in the hundreds–thousands**, contributions
**5–200**, chart values **0–500/day**. Zeroes are common on a young platform — empty states
are not edge cases here, they are the **launch-day** experience.

---

## 7. Every state to design

- **Loading:** none — server-rendered, no skeletons. Page arrives complete.
- **Empty:** *no contributors yet*; *any of the three top-content tiles with zero*; *no
  registrations in 7 days*; *all-clear pending*; *a flat all-zero chart*. **Design the
  brand-new-platform view where nearly everything is empty** — it must still look
  intentional, not broken.
- **Overflow:** long teacher names, content titles and school names all truncate with
  ellipsis today. English strings run ~15–30% longer than BM — **layouts must not break when
  the language flips**.
- **Flash messages:** a `<x-flash>` slot sits between the header and the content (e.g. after
  toggling a teacher's status elsewhere and being redirected back).
- **Dark mode:** fully supported and must stay first-class, not an afterthought.
- **Notification bell:** currently renders a red dot but **is not wired to anything**.
  Either give it a real purpose in the redesign or drop it — don't ship decorative alarm UI.

---

## 8. Known weaknesses — the actual brief for the revamp

These are the honest problems with the page today. Fixing them is the point of the redesign.

1. **Information hierarchy is upside-down.** The all-time summary totals — the most
   scannable, most "at a glance" numbers on the page — are **last**, below everything.
   The admin's first question ("is the platform healthy?") is answered at the bottom.
2. **The period selector's scope is invisible.** It changes only the chart, but it sits at
   the very top where it reads as a global filter for the whole page.
3. **Pending Actions is a dead end.** Every row describes something actionable
   ("3 videos unpublished") but **nothing is clickable**. This should be the page's
   action hub.
4. **No trend or comparison anywhere.** Every number is an absolute. There is no "+12% vs
   last week", no direction, no context — so the admin cannot tell good from bad.
5. **Two competing "top" sections.** ② Top Contributors and ③ Top-Performing Content sit
   adjacent, look similar, and compete. Their relationship should be clearer.
6. **Emoji are doing real UI work.** 🏅⭐📊⏳👨‍🎓🎬📝 carry section identity and metric
   meaning throughout. They render inconsistently across platforms and read as informal for
   a ministry tool. Consider a proper icon set — the sidebar already uses Feather SVGs.
7. **Mobile is an afterthought.** One breakpoint at `900px`; the sidebar becomes a wrapping
   horizontal strip and the 300px chart is cramped. Needs a real small-screen design.
8. **Everything is inline styles.** No reusable card/stat/section components exist for this
   page, so patterns have drifted (three different card recipes, several radii for the same
   role). A redesign should define a **small component set** and apply it consistently.

### Must survive the redesign (non-negotiable)
- **Read-only.** No edit/delete affordances for content. Two actions only: period, export.
- **Honest numbers.** Transparent, documented ranking; deterministic tie-breaks; real
  aggregates; page and exported report must always match.
- **The accessible data table** under the chart, and the no-JS server-rendered fallback.
- **Both themes**, on one shared token set with matching names.
- **Bilingual BM/EN** without layout breakage.
- **Accessibility:** `role="img"` + aria-label on the chart, `aria-current` on the active
  period, real `<input type="checkbox">` for series toggles (not divs), visible focus rings,
  `prefers-reduced-motion` honoured, min `38–46px` touch targets.
- **One teal accent**; pastels as identity tint only.
- **The warm neutral background.**

---

## 9. BM → English glossary

| Bahasa Melayu | English |
|---|---|
| Utama | Home |
| Selamat datang | Welcome |
| Gambaran keseluruhan | Overview |
| Pengguna | Users |
| Kandungan | Content |
| Cikgu / Guru | Teacher |
| Murid | Student |
| Tetapan | Settings |
| Tempoh laporan | Reporting period |
| 7 hari / 30 hari / 12 bulan | 7 days / 30 days / 12 months |
| Jana PDF / Jana Word | Generate PDF / Generate Word |
| Penyumbang Teratas | Top Contributors |
| Sumbangan | Contribution(s) |
| Lihat semua | View all |
| Kandungan Berprestasi Tinggi | Top-Performing Content |
| Video paling ditonton | Most-watched video |
| Bahan paling dimuat turun | Most-downloaded material |
| Kuiz paling dicuba | Most-attempted quiz |
| Tontonan | Views |
| Muat turun | Downloads |
| Percubaan selesai | Completed attempts |
| Aktiviti Platform | Platform Activity |
| Kuiz selesai / Kuiz lulus | Quizzes completed / passed |
| Muat naik | Uploads |
| Lihat data sebagai jadual | View data as a table |
| Pendaftaran | Registrations |
| akaun baharu | new accounts |
| Hari ini / Semalam | Today / Yesterday |
| Tindakan Menunggu | Pending Actions |
| dinyahaktifkan | deactivated |
| belum diterbitkan | not yet published |
| Tiada tindakan menunggu | No pending actions |
| Jumlah murid / cikgu / video / kuiz | Total students / teachers / videos / quizzes |
| Tiada data lagi | No data yet |
| Bahan | Materials |
| Tahun | Year (grade level, 1–6) |
| Subjek | Subject |
| Bab | Chapter |

---

*This describes the Admin Home page exactly as it is today. Treat §8 as the brief:
keep the honesty, the calm, the warmth and the accessibility — fix the hierarchy, make the
pending signals actionable, add trend context, and give it a real mobile design.*
