# Phase 17 — QA, Testing & Release Readiness

**Status:** ✅ Complete
**Date:** 2026-10-09
**Result:** The QA pass closed the gaps that only show up when you measure them: every page now has exactly one `<h1>` and a keyboard skip link, auth pages are kept out of search indexes, and the whole palette is proven against WCAG 2.1 with a real contrast implementation rather than by eye. Form-control borders went from **1.48:1** to **4.77:1** in light mode and **1.73:1** to **6.79:1** in dark mode. The end-to-end smoke walks admin sign-in → create → publish → search → detail → call/WhatsApp/share → enquiry → submission → approve → publish, and the whole test suite no longer needs a Vite build to run. **326 tests passing** (2017 assertions, up from 237/1730), Pint clean, clean clone green.

---

## 17.1 Full Pest suite

237 tests at the end of Phase 16; **326** now. The 89 new tests live in four files:

| File | Tests | Covers |
|---|---|---|
| `tests/Feature/Qa/AccessibilityMarkupTest.php` | 37 | 17.3 / 17.4 / 17.5 markup invariants |
| `tests/Unit/ContrastTest.php` | 40 | 17.5 WCAG contrast maths + token pairs |
| `tests/Feature/Qa/EndToEndSmokeTest.php` | 4 | 17.7 the whole listing journey |
| `tests/Feature/Qa/PerformanceBudgetTest.php` | 8 | 17.8 first-load, image weight, server budget |

### The suite no longer needs a Vite build

`public/build` is gitignored, so a clean clone has no manifest — and `@vite` throws without one. `tests/TestCase.php` now calls `$this->withoutVite()` in `setUp()`. No test asserted on the compiled `<link>`/`<script>` output, so this is a pure win: `composer install` → `npm ci` (optional) → `php artisan test` now works without `npm run build`. The asset-budget test is the one exception and it `markTestSkipped()`s itself cleanly when the manifest is absent.

## 17.2 Pint

`vendor/bin/pint --test` passes. Three new files needed `new_with_parentheses`, `unary_operator_spaces`, `not_operator_with_successor_space`, `no_unused_imports`, `ordered_imports` and `no_blank_lines_after_phpdoc` on first run; `pint` fixed them in place.

## 17.3 / 17.4 / 17.6 What automation could and could not cover

`AccessibilityMarkupTest` asserts the invariants that can be asserted from rendered HTML, over datasets of public, auth and account pages:

- exactly one `<h1>` per page
- a skip link targeting `#main`
- every `<img>` carries an `alt`
- every form control has an accessible name (label, `aria-label`, `aria-labelledby`, parent `<label>`, or is the honeypot)
- `lang` matches the active locale
- `dark:` variants and the theme boot script are present (so nothing ships light-only)
- `sm:` / `lg:` breakpoint classes are present (so nothing ships fixed-width)
- `focus:ring` is present on interactive controls
- auth pages emit `noindex, nofollow`

Layout at 375/768/1440, dark-mode *appearance*, and Chrome/Safari/Firefox/Edge rendering genuinely need a human. Those moved to **`docs/qa-checklists.md`** — 13 responsive checks, 14 dark-mode checks, 10 tap-target checks and 12 per-browser checks, each with a sign-off table.

## 17.5 Accessibility

### The audit, not the guesswork

A DOM-based audit of every public, auth, account and admin page produced the numbers that drove the fixes. Alt text was already clean (`0` images missing `alt` across every page); the structural gaps were real.

### Defects found and fixed

| Defect | Before | After |
|---|---|---|
| `guest.blade.php` had no skip link and no `noindex` | auth pages indexed, no keyboard bypass | skip link + `id="main"` + `noindex, nofollow` |
| All six auth views (`login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password`) had **zero** headings | no `<h1>` | `<h1>` on each, translated |
| `/dashboard` and `/profile` used `<h2>` as the page title | no `<h1>` on the account area | promoted to `<h1>`; section `<h2>`s kept |
| `app.blade.php` had no skip link | no keyboard bypass | skip link + `<main id="main">` |
| `Verify Email Address` was missing from `lang/si.json` | key fell back to English | added, plus both `lang/api` exports |

### Contrast — measured, not eyeballed

`tests/Unit/ContrastTest.php` implements the full WCAG 2.1 pipeline: `oklch()` → linear sRGB → relative luminance → contrast ratio, verified against the published reference values (`#767676` on white = 4.54, black on white = 21.00) and against Tailwind v4's own hex output for `slate-50/100/200`. Brand and accent tokens are parsed out of `resources/css/app.css`, so a palette edit that breaks AA fails the suite.

The audit found four real failures. Body text was already fine (slate-600 on white is 7.58:1; slate-100 on slate-900 is 16.28:1). What failed was **non-text UI contrast** (WCAG 1.4.11, 3:1) and one text pair:

| Surface | Token | Before | After | Threshold |
|---|---|---|---|---|
| Form field borders, light | `border-slate-300` → `border-slate-500` | **1.48:1** | **4.77:1** | 3.0 |
| Form field borders, dark | `dark:border-slate-700` → `dark:border-slate-400` | **1.73:1** | **6.79:1** | 3.0 |
| Active nav indicator on `brand-50` | `border-brand-500` → `border-brand-600` | **2.86:1** | **4.22:1** | 3.0 |
| Primary button label | `bg-brand-600` → `bg-brand-700` | **4.49:1** | **6.40:1** | 4.5 |
| Filter chip label on `brand-50` | `text-brand-600/70` → `text-brand-700` | **2.63:1** | **6.02:1** | 4.5 |

The border fix touched every real form control — the 15 search filters, all 18 wizard fields, the finance calculator, the enquiry form, the hero search, Breeze's `text-input` / `secondary-button` / `primary-button` and the login checkbox. **Decorative** borders (card outlines, panel dividers, the dashed upload dropzone, the empty-state box) deliberately kept `slate-200` / `slate-300`; 1.4.11 applies to boundaries that identify a control, and card chrome does not.

`accent-600` was checked and turns out to be unused as a text colour anywhere, so it is not asserted as a text pair. Placeholders stayed at `slate-400`: every field has a real `<label>`, so the placeholder is a hint, not the accessible name, and lightening the hint keeps it visually subordinate.

### Known, accepted gaps

- **Filament's global search input** has no accessible name (placeholder only). It is Filament core, not our Blade, and overriding it would fork vendor views.
- **`/admin` has no skip link.** Filament owns the panel layout.
- **Desktop nav links and the language switcher** are under 44px tall. They are mouse targets on wide viewports and remain above the WCAG 2.5.8 AA 24px floor; primary mobile CTAs all carry `h-11` / `min-h-11`. Documented in `docs/qa-checklists.md` §17.5.

## 17.7 End-to-end smoke

`tests/Feature/Qa/EndToEndSmokeTest.php` walks the whole journey in one test:

1. staff signs in over a real `POST /login`
2. creates a listing through Filament's `CreateVehicle` page
3. confirms it is **invisible** on the storefront while still a draft (404)
4. publishes it from the inventory table via the real `publish` table action
5. finds it through `/vehicles?keyword=…`
6. opens the detail page and confirms Call / WhatsApp / Share are present
7. submits an enquiry and confirms it is stored, linked and `new`
8. a seller runs the five-step `/submit` wizard with a real photo upload
9. the admin approves the submission; it becomes a **draft** vehicle, still 404
10. publishing it makes it publicly visible

Three companions cover the guest path (home → search → detail → contact → submit without ever authenticating), the guard rails (honeypot submissions store nothing; `/admin/*` bounces guests), and the viewer role (can list, cannot create).

### The bug this immediately caught

Step 2 500'd on the first run: the Filament form's `mileage_km` is optional, but a blank optional `TextInput` dehydrates as `null` and `vehicles.mileage_km` is `NOT NULL DEFAULT 0`. Any admin who left mileage empty got an integrity-constraint violation. Fixed in `VehicleResource` with `->default(0)` plus `->dehydrateStateUsing(fn ($state): int => (int) ($state ?? 0))`. The storefront renders `number_format($vehicle->mileage_km).' km'` everywhere, so `0` is already the established representation for an unknown odometer.

## 17.8 Performance budgets

`tests/Feature/Qa/PerformanceBudgetTest.php` holds three budgets, over a seeded catalogue of 24 vehicles with 8 galleries:

| Budget | Ceiling | Measured |
|---|---|---|
| Home HTML | 140 kB | ~35 kB |
| `/vehicles` HTML | 160 kB | ~143 kB |
| Filtered search HTML | 180 kB | under budget |
| Compiled CSS, gzipped | 20 kB | **15.75 kB** (`app-Dz-7YPmB.css`) |
| Compiled JS, gzipped | 25 kB | **19.51 kB** (`app-DMsN-rLE.js`) |
| Home / `/vehicles` server time (best of 3, warm) | 1500 ms | well under |
| `/vehicles` query count | ≤ 20 | bounded |

A fourth test asserts the detail page only ever references a **bounded** image variant (`-800w`, `-1600w` or `-og`) — no unbounded source image can leak into the page.

## 17.9 Release readiness

- **`docs/deploy-checklist.md`** — seven sections covering build artifacts, environment, TLS/headers, storage and queue, database, optimise-and-verify, and post-deploy, plus a rollback procedure. Every hard gate is marked. It records the two project-specific traps: `CACHE_STORE` must stay `file` (Phase 16 measured 40–114 cache queries per request under `database`), and `public/build` is gitignored so it must be built and shipped, never pulled.
- **`docs/qa-checklists.md`** — the manual checklists for 17.3, 17.4, 17.5 and 17.6 with sign-off tables.

---

## Verify

| Gate | Result |
|---|---|
| `php artisan test` | ✅ 326 passed / 2017 assertions |
| `vendor/bin/pint --test` | ✅ no changes |
| Clean clone (`composer install` → `php artisan test`, no build needed) | ✅ 326 passed |
| `npm run build` | ✅ `app-Dz-7YPmB.css` 15.75 kB gz, `app-DMsN-rLE.js` 19.51 kB gz |
| Contrast audit | ✅ all asserted pairs meet 4.5:1 (text) / 3:1 (UI) |
| End-to-end smoke | ✅ full journey green |
| Deploy checklist written | ✅ `docs/deploy-checklist.md` |
| Manual QA checklists written | ✅ `docs/qa-checklists.md` |

## Gotchas

1. **Pest has no `->or->` combinator** for "matches any of". `expect($x)->toContain('a')->or->toContain('b')` silently does not do what it looks like. Write the disjunction explicitly and put the value in the failure message.
2. **A regex for `<img src>` will match Alpine's `:src`.** `\bsrc="` matches inside `:src="images[active]"` because `:` is a word boundary. Use `(?<![:\w-])src="` or you will assert against JavaScript, not markup.
3. **`with(['home', 'search'])` passes the keys, not the values.** Pest datasets need `with('datasetName')` or explicit `[...] => [...]` maps. Bare string lists silently make `$uri = 'home'` and every request 404s.
4. **Filament `CreateRecord` exposes `create()`, not `save()`.** Calling `->call('save')` throws `MethodNotFoundException`.
5. **An optional Filament `TextInput` dehydrates blank as `null`.** If the column is `NOT NULL`, the insert fails at runtime with a `QueryException`, not a validation error. Add `->default()` and a `dehydrateStateUsing()` guard, or make the column nullable.
6. **`tests/Unit` is not bound to `Tests\TestCase`.** Pest only extends `TestCase` + `RefreshDatabase` into `Feature/`. Unit tests get the plain PHPUnit case — fine for pure maths, but `base_path()` and the container are not guaranteed, so resolve file paths from `__DIR__`.
7. **Tailwind v4's default palette is `oklch()`, not hex**, and only emits the steps you actually use. Reading `--color-slate-600` out of the compiled CSS requires an oklch parser; hard-code the published values and verify your converter against `slate-50/100/200`, which round-trip exactly.
8. **PowerShell cannot pass `php -r` scripts containing `$`, `=` or quotes** — this bit three times in this phase alone. Write a temp PHP file under `%TEMP%\opencode\` and run that.
9. **Pest's `expect(...)->toBe(...)` on `array_keys` of two lang files is the canary for a missing export.** Adding a key to `lang/si.json` without re-running `p9_export_api_lang.php` fails `LocalizeTest`, which is exactly what it is for — run the exporter whenever the catalogue grows.
