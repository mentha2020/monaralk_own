# Monaralk

A car listing website: a public storefront for browsing, filtering, comparing and enquiring about vehicles, a self-service listing wizard for sellers, and a Filament admin panel for managing the catalogue, leads and submissions.

Built with **Laravel 12** + **Filament 3**, server-rendered with **Livewire 3** and **Tailwind CSS v4**. English and Sinhala (`en` / `si`) throughout, prices in LKR.

---

## Requirements

| | |
|---|---|
| PHP | `^8.3` (GD with `imagewebp` for the image pipeline) |
| Database | **MySQL 8.4** (or MariaDB 10.6+ — note `ONLY_FULL_GROUP_BY` is kept on) |
| Node | 20+ (Vite 6 / Tailwind 4) |
| Composer | 2.x |

## Getting started

```bash
git clone https://github.com/mentha2020/monaralk_own.git
cd monaralk

composer install
npm ci

cp .env.example .env
php artisan key:generate

# point DB_* at a MySQL 8.4 schema, then:
php artisan migrate --seed

npm run build
php artisan storage:link
```

The seeder creates roles, settings, lookup tables, pages, sample vehicles and three staff accounts — `admin@monaralk.lk` (`super_admin`), `editor@monaralk.lk` and `viewer@monaralk.lk`, all with the password `password`.

## Development

```bash
php artisan serve              # http://localhost:8000
npm run dev                    # Vite / Tailwind hot rebuild
php artisan queue:work         # required: image variants are queued
php artisan pail               # tail the log
```

`composer dev` runs all four together.

> Re-run `npm run build` after editing any Blade view — Tailwind 4 scans templates at build time, so new utility classes do not appear until the CSS is rebuilt.

## Testing

```bash
php artisan test               # full Pest suite
php vendor/bin/pint            # format
php vendor/bin/pint --test     # CI-style check
```

The suite runs against a **separate `monaralk_test` schema** (see `phpunit.xml`), uses `array` session/cache/mail drivers and disables the catalogue listing cache so cache behaviour is asserted explicitly.

## Production

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize          # config:cache + route:cache + view:cache
php artisan queue:work --tries=3
```

Set `APP_ENV=production`, `APP_DEBUG=false`, serve over HTTPS, and schedule `php artisan schedule:run` from cron.

Optional CDN: set `CDN_URL` (uploaded images) and `ASSET_URL` (built assets) — both fall back to `APP_URL` when blank.

## Project structure

```
app/Modules/
  Catalog/      vehicles, makes/models, images, search + listing cache
  Leads/        buyer enquiries
  Submissions/  public "sell your car" wizard + moderation
  Accounts/     users, favourites, compare, saved searches
  Settings/     site settings, static pages, roles/permissions
  Shared/       security headers, locale, sitemap, contact rules
app/Livewire/   storefront + wizard components
resources/views public templates (components/, vehicles/, accounts/, …)
routes/         web.php · auth.php · console.php
database/       22 migrations · factories · seeders
tests/Feature/  Pest suites (Storefront, Admin, Security, Localization, …)
docs/phases/    one report per build phase
```

## Documentation

The build is delivered phase by phase; `ROADMAP.md` tracks scope, sequence and status, and each completed phase has a report under [`docs/phases/`](docs/phases/).

Admin panel lives at `/admin`; customer dashboard at `/dashboard`.

## License

MIT.
