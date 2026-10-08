# Phase 5 — Public Storefront

**Status:** ✅ Complete
**Date:** 2026-10-09
**Result:** Storefront layout + home + `/vehicles` Livewire search + listing card + detail page + public DomPDF spec sheet. **158 tests passing** (455 assertions), Pint clean.

---

## 5a Layout & navigation

`resources/views/layouts/storefront.blade.php` — the public shell (Breeze stays untouched for `/login`, `/register`, `/profile`):

- sticky header: logo (`Setting::get('site.name')`), Home / Browse Cars nav with `routeIs()` active state, **dark-mode toggle** (`data-theme-toggle` + `localStorage['monaralk-theme']`, applied by an inline head script before first paint so there is no flash), auth/staff links (Admin only via `auth()->user()?->isStaff()`), hamburger for `sm:hidden`.
- mobile drawer shares one `x-data="{ open: false }"` on the `<header>` so the toggle button and the panel are in the same scope.
- `@yield('title' | 'description' | 'canonical')` + `og:site_name` / `og:type` / `og:title` (share-URL pattern, conflict E).
- footer: tagline + Browse / Account / Contact columns driven by `Setting::get('contact.phone'|'contact.email')`, LKR note, copyright.
- `@stack('head')` and `@stack('scripts')` for per-page Alpine (gallery, calculator).

Dark-mode tokens were already live from Phase 3, so 5a only had to expose the toggle.

## 5b Home

`app/Modules/Catalog/Http/Controllers/HomeController.php` + `resources/views/home.blade.php`:

- hero with a plain GET form → `route('vehicles.index')` (`keyword`, `make_id`, `max_price`).
- `$stats` = publicly-visible listings · makes · featured (3 scalar counts).
- featured strip (4) · latest grid (8) · brand grid (`withCount` filtered to `listings_count > 0`) · trust section.
- one shared `$eager = ['make','model','fuelType','transmission','coverImage']` for both vehicle collections.

## 5c `/vehicles` — Livewire search

`app/Livewire/VehicleSearch.php` + `resources/views/livewire/vehicle-search.blade.php`:

| Piece | Detail |
|---|---|
| URL state | 16 `#[Url]` properties (keyword, make, model, body, fuel, transmission, colour, condition, location, min/max year, min/max price, max mileage, featured, sort) — full query-string sync, `except:` keeps defaults out of the URL |
| Query | `Vehicle::query()->filter($filters)->paginate(12)->withQueryString()` — one shared `scopeFilter()` for web + admin |
| Sorts | `SORTS` const: latest · price asc/desc · year desc/asc · mileage asc · newest |
| Dependent selects | `updatedMakeId()` clears `model_id`; `modelOptions()` scopes to the chosen make |
| Mobile | `toggleFilters()` + `x-cloak` drawer; `showFilters` is deliberately **not** `#[Url]` |
| Chips | `activeFilters()` builds label+value chips (relation chips resolve the model name) with `clearFilter('x')` / `clearAll()` |
| Pagination | `WithPagination` + `{{ $vehicles->links() }}` |

`HomeController` and `VehicleController@index` are thin; the controller for `/vehicles` is `VehicleController@index`.

## 5d `<x-listing.card>`

`resources/views/components/listing/card.blade.php` — `@props(['vehicle'])`; title is composed as `make + model + trim` (there is **no `title` column** on `vehicles`), cover image falls back to an inline car SVG, Featured / Sold badges, condition badge, price + year + mileage · fuel · transmission, location, optional `@isset($favourite)` and `@isset($contactActions)` slots (both consumed from Phase 6/8).

## 5e Detail page

`app/Modules/Catalog/Http/Controllers/VehicleController.php@show` + `resources/views/vehicles/show.blade.php`:

- `abort_unless(isVisible(...))` — `status->isPubliclyVisible() && published_at <= now()` → 404 for draft/pending/archived/scheduled, sold stays visible.
- eager loads make/model/both colours/body/fuel/transmission/images/features; `$related` = same make, `take(4)`, **with `make` eager-loaded**.
- view counter: `incrementViews()` once per session (`vehicle.viewed.{id}`), `withoutTouching` + `withoutEvents`.
- Alpine `gallery(count, images)` lightbox (`x-cloak`, prev/next, ESC/arrows) over thumbnails.
- `$specRows` spec table, feature chips, Provenance + Finance blocks, Description, Similar cars, Listing details aside.
- `financeCalculator` Alpine widget pushed via `@push('scripts')`.
- `GET /vehicles/{vehicle}/spec-sheet` → `route('vehicles.spec-sheet')`.

## 5f Public spec sheet

`VehicleController@specSheet` reuses `app/Modules/Catalog/Pdfs/VehicleSpecSheet.php` (Phase 4) behind the same `isVisible()` guard; the route sits on `web` middleware so guests get the PDF.

---

## Verification

| Check | Result |
|---|---|
| `php artisan test` | ✅ **158 passed** (455 assertions) — +26 new |
| `php vendor/bin/pint` | ✅ passed |
| `npm run build` | ✅ CSS 131.53 kB (22.28 gzip) · JS 51.52 kB (19.51 gzip) |
| Live `/` | ✅ 200 |
| Live `/vehicles` | ✅ 200 + `Refine search` + `Sort by` |
| Live `/vehicles?make_id=1&sort=price_asc&max_price=5000000` | ✅ 200 + applied-filter chip |
| Live `/vehicles/{slug}` | ✅ 200 + finance calculator |
| Live `/vehicles/{slug}/spec-sheet` | ✅ 200 + `application/pdf` |
| Live unknown slug | ✅ 404 |

### New tests

| File | Tests | Covers |
|---|---|---|
| `Feature/Storefront/HomeTest.php` | 4 | hero + stats · featured strip · latest grid · brand grid filtered to non-zero counts |
| `Feature/Storefront/VehicleSearchTest.php` | 9 | interface renders · make filter from query string · condition/price/year/mileage/keyword combo · price sort order · empty state · pagination (2 pages, zero-padded sentinels) · query-string chips · `clearFilter`/`clearAll` · make change resets model |
| `Feature/Storefront/VehicleDetailTest.php` | 12 | published renders · draft/pending/archived 404 (dataset) · scheduled-not-live 404 · sold visible · unknown slug 404 · features + related · gallery · one view per session · public PDF · hidden vehicle PDF → 404 |
| `Feature/Storefront/QueryCountTest.php` | 1 | 20 seeded listings: home 11 · listing 16 · detail 16 queries (all `< 20`) |

---

## Gotchas hit (carry forward)

1. **Livewire 3's default component namespace is `App\Livewire`, not `App\Http\Livewire`.** `Livewire::test()` and `<livewire:vehicle-search />` both fail with "Unable to find component" if the class lives under `app/Http/Livewire/`. Moved the class, then the test's `use` line had to be updated too — the old import silently resolves to nothing.
2. **`filled(false)` is `true`.** Laravel's `blank()` only treats `null`, `''` and `[]` as blank — **booleans are never blank** — so `if (filled($filters['featured'] ?? null))` turned *every* search into "featured only" and the listing page returned 0 rows for every visitor while `Vehicle::filter([])->count()` (called without the key) returned the right number. Use `! empty($filters['featured'] ?? false)` for boolean filter flags. The tell was a `count(*) ... and is_featured = ?` binding `1` in `DB::listen` output while the caller passed `false`.
3. **`pluck()` returns `Illuminate\Support\Collection`, not `Illuminate\Database\Eloquent\Collection`.** Typing `locationOptions(): Collection` with the Eloquent import gives a `TypeError` at runtime even though both are "Collection".
4. **DomPDF's `download()` returns `Illuminate\Http\Response`, not `BinaryFileResponse`.** A controller return type of `BinaryFileResponse` on a public spec-sheet route 500s with a `TypeError`. Use `Symfony\Component\HttpFoundation\Response`.
5. **A missing eager load shows up as a constant +1 per related row.** The detail page was at exactly 20 queries because `$related` was missing `make` while the card reads `make?->name` — one `select * from makes where id = ?` per related car. `DB::listen` + dumping the SQL makes this obvious in one run; the test bound (`< 20`) caught it.
6. **Blade `assertSee` on a composed title needs a sentinel that is a real substring.** `PAGEITEM1` is a prefix of `PAGEITEM10`…`PAGEITEM13`, so "not on page 1" assertions are impossible. Zero-pad the markers (`PAGEITEM01`) and assert absence of the padded value.
7. **`app/Http/Livewire/` and `title` columns:** `vehicles` has no `title` column — anything reading `$vehicle->title` returns `null` (the PDF template relies on `??`). Don't add a `title` override to `Vehicle::factory()`; the card composes the heading from `make + model + trim`.
8. **PS 5.1 `Set-Content -Encoding UTF8` writes a BOM** and a BOM before `<?php` is a fatal "Namespace declaration statement has to be the very first statement". Strip with `[System.IO.File]::ReadAllText()` + `Substring(1)` and rewrite with `New-Object System.Text.UTF8Encoding($false)`.

**Phase 5 complete. Phase 6 (Contact Actions: Call · WhatsApp · Share) cleared to begin.**
