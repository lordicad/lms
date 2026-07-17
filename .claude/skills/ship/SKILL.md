---
name: ship
description: Ship a change to production for LMS MOE — build front-end assets if they changed, commit, and push to main (which auto-deploys via the GitHub webhook). Flags any new migration/seeder that needs a manual server run. Use when the user says "ship it", "deploy", "push to prod", or wants a change to go live.
---

# Ship a change to production (LMS MOE)

**Production truth:** a push to `main` auto-deploys to
https://lms-moe.weststar-dev.com within seconds (GitHub webhook →
`deploy.php` → `deploy.sh`: `git reset --hard origin/main` → `composer install`
→ `npm run build` → copy assets to docroot → cache rebuild). There is no staging.
So `main` must always work. See [docs/AUTO-DEPLOY.md](../../../docs/AUTO-DEPLOY.md)
and [docs/COLLABORATOR-GUIDE.md](../../../docs/COLLABORATOR-GUIDE.md).

Follow these steps in order.

## 1. Review what's changing
- `git status` and `git diff --stat` to see the scope.
- Confirm no secrets are staged: never commit `.env`, credentials, API keys, or
  anything under `public/uploads/`.

## 2. Build front-end assets if the front-end changed
If anything under `resources/js/`, `resources/css/`, `tailwind.config.js`, or any
`*.blade.php` changed (Tailwind scans Blade for classes), run:
```
npm run build
```
This both **verifies the build compiles** and refreshes the committed
`public/build/` (the server rebuilds too, but committing keeps a working
fallback). Stage `public/build/` with the rest.

## 3. Commit
Stage the intended files and commit with a clear, specific message. End the
message with:
```
Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
```

## 4. Check for migrations / seeders BEFORE pushing
List new files under `database/migrations/` and `database/seeders/` in this
change. Migrations and seeders do **NOT** run automatically (manual-data policy).
If the change adds or edits any:
- **Warn the user clearly** that after the push, someone with server access must
  run, from the repo root on the server:
  ```
  php artisan migrate --force
  php artisan db:seed --class=<SeederName> --force   # only if a seeder is needed
  ```
- Until that's run, code depending on the new schema will error in production.

## 5. Push (this is the deploy)
```
git push origin main
```
Confirm the push succeeded. The webhook now deploys automatically.

## 6. Report
Tell the user:
- It's deploying and will be live in ~30–90s.
- To verify: hard-refresh the live site (Ctrl/Cmd+F5) to skip cached assets.
- A GitHub webhook delivery may show a red "timed out" even on success (the
  deploy can outlast GitHub's 10s timeout) — judge success by the live site.
- Repeat any migration reminder from step 4.

## Guardrails
- `main` is production — do not push half-finished work.
- Never run `php artisan migrate` against production from here (no server access
  in this environment); flag it for the server operator instead.
- If the user is on a branch with protection / a PR workflow, open a PR instead
  of pushing straight to `main`.
