# Phase 1 — Scaffold & Stack Bootstrap

**Status:** ✅ Complete
**Date:** 2026-10-08
**Workspace:** `D:\My_Project\Laravel\monaralk`
**Result:** Full stack installed and green. Conflicts A and B resolved. Cleared to start Phase 2.

---

## 1.1 Laravel 12 scaffold

| Check | Result |
|---|---|
| `composer create-project laravel/laravel` | ⚠️ pulled **Laravel 13.35.0** — rejected, the brief is Laravel 12 |
| Retry with `laravel/laravel:^12.0` into temp dir | ✅ `monaralk_scaffold/`, temp dir removed after merge |
| Installed framework | ✅ **laravel/framework 12.69.3** |
| Skeleton ships Tailwind v4 already | ✅ `@tailwindcss/vite` + `@import 'tailwindcss'` + `@source` + `@theme` |
| PHP requirement raised to | `^8.3` (matches runtime 8.3.30) |
| `php artisan about` | ✅ Monaralk / 12.69.3 / PHP 8.3.30 / mysql |

---

## 1.2 Database wiring

| Item | Value |
|---|---|
| Databases created | `monaralk` (dev), `monaralk_test` (test, added in 1.8) |
| Server | MySQL **8.4.3**, `127.0.0.1:3306`, `root`, no password |
| `DB_CHARSET` / `DB_COLLATION` | `utf8mb4` / `utf8mb4_0900_ai_ci` |
| `APP_KEY` | ✅ generated |
| `php artisan migrate` | ✅ `cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`, `migrations`, `password_reset_tokens`, `sessions`, `users` |
| `migrate:status` | ✅ all Ran (batch 1) |

---

## 1.3 Composer packages

Installed by editing `composer.json` directly then `composer update` — see *Gotchas* below.

| Package | Constraint | Installed |
|---|---|---|
| `filament/filament` | `^3.3` | **3.3.56** |
| `laravel/breeze` | `^2.4` | **2.4.2** |
| `spatie/laravel-permission` | `^7.4` | **7.4.2** |
| `maatwebsite/excel` | `^4.0` | **4.0.3** (conflict D did **not** materialise) |
| `barryvdh/laravel-dompdf` | `^3.1` | **3.1.2** |
| `spatie/laravel-sitemap` | `^7.4` | **7.4.0** |
| `livewire/livewire` | (via Filament) | **3.8.10** |
| `pestphp/pest` (dev) | `^4.1` | **4.7.8** |
| `pestphp/pest-plugin-laravel` (dev) | `^4.1` | **4.1.0** |
| `phpunit/phpunit` | — | **12.5.33** (pulled by Pest) |

Removed from `require-dev`: `phpunit/phpunit ^11.5.3` — Pest 4 requires PHPUnit 12, the two constraints conflict.

`composer update`: ✅ exit 0, 112 packages, **no security advisories**.

---

## 1.4 Breeze (Blade)

`php artisan breeze:install blade` ✅ — published Blade views/controllers, `tests/Feature/Auth/*`, and Pest-style `tests/Pest.php`.

**⚠️ It silently overwrote five things** (all fixed in 1.5 / 1.6): `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`, `package.json`, and it created `tailwind.config.js` + `postcss.config.js`.

---

## 1.5 Conflict A resolved — Tailwind v4 restored

| File | Change |
|---|---|
| `vite.config.js` | re-added `import tailwindcss from '@tailwindcss/vite'` + `tailwindcss()` plugin |
| `resources/css/app.css` | `@import 'tailwindcss'` · `@custom-variant dark (&:where(.dark, .dark *))` · `@plugin "@tailwindcss/forms"` · 4× `@source` globs |
| `tailwind.config.js` | 🗑 deleted (TW4 config lives in CSS) |
| `postcss.config.js` | 🗑 deleted (vite plugin handles it; the TW3 `tailwindcss` plugin would break the build) |
| `package.json` | `tailwindcss ^4.0.0`, `@tailwindcss/forms ^0.5.11`, dropped `alpinejs` / `autoprefixer` / `postcss` / `concurrently` |

`@custom-variant dark` is required — Breeze's Blade stack uses `dark:` with a **`.dark` class** strategy, while TW4's default is `prefers-color-scheme`. 22 `dark:` usages confirmed in `resources/views`.

Installed: `tailwindcss 4.3.3`, `@tailwindcss/forms 0.5.11`, `@tailwindcss/vite 4.3.3`, `vite 6.4.4`.

---

## 1.6 Conflict B resolved — single Alpine

- `resources/js/app.js` → now only `import './bootstrap';`. No Alpine.
- Breeze's views still use `x-data` / `x-init` / `@click.outside` (navigation, dropdown, modal, profile forms) — these are driven by **Livewire 3's** bundled Alpine.
- `Livewire::forceAssetInjection()` added to `AppServiceProvider::boot()` so Livewire injects its script on layouts that lack `@livewireScripts`.

**Why not `bootstrap/app.php`:** `Application::configure()->create()` returns before the `RegisterFacades` bootstrapper runs → `Fatal error: A facade root has not been set`.

**Evidence it worked:** `npm run build` JS bundle **107.37 kB → 51.52 kB**.

---

## 1.7 Filament 3

`php artisan filament:install --panels` ✅

- `app/Providers/Filament/AdminPanelProvider.php` created
- 19 asset files (2.11 MB) published to `public/js/filament` + `public/css/filament` — **committed** (precompiled default theme; Conflict C deferred by design)
- Route cache / config cache / compiled views cleared

---

## 1.8 Pest baseline

Added `pestphp/pest ^4.1` + `pestphp/pest-plugin-laravel ^4.1`; Breeze's scaffolding already emitted Pest syntax.

**Test database:** `RefreshDatabase` is bound to the `Feature` suite in `tests/Pest.php`, so running against `monaralk` would wipe dev data. Created a separate **`monaralk_test`** MySQL database and pointed `phpunit.xml` at it:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="monaralk_test"/>
```

MySQL (not in-memory SQLite) is deliberate: it preserves production parity for `ONLY_FULL_GROUP_BY`, `STRICT_TRANS_TABLES`, the `utf8mb4_0900_ai_ci` collation and index types — see conflict F.

---

## 1.9 Build + verify

| Check | Result |
|---|---|
| `npm run build` | ✅ 58 modules, CSS 56.20 kB, JS 51.52 kB, built in 1.15s |
| `npm audit` | ✅ **0 vulnerabilities** (was 7 under Breeze's deps) |
| `php artisan migrate` | ✅ Nothing to migrate |
| `php artisan test` | ✅ **25 passed** (61 assertions), 14.98s |

### Live HTTP verification (`php artisan serve`)

| Route | Status | Notes |
|---|---|---|
| `/up` | 200 | health |
| `/` | 200 | Vite assets ✅, Livewire injected ✅ |
| `/login` | 200 | title **Monaralk**, Vite ✅, Livewire ✅ |
| `/admin` | 302 | → `http://127.0.0.1:8011/admin/login` (guard working) |
| `/admin/login` | 200 | Filament ✅, Livewire ✅ |

---

## Gotchas hit (carry forward)

1. **PowerShell strips `^` from native-command arguments.** `composer require "pkg:^3.3"` becomes `pkg:3.3` and fails. **Always write the constraint into `composer.json`, then run `composer update`.**
2. `barryvdh/laravel-dompdf 3.1.0` only allows `illuminate ^9|^10|^11`; **3.1.2** allows `^9..^13`. A bare `^3.1` resolved to a satisfying version after `composer update`.
3. `Livewire::forceAssetInjection()` **cannot** run inside `bootstrap/app.php`.
4. `@tailwindcss/forms` has **no 0.6.x** — latest is **0.5.11**, and it declares `tailwindcss >=4.0.0-alpha.20` so it *is* TW4-compatible.
5. **Never re-run `breeze:install`** — it re-writes `vite.config.js`, `app.css`, `app.js`, `package.json` and re-creates the TW3 configs.

---

## Exit criteria

| Criterion | Status |
|---|---|
| Laravel 12 scaffolded, `php artisan about` clean | ✅ |
| MySQL `monaralk` DB created and migrated | ✅ |
| All pinned packages installed, no advisories | ✅ |
| Breeze Blade auth installed | ✅ |
| Conflict A resolved — TW4 build works | ✅ |
| Conflict B resolved — single Alpine, Livewire injects | ✅ |
| Filament panel live at `/admin` | ✅ |
| Pest green | ✅ **25 passed** |
| Build clean, 0 npm vulnerabilities | ✅ |

**Phase 1 complete. Phase 2 (Architecture Foundation & Database) cleared to begin.**
