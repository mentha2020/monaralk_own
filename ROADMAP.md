# Monaralk — Project Roadmap

Car listing website: public storefront + Filament admin. Mobile app is **deferred** (Part C held for later).

**Stack:** Laravel 12 · PHP 8.3.30 · MySQL 8.4 · Livewire 3 · Alpine.js · Tailwind CSS v4 · Filament 3.3 · Breeze (Blade) · Spatie Permission · Laravel Excel · DomPDF · Pest
**Brand / locale:** Monaralk placeholder · LKR · `en` + `si` (Sinhala, LTR)
**Shape:** Public listing site + Filament admin, modular monolith, API-ready structure
**Repo:** `monaralk/` (Laravel)

---

## Scope

- Public site: browse / search / filter vehicles, detail page with gallery, enquiry form, public vehicle submissions with admin approval, favourites, compare, SEO package, dark mode, `en` + `si`.
- Admin: Filament 3 panel — vehicles, catalog lookups, images, enquiries, submissions, users/roles, settings, Excel import/export, DomPDF spec sheets.
- Contact actions: Call · WhatsApp · Share on every listing card and detail page.
- Mobile app (Flutter, Android first, then iOS): **on hold — Part C below retained for later execution.**

---

## Environment Baseline (verified, Phase 0)

> Full report: [`docs/phases/PHASE_0.md`](docs/phases/PHASE_0.md)

| Item | Status |
|---|---|
| PHP | 8.3.30 (ZTS, 64-bit Windows, Laragon) ✓ |
| Composer | 2.10.1 ✓ |
| Node / npm | 24.17.0 / 11.13.0 ✓ |
| Git | 2.55.0 ✓ |
| Database | **MySQL 8.4.3** @ `127.0.0.1:3306` (Laragon), `root`, no password, full GRANT ✓ |
| DB server charset | `utf8mb4` / `utf8mb4_0900_ai_ci` ✓ |
| PHP extensions | `pdo_mysql`, `mysqli`, `gd`, `zip`, `fileinfo`, `mbstring`, `exif`, `bcmath`, `intl` ✓ |
| `upload_max_filesize` | 2G ✓ |
| Laravel | `v12.69.x` (latest 12.x) ✓ |
| Filament | `3.3.56` — supports `illuminate ^12`, requires `livewire ^3.5` ✓ |
| Breeze | `2.4.2` — supports Laravel 11/12/13 ✓ |
| Spatie Permission | `^7.4` — requires PHP 8.3 + `illuminate ^12` ✓ |
| Laravel Excel | `^4.0.3` — requires PHP 8.3 + `illuminate ^12` ✓ |
| DomPDF | `barryvdh/laravel-dompdf ^3.1` ✓ |
| Sitemap | `spatie/laravel-sitemap ^7.4` (v8 needs PHP 8.4 → avoid) ✓ |
| Pest | `^4.1` + `pestphp/pest-plugin-laravel ^4.1` (needs Laravel ≥12.52) ✓ |
| Tailwind CSS | v4.3.3 + `@tailwindcss/vite` — Laravel 12 skeleton already ships TW4 ✓ |
| Git | 2.55.0 ✓ |
| Workspace | `D:\My_Project\Laravel\monaralk` — empty at start ✓ |

### Known conflicts and their resolution

| # | Conflict | Resolution |
|---|---|---|
| A | Breeze installs Tailwind **v3** config (`tailwind.config.js`, `postcss.config.js`, `@tailwind` directives, `tailwindcss ^3.1.0`) | ✅ Resolved (1.5): `@tailwindcss/vite` restored in `vite.config.js`; `@import 'tailwindcss'` + `@source` + `@custom-variant dark` in `app.css`; TW3 config files deleted; `tailwindcss ^4` + `@tailwindcss/forms ^0.5.11` (0.5.11 is the max — 0.6 does not exist) |
| B | Breeze bundles its own Alpine → collides with Livewire 3's bundled Alpine (Filament requires Livewire) | ✅ Resolved (1.6): Alpine stripped from `resources/js/app.js`; `Livewire::forceAssetInjection()` set in `AppServiceProvider::boot()` — putting it in `bootstrap/app.php` throws *"A facade root has not been set"* |
| C | Filament custom theming needs a Tailwind v3 build | Deferred; v1 ships Filament's default precompiled theme |
| D | `maatwebsite/excel ^4.0` is a recent major | Fall back to `^3.1.70` (also Laravel-12 compatible) if its API surprises |
| E | Facebook cannot post a bare image | Share links the listing URL; `og:image` supplies the preview |
| **F** | **DB is MySQL 8.4.3, not MariaDB 10.4** (server swapped during planning) | Use `utf8mb4_0900_ai_ci`; honour `ONLY_FULL_GROUP_BY` in every query — enforced in `VehicleQueryService` |

---

## Architecture Decisions

**Modular monolith** — domain modules under `app/Modules/`, models live *inside* modules:

```
app/
  Modules/
    Catalog/       Models, Services, Actions, Policies, Filament/Resources, Http
    Leads/         (enquiries)
    Submissions/   (public vehicle submissions)
    Accounts/      (favourites/compare sync, user policies)
    Settings/      (settings, static pages)
  Http/            shared middleware (SetLocale), controllers
  View/Components/ shared Blade components
```

- `App\Modules\...` maps to `app\Modules\...` with no composer.json changes.
- Two discovery hooks registered once in a provider: `Factory::guessFactoryNamesUsing()` (flat `Database\Factories\VehicleFactory`) and explicit `Gate::policy()` calls.
- Filament panel uses `->discoverResources(in: app_path('Modules'), for: 'App\Modules')`.

**Conventions:** Form Requests for all public writes · Policies + Spatie permissions for authz · Services for enquiry/submission/approval/image logic · DB transactions around submission→vehicle approval · eager loading (`make`, `model`, `coverImage`) on every list · composite + FK indexes on all filter columns.

- **Search:** query builder over indexed columns + `LIKE` keyword (Scout/FULLTEXT-ready seams, no infra now).
- **Favourites/Compare:** hybrid — Alpine `localStorage` for guests, Livewire endpoint merges into `saved_vehicles` on login.
- **Images:** GD-based `VehicleImageService` (no new dependency) — original + 800w/1600w variants, cover flag, sort order.
- **i18n:** `en` + `si` (Sinhala, LTR — no RTL work), optional `{locale}` route prefix, admin stays English.

---

## Database (migrations only)

| Group | Tables |
|---|---|
| Catalog | `makes`, `vehicle_models`, `body_types`, `fuel_types`, `transmissions`, `colors`, `features`, `vehicles`, `vehicle_features`, `vehicle_images` |
| Leads | `enquiries` |
| Submissions | `vehicle_submissions` |
| Accounts | `saved_vehicles` (`type` = favourite\|compare) + Breeze/Spatie tables |
| Content | `settings`, `pages` |

Key `vehicles` columns: `slug` (unique), `make_id`/`model_id`/`body_type_id`/`fuel_type_id`/`transmission_id`, `trim`, `year`, `mileage_km`, `price` (decimal 12,2), `condition` enum, `status` enum (`draft|pending|published|sold|archived`), `published_at`, `is_featured`, `views_count`, `vin`/`registration_number`, provenance (`owners_count`, `accident_history`, `warranty`, `last_service_date`), finance (`finance_deposit`, `finance_term_months`, `finance_apr` — fall back to settings), contact override (`phone`, `whatsapp`, `phone_display`), `exterior_color_id`/`interior_color_id`, `location`, `source` enum (`admin|public`), `user_id`, `meta_title`/`meta_description`, soft deletes.

Indexes: `(status, published_at)`, `(make_id, model_id)`, `year`, `price`, `mileage_km`, `condition`, `is_featured`, `slug`, `vin`; `vehicle_images(vehicle_id, sort_order)`; `enquiries(status, created_at)`; `saved_vehicles(user_id, type)`.

---

## Part A — Web Platform

### Phase 0 — Inspection & Environment Baseline
| # | Sub-phase |
|---|---|
| 0.1 | Verify toolchain: PHP, Composer, Node, npm |
| 0.2 | Verify database connectivity, DB creation, required PHP extensions |
| 0.3 | Verify package compatibility against Laravel 12 / PHP 8.3 |
| 0.4 | Confirm empty workspace — nothing to overwrite |
| 0.5 | Record conflicts A–E in this document |

**Exit:** baseline documented, zero blockers.

### Phase 1 — Scaffold & Stack Bootstrap
| # | Sub-phase |
|---|---|
| 1.1 | `composer create-project laravel/laravel .` |
| 1.2 | Create `monaralk` DB; wire `.env` (utf8mb4) |
| 1.3 | Composer: `filament/filament:^3.3`, `laravel/breeze:^2.4`, `spatie/laravel-permission:^7.4`, `maatwebsite/excel:^4.0`, `barryvdh/laravel-dompdf:^3.1`, `spatie/laravel-sitemap:^7.4` |
| 1.4 | `breeze:install blade` |
| 1.5 | **Conflict A** — migrate build to Tailwind v4 |
| 1.6 | **Conflict B** — remove Breeze's Alpine, force Livewire asset injection |
| 1.7 | `filament:install --panels` → `/admin` |
| 1.8 | Pest baseline |
| 1.9 | `npm run build` + `php artisan migrate` |

**Verify:** build ✓ · `/login` ✓ · `/admin` ✓ · `php artisan test` green ✓

### Phase 2 — Architecture Foundation & Database
- **2a** Module skeleton + discovery hooks + enums (`VehicleStatus`, `VehicleCondition`, `VehicleSource`, `EnquiryStatus`, `SubmissionStatus`, `SavedType`)
- **2b** Migrations (all tables above)
- **2c** Vehicles schema detail (identity, specs, provenance, finance, contact, SEO, flags)
- **2d** Indexes on every filter column
- **2e** Models: relations, casts, accessors (`contactPhone`, `contactWhatsApp`, `formattedPrice`), scopes (`published()`, `featured()`, `filter()`)
- **2f** Factories + seeders (roles, admin user, lookups, ~12 demo vehicles with images/features)
- **2g** Policies: `VehiclePolicy`, `EnquiryPolicy`, `SubmissionPolicy`, `UserPolicy`

**Verify:** `migrate:fresh --seed` ✓ · relation/scope tests ✓ · policy tests ✓

### Phase 3 — Authentication, RBAC & Authorization
- 3.1 Spatie roles `super_admin|admin|editor|viewer` + middleware aliases
- 3.2 `HasRoles` on `User`
- 3.3 Filament `canAccessPanel()` restricted to staff roles
- 3.4 Gate registrations for every policy
- 3.5 Re-theme Breeze auth views (brand, dark mode)
- 3.6 Password reset / email verification verified

**Verify:** role-gate tests ✓ · panel-access tests ✓ · 403 on unauthorized ✓

### Phase 4 — Admin Panel (Filament 3)
- **4a** `VehicleResource` — sections: Basic · Specs · Provenance · Finance · Contact override · SEO · Images (cover/sort) · Features; status actions (`publish`, `mark sold`, `feature`)
- **4b** Lookup resources: Make, Model, BodyType, FuelType, Transmission, Color, Feature
- **4c** `VehicleImageService` — GD, original + 800w/1600w variants, cover, sort, alt, transactional delete
- **4d** `EnquiryResource` — status pipeline + notes
- **4e** `SubmissionResource` — approve/reject; approve creates `vehicles` row inside `DB::transaction()`
- **4f** `UserResource` + `RoleResource`; `SettingResource` (`contact.phone`, `contact.whatsapp`, site name, finance defaults); `PageResource`
- **4g** Laravel Excel inventory export + bulk import; DomPDF spec sheet
- **4h** Dashboard stat widgets

**Verify:** policy tests ✓ · image upload test ✓ · approval-transaction test ✓ · Excel round-trip ✓

### Phase 5 — Public Storefront
- **5a** Responsive layout/nav/footer, dark-mode toggle *(brand tokens already live in the TW4 `@theme` from Phase 3)*
- **5b** Home: hero search, featured strip, latest, brand grid, trust section
- **5c** `/vehicles` Livewire search — full filter set + keyword + sort + pagination + URL query-string sync + mobile drawer + applied-filter chips
- **5d** `<x-listing.card>` — cover, price, specs, featured badge, favourite slot, contact-actions slot
- **5e** Detail: gallery lightbox, spec table, feature chips, provenance, seller card, related, view counter, Alpine financing calculator
- **5f** DomPDF spec sheet download

**Verify:** filter-combo tests ✓ · 404 for unpublished ✓ · no N+1 ✓ · responsive pass ✓

### Phase 6 — Contact Actions: Call · WhatsApp · Share
| # | Sub-phase |
|---|---|
| 6.1 | `settings`: `contact.phone`, `contact.whatsapp` + Filament editing + validation (`digits_between:8,15`) |
| 6.2 | `vehicles.phone`/`whatsapp`/`phone_display` columns + admin Contact section (placeholder = global fallback) |
| 6.3 | Accessors: per-listing → Settings fallback; `tel:` normalization; WhatsApp digits-only |
| 6.4 | `<x-listing.contact-actions>` with `$variant = card\|hero`, renders only when a number exists |
| 6.5 | **Call:** `tel:` · solid high-contrast · 44×44+ · `font-semibold` · `whitespace-nowrap` |
| 6.6 | **WhatsApp:** `wa.me/{digits}?text=` — *interested in "{title}" — {price} LKR {url}* |
| 6.7 | **Share:** Alpine dropdown → Facebook sharer + Copy Link with toast |
| 6.8 | Wired into every listing card + detail hero |
| 6.9 | `target="_blank" rel="noopener noreferrer nofollow"` |

**Verify:** fallback tests ✓ · `wa.me` encoding ✓ · share encoding ✓ · hidden when unconfigured ✓

### Phase 7 — Enquiries & Public Submissions
- **7a** Detail enquiry form + general contact: Form Request, honeypot, per-IP rate limit, auth prefill, queued notification
- **7b** `/submit` multi-step Livewire wizard: details → specs → photos → contact → review → `vehicle_submissions` (`pending`) → confirmation
- **7c** Moderation: approve → `DB::transaction` creates `vehicles` (`source=public`, `status=draft`); reject → reason email

**Verify:** validation ✓ · rate-limit ✓ · approve creates exactly one vehicle ✓ · reject leaves none ✓

### Phase 8 — Accounts: Favourites & Compare (Hybrid)
- 8.1 `saved_vehicles` (`user_id`, `vehicle_id`, `type`, unique pair)
- 8.2 Guest: Alpine `localStorage`
- 8.3 Livewire toggle, optimistic UI
- 8.4 On login/register: merge localStorage → server in a transaction (de-dupes)
- 8.5 `/favourites`, `/compare` (cap 4, side-by-side table)
- 8.6 Nav count badges

**Verify:** guest persistence ✓ · merge-on-login no duplicates ✓ · compare cap ✓ · cross-user isolation ✓

### Phase 9 — Localization (`en` + `si`)
- 9.1 `lang/en` + `lang/si` (Sinhala, LTR — no RTL)
- 9.2 `SetLocale` middleware + optional `{locale}` prefix
- 9.3 Nav switcher + session persistence
- 9.4 Translate views/validation/pagination
- 9.5 Money/date per locale (LKR stays LKR)
- 9.6 Admin stays English
- 9.7 Export shared JSON strings for the future API/app (`lang/api/{en,si}.json`)

**Verify:** locale routing ✓ · fallback-to-`en` ✓ · Sinhala renders LTR ✓

### Phase 10 — SEO & Content
- 10.1 `<head>` component: title, description, canonical, robots
- 10.2 OpenGraph + Twitter card — `og:image` = cover (1200×630), `og:type=product`, price/currency
- 10.3 JSON-LD `Product`/`Car` on detail
- 10.4 `sitemap.xml` (spatie ^7.4) + `robots.txt`
- 10.5 Slugs/permalinks, 404 & 500 pages
- 10.6 Static pages from `settings`/`pages`
- 10.7 Image `alt`, `loading="lazy"`, semantic headings

**Verify:** `og:image` = cover ✓ · sitemap lists all published ✓ · JSON-LD validates ✓

---

## Part B — REST API (reserved for the mobile app)

> Held until the mobile app is scheduled. Retained so the service layer built in Phase 2 stays API-compatible.

### Phase 11 — API v1 (Laravel Sanctum) — *on hold*
- `php artisan install:api` → Sanctum, `routes/api.php`, `personal_access_tokens`
- `/api/v1` routes grouped by domain: vehicles (list/detail/related), catalog lookups, filters, auth, enquiries, submissions, favourites/compare, device tokens
- Reuses Phase 2e services — same query service behind Livewire *and* API
- `ApiResource` classes, absolute image URLs via `Storage::disk('public')->url()`, error contract, bearer tokens, rate limiters, `docs/openapi.yaml`

**Verify:** Pest HTTP tests per endpoint ✓ · filter parity ✓ · 401/422/429 ✓ · CORS preflight ✓

---

## Part C — Mobile App (Flutter) — **HELD FOR LATER**

> Not in current scope. Retained verbatim for when it is scheduled.
> Environment already verified: Flutter 3.41.9 / Dart 3.11.5, Android SDK 34–36.1 + build-tools, JDK 21 (Android Studio JBR), Pixel_7 emulator (android-37 image), licenses present. No Xcode (Windows) → Android first, iOS later via macOS/CI.
> Packages already resolved: `firebase_messaging`, `dio`, `go_router`, `flutter_riverpod`, `cached_network_image`, `url_launcher`, `share_plus`, `image_picker`.

- **Phase 12 — Flutter foundation:** license acceptance, `flutter create` (android + ios), Dio client + bearer interceptor, Riverpod, go_router deep links, Material 3 theme mirroring Tailwind palette, `app_en.arb`/`app_si.arb`, test harness.
- **Phase 13 — App features:** home, search with filters, listing card, detail + gallery + calculator, **native Call/WhatsApp/Share** (needs `<queries>` in `AndroidManifest` for `tel:`/`wa.me`), enquiry, multi-step submit with camera, auth, favourites/compare with server merge, settings.
- **Phase 14 — Push (FCM):** `device_tokens` migration, `laravel-notification-channels/fcm` + `kreait/laravel-firebase`, triggers (enquiry replied, submission approved/rejected, price drop), `firebase_messaging` in app. **Gate: a Firebase project must exist.**
- **Phase 15 — Android packaging:** appId, signing keystore, ProGuard, icons/splash, manifest permissions + deep links, release APK/AAB, device smoke test, iOS-readiness audit.

---

## Part D — Polish & Release

### Phase 16 — Performance, Hardening & Docs
- 16.1 Caching: config/route/view; filtered-listing cache with invalidation on publish
- 16.2 Eager-loading audit across web (N+1 assertions in tests)
- 16.3 Query log review → missing indexes via migration
- 16.4 Queue for mail/image processing; `storage:link`
- 16.5 Security: rate limits, honeypot, CSP/security headers, upload MIME+size validation, XSS audit, secrets out of VCS
- 16.6 Image pipeline: WebP variants, `loading="lazy"`, CDN-ready disk config
- 16.7 `php artisan optimize` smoke; finalize `.env.example` + README

**Verify:** `route:cache` ✓ · `config:cache` ✓ · header checks ✓ · upload-rejection tests ✓ · clean clone works ✓

### Phase 17 — QA, Testing & Release Readiness
- 17.1 Full Pest suite (feature/unit/policy/HTTP)
- 17.2 `pint --test` formatting pass
- 17.3 Responsive QA: 375 / 768 / 1440
- 17.4 Dark-mode QA across public + admin
- 17.5 Accessibility: 44×44 tap targets, contrast, focus states, alt text
- 17.6 Browser QA: Chrome/Safari/Firefox/Edge
- 17.7 End-to-end smoke: admin login → create → publish → search → detail → call/WhatsApp/share → enquiry → submission approve
- 17.8 Performance budgets: first-load, image weight, TTFB
- 17.9 Deploy checklist: `APP_DEBUG=false`, `APP_ENV=production`, HTTPS, cron + queue worker, backup, `php artisan optimize`

**Verify:** all tests green ✓ · no Pint diffs ✓ · smoke passes ✓ · checklist signed off ✓

---

## Sequence & Dependencies

```
A  0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10
B                                     11 (held)
C                                      12 → 13 → 14 → 15 (held)
D                                              16 → 17
```

- Phase 6 depends on 5d/5e (card + detail components exist)
- Phase 7c depends on 4e (moderation resource exists)
- Phase 9 must land before 10.1 (SEO meta needs translation)
- Phases 7a and 8 can run in parallel after 6
- Each phase ends with a git commit + push to GitHub

---

## Risk Register

| Risk | Mitigation |
|---|---|
| Breeze's TW3 config breaks the TW4 build | ✅ Handled (1.5) — resolved before any UI work. **Never re-run `breeze:install`**: it re-writes `vite.config.js`, `app.css`, `app.js`, `package.json` and re-creates the TW3 configs |
| Alpine double-init breaks Livewire | ✅ Handled (1.6) — Breeze's Alpine removed; Livewire 3 owns Alpine for the whole app (Breeze's `x-data` markup is driven by it) |
| Filament theming vs TW4 conflict | Filament default theme (precompiled); custom theming deferred past v1 |
| `maatwebsite/excel ^4.0` recent major | Fall back to `^3.1.70` |
| `spatie/laravel-sitemap` v8 needs PHP 8.4 | Pinned to `^7.4` |
| Facebook can't post bare images | Share links the URL; `og:image` supplies the preview |

## Explicitly Deferred Past v1
Mobile app (Part C) · Filament custom theme · Laravel Scout/full-text search · multi-tenant/branch scoping · payment/finance transactions · service workshop & parts modules · S3/CDN (structure allows it) · admin UI translations · compare export/PDF.

---

## Phase Status

| Phase | Name | Status |
|---|---|---|
| 0 | Inspection & Environment Baseline | ✅ **Complete** — [report](docs/phases/PHASE_0.md) |
| 1 | Scaffold & Stack Bootstrap | ✅ **Complete** — [report](docs/phases/PHASE_1.md) |
| 2 | Architecture Foundation & Database | ✅ **Complete** — [report](docs/phases/PHASE_2.md) |
| 3 | Authentication, RBAC & Authorization | ✅ **Complete** — [report](docs/phases/PHASE_3.md) |
| 4 | Admin Panel (Filament 3) | ✅ **Complete** — [report](docs/phases/PHASE_4.md) |
| 5 | Public Storefront | ✅ **Complete** — [report](docs/phases/PHASE_5.md) |
| 6 | Contact Actions: Call · WhatsApp · Share | ✅ **Complete** — [report](docs/phases/PHASE_6.md) |
| 7 | Enquiries & Public Submissions | ✅ **Complete** — [report](docs/phases/PHASE_7.md) |
| 8 | Accounts: Favourites & Compare | ✅ **Complete** — [report](docs/phases/PHASE_8.md) |
| 9 | Localization (`en` + `si`) | ✅ **Complete** — [report](docs/phases/PHASE_9.md) |
| 10 | SEO & Content | ⬜ |
| 16 | Performance, Hardening & Docs | ⬜ |
| 17 | QA, Testing & Release Readiness | ⬜ |
| 11 | REST API v1 — *held* | 🔒 held |
| 12–15 | Mobile app (Flutter) — *held* | 🔒 held |

**Workflow rule:** each phase ends with a git commit **and** a push to GitHub before the next phase begins.

---

## Change Log

| Date | Change |
|---|---|
| 2026-10-08 | Initial roadmap (Phases 0–17, web + API + Flutter) |
| 2026-10-08 | Added Phase 6 — Call / WhatsApp / Share contact actions |
| 2026-10-08 | Added Part B (Sanctum API) and Part C (Flutter app); mobile marked **held for later**, Part A + D in current scope |
| 2026-10-08 | Phase 0 complete — DB corrected to **MySQL 8.4.3**, conflict F added |
| 2026-10-08 | Phase 1 complete — Laravel 12.69.3 scaffold, all stack packages pinned & installed, conflicts **A** + **B** resolved, `monaralk_test` MySQL DB wired for Pest |
| 2026-10-08 | Phase 2 complete — 5 modules + discovery hooks, 6 enums, 15 new migrations, 14 models, 14 factories, 7 seeders, 4 policies; `migrate:fresh --seed` + 56 tests green |
| 2026-10-08 | Phase 3 complete — Spatie middleware aliases, `FilamentUser::canAccessPanel()` staff gate, brand tokens + dark mode across every Breeze view; 77 tests green |
| 2026-10-08 | Phase 4 complete — 15 Filament resources, image pipeline (GD 800w/1600w variants + observer), transactional submission approval, XLSX export/import, DomPDF spec sheet, dashboard widgets; 132 tests green |
| 2026-10-09 | Phase 5 complete — storefront layout + home, Livewire `/vehicles` search (16 URL-synced filters, sort, chips, pagination), `<x-listing.card>`, detail gallery/lightbox/view counter/finance calculator, public spec-sheet PDF; 158 tests green |
| 2026-10-09 | Phase 6 complete — `<x-listing.contact-actions>` (Call · WhatsApp · Share) on every card + detail seller panel, per-listing → global fallback, `tel:`/`wa.me` normalisation, Alpine share dropdown with copy toast, `ContactNumber` Filament rule; 168 tests green |
| 2026-10-09 | Phase 7 complete — enquiry forms (honeypot + per-IP throttle + auth prefill + queued team mail), five-step `/submit` wizard writing `vehicle_submissions`, admin reject now demands a reason and emails it to the submitter; 188 tests green |
| 2026-10-09 | Phase 8 complete — hybrid favourites/compare (guest `localStorage`, signed-in Livewire optimistic toggle), transactional merge on login with de-dupe + cap, `/favourites` and 4-column `/compare` table, header count badges; 198 tests green |
| 2026-10-09 | Phase 9 complete — `en` + `si` localization: 255 Sinhala strings, `SetLocale` middleware (session/cookie, admin pinned to English), header language switcher, Sinhala validation/pagination messages, locale-aware dates with LKR unchanged, `lang/api/{en,si}.json` exports; 207 tests green |
