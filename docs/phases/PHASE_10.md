# Phase 10 — SEO & Content

**Status:** ✅ Complete
**Date:** 2026-10-09
**Result:** Every public URL now ships a complete, once-escaped `<head>` (title/description/canonical/robots + OpenGraph + Twitter card), the vehicle detail page carries a **1200×630 social crop**, `og:type=product` price tags and a schema-valid **`Car` JSON-LD** block, and `/sitemap.xml` + `/robots.txt` are served by the app. Static pages, branded 404/500 pages and a heading/`alt`/`lazy` audit round it off. **221 tests passing** (1605 assertions), Pint clean.

---

## 10.1 `<x-seo>` head component

`resources/views/components/seo.blade.php`, rendered once from `layouts/storefront.blade.php` in place of the old ad-hoc `@yield`/`@hasSection` block.

| Input | Source | Default |
|---|---|---|
| `title` · `description` · `canonical` · `robots` | section | site name · site tagline · `url()->current()` · `index, follow, max-image-preview:large, max-snippet:-1` |
| `og:type` | section | `website` (`product` on a listing, `article` on a page) |
| `og:image` · `og:image:alt` · `og:image:width/height` | section | omitted when the page has no share image |
| `og:price:amount` · `og:price:currency` | section | — (`product:price:*`) |
| Twitter | derived | `summary_large_image` when an image exists, otherwise `summary` |

It also emits `og:site_name`, `og:locale` (`en_LK` / `si_LK`), `og:url` and a light/dark `theme-color` pair.

**Escaping rule (see Gotcha 1):** inline `@section('x', 'value')` values are HTML-escaped *by Laravel itself* (`ManagesLayouts::startSection()` calls `e($content)`), so the component echoes them **raw**, exactly like `@yield`. Escaping them a second time turned `Sri Lanka's` into a literal `&#039;` on every page — a test now pins single escaping. Defaults are escaped once by `yieldContent()` itself. No view uses a block `@section('title')` / `@section('description')` (grep-verified), so nothing raw can leak.

## 10.2 OpenGraph + Twitter card

`vehicles/show.blade.php` sets `canonical`, `og:type=product`, `og:image`/`og:image:alt`, `og:image:width/height` (only when the dedicated crop exists) and `product:price:amount` (digit string, no separators) + `product:price:currency=LKR`.

**New 1200×630 social crop**

- Migration `2026_10_09_100000_add_path_og_to_vehicle_images_table` → `vehicle_images.path_og`.
- `VehicleImageService::generateOgImage()` runs inside `generateVariants()`: centre crop with cover-fit scale from the already-decoded original, JPEG q85, written as `{name}-og.jpg` beside the `-800w`/`-1600w` variants; deleted by `purge()` with the rest.
- `VehicleImageFactory` ships `path_og`; `Vehicle::ogImage()` prefers `path_og` and falls back to `path_1600w` → `path`, so images uploaded before this phase (or where GD failed) still share a card. Existing dev rows were reprocessed in place.

## 10.3 JSON-LD `Car`

`@push('head')` on the detail page (the layout already had `@stack('head')`): `@context/@type: Car`, `name`, `url`, `description`, `sku`, `brand`, `model`, `vehicleModelDate`, `vehicleConfiguration`, `bodyType`, `color`, `fuelType`, `vehicleTransmission`, `itemCondition` (`NewCondition`/`UsedCondition`), `mileageFromOdometer` (`KMT`), `image[]` (every gallery URL) and `offers` — `Offer`, `priceCurrency: LKR`, `price` digits, `availability` `InStock`/`SoldOut`, `seller` as an `Organization`. Encoded with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` so no admin-entered text can close the script tag; the test decodes it and asserts the schema keys.

## 10.4 `sitemap.xml` + `robots.txt`

`app/Modules/Shared/Http/Controllers/SeoController` behind `GET /sitemap.xml` (`sitemap`) and `GET /robots.txt` (`robots`).

- Sitemap: home · listings · contact · submit (static, `changefreq` + `priority`) plus **published** vehicles (`weekly`, `0.8`, `lastmod = updated_at`) and **published** pages (`monthly`, `0.4`). Draft/pending/archived vehicles and unpublished pages never appear.
- `robots.txt`: `Allow` everything public, `Disallow` `/admin`, `/dashboard`, `/profile`, `/favourites`, `/compare`, `/saved/`, `/language/`, an `Allow: /$` and the absolute `Sitemap:` line.

## 10.5 Slugs, 404 and 500

- Permalinks were already slug-based (`Vehicle::getRouteKeyName()` = slug, `Page` too) — verified live: `/vehicles/perodua-axia-2023` and `/pages/about-us`.
- `resources/views/errors/404.blade.php` — branded, `noindex, follow`, "Page not found" with *Go to homepage* / *Browse cars* CTAs.
- `resources/views/errors/500.blade.php` — `noindex, nofollow`, *Go to homepage* / *Contact us*.

## 10.6 Static pages

`GET /pages/{page}` → `Settings\Http\Controllers\PageController::show` (`pages.show`): `abort_unless($page->is_published, 404)`, then `pages/show.blade.php` with `meta_title`/`meta_description` overrides, its own canonical, `og:type=article`, and content split into paragraphs on blank lines. The storefront footer's brand column links every published page (one `Page::published()` query); the four seeded pages (`about-us`, `contact-us`, `privacy-policy`, `terms-of-service`) all resolve.

## 10.7 Image `alt`, `loading="lazy"`, headings

- Audit: listing cards, detail gallery and the compare table already carried `alt` + `loading="lazy"` (+ `decoding="async"`); the wizard's preview thumbnails gained `loading="lazy" decoding="async"` (decorative previews keep `alt=""`, their "Cover" badge carries the meaning).
- Headings: no view now renders more than one `<h1>` — the five wizard step titles were demoted to `<h2>` under the page's *Sell your car* `<h1>`.

---

## Verification

| Check | Result |
|---|---|
| `php artisan test` | ✅ **221 passed** (1605 assertions) — +14 new |
| `php vendor/bin/pint` | ✅ passed |
| `npm run build` | ✅ CSS **84.76 kB** (15.74 gzip) · JS 51.52 kB (19.51 gzip) |
| Live `/` | ✅ 200 — title, description (single-escaped), canonical, robots, `og:*`, `twitter:card`, footer page links |
| Live `/vehicles/perodua-axia-2023` | ✅ 200 — `og:type=product`, `-og.jpg` with `1200×630`, `product:price:*`, `Car` JSON-LD |
| Live `/sitemap.xml` | ✅ 200 `application/xml` — 20 `<loc>` (4 static + 12 vehicles + 4 pages) |
| Live `/robots.txt` | ✅ 200 `text/plain` — disallows + `Sitemap:` line |
| Live `/pages/about-us` | ✅ 200 — own title/canonical, `og:type=article` |
| Live `/definitely-missing` | ✅ **404** branded + `noindex` |

### Roadmap Verify list

| Requirement | Result |
|---|---|
| `og:image` = cover ✓ | ✅ `Vehicle::ogImage()` → generated **1200×630** `-og` crop of the cover (`og:image:width/height` emitted alongside); falls back to the 1600w variant |
| sitemap lists all published ✓ | ✅ every `status = published` vehicle and every `is_published` page; drafts excluded (asserted both ways) |
| JSON-LD validates ✓ | ✅ decoded in the test — `@context/@type`, brand/model/year/mileage/condition, `offers.price` + `priceCurrency: LKR` + `availability` |

### New tests

| File | Tests | Covers |
|---|---|---|
| `tests/Feature/Seo/SeoTest.php` | 12 | default head tags · product OG/Twitter + canonical · JSON-LD decode/assert · **single escaping** · cover fallback without a crop · robots.txt · sitemap in/out · static pages (published + draft 404) + footer links · branded 404 with `noindex` · 500 view · `alt`/`lazy` on listing images |
| `tests/Feature/Admin/VehicleImageTest.php` | +2 | `-og` crop written at 1200×630 · `purge()` removes it too (file total 9) |

---

## Gotchas hit (carry forward)

1. **Inline `@section('x', 'value')` values are already HTML-escaped** — `ManagesLayouts::startSection()` runs `e($content)` for the inline form. Echoing them through `{{ }}` double-escapes (`&amp;#039;`); block sections are raw. Blade components reading sections with `$__env->yieldContent()` must therefore use `{!! … !!}` (raw), exactly like `@yield`, and let `yieldContent()` escape its own default (`e($default)` on line 157).
2. **A physical `public/robots.txt` shadows the route.** Both `php artisan serve` and Apache serve real files before the router — the stock 24-byte Laravel skeleton file was returning instead of the controller. Delete the file; keep the route as the single source of truth (it needs the environment's absolute `Sitemap:` URL anyway).
3. **`spatie/laravel-sitemap` v7 `Sitemap::add()` takes ONE argument.** PHP silently accepts extra arguments, so `->add($url, now(), 'daily', 1.0)` compiled fine and dropped the metadata; and there is no `toXml()` — use `(new Url($url))->setChangeFrequency(...)->setPriority(...)` and `$sitemap->render()`.
4. **`json_encode` + `JSON_PRETTY_PRINT` indents with 4 spaces** — reformatting an existing 2-space lang file rewrites every line (and git sees CRLF↔LF as a full rewrite too). Round-trip detect the flags, de-indent programmatically and keep the original line endings when editing `lang/*.json` by hand.
5. **Windows/PowerShell mangles `$var` and double quotes in `php -r` / `artisan tinker --execute`.** Write a temp PHP file instead — it is also the only reliable way to inspect raw bytes (console shows `?` for Sinhala and mangles multi-line `text/plain` bodies).
6. **Sitemap/robots smoke tests must go over HTTP, not just the controller.** The 24-byte robots body was invisible until `Content-Length` was read off the live response; call the controller directly *and* fetch the route.
7. **Reprocess existing images after adding a variant column** — new crops only appear for uploads made after the migration; run `generateVariants()` over `vehicle_images` once so old listings get a `og:image` too.

**Phase 10 complete. Phase 16 (Performance, Hardening & Docs) cleared to begin.**
