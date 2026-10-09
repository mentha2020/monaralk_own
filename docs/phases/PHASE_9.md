# Phase 9 — Localization (`en` + `si`)

**Status:** ✅ Complete
**Date:** 2026-10-09
**Result:** The whole storefront speaks **English** or **සිංහල** — 255 translated strings, framework validation/pagination/auth messages in Sinhala, a header language switcher with session **and** cookie persistence, locale-aware dates/relative times (money deliberately stays `LKR`), admin pinned to English, and a shared `lang/api/{en,si}.json` export for the future app. **207 tests passing** (1505 assertions), Pint clean.

---

## 9.1 Language files

| File | Purpose |
|---|---|
| `lang/si.json` | 255 storefront/app strings, key = English source text (Laravel JSON convention) |
| `lang/si/validation.php` | All validator messages + Sinhala `attributes` labels (field names) |
| `lang/si/pagination.php` · `lang/si/auth.php` · `lang/si/passwords.php` | Paginator, login and reset messages |
| `lang/api/en.json` · `lang/api/si.json` | Identical key sets for the future REST API / mobile app (`en` is the identity map) |

- **No `lang/en` directory** — English is served by the framework's own `lang/en/*` plus the JSON "missing key returns the key" fallback, so nothing is duplicated.
- Sinhala is a plain LTR locale: `<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">` already rendered the right code, and no RTL handling was added.

## 9.2 `SetLocale`

`app/Http/Middleware/SetLocale.php`, appended to the `web` group in `bootstrap/app.php`, runs after the session exists and resolves, in order:

1. **`admin*` URL → `en`** (9.6, belt and braces — Filament's own routes are outside the `web` group).
2. `session('locale')` → 3. cookie `locale` → 4. `config('app.locale')`, rejecting anything not in `config('app.supported_locales') = ['en', 'si']`.

It sets `App::setLocale()`, `Carbon::setLocale()` **and** `CarbonImmutable::setLocale()`, so translations, month names and `diffForHumans()` all move together.

**URL prefix:** the roadmap lists a `{locale}` prefix as *optional* and it was **not** taken — every existing route name, test and canonical URL would have to carry a prefix, and Phase 10's canonical/`hreflang` rules work better with one URL per resource. Persistence is session/cookie based instead, via `GET /language/{locale}`.

## 9.3 Switcher

`app/Modules/Shared/Http/Controllers/LanguageController` + `Route::get('/language/{locale}', …)->name('language.switch')`: validates the locale (404 otherwise), writes `session('locale')`, queues a 1-year `locale` cookie and `back(fallback: route('home'))`.

UI: an `EN · සිං` pair in the header (desktop) and at the foot of the mobile menu, with `aria-current` and the active chip styled, plus `aria-label="{{ __('Language') }}"`.

## 9.4 Translated copy

- Every `__()` string in `resources/views/**` and `app/**` — single- **and** double-quoted — has a Sinhala entry. The **coverage test** enforces this: it extracts every literal key and asserts none resolves to itself under `si`.
- Validator messages, field names, pagination links, login/password-reset messages are in Sinhala; a missing key falls back to `en`.
- Service and browser caps now speak the same language: `SavedVehicles::capMessage()` uses `__('Compare is limited to :count cars.', …)` and the guest Alpine notice renders that translated template with a `{count}` hole filled in JS.

## 9.5 Money and dates

- **Money stays `LKR 4,500,000` in both locales** — prices are rupees only, no conversion and no per-locale symbol (tested).
- Dates localise: `last_service_date` now renders with `isoFormat('D MMM YYYY')` (October → *ඔක්*), and `published_at` uses `diffForHumans()` which is Carbon-locale aware.
- The DomPDF spec sheet stays English (`<html lang="en">`, no `__()`): Sinhala glyphs need a font DomPDF does not ship yet — deferred to Phase 16.

## 9.6–9.7

- **Admin is English** — explicit guard in `SetLocale`, plus the `web`-group-only application; Filament's panel routes never see the storefront locale.
- **API export** — `lang/api/{en,si}.json` are generated from `lang/si.json` with matching key sets; a test fails if they drift.

---

## Verification

| Check | Result |
|---|---|
| `php artisan test` | ✅ **207 passed** (1505 assertions) — +9 new |
| `php vendor/bin/pint` | ✅ passed |
| `npm run build` | ✅ CSS **84.54 kB** (15.70 gzip) · JS 51.52 kB (19.51 gzip) |
| Live `/vehicles` (fresh session) | ✅ 200, `lang="en"`, switcher links, no Sinhala copy |
| Live `/language/si` → `/vehicles` | ✅ 200, `lang="si"`, `රථ බලන්න` in the nav, English nav gone |
| Live `/language/fr` | ✅ **404** |

### Roadmap Verify list

| Requirement | Result |
|---|---|
| locale routing ✓ | ✅ `GET /language/{locale}` + `session`/`cookie` persistence carry the locale onto every subsequent request (URL prefix deliberately skipped, see 9.2) |
| fallback-to-`en` ✓ | ✅ unsupported stored locale → `en`; missing keys → framework `en` (coverage test proves nothing is missing) |
| Sinhala renders LTR ✓ | ✅ `lang="si"` on the document, Sinhala nav/headings render, no RTL rules anywhere |

### New tests

| File | Tests | Covers |
|---|---|---|
| `tests/Feature/Localization/LocalizeTest.php` | 9 | default English · switch → session + cookie + Sinhala page · bad/stored-bad locale → `en` · admin stays English · LKR money + Sinhala dates/pagination/validation · cap message in service + browser · **coverage** (every `__()` key has a Sinhala entry) · `lang/api/*` sync · signed-in favourites page in Sinhala |

---

## Gotchas hit (carry forward)

1. **Coverage tests must accept PHP lang files too.** `auth.password` is a group key resolved by `lang/si/auth.php`, not `si.json`; filter with `trans($key) === $key` under the `si` locale rather than diffing key lists.
2. **Breeze writes `__("...")` with double quotes.** A grep for `__('...')` misses them (two dashboard/profile strings did); the extractor must match both quote styles.
3. **Translate the placeholder, interpolate in JS.** Hard-coding `'Compare is limited to ' + cap` in the guest Alpine component bypasses the catalogue — pass `__('… :count …', ['count' => '{count}'])` into JS and swap `{count}` client-side.
4. **`App::setLocale()` alone does not localise dates.** Call `Carbon::setLocale()` (and `CarbonImmutable`) in the same middleware or `isoFormat`/`diffForHumans()` stay English.
5. **`Cookie::queue()` only lands if `AddQueuedCookiesToResponse` runs** — it does inside the `web` group, so keep `SetLocale` a `web` middleware rather than a global one.
6. **DomPDF cannot render Sinhala yet.** Leave the spec-sheet PDF on `lang="en"` with English date formats until a Sinhala-capable font is bundled (Phase 16).

**Phase 9 complete. Phase 10 (SEO & Content) cleared to begin.**
