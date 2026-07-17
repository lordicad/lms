# Collaborator Guide — LMS MOE

Welcome 👋 This project is a **Laravel 12** web app (PHP 8.2+) with a **Flutter**
mobile app under [`mobile/`](../mobile). This guide covers the one thing that will
surprise you if you don't know it: **pushing to `main` deploys to production
instantly.**

- **Repo:** https://github.com/lordicad/lms
- **Live site:** https://lms-moe.weststar-dev.com

---

## ⚡ The golden rule

> **Every push to `main` goes live on the production site within a few seconds.**

There is no separate "deploy" step and no staging server. `git push origin main`
**is** the deploy. So `main` must always be in a working state.

You do **not** need any deploy token, password, or server access to deploy — your
GitHub push triggers everything automatically.

**A deploy ships all of `main`, not just your commit.** `deploy.sh` does
`git reset --hard origin/main`, so your push carries live whatever anyone else
has already pushed. With several people on `main` and no review gate, "my change
is small" does not bound what your deploy releases. Pull first and look at what
you are about to take live with you.

## The everyday loop

```bash
git pull --rebase origin main   # main moves fast; do this first
# ...make your change...
npm run build                   # ONLY if you touched Blade / resources / tailwind.config.js
git add -p                      # stage deliberately — never `git add -A`
git commit
git push origin main            # ← this is the deploy; live in ~2s
```

Nothing else is required — no token, no manual trigger. Triggering `deploy.php`
by hand after a push only deploys the same commit twice.

Then **look at the live page you changed** (hard-refresh, Ctrl/Cmd+F5). This repo
has **no PHP test suite**, so your own eyes are the only check that a change
works in production.

---

## How the auto-deploy works

```
you: git push origin main
        │
        ▼
GitHub webhook  ──►  the server runs deploy.sh:
                       1. git reset --hard origin/main   (pulls whatever is on main)
                       2. composer install               (SKIPPED — not on PATH)
                       3. php artisan migrate --force    (schema changes apply)
                       4. npm run build                  (SKIPPED — not on PATH)
                       5. copy committed public/build/ to the web docroot
                       6. clear + rebuild Laravel caches
```

A deploy takes about **2 seconds**.

### ⚠️ The webhook shell has no Composer and no npm

Steps 2 and 4 skip on **every** deploy. The log says so plainly:

```
!! composer not on PATH; skipping. Run 'composer install' by hand.
!! npm not on PATH; skipping build, using the committed public/build.
```

This has one consequence that will bite you:

> **The committed `public/build/` IS production.** It is not a fallback.

Tailwind only sees your classes at build time, so if you change a Blade view and
push without rebuilding, the site deploys a stylesheet **missing those classes**.
Nothing errors — the page just renders unstyled. Always:

```bash
npm run build          # then commit public/build/ with your change
```

Likewise, a new Composer package needs a manual `composer install` on the server.

### The build guard

Remembering the rule above is not a plan, so the repo ships a `pre-push` hook
that enforces it: push front-end sources to `main` without a matching
`public/build/` change and git refuses, naming the files. Enable it **once per
clone** (git does not do this automatically, and a hook only protects the clone
that has it):

```bash
git config core.hooksPath .githooks
```

It only blocks pushes to `main`, since only `main` deploys. To override when you
know the build is already current:

```bash
git push --no-verify
```

### What auto-deploys, and what doesn't

| Change | Goes live on push? |
|---|---|
| PHP, Blade views, routes, config | ✅ Instantly |
| Front-end CSS/JS (`resources/`) | ⚠️ **Only if you ran `npm run build` and committed `public/build/`** |
| **Database migrations** | ✅ Yes — `deploy.sh` runs `migrate --force` |
| New Composer package | ❌ No — needs manual `composer install` on the server |
| **Seeders** | ❌ No — run manually |

### Migrations run automatically

A migration merged to `main` hits the production database the moment it is
pushed. There is **no review gate** between merge and live schema:

- Write migrations that are safe to apply unattended.
- **Back up the database before pushing anything destructive** (dropped columns,
  type changes, renames). Nothing will stop you.

The migrate step is non-fatal, so a failure does not abort the deploy. That makes
`!! migrate failed` in the log a **broken release, not a warning** — the new code
is already live against the old schema.

Seeders stay manual (they are not idempotent and would re-run on every push):

```bash
php artisan db:seed --class=Kurikulum2027Seeder --force
```

---

## Running it locally

Standard Laravel setup:

```bash
git clone https://github.com/lordicad/lms.git
cd lms

composer install
npm install

cp .env.example .env
php artisan key:generate

# Enable the repo's git hooks (once per clone) — see "The build guard" below.
git config core.hooksPath .githooks

# configure your DB in .env, then:
php artisan migrate
php artisan db:seed        # taxonomy + demo data for local work

# run the app (server + vite together):
composer run dev
```

Notes:
- **Use MySQL locally, not SQLite.** Some migrations are MySQL-specific
  (`ALTER TABLE ... MODIFY ... ENUM`) and will fail on SQLite.
- Never commit your `.env` — it's gitignored. Put new config keys in
  `.env.example` (with blank/placeholder values) so others know they exist.

---

## Do / Don't

**Do**
- Keep `main` working — it's production.
- `git pull --rebase origin main` before you start and before you push.
- Run `npm run build` and commit `public/build/` whenever you touch Blade,
  `resources/`, or `tailwind.config.js`.
- Put any new env var in `.env.example` (blank), never the real value.
- Back up the database before pushing a destructive migration — it applies on
  push, unattended.
- Look at the live page after deploying. There are no tests to catch you.

**Don't**
- Don't commit `.env`, real credentials, API keys, or anything under
  `public/uploads/`. This repo is **public** — a leaked secret is public within
  seconds of a push, and rewriting history does not un-leak it.
- Don't `git add -A`. Stage deliberately: it is how untracked secrets and
  half-finished work reach `main`.
- Don't push half-finished work straight to `main` — it goes live.
- Don't push a schema change you have not thought about. Nothing gates it.

---

## Checking a deploy

After you push, you can confirm it went out:
- Watch the **live site** (hard-refresh, Ctrl/Cmd+F5, to skip cached assets).
- Repo admins can see delivery status in **GitHub → repo → Settings → Webhooks →
  Recent Deliveries**.

> Heads-up: a deploy can take longer than GitHub's webhook timeout, so a delivery
> may show a red "timed out" even though the deploy **actually finished fine** on
> the server. Judge success by the live site, not the webhook color.

---

## Using Claude Code to ship

This repo ships with a **`/ship` skill** for Claude Code
([`.claude/skills/ship/SKILL.md`](../.claude/skills/ship/SKILL.md)). In Claude
Code, just say **"ship it"** (or run `/ship`) and Claude will: pull, rebuild
front-end assets if they changed, commit, and push to `main` (which auto-deploys).
It follows the exact rules in this guide, so you don't have to remember them.

## More detail

See [`docs/AUTO-DEPLOY.md`](AUTO-DEPLOY.md) for the server-side mechanics (the
`deploy.php` endpoint, `deploy.sh`, the split-deployment docroot, and the
`DEPLOY_TOKEN` — all owner/server concerns you won't normally need to touch).

---

_This guide and the `/ship` skill are written to be read by both people and
Claude Code — they're the source of truth for how deploying works on this repo._
