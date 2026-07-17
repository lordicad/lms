---
name: ship
description: Ship a change to production for LMS MOE — pull, rebuild front-end assets if they changed, commit, and push to main (which auto-deploys via the GitHub webhook, migrations included). Use when the user says "ship it", "deploy", "push to prod", or wants a change to go live.
---

# Ship a change to production (LMS MOE)

**Production truth:** a push to `main` auto-deploys to
https://lms-moe.weststar-dev.com in ~2s (GitHub webhook → `deploy.php` →
`deploy.sh`: `git reset --hard origin/main` → `migrate --force` → copy committed
`public/build/` to docroot → cache rebuild). There is no staging and no review
gate, so `main` must always work. See
[docs/AUTO-DEPLOY.md](../../../docs/AUTO-DEPLOY.md) and
[docs/COLLABORATOR-GUIDE.md](../../../docs/COLLABORATOR-GUIDE.md).

**The webhook shell has no `composer` and no `npm`.** Both steps skip on every
deploy. Two consequences drive everything below:
- the committed `public/build/` **is** production, not a fallback;
- a new Composer package needs a manual `composer install` on the server.

Follow these steps in order.

## 1. Pull first
```
git pull --rebase origin main
```
`main` moves fast and may be many commits ahead. This also shows you what your
deploy will carry live besides your own work — `deploy.sh` resets to
`origin/main`, so a push ships **everything** on it. If someone else's unfinished
work is sitting there, say so before pushing.

## 2. Review what's changing
- `git status` and `git diff --stat` to see the scope.
- Stage deliberately. **Never `git add -A`** — this repo is public, and untracked
  secrets (`autodeploy.md`, `.deploy.env`, `Filezilla_creds.md`) or half-finished
  work reach production in ~2s. Check `git diff --cached` before committing.

## 3. Build front-end assets if the front-end changed
If anything under `resources/js/`, `resources/css/`, `tailwind.config.js`, or any
`*.blade.php` changed (Tailwind scans Blade for classes), run:
```
npm run build
```
Then stage `public/build/` with the rest. **This is not optional**: the server
cannot build, so skipping it deploys a stylesheet missing the new classes. It
fails silently — the page renders unstyled rather than erroring.

## 4. Commit
Stage the intended files and commit with a clear, specific message. End the
message with:
```
Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
```

## 5. Check for migrations / seeders BEFORE pushing
List new or edited files under `database/migrations/` and `database/seeders/`.

**Migrations run automatically on deploy** (`deploy.sh` step 3), so a push
applies them to the production database unattended, with nothing to gate them.
- If the migration is **destructive** (drops a column/table, changes a type,
  renames) — **stop and tell the user to back up the database first.** Do not
  push it on your own initiative.
- The migrate step is non-fatal, so a failure will not abort the deploy. That
  makes `!! migrate failed` a broken release, not a warning: the new code goes
  live against the old schema.

**Seeders do not run.** If the change needs one, tell the user to run it by hand:
```
php artisan db:seed --class=<SeederName> --force
```

## 6. Push (this is the deploy)
Confirm with the user before pushing — the push *is* the production deploy.
```
git push origin main
```
The webhook then deploys automatically. Do **not** also trigger `deploy.php` by
hand: it just deploys the same commit twice.

## 7. Report and verify
- It's live in ~2s.
- **Check the live page you changed** (hard-refresh, Ctrl/Cmd+F5). There is **no
  PHP test suite** in this repo, so nothing else will catch a broken change.
- A GitHub webhook delivery may show a red "timed out" even on success — judge by
  the live site, not the webhook colour.
- Repeat any seeder/`composer install` reminder from step 5.

## Guardrails
- `main` is production — do not push half-finished work.
- The repo is **public**. Never commit `.env*`, `autodeploy.md`, `.deploy.env`,
  credentials, API keys, or anything under `public/uploads/`. A push publishes
  within seconds and history rewriting does not un-leak a secret.
- Never push a destructive migration without an explicit backup confirmation.
- If the user is on a branch with protection / a PR workflow, open a PR instead
  of pushing straight to `main`.
