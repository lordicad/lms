#!/usr/bin/env bash
#
# Auto-deploy for LMS MOE (Laravel). Runs on the host, triggered by
# public/deploy.php (webhook) or by hand from the cPanel Terminal.
#
# Steps: git pull -> composer install -> migrate -> front-end build -> optimize/cache.
#
# Migrations run automatically. Seeders do NOT — run those by hand when a release
# needs them:
#     php artisan db:seed --class=Kurikulum2027Seeder --force
#
# Two things are project-specific; they are marked CONFIGURE below.

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

# --- 1. Pull the exact remote state, discard any local drift ------------------
# reset --hard only touches TRACKED files, so .env and public/uploads survive.
echo "==> Fetching origin/$BRANCH"
git fetch --prune origin "$BRANCH"
git reset --hard "origin/$BRANCH"

# --- 2. Install PHP dependencies ----------------------------------------------
# Guarded by command -v and non-fatal: if composer is not on PATH (e.g. the
# webhook shell) the deploy continues with the existing vendor/.
if command -v composer >/dev/null 2>&1; then
    echo "==> composer install"
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
        || echo "!! composer install failed (continuing with existing vendor/)"
else
    echo "!! composer not on PATH; skipping. Run 'composer install' by hand."
fi

# --- 3. Database migrations ---------------------------------------------------
# --force is required: the deploy shell is non-interactive, so migrate would
# otherwise refuse to run in production rather than prompt.
#
# Non-fatal to match the steps around it. A failure here is louder than it looks:
# the new code is already checked out and will run against the old schema, so
# treat "!! migrate failed" in the log as a broken release, not a warning.
echo "==> migrate"
"$PHP" artisan migrate --force || echo "!! migrate failed — new code is live against the OLD schema; fix this now."

# --- 4. Front-end build -------------------------------------------------------
# Build into public/build, then copy it into the served docroot. On this split
# deployment the docroot is separate from the repo (index.php calls
# usePublicPath), so the build MUST be copied there or the live site keeps
# serving old hashed assets. Guarded and non-fatal.
if command -v npm >/dev/null 2>&1; then
    echo "==> npm ci && npm run build"
    npm ci --no-audit --no-fund || npm install --no-audit --no-fund || echo "!! npm install failed"
    npm run build || echo "!! npm run build failed (continuing with existing public/build)"
else
    echo "!! npm not on PATH; skipping build, using the committed public/build."
fi

# Copy built assets into the served docroot (set PUBLIC_DOCROOT in .env).
PUBLIC_DOCROOT="$(sed -n 's/^PUBLIC_DOCROOT=//p' .env 2>/dev/null | head -n1 | tr -d '\r' | sed -e 's/^["'\'']//' -e 's/["'\'']$//')"
if [ -n "$PUBLIC_DOCROOT" ] && [ -d "$PUBLIC_DOCROOT" ]; then
    echo "==> Syncing public/build -> $PUBLIC_DOCROOT/build"
    if command -v rsync >/dev/null 2>&1; then
        rsync -a --delete public/build/ "$PUBLIC_DOCROOT/build/"
    else
        rm -rf "$PUBLIC_DOCROOT/build"
        cp -a public/build "$PUBLIC_DOCROOT/build"
    fi

    # Also mirror public/images -> docroot so file-referenced static assets (e.g.
    # the WeLearn auth logo at /images/welearn-banner.png) resolve. Additive — no
    # --delete — so nothing already living in the docroot's images/ is removed.
    if [ -d public/images ]; then
        echo "==> Syncing public/images -> $PUBLIC_DOCROOT/images"
        mkdir -p "$PUBLIC_DOCROOT/images"
        if command -v rsync >/dev/null 2>&1; then
            rsync -a public/images/ "$PUBLIC_DOCROOT/images/"
        else
            cp -a public/images/. "$PUBLIC_DOCROOT/images/"
        fi
    fi
elif [ -n "$PUBLIC_DOCROOT" ]; then
    echo "!! PUBLIC_DOCROOT '$PUBLIC_DOCROOT' does not exist; skipping asset sync." >&2
fi

# --- 5. Optimize & cache ------------------------------------------------------
echo "==> Optimize & cache"
"$PHP" artisan optimize:clear
"$PHP" artisan config:cache || echo "!! config:cache failed (continuing, app runs uncached)"
"$PHP" artisan view:cache   || echo "!! view:cache failed (continuing)"
# route:cache is intentionally omitted: closure routes would break it.

echo "==> Deploy done ($(date))"
