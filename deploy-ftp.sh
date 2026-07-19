#!/usr/bin/env bash
#
# Push local changes straight to the lms-moe host over FTPS.
#
#   ./deploy-ftp.sh            # upload the tracked change set + built assets
#   ./deploy-ftp.sh --dry      # list what would go, upload nothing
#   ./deploy-ftp.sh --assets   # only public/build -> docroot
#
# READ THIS FIRST — this is a stopgap, not the deploy.
#
# The host's own deploy.sh starts with `git reset --hard origin/main`, so every file
# below is overwritten the next time anyone pushes to github.com/lordicad/lms. Uploads
# here last until that happens (today: hours). The durable route is a patch merged into
# that repo; see docs/AUTO-DEPLOY.md. Use this to see a change on the live host now,
# not to ship it.
#
# lftp would be the natural tool (the sibling site's deploy.sh uses it) but it is not
# installable on this machine — no pacman/MSYS2, not in winget, absent from Git for
# Windows, no WSL. curl speaks the same explicit-TLS FTP and is already present.

set -euo pipefail

cd "$(dirname "$0")"

ENV_FILE=".deploy.env"
[ -f "$ENV_FILE" ] || { echo "Missing $ENV_FILE"; exit 1; }
# shellcheck disable=SC1090
set -a; source "$ENV_FILE"; set +a

DRY=""
ASSETS_ONLY=""
for arg in "$@"; do
  case "$arg" in
    --dry|--dry-run) DRY="1" ;;
    --assets)        ASSETS_ONLY="1" ;;
    *) echo "Unknown option: $arg"; exit 1 ;;
  esac
done

# Files that live in the repo root on the host. Order matters: a view or route that
# references something must not land before the thing it references, or every page
# using the shared layout 500s for real users in the gap.
APP_FILES=(
  "lang/en.json"
  "app/Models/Material.php"
  "app/Http/Controllers/DownloadController.php"
  "app/Http/Controllers/Admin/AdminContentController.php"
  "resources/views/admin/kandungan/video.blade.php"
  "resources/views/admin/kandungan/bahan.blade.php"
  "routes/web.php"
  "resources/views/layouts/app.blade.php"
)

ftp_put() {         # ftp_put <local> <remote-path-relative-to-home>
  local src="$1" dest="$2"
  if [ -n "$DRY" ]; then
    printf '  would upload  %s\n' "$dest"
    return 0
  fi
  # Line endings are normalised: the repo is eol=lf and these files are edited on Windows.
  local tmp; tmp="$(mktemp)"
  if file "$src" 2>/dev/null | grep -q text; then sed 's/\r$//' "$src" > "$tmp"; else cp "$src" "$tmp"; fi

  if curl -sS --ssl-reqd -k --max-time 120 --ftp-create-dirs \
       --user "$FTP_USER:$FTP_PASS" -T "$tmp" "ftp://$FTP_HOST/$dest" >/dev/null; then
    printf '  ok            %s\n' "$dest"
  else
    printf '  FAILED        %s\n' "$dest"; rm -f "$tmp"; return 1
  fi
  rm -f "$tmp"
}

echo "==> Target: $FTP_HOST"
echo "==> App:    $REMOTE_APP"
echo "==> Docroot:$REMOTE_DOCROOT ${DRY:+(dry run)}"

# --- 1. Built assets -----------------------------------------------------------------
# They are served from the docroot, not the repo's public/ (split deployment). Upload the
# hashed asset BEFORE the manifest, so the manifest never points at a file that is not
# there yet — that gap would serve an unstyled site.
if [ -d public/build/assets ]; then
  echo "==> Assets"
  for f in public/build/assets/*; do
    [ -e "$f" ] || continue
    ftp_put "$f" "$REMOTE_DOCROOT/build/assets/$(basename "$f")"
  done
  ftp_put "public/build/manifest.json" "$REMOTE_DOCROOT/build/manifest.json"
fi

[ -n "$ASSETS_ONLY" ] && { echo "==> Done (assets only)."; exit 0; }

# --- 2. Application files ------------------------------------------------------------
echo "==> App files"
for f in "${APP_FILES[@]}"; do
  [ -f "$f" ] || { echo "  skip (missing)  $f"; continue; }
  ftp_put "$f" "$REMOTE_APP/$f"
done

# --- 3. Smoke test -------------------------------------------------------------------
# The layout is shared by every teacher and admin page, so a bad upload takes the site
# down rather than just the new page. Check before walking away.
if [ -z "$DRY" ]; then
  echo "==> Smoke test"
  for path in "/" "/login" "/admin/kandungan/video" "/admin/kandungan/bahan"; do
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 25 "https://lms-moe.weststar-dev.com$path" || echo "---")
    printf '  %s  %s\n' "$code" "$path"
  done
  echo "    (302 on /admin/* is correct: it is the login redirect, so the route exists)"
fi

echo "==> Done. Remember: the next push to origin/main erases all of this."
