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

---

## How the auto-deploy works

```
you: git push origin main
        │
        ▼
GitHub webhook  ──►  the server runs deploy.sh:
                       1. git reset --hard origin/main   (pulls your code)
                       2. composer install               (PHP dependencies)
                       3. npm run build                  (front-end assets)
                       4. copy built assets to the web docroot
                       5. clear + rebuild Laravel caches
```

The server has PHP, Composer and Node, so **you don't have to build anything
before pushing** — it builds server-side. A deploy takes roughly 30–90 seconds.

### What auto-deploys, and what doesn't

| Change | Goes live on push? |
|---|---|
| PHP, Blade views, routes, config | ✅ Instantly |
| Front-end CSS/JS (`resources/`) | ✅ Yes — the server runs `npm run build` |
| New Composer package | ✅ Yes — the server runs `composer install` |
| **Database migrations** | ❌ **No — run manually** (see below) |
| **Seeders** | ❌ **No — run manually** |

### Migrations & seeders are manual (by design)

Schema changes do **not** run automatically — this is deliberate, so a push can
never alter or drop production data by surprise. If your change includes a new
migration:

1. Commit and push it as usual.
2. Tell the project owner — someone with server access must run it once:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=Kurikulum2027Seeder --force   # only if needed
   ```

Until that's run, code expecting the new columns will error in production. So
**flag any PR/commit that adds a migration.**

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
- Put any new env var in `.env.example` (blank), never the real value.
- Call out migrations so the owner runs them after your push.

**Don't**
- Don't commit `.env`, real credentials, API keys, or anything under
  `public/uploads/`.
- Don't push half-finished work straight to `main` — it goes live.
- Don't run `php artisan migrate` against production yourself unless you have
  server access and have coordinated it.

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
Code, just say **"ship it"** (or run `/ship`) and Claude will: build front-end
assets if they changed, commit, push to `main` (which auto-deploys), and warn you
about any migration that needs a manual server run. It follows the exact rules in
this guide, so you don't have to remember them.

## More detail

See [`docs/AUTO-DEPLOY.md`](AUTO-DEPLOY.md) for the server-side mechanics (the
`deploy.php` endpoint, `deploy.sh`, the split-deployment docroot, and the
`DEPLOY_TOKEN` — all owner/server concerns you won't normally need to touch).

---

_This guide and the `/ship` skill are written to be read by both people and
Claude Code — they're the source of truth for how deploying works on this repo._
