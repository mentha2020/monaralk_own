# Phase 0 — Inspection & Environment Baseline

**Status:** ✅ Complete
**Date:** 2026-10-08
**Workspace:** `D:\My_Project\Laravel\monaralk`
**Result:** Zero blockers. Cleared to start Phase 1.

---

## 0.1 Toolchain

| Tool | Version | Status |
|---|---|---|
| PHP | 8.3.30 (ZTS, Visual C++ 2019 x64) | ✅ meets `^8.2` / `^8.3` requirements |
| Composer | 2.10.1 (`C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`) | ✅ |
| Node.js | v24.17.0 | ✅ |
| npm | 11.13.0 | ✅ |
| Git | 2.55.0.windows.3 | ✅ |
| OS / arch | Windows 11 (26200), 64-bit | ✅ |
| PHP memory_limit | 512M, `max_execution_time` 0 (CLI) | ✅ |
| `upload_max_filesize` / `post_max_size` | 2G / 2G | ✅ ample for vehicle photos |

### PHP extensions

All required extensions **present**:

`pdo_mysql` `mysqlnd` `mysqli` `gd` `zip` `fileinfo` `mbstring` `exif` `curl` `openssl` `bcmath` `intl` `dom` `session` `tokenizer` `xml` `simplexml` `xmlreader` `xmlwriter` `ctype` `filter` `json` `sodium` `pdo_sqlite` `xsl` `calendar` `iconv`

Notable: `gd` ✅ (needed for `VehicleImageService` image variants), `exif` ✅ (image metadata), `zip` ✅ (Excel/PDF), `bcmath` ✅ (finance calculations).

Also present: `pdo_pgsql`, `pgsql`, `sqlite3` — not required, no conflict.

### PHP config

```
date.timezone         = UTC
default_charset       = UTF-8
error_reporting       = 32767 (E_ALL)
display_errors        = 1  (CLI only — production must set APP_DEBUG=false)
```

---

## 0.2 Database

**Correction to the pre-phase assumption:** the server on `127.0.0.1:3306` is **MySQL 8.4.3 Community Server**, *not* MariaDB 10.4.32. An earlier probe during the planning session returned `10.4.32-MariaDB`; Laragon was switched to the MySQL 8.4 instance afterwards (two `mysqld` processes started 2026-10-08 23:15:42). Current authoritative state:

```
version       = 8.4.3 (MySQL Community Server - GPL)
port          = 3306
basedir       = C:\laragon\bin\mysql\mysql-8.4.3-winx64\
datadir       = C:\laragon\data\mysql-8.4\
charset_server = utf8mb4
collation_server = utf8mb4_0900_ai_ci
os            = Win64
transaction_isolation = REPEATABLE-READ
max_allowed_packet    = 512M
sql_mode   = ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,
             NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
```

| Check | Result |
|---|---|
| TCP `127.0.0.1:3306` reachable | ✅ |
| Listening process | `mysqld.exe` (Laragon), pid 13380 |
| Auth | `root@localhost`, **no password**, full `GRANT OPTION` (can CREATE / DROP databases) |
| Existing databases | 50 (unrelated to this project) |
| `monaralk` database exists | ❌ **not yet** — created in Phase 1.2 |
| Laragon running | ✅ pid 2064 |

**Implication for the stack:** MySQL 8.4 rather than MariaDB 10.4.
- ✅ Native `utf8mb4_0900_ai_ci`, JSON columns, functional indexes, CTEs, window functions — all available.
- ✅ Full-text indexes (`InnoDB`) available if we later promote search beyond `LIKE`.
- ⚠️ MySQL 8.4 removed `mysql_native_password` by default → Laravel connects via `pdo_mysql`/`caching_sha2_password`, which is supported. No action needed.
- ⚠️ `ONLY_FULL_GROUP_BY` is ON — all aggregate queries must select only grouped columns or use aggregates. This is a **good** constraint; it will be respected in the `VehicleQueryService`.
- `.env` will set `DB_CONNECTION=mysql`, `DB_PORT=3306`, `DB_DATABASE=monaralk`, charset `utf8mb4`, collation `utf8mb4_0900_ai_ci`.

---

## 0.3 Package compatibility (pinned versions all exist)

| Package | Pinned | Exists | PHP | Laravel |
|---|---|---|---|---|
| `laravel/framework` | 12.69.3 | ✅ | ^8.2 | — |
| `filament/filament` | 3.3.56 | ✅ | ^8.1 | `illuminate ^10.45\|^11\|^12\|^13`, needs `livewire ^3.5` |
| `laravel/breeze` | 2.4.2 | ✅ | ^8.2 | `illuminate ^11\|^12\|^13` |
| `spatie/laravel-permission` | 7.4.2 | ✅ | ^8.3 | `illuminate ^12\|^13` |
| `maatwebsite/excel` | 4.0.3 | ✅ | ^8.3 | `illuminate ^12\|^13` |
| `barryvdh/laravel-dompdf` | 3.1.2 | ✅ | ^8.1 | `illuminate ^9..^13` |
| `spatie/laravel-sitemap` | **7.4.0** | ✅ | ^8.2 | `illuminate ^11\|^12\|^13` |
| `pestphp/pest` | 4.6.0 | ✅ | ^8.3 | — |
| `pestphp/pest-plugin-laravel` | 4.1.0 | ✅ | ^8.3 | `laravel ^11.45.2\|^12.52\|^13` |
| `livewire/livewire` | 3.8.10 | ✅ | — | Filament pulls `^3.5` |
| `laravel/tinker` | 3.0.2 | ✅ | — | — |

Latest-available majors that must be **avoided** (conflict notes retained):

| Package | Latest | Why not |
|---|---|---|
| `laravel/framework` | 13.35.0 | Brief is Laravel **12** |
| `filament/filament` | 5.10.1 | Brief is Filament **3** |
| `spatie/laravel-permission` | 8.3.0 | Requires PHP 8.3 **and** a newer illuminate baseline than we pin — use `^7.4` |
| `spatie/laravel-sitemap` | 8.2.0 | Requires **PHP ^8.4** (we have 8.3) → pin `^7.4` |
| `pestphp/pest` | 5.3.1 | Requires PHP ^8.4 / Laravel 13 → pin `^4.1` |
| `livewire/livewire` | 4.4.7 | Filament 3.3 requires `^3.5` → resolves to 3.8.x |

Frontend (npm):

| Package | Version | Status |
|---|---|---|
| `tailwindcss` | 4.3.3 | ✅ v4 as briefed |
| `@tailwindcss/vite` | 4.3.3 | ✅ |
| `laravel-vite-plugin` | 3.2.0 | ✅ |
| `vite` | 8.3.4 | ✅ |

---

## 0.4 Workspace inspection

| Check | Result |
|---|---|
| Directory exists | ✅ `D:\My_Project\Laravel\monaralk` |
| Contents at start | **empty** — no Laravel app, no code to overwrite ✅ |
| Contents now | `ROADMAP.md` only (written this phase) |
| Git repository initialized | ❌ no (Phase 0.5 / git bootstrap) |
| `composer.json` present | ❌ no |
| `.env` present | ❌ no |
| Existing migrations/models | ❌ none — no reuse candidates |

**Conclusion:** greenfield install. Nothing can be clobbered.

---

## 0.5 Conflicts register (carried into Phase 1)

| # | Conflict | Resolution | Phase |
|---|---|---|---|
| A | Breeze installs Tailwind **v3** config (`tailwind.config.js`, `postcss.config.js`, `@tailwind` directives, `tailwindcss ^3.1.0`) | Migrate to TW4: `@tailwindcss/vite`, `@import 'tailwindcss'`, `@source`, `@custom-variant dark` for Breeze's `.dark` class | 1.5 |
| B | Breeze bundles its own Alpine → collides with Livewire 3's bundled Alpine (Filament requires Livewire) | Strip Alpine from `resources/js/app.js`; `\Livewire\Livewire::forceAssetInjection()` | 1.6 |
| C | Filament custom theming needs a Tailwind v3 build | Ship Filament's default precompiled theme; defer custom theming | v1 |
| D | `maatwebsite/excel ^4.0` is a recent major | Fall back to `^3.1.70` if its API surprises | 1.3 |
| E | Facebook cannot post a bare image | Share links the listing URL; `og:image` supplies the preview | 10.2 |
| **F** | **DB is MySQL 8.4.3, not MariaDB 10.4** | Use `utf8mb4_0900_ai_ci`; honour `ONLY_FULL_GROUP_BY` in all queries | 1.2 / 2 |

---

## 0.6 Git / GitHub readiness

| Check | Result |
|---|---|
| Git installed | ✅ 2.55.0 |
| Repo initialized in project | ❌ not yet |
| Global `user.name` / `user.email` | ❌ **not configured** |
| Global `credential.helper` | ❌ not configured |
| `gh` CLI installed | ❌ **not installed** |
| `GH_TOKEN` / `GITHUB_TOKEN` env var | ❌ not set |
| github.com:443 reachable | ✅ |

**Open items for the first push** (needed before Phase 0 can be pushed):
1. Git identity (`user.name`, `user.email`) — will be set locally for this repo.
2. Authentication — one of: `gh` CLI login, a `GITHUB_TOKEN`, or Windows Credential Manager via `git credential-manager`.
3. Remote repository must exist.

---

## 0.7 Exit criteria

| Criterion | Status |
|---|---|
| Toolchain verified | ✅ |
| Database reachable with privileges to create schema | ✅ |
| All pinned packages exist and resolve under PHP 8.3 / Laravel 12 | ✅ |
| Workspace empty — no working functionality to damage | ✅ |
| Conflicts documented with a resolution + owning phase | ✅ |
| Zero blockers | ✅ |

**Phase 0 complete. Phase 1 (Scaffold & Stack Bootstrap) cleared to begin.**
