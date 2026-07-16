#!/usr/bin/env bash
#
# Auto-deploy for LMS MOE (Laravel). Runs on the host, triggered by
# public/deploy.php (webhook) or by hand from the cPanel Terminal.
#
# MANUAL-MIGRATION POLICY: this script does NOT run migrations. When a release
# ships schema changes, run them yourself first, then trigger the deploy:
#
#     php artisan migrate --force
#
# Only three things are project-specific; they are marked CONFIGURE below.

set -euo pipefail

# --- CONFIGURE 1: the branch the server checkout tracks -----------------------
BRANCH="main"

# --- CONFIGURE 2: PHP binary (cPanel EA-PHP first, then whatever is on PATH) ---
PHP=""
for cand in ea-php84 ea-php83 ea-php82 php; do
    if command -v "$cand" >/dev/null 2>&1; then
        PHP="$cand"
        break
    fi
done
if [ -z "$PHP" ]; then
    echo "No usable PHP binary found (tried ea-php84/83/82, php)." >&2
    exit 1
fi

# Always operate from the repo root (this script lives there).
cd "$(dirname "$0")"

echo "==> Deploy start ($(date))"
echo "==> Using PHP: $("$PHP" -v | head -n1)"

# --- Pull the exact remote state, discard any local drift ---------------------
# reset --hard only touches TRACKED files, so .env and public/uploads survive.
echo "==> Fetching origin/$BRANCH"
git fetch --prune origin "$BRANCH"
git reset --hard "origin/$BRANCH"

# --- Clear stale caches before touching the DB --------------------------------
echo "==> Clearing caches"
"$PHP" artisan optimize:clear

# --- CONFIGURE 3: idempotent reference seeders (safe to run every deploy) ------
# Each uses updateOrCreate/firstOrCreate. Order matters: grades before curriculum.
# NEVER add DatabaseSeeder or the demo/admin seeders here.
echo "==> Seeding reference data"
"$PHP" artisan db:seed --class=GradeSeeder --force
"$PHP" artisan db:seed --class=Kurikulum2027Seeder --force

# --- Rebuild production caches (non-fatal: app still serves if a rebuild fails)-
echo "==> Rebuilding caches"
"$PHP" artisan config:cache || echo "!! config:cache failed (continuing, app runs uncached)"
"$PHP" artisan view:cache   || echo "!! view:cache failed (continuing)"
# route:cache is intentionally omitted: closure routes would break it. Enable
# it here only after confirming every route is in a controller.

# --- Link storage (no-op if it already exists / symlinks unsupported) ---------
"$PHP" artisan storage:link 2>/dev/null || true

echo "==> Deploy done ($(date))"
