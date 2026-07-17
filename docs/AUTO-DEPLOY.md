# Auto-deploy

`git push` → GitHub webhook → `public/deploy.php?token=…` → `deploy.sh` on the host.

The token only **authenticates** the trigger; the webhook is what **fires** the
deploy. Without the webhook, deployment is a manual `bash deploy.sh` in the
cPanel Terminal.

## What deploy.sh does

1. `git fetch` + `git reset --hard origin/main` (discards local drift; `.env` and
   `public/uploads/` are untouched — they are not tracked).
2. `composer install --no-dev --optimize-autoloader` (if composer is on PATH).
3. `npm ci && npm run build`, then copy `public/build/` into the docroot (if npm
   is on PATH; otherwise the committed `public/build/` is used).
4. `artisan optimize:clear`, then rebuild `config` and `view` caches (non-fatal).

It does **NOT** run migrations or seeders. Run those by hand when a release needs
them:

```bash
php artisan migrate --force
php artisan db:seed --class=Kurikulum2027Seeder --force
```

The webhook shell has **neither `composer` nor `npm` on PATH**, so both steps
above skip every time and the deploy log says so:

```
!! composer not on PATH; skipping. Run 'composer install' by hand.
!! npm not on PATH; skipping build, using the committed public/build.
```

So the committed `public/build/` is not a fallback — it **is** what production
serves. Always run `npm run build` and commit the result alongside any change to
a Blade view, `resources/js`, or `resources/css`; Tailwind's content globs only
pick up new classes at build time, so skipping it deploys a stylesheet that is
missing them.

**Split deployment.** The served docroot is a separate directory from the repo's
`public/`; its `index.php` calls `usePublicPath(__DIR__)`, so Laravel reads the
Vite manifest from the docroot's `build/`. Set `PUBLIC_DOCROOT` in the server
`.env` to that docroot and `deploy.sh` will `rsync public/build/` into it on every
deploy. Without it, the site serves stale hashed asset filenames after a deploy.
The docroot's `index.php` / `.htaccess` and the `deploy.php` shim are maintained
by hand there and are intentionally not tracked in git.

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
