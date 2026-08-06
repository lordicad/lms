# LMS MOE — System Brief for AWS Migration Planning

> **Purpose of this document.** This is a self-contained description of the LMS MOE
> system, written to hand to an AI assistant (or a cloud architect) so it can help
> plan a migration from the current shared-cPanel hosting to AWS. It describes what
> the system *is* today, how it is deployed *now*, and where the pain points and
> decision points are. It does **not** prescribe an AWS target — that is what the
> planning conversation is for. Where a natural AWS mapping exists, it is noted as a
> *candidate*, not a decision.

---

## 1. What the system is

**LMS MOE** ("WeLearn") is a Learning Management System for Malaysian primary-school
education (Kurikulum 2027). It has three user roles — **student**, **teacher (cikgu)**,
and **admin** — and two clients against one backend:

1. **Web app** — server-rendered Laravel (Blade + Tailwind + Alpine.js). Used by all
   three roles.
2. **Mobile app** — a Flutter app (Android-first) for students and teachers, talking to
   the same backend over a token-authenticated REST API.

Core domain: teachers organise curriculum into **Subjects → Chapters (Bab) → Lessons**,
attach **videos** (uploaded MP4 or YouTube-hosted), **materials** (PDF/DOCX/PPTX/XLSX),
and **quizzes**. Students browse by grade/subject, watch videos, download materials,
take quizzes, and appear on a **leaderboard/ranking**. Teachers get dashboards, a
"Bakat" (talent) engagement score, and notifications. Admins oversee all content,
users, students, and generate reports.

Primary locale is **Malay (ms / ms_MY)**.

Production URL today: `https://lms-moe.weststar-dev.com`

---

## 2. Technology stack

### Backend
| Thing | Detail |
|---|---|
| Framework | **Laravel 12** |
| Language | **PHP 8.2+** (deploy prefers `ea-php84`, falls back to 83/82) |
| Auth (web) | Session cookies (Laravel Breeze) |
| Auth (API/mobile) | **Laravel Sanctum** personal access tokens |
| Social auth | **Laravel Socialite** — Google / YouTube OAuth |
| PDF export | `barryvdh/laravel-dompdf` |
| Word export | `phpoffice/phpword` |
| REPL/tooling | Laravel Tinker, Pint, Pail, Sail (dev only) |
| Tests | PHPUnit 11 |

### Frontend (web)
| Thing | Detail |
|---|---|
| Templating | Blade |
| CSS | **Tailwind CSS 3** (+ `@tailwindcss/forms`) |
| JS | **Alpine.js 3**, Axios |
| Charts | **Chart.js 4** |
| Bundler | **Vite 7** → outputs to `public/build` |

### Mobile
| Thing | Detail |
|---|---|
| Framework | **Flutter** (Dart SDK ^3.11) |
| Networking | `http` package, Sanctum bearer tokens |
| Secure storage | `flutter_secure_storage`, `shared_preferences` |
| Video | `youtube_player_flutter`, `video_player`, `chewie` |
| Files | `path_provider`, `open_filex`, `url_launcher` (download-then-open PDF/Office/images) |
| Platforms | Android (primary; iOS folder exists, launcher icons Android-only) |

---

## 3. Data & storage

### Database
- **Production: MySQL 8** (`DB_CONNECTION=mysql`, port 3306).
- **Local dev: SQLite** (`database/database.sqlite`) — used for tests and quick local runs.
- **31 migrations.** Schema is small and well-defined. Key tables/models:
  `users`, `schools`, `school_classes`, `subject_teacher` (pivot), `grades`,
  `subjects`, `chapters`, `lessons`, `materials`, `quizzes`, `questions`,
  `question_options`, `quiz_attempts`, `attempt_answers`, `lesson_views`,
  `lesson_progress`, `favourites`, `teacher_notifications`, `youtube_channels`,
  plus Laravel's `cache`, `jobs`, `sessions`, `personal_access_tokens`.
- Roles derived: users have a role and a derived "homeroom" concept; leaderboard/demo
  data is seeded via migrations + seeders (`Kurikulum2027Seeder`, leaderboard seeders).
- Database is currently **small** (dev SQLite is ~94 KB). This is an early-stage /
  pre-scale system.

### File uploads (the important part for AWS)
Teacher-uploaded files are the main stateful asset outside the DB. Configured in
`config/filesystems.php` as a dedicated **`uploads`** disk:

- Currently a **local disk** rooted at `public_path('uploads')` (i.e. served directly
  by the web server, *not* through Laravel).
- Deliberately under the public docroot rather than `storage/` because: (a) shared
  cPanel makes `storage:link` symlinks fragile, and (b) direct Apache/LiteSpeed serving
  gives **HTTP Range support for free** — needed for seeking within long videos.
- Script execution inside the tree is denied via `public/uploads/.htaccess`.
- `UPLOADS_ROOT` env var can override the physical write path (needed on the current
  "split deployment" where docroot ≠ repo dir).

**Upload size limits** (env-configured): videos **100 MB**, materials **30 MB**, quiz
files **30 MB**. Videos can also be YouTube-hosted instead of uploaded (see §5).

**An `s3` disk is already defined** in `config/filesystems.php` (keys/secret/region/
bucket/endpoint via env) but is **not currently in use**. Switching the `uploads` disk
to S3 is therefore a small, already-anticipated change — *but* the Range-request/direct-
serve behaviour and the `.htaccess` script-execution block need an equivalent on S3/
CloudFront.

---

## 4. Runtime services & their current drivers

The app is currently configured for the **simplest possible single-server** operation.
Every one of these is a migration decision point:

| Concern | Current driver | Notes |
|---|---|---|
| **Sessions** | `file` | Single-server assumption. Multi-instance needs shared store. |
| **Cache** | `file` | Same. |
| **Queue** | `sync` | **No queue worker runs.** All work is inline/synchronous. |
| **Mail** | `log` | Mail is written to the log, not actually sent. `AccountCredentialsMail` exists (sends account credentials) but is not wired to a real transport. |
| **Scheduler** | none | `routes/console.php` has only the stock `inspire` command. No cron jobs. |
| **Jobs** | none | `app/Jobs` does not exist — nothing is queued. |
| **Logging** | `stack` → `single` file, level `warning` | Local file logging. |

**Implication:** the app has *no* current dependency on Redis, a message broker, a
background worker, or an SMTP service. Those are greenfield choices in the AWS design,
not migrations of existing infrastructure.

---

## 5. External integrations

- **Google / YouTube OAuth** (Socialite). Teachers connect a YouTube channel; the OAuth
  callback is `https://lms-moe.weststar-dev.com/oauth/youtube/callback`. Requires
  `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `YOUTUBE_API_KEY`.
  **Redirect URIs are registered in Google Cloud Console** and must be updated for any
  new domain. Mobile connects via the web OAuth flow; the API only lists/disconnects
  channels.
- **YouTube as a video CDN.** Videos may be YouTube-hosted rather than uploaded, which
  offloads the heaviest bandwidth from our own storage. This matters for sizing S3/
  CloudFront egress.
- No payment, SMS, analytics, or other third-party services.

---

## 6. Current hosting & deployment (what we're migrating away from)

- **Shared cPanel hosting** (Apache/LiteSpeed), likely provider `host3`/similar.
- **Split deployment**: the served **docroot is a separate directory from the git repo
  checkout**. `index.php` calls `usePublicPath()` to point Laravel at the real docroot.
  This is the source of most of the deployment complexity (the `UPLOADS_ROOT` and
  `PUBLIC_DOCROOT` env vars exist purely to cope with it).
- **Git-based auto-deploy**:
  - A push to `main` fires a **GitHub webhook** → hits `public/deploy.php?token=…`
    → runs `deploy.sh` on the host.
  - `deploy.sh` does: `git reset --hard origin/main` → `composer install --no-dev`
    → `php artisan migrate --force` → `npm ci && npm run build` → **rsync `public/build`
    and `public/images` into the separate docroot** → `artisan optimize:clear` +
    `config:cache` + `view:cache`. (`route:cache` is intentionally skipped — closure
    routes.)
  - Migrations run automatically on deploy; **seeders do not** (run by hand).
  - Deploy token is a production secret stored in the server `.env` (`DEPLOY_TOKEN`)
    and the GitHub webhook URL.
- **Known fragilities of the current setup** (things AWS should fix, not reproduce):
  - Symlink fragility (`storage:link`) → why uploads live in the public docroot.
  - Non-reproducible asset builds: `tailwind.config.js` scans
    `storage/framework/views`, so the CSS bundle depends on which Blade views happen to
    be compiled on the build machine. Builds are currently committed/rsynced rather than
    produced deterministically in CI.
  - Secrets have leaked into tracked-adjacent files before (`.deploy.env`,
    `autodeploy.md`); the repo is public (`lordicad/lms`), so secret hygiene is a live
    concern.
  - `migrate --force` on the live server against already-checked-out new code = a
    failed migration leaves new code running on the old schema.

---

## 7. Environment variables (the config surface)

From `.env.example` — this is the full externalised config surface an AWS deployment
must supply (via Secrets Manager / SSM Parameter Store / task env, TBD):

```
APP_NAME, APP_ENV=production, APP_KEY, APP_DEBUG=false, APP_URL
APP_LOCALE=ms, APP_FALLBACK_LOCALE=ms, APP_FAKER_LOCALE=ms_MY
LOG_CHANNEL=stack, LOG_STACK=single, LOG_LEVEL=warning
DB_CONNECTION=mysql, DB_HOST, DB_PORT=3306, DB_DATABASE, DB_USERNAME, DB_PASSWORD
SESSION_DRIVER=file, SESSION_LIFETIME=120, CACHE_STORE=file, QUEUE_CONNECTION=sync
MAIL_MAILER=log, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
TEACHER_REG_CODE, VIDEO_MAX_MB=100, MATERIAL_MAX_MB=30, QUIZ_FILE_MAX_MB=30
UPLOADS_ROOT, PUBLIC_DOCROOT           # split-deployment plumbing — likely obsolete on AWS
GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, YOUTUBE_API_KEY, GOOGLE_REDIRECT_URI
DEPLOY_TOKEN                            # current webhook deploy secret
# Defined but unused today — ready for S3:
AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET,
AWS_URL, AWS_ENDPOINT, AWS_USE_PATH_STYLE_ENDPOINT
```

---

## 8. Sizing & load profile (as far as known)

- **Early-stage.** Database is tiny; user base is not yet at scale (dummy/demo users
  seeded for testing — `dummy-credentials.csv`).
- **Read-heavy** content browsing; write load is teacher uploads + student quiz
  attempts + progress/view tracking.
- **Bandwidth** is the main cost driver: uploaded video streaming with Range requests.
  YouTube-hosting shifts much of this off our infrastructure.
- No current high-availability, autoscaling, or multi-region requirement stated.

---

## 9. Migration decision points (agenda for the planning session)

These are the questions the AWS plan needs to answer. Each has a *candidate* mapping —
treat as a starting point, not a decision.

1. **Compute for the Laravel app.**
   Candidates: single EC2 (closest to today, cheapest), Elastic Beanstalk (PHP
   platform, low-effort), ECS Fargate + ALB (containerised, scalable), or Lightsail
   (simplest managed). Trade-off: how much do we want to invest in containerisation vs.
   lift-and-shift?

2. **Database.** MySQL → **Amazon RDS for MySQL** (or Aurora MySQL). Multi-AZ? Instance
   size given the tiny current dataset? Keep SQLite for local/test only.

3. **File storage / uploads.** Move the `uploads` disk to **S3** (already wired in
   config). Must preserve: (a) **HTTP Range requests** for video seeking → serve via
   **CloudFront** in front of S3; (b) the **script-execution block** that `.htaccess`
   currently provides; (c) signed vs. public access model for materials/videos.
   Decide: public-read + CloudFront, or private + signed URLs?

4. **Static assets (`public/build`).** Serve via S3+CloudFront, or bake into the app
   image/instance? This also lets us fix the non-reproducible build (build in CI once,
   not per-host).

5. **Sessions & cache.** If we go multi-instance, `file` no longer works → move to
   **ElastiCache (Redis)** or DB-backed. If single-instance, defer.

6. **Queue / background work.** Currently `sync`. If we introduce real email, report
   generation, or heavier processing, plan **SQS + a worker** (ECS service or
   `queue:work` on EC2). Otherwise keep `sync` for now.

7. **Email.** `log` today. Real credential emails (`AccountCredentialsMail`) suggest we
   will want **Amazon SES**.

8. **Secrets & config.** Replace the `.env`-on-server model with **Secrets Manager** or
   **SSM Parameter Store**. Removes the public-repo secret-leak risk.

9. **Deployment pipeline.** Replace the webhook-`deploy.sh`-rsync model with **CodePipeline/
   CodeBuild/CodeDeploy** or **GitHub Actions → ECR/ECS**. This fixes: split-docroot
   plumbing, non-reproducible builds, and `migrate --force` risk (run migrations as a
   controlled pipeline step, not inline on the web host).

10. **DNS / TLS.** `lms-moe.weststar-dev.com` → Route 53 + ACM certificate on the
    ALB/CloudFront. **Update the Google/YouTube OAuth redirect URI** to the new domain
    if it changes.

11. **Logging & monitoring.** Local `single` file → **CloudWatch Logs**; add health
    checks and alarms.

12. **Mobile app impact.** The Flutter app hard-points at the API base URL. Confirm
    whether the domain changes; if so, ship a mobile update or use a stable custom
    domain to avoid it.

---

## 10. What makes this migration *low-risk*

- The app is a **standard Laravel 12 monolith** with a small schema and no exotic
  infrastructure dependencies (no Redis, no queue worker, no cron, no message broker
  today).
- The **S3 disk is already configured** — uploads can move with a config change plus a
  data copy.
- Stateless-ready: the only server-local state is sessions/cache (`file`) and the
  uploads directory. Both have clean AWS targets.
- The whole config surface is externalised through `.env` (§7).

## 11. What needs care

- **Video Range-request serving** must survive the move (CloudFront/S3).
- **OAuth redirect URIs** must be re-registered for any new domain.
- **Reproducible front-end builds** (the Tailwind `storage/framework/views` glob) should
  be fixed as part of moving builds into CI.
- **Secret management** must not repeat the public-repo leakage pattern.
- **Migration execution** must move out of the inline web-deploy path into a controlled
  pipeline step.
- **Data migration**: copy MySQL → RDS (dump/restore or DMS) and `public/uploads/*` →
  S3, with a cutover plan.

---

*Prepared as an input for AWS migration planning. Reflects the repository state on the
`main` branch. Verify current production `.env` values and actual data volumes with the
ops owner before finalising instance sizing.*
