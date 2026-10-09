# Phase 16 — Performance, Hardening & Docs

**Status:** ✅ Complete
**Date:** 2026-10-09
**Result:** The storefront now serves filtered listings and its filter option lists out of a **versioned, observer-invalidated cache**, every listing page holds its query count constant regardless of row count, the two admin list screens that were filesorting gained covering indexes, image variants are **queued** and ship **WebP**, and every response — including unknown-route 404s — carries a full security header + CSP stack. Auth and the submit wizard are rate-limited, uploads are MIME/size validated, and `.env.example` + `README.md` are now genuinely project-specific. **237 tests passing** (1730 assertions), Pint clean.

---

## 16.1 Caching

`config/catalog.php` exposes `catalog.cache.enabled` (`CATALOG_CACHE_ENABLED`, default `true`) and `catalog.cache.ttl` (`CATALOG_CACHE_TTL`, default `300`).

`app/Modules/Catalog/Support/CatalogCache.php` wraps `Cache::remember` / `rememberForever` with a **version key** (`catalog.cache.version`, stored forever). Every key embeds that version:

```
catalog.{segment}.{version}.{md5(payload)}
```

`invalidate()` simply writes a new `uniqid()` version — all keys for the previous version become unreachable in one write, with no key enumeration and no `flush()`.

`app/Modules/Catalog/Observers/CatalogCacheObserver.php` hooks `saved` / `deleted` on `Vehicle`, `VehicleImage`, `Make`, `VehicleModel`, `BodyType`, `FuelType`, `Transmission`, `Color` and `Feature` (registered in `ModulesServiceProvider::registerObservers()`), so any catalogue change bumps the version.

Cached:

| Segment | Where | TTL |
|---|---|---|
| `search` | `VehicleSearch::vehicles()` — the filtered, paginated result set | `catalog.cache.ttl` |
| `options` | makes, body types, fuel types, transmissions, colors, model options, location options, year bounds | forever |
| `wizard-options` | the same option lists for `SubmitVehicleWizard::render()` | forever |

`VehicleSearch` now computes `page` itself (`max(1, (int) $this->getPage())`) and passes it explicitly to `->paginate(self::PER_PAGE, ['*'], 'page', $page)`, so the cache key captures the page instead of depending on the request inside `paginate()`. The hydrated `LengthAwarePaginator` is re-bound with `->withQueryString()` after the cache returns.

`phpunit.xml` sets `CATALOG_CACHE_ENABLED=false` so the rest of the suite stays deterministic; cache behaviour is asserted explicitly in `SearchCacheTest`.

### Why `CACHE_STORE=file`, not `database`

The first live run with `CACHE_STORE=database` produced **40–114 `cache` queries per request** with writes up to 52 ms — every `Cache::get`/`put` became a round trip, and the cache store's own writes invalidated the "N+1 invariance" we were trying to prove. `.env` now uses `CACHE_STORE=file` (`.env.example` matches). The array store keeps `RefreshDatabase` and the throttle tests isolated, because the array store is per-app-instance.

## 16.2 Eager-loading audit

`Vehicle::scopeFilter()` already carried `->with([...])` for every relation the listing touches (make, model, body type, fuel, transmission, colour, images, features), and `HomeController`, `VehicleController`, `VehicleSpecSheet` and `SavedVehicleController` were already eager. **No controller changes were required.**

What was missing was proof. `tests/Feature/Storefront/NPlusOneTest.php`:

- **query-count invariance** — the same request with 6 vs 20 rows runs the same number of queries;
- **paging invariance** — page 1 and page 2 run the same count (a warm-up request runs first so one-time cache/settings writes don't skew the comparison), and page 2 actually contains the 13th row.

`tests/Feature/Storefront/SearchCacheTest.php` proves the cache turns warm: a result-query detector (queries starting `select * from vehicles`, excluding `count(*)` and the `distinct location` option query) asserts **cold = 1, warm = 0, after publishing a new listing = 1**, and that the cache can be switched off.

## 16.3 Query log review → indexes

`p16_query_log.php` kernel-handled every storefront/admin route and logged each query plus its `EXPLAIN`. The catalogue already had the indexes it needed — `(status,published_at)`, `(make_id,model_id)`, year/price/mileage/condition/is_featured/location/vin/reg/created_at on `vehicles` (the full scan in `EXPLAIN` on `vehicles` was a 12-row table cost, not a missing index).

Two genuinely missing ones, added in `2026_10_09_130000_add_list_sort_indexes.php`:

| Table | Index | Why |
|---|---|---|
| `enquiries` | `(created_at)` | `EnquiryResource` default-sorts `created_at desc` with no status filter → filesort on every admin open |
| `vehicle_submissions` | `(status, created_at)` | `SubmissionResource` default-sorts the same way, filtered by status |

`SubmitVehicleWizard::summary()` also had a micro N+1: it looked up every option name with a full find. A new `optionName(string $model, mixed $id)` runs `whereKey()->value('name')` only when `filled($id)`.

## 16.4 Queue + `storage:link`

`app/Jobs/GenerateVehicleImageVariants.php` (`ShouldQueue`, `SerializesModels`, `deleteWhenMissingModels = true`) runs `VehicleImageService::generateVariants()`. `VehicleImageObserver` now dispatches it on `created`/`updated` instead of generating inline, and the `updated()` old-file cleanup also removes `path_og`.

Two constraints worth remembering:

- **Never use `dispatch-afterCommit` here.** `RefreshDatabase`'s wrapping transaction never commits, so the job would silently never run in tests. The DB-queue atomicity already comes from `store()`'s own DB transaction.
- **Sync in tests.** `phpunit.xml` keeps `QUEUE_CONNECTION=sync`, so the job runs inline and `VehicleImageTest` can assert the real WebP bytes.

`storage:link` was already in place. The image pipeline is now queued, which is why `README.md` documents `php artisan queue:work`.

## 16.5 Security

**Headers** — `app/Http/Middleware/SecurityHeaders.php` sets `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Permitted-Cross-Domain-Policies: none`, `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()` and a CSP. HSTS and `upgrade-insecure-requests` only when `isSecure() && isProduction()`; `connect-src` opens `ws://localhost:* wss://localhost:*` only in local.

The CSP allows the app plus `fonts.bunny.net` / `fonts.googleapis.com` / `fonts.gstatic.com` for styles and fonts, blocks `object-src`, and keeps `frame-ancestors 'self'`. **`script-src 'self' 'unsafe-inline'` is deliberate**: Livewire and Filament both inject inline scripts and neither supports nonces, so a nonce would break the app — `'unsafe-inline'` still blocks *external* script origins, which is the actual XSS vector on a listing site.

**Registration matters (Gotcha 1).** The middleware was first appended to the `web` group, and unknown-route 404s came back **without any header** — a 404 thrown during routing never enters a route's middleware stack. It is now appended to the **global** stack in `bootstrap/app.php`, so it wraps the router and covers 404/419/429, Filament assets and `/up` alike. Verified live: `/`, `/vehicles`, `/submit`, `/sitemap.xml`, `/robots.txt` and `/definitely-missing` all carry the full set.

**Rate limits** — `routes/auth.php` adds `throttle:5,1` to register/login, `throttle:3,1` to forgot-password and `throttle:5,1` to reset-password. The submit wizard gained `RATE_LIMIT = 5` / `RATE_WINDOW = 600` per IP via `RateLimiter`, checked **after** the honeypot and **before** validation so a throttled user sees a form error, not a crash. A branded `resources/views/errors/429.blade.php` matches the existing 404/500 pages.

**Honeypot** — the wizard gained `public string $website = ''` rendered off-screen at `-left-[9999px]`. It is deliberately **not** validated; `submit()` reads it first and, when filled, returns `fakeSuccess()` (a plausible `SUB-XXXXXXXX` reference, `submissionId = 0`) without touching the database — so a bot cannot tell whether it worked.

**Upload validation** — `photos.*` was already `mimes:jpg,jpeg,png,webp` + `max:5120`, but nothing proved it. `tests/Feature/Submissions/UploadSecurityTest.php` covers three cases: a PHP script wearing a `.jpg` extension, a photo over the size limit, and a real photo that must still pass.

**XSS audit** — every `{!! !!}` in the views was re-checked (raw inline sections, JSON-LD with `JSON_HEX_TAG`, component props). Nothing raw can carry admin- or user-entered markup.

**Secrets** — `git ls-files` shows only `.env.example`; `.env` is gitignored (`.gitignore:10`) and `git grep` finds no `APP_KEY` in tracked files.

### Gotcha 2 — a non-previewable upload 500'd the wizard

`Livewire`'s temp-upload endpoint validates only `required|file|max:12288` — **no MIME check** — and `SubmitVehicleWizard::updated()` does not validate `photos`. So a PHP file uploaded as `payload.jpg` landed in `$photos` and the view called `$photo->temporaryUrl()` unconditionally, which throws `FileNotPreviewableException` → a **500 before `next()` could ever show the validation error**. `Testing\File::getMimeType()` also reports from the *filename*, so `UploadedFile::fake()->createWithContent('payload.jpg', ...)` claims `image/jpeg` and the fake sails through `mimes`.

Fixed in two layers:

1. `SubmitVehicleWizard::photoPreviewUrl(mixed $photo): ?string` returns `null` unless the object has `temporaryUrl()` **and** reports `isPreviewable()`; both `<picture>` preview sites now render a neutral placeholder div instead of an `<img>` when it is `null`.
2. The test sets `$file->mimeType('text/x-php')` — exactly what `finfo` reports for real PHP source — so `guessExtension()` returns `null` and `mimes` rejects it. (Verified: `text/x-php` is not in Symfony's MIME map, so the extension resolves to `NULL` and `in_array(null, ['jpg','jpeg','png','webp'])` is false.)

Production is therefore protected at validation time, and the page degrades gracefully rather than 500ing regardless.

## 16.6 Image pipeline — WebP + CDN

Migration `2026_10_09_120000_add_webp_variants_to_vehicle_images_table` adds nullable `path_800w_webp` and `path_1600w_webp` after `path_og`.

`VehicleImageService` gained `variantPath(string $path, string $suffix, string $extension = 'jpg')` and a private `generateWebp($canvas, string $webpPath)` that guards `function_exists('imagewebp')`, writes quality 80, and **returns `false` → column stays `null`** rather than pointing at a file that was never written. `generateVariants()` emits `-800w.webp` / `-1600w.webp` alongside every JPEG; `purge()` deletes them too.

**Never emit a `<source>` pointing at a missing file** — a browser that gets a 404 on `<source>` will *not* fall back to the `<img>`. So `VehicleImageFactory` deliberately leaves the WebP columns `null`, and every view guards with `@if ($image->path_800w_webp)` before rendering `<source>`.

Converted to `<picture>`: `components/listing/card.blade.php`, `vehicles/show.blade.php` (main gallery + thumbnail row) and `accounts/compare.blade.php`.

The gallery's `<img>` is driven by Alpine's `x-show="active === N"`. A `<picture>` with one static `<source>` would win over every `<img src>`, so **all frames would show image 0**. The fix: a static `src` per image with the `<picture>` scoped to that single image, `x-show` moved onto the `<img>`, and the `images[]` array kept only to drive the lightbox.

All 36 existing dev rows were regenerated in place — no missing variants, no null WebP columns.

**CDN** — `config/filesystems.php` computes `$assetBase` from `CDN_URL`, falling back to `APP_URL` when blank, and `config/app.php` gained `asset_url => env('ASSET_URL')` so `asset()` is CDN-capable too. The storefront already resolves images through `Storage::disk('public')->url(...)`, so setting `CDN_URL` moves every image at once.

## 16.7 `optimize` smoke, `.env.example`, README

`php artisan optimize`, `route:cache`, `config:cache` and `view:cache` all complete (views are the slow one at ~33 s). Smoke-tested over HTTP **with the caches live**: `/`, `/vehicles`, `/submit`, `/sitemap.xml`, `/robots.txt` → 200 with headers; `/definitely-missing` → 404 with headers; `/admin` → 302 to `/admin/login` → 200 with headers; three consecutive `/vehicles?make_id=1` requests at 447 → 344 → 336 ms with 14 file-cache entries written; the detail page renders 6 `<picture>` tags, 6 WebP sources and 5 `loading="lazy"` (the first gallery image is eager by design). Caches were cleared afterwards so the test suite runs uncached.

`.env.example` is rewritten for this project: `APP_NAME=Monaralk`, MySQL 8.4 block, `CACHE_STORE=file`, `CATALOG_CACHE_ENABLED`/`TTL`, `CDN_URL`/`ASSET_URL`, `QUEUE_CONNECTION=database` — and no `APP_KEY`.

`README.md` replaces the stock Laravel text: what Monaralk is, requirements, a clean-clone setup, dev commands (including the queue worker and the "re-run `npm run build` after editing Blade" rule), testing, production deploy, module layout and a pointer to the roadmap + phase reports. Seeded staff accounts are documented as they actually are.

### A flaky assertion the clean-clone gate caught

The first clean-clone run failed `SavedVehiclesTest > saved vehicles never leak between accounts`; the second passed. The failure dump showed the saved card rendering normally, which ruled out the page — and the card's own `aria-label` gave it away:

```
aria-label="O&#039;Reilly Ltd 11 Placeat Animi 11 Limited"
```

`MakeFactory` names makes with `faker->company()`, which occasionally yields an **apostrophe**. Blade escapes it to `&#039;`, but the test searched for the **raw** title via `assertSee($vehicle->listingTitle(), false)` — so whether the assertion held was pure Faker luck. The same latent bug was on every `assertSee`/`assertDontSee` whose needle was Faker-generated catalogue data: five in `SavedVehiclesTest` and one in `SubmitWizardTest`.

Fixed by dropping the `false` so `assertSee` escapes the needle to match what Blade actually emits (markup/attribute/URL needles keep `false`, where it is correct). A new deterministic case — `a saved listing whose title needs escaping is still matched on the page` — pins a Make named `O'Reilly & Sons`, asserting the escaped form is found **and** the raw form is absent.

---

## Verify

| Gate | Result |
|---|---|
| `route:cache` | ✅ Routes cached successfully |
| `config:cache` | ✅ Configuration cached successfully |
| Header checks | ✅ 200/404/redirect responses all carry the full set, live |
| Upload-rejection tests | ✅ PHP-as-jpg rejected, oversized rejected, real photo accepted |
| Clean clone works | ✅ clone → `composer install` → `npm ci` → `key:generate` → 237 tests |
| `php artisan test` | ✅ **237 passed** (1730 assertions) |
| `php vendor/bin/pint --test` | ✅ passed |

## Gotchas

1. **Route-group middleware does not run for unknown URLs** — a 404 thrown during routing never enters a route's stack, so header/CSP middleware must be appended to the **global** stack to cover 404s.
2. **Livewire's temp upload does not check MIME**, and `Testing\File` reports MIME from the *filename* — so an upload test must set `mimeType()` explicitly to simulate `finfo`, and the view must not assume a previewable file.
3. **`text/x-php` has no Symfony extension mapping**, so `guessExtension()` returns `null` and `mimes` rejects it — which is exactly the desired behaviour for PHP-in-jpg.
4. **Never point a `<source>` at a nullable/possibly-absent file**; browsers do not fall back on 404.
5. **A static `<source>` beats every `<img :src>`** — Alpine-driven galleries need the `<picture>` scoped per image, or all frames render frame 0.
6. **`dispatch-afterCommit` jobs never run under `RefreshDatabase`.**
7. **The database cache store turns every `Cache::get` into a query** — it silently destroys both performance and query-count assertions.
8. **`assertSee($value, false)` on Blade-rendered catalogue data is a flake generator** — Blade escapes, the raw Faker title does not, and `faker->company()` supplies apostrophes at random. Escape the needle (drop `false`) unless the needle is markup you authored.
