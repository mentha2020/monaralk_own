# Deploy Checklist

Run this before and after every production deploy. Items marked **gate** must
pass or the deploy is rolled back.

Automated coverage: `tests/Feature/Security/SecurityHeadersTest.php` asserts the
production-only HSTS behaviour, and `tests/Feature/Qa/PerformanceBudgetTest.php`
asserts first-load weight. Everything else here is a manual gate.

---

## 1. Build artifacts (build machine)

- [ ] `git pull` is clean and at the intended commit
- [ ] `npm ci` completes
- [ ] `npm run build` completes and `public/build/manifest.json` is written
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan test` is green (326 tests) — **gate**
- [ ] `vendor/bin/pint --test` reports no changes — **gate**
- [ ] Build artifacts (`public/build`, `vendor/`, `node_modules/`) are packaged
      or available to the server; `public/build` is gitignored and is **not**
      pulled from git

## 2. Environment

- [ ] `APP_ENV=production` — **gate**
- [ ] `APP_DEBUG=false` — **gate**
- [ ] `APP_KEY` is set and identical across all app servers (`php artisan
      key:generate --show` on one machine only)
- [ ] `APP_URL` is the public `https://` URL
- [ ] `ASSET_URL` / `CDN_URL` point at the served asset origin, or are unset so
      `APP_URL` is used
- [ ] `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` point at the
      production database, not `monaralk` or `monaralk_test`
- [ ] `CACHE_STORE=file` — **do not** switch to `database`; the listing cache
      writes on every publish and the database cache store measured 40-114
      cache queries per request (Phase 16)
- [ ] `SESSION_DRIVER=database`
- [ ] `QUEUE_CONNECTION=database`
- [ ] `MAIL_MAILER` is a real transport (not `log`); `MAIL_FROM_ADDRESS` is a
      deliverable address on your domain
- [ ] `CATALOG_CACHE_ENABLED=true` and `CATALOG_CACHE_TTL` is set as desired

## 3. TLS and headers

- [ ] Certificate is valid and covers the apex and `www` if both are served
- [ ] HTTP redirects to HTTPS at the load balancer or web server
- [ ] `php artisan tinker` then `config('app.env')` reads `production` — the
      `SecurityHeaders` middleware only emits HSTS when the request is secure
      **and** the app is in production, so verify over a real HTTPS request
- [ ] `curl -I https://…/` returns `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`,
      `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`
      — **gate**
- [ ] `curl -I https://…/definitely-missing` returns the same headers on the 404

## 4. Storage and queue

- [ ] `php artisan storage:link` has been run and `public/storage` resolves
- [ ] `storage/app/public` and `storage/framework/*` and `storage/logs` are
      writable by the web user
- [ ] A queue worker is running under a process manager and is supervised,
      e.g. `php artisan queue:work --sleep=1 --tries=3 --max-time=3600`
- [ ] `php artisan queue:restart` has been issued after deploying new code
- [ ] The `schedule:run` cron entry fires every minute:
      `* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1`

## 5. Database

- [ ] `php artisan migrate --force` — **gate**
- [ ] A restore was tested from the latest backup within the last 7 days
- [ ] Backup job is scheduled and its last run succeeded

## 6. Optimise and verify

- [ ] `php artisan optimize` (config:cache, route:cache, view:cache) — **gate**
- [ ] `php artisan about` shows the expected environment and drivers
- [ ] `GET /` returns 200 — **gate**
- [ ] `GET /vehicles` returns 200 and shows listings
- [ ] `GET /sitemap.xml` and `GET /robots.txt` return 200 — **gate**
- [ ] `GET /admin` redirects to `/admin/login`, and the panel loads with no
      missing CSS (a stale `public/build` hash is the usual cause)
- [ ] `GET /definitely-missing` returns the branded 404 with the security headers
- [ ] Submit a test enquiry through `/contact` and confirm it appears in the
      admin enquiries list and the team notification is delivered
- [ ] Submit a test car through `/submit`, approve it in the admin, publish it
      and confirm it appears in storefront search
- [ ] Switch the header language control to `si` and confirm Sinhala renders
      without tofu boxes
- [ ] Toggle dark mode and confirm it persists across a reload
- [ ] Download a vehicle spec-sheet PDF from a detail page

## 7. Post-deploy

- [ ] Real-user smoke on a phone at 375px on cellular
- [ ] `php artisan cache:clear` only if a stale listing was observed; the
      observer invalidation normally makes this unnecessary
- [ ] Error tracking / log shipping is receiving events
- [ ] Announce or ticket the deploy and record the commit SHA

## Rollback

1. `php artisan queue:stop` (or stop the supervisor program)
2. Re-deploy the previous known-good commit's `public/build` and `vendor/`
3. `php artisan migrate:rollback --step=1` only if the deploy included a
   migration; otherwise leave the database alone
4. `php artisan optimize`
5. `php artisan queue:restart`
6. Re-run section 6
