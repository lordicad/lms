# Auto-deploy

`git push` → GitHub webhook → `public/deploy.php?token=…` → `deploy.sh` on the host.

The token only **authenticates** the trigger; the webhook is what **fires** the
deploy. Without the webhook, deployment is a manual `bash deploy.sh` in the
cPanel Terminal.

## What deploy.sh does

1. `git fetch` + `git reset --hard origin/main` (discards local drift; `.env` and
   `public/uploads/` are untouched — they are not tracked).
2. `artisan optimize:clear`.
3. Runs the **idempotent** reference seeders: `GradeSeeder`, then
   `Kurikulum2027Seeder` (both `updateOrCreate`).
4. Rebuilds `config` and `view` caches (non-fatal).
5. `artisan storage:link` (best-effort).

It does **NOT** run migrations. Manual-migration policy — run schema changes by
hand first, then trigger the deploy:

```bash
php artisan migrate --force
```

Built front-end assets ship in git (`public/build/`) because the host has no
Node. After changing anything under `resources/`, rebuild and commit:

```bash
npm run build
git add public/build && git commit -m "build assets"
```

New PHP dependencies also need a manual `composer install` on the host — the
webhook user has no Composer.

## One-time server setup (cPanel)

1. Repo cloned; the domain's docroot points at the repo's `public/` dir; the
   checked-out branch tracks `origin/main`.

2. Generate the token and put it in the **server** `.env` only:

   ```bash
   openssl rand -hex 24            # 48-char hex string
   ```

   ```
   DEPLOY_TOKEN=<that string>
   ```

3. Test the script directly first:

   ```bash
   bash deploy.sh
   ```

4. Then test the webhook endpoint:

   ```bash
   curl -sS --max-time 150 "https://<host>/deploy.php?token=<TOKEN>"
   ```

5. In GitHub: repo **Settings → Webhooks → Add webhook**
   - Payload URL: `https://<host>/deploy.php?token=<TOKEN>`
   - Content type: `application/json`
   - Events: *Just the push event*

## Gotchas

- **`shell_exec` disabled** — many shared hosts disable it. `deploy.php` detects
  this and tells you to run `bash deploy.sh` from the Terminal or a cron job.
- **Token in the URL** is visible in access logs. Prefer the header form when you
  can: `curl -H "X-Deploy-Token: <TOKEN>" https://<host>/deploy.php`.
- **`DEPLOY_TOKEN` unset** → `deploy.php` returns **503** and refuses to run
  (fail-closed). This is expected until you set it on the server.
