# Phase 2 — Architecture Foundation & Database

**Status:** ✅ Complete
**Date:** 2026-10-08
**Result:** Full schema, models, seed data and policies in place. `migrate:fresh --seed` green, 56 tests passing, Pint clean.

---

## 2a Module skeleton + discovery hooks + enums

```
app/Modules/
  Catalog/       Enums, Models, Policies, Services, Actions, Filament/Resources
  Leads/         Enums, Models, Policies, Services, Filament/Resources
  Submissions/   Enums, Models, Policies, Services, Filament/Resources
  Accounts/      Enums, Models, Policies, Services, Filament/Resources
  Settings/      Enums, Models, Policies, Services, Filament/Resources
  Shared/        HasSlug trait
  ModulesServiceProvider.php
```

`composer.json` untouched — `App\` → `app/` PSR-4 already covers `app/Modules`.

### `App\Modules\ModulesServiceProvider` (registered in `bootstrap/providers.php`)

| Hook | Purpose |
|---|---|
| `Factory::guessFactoryNamesUsing()` | flat `Database\Factories\VehicleFactory` for models nested in modules |
| `Gate::policy(…)` ×4 | explicit policy registration (no auto-guessing across module namespaces) |

### Filament discovery (`AdminPanelProvider`)

`discoverResources` / `discoverPages` / `discoverWidgets` all now point at `app_path('Modules')` for `App\Modules` — resources live inside modules, not `app/Filament`.

### Enums

| Enum | Cases |
|---|---|
| `Catalog\VehicleStatus` | `draft` `pending` `published` `sold` `archived` |
| `Catalog\VehicleCondition` | `new` `certified_pre_owned` `used` |
| `Catalog\VehicleSource` | `admin` `public` |
| `Leads\EnquiryStatus` | `new` `contacted` `resolved` `spam` |
| `Submissions\SubmissionStatus` | `pending` `approved` `rejected` `needs_changes` |
| `Accounts\SavedType` | `favourite` `compare` |

Each exposes `label()`, `color()` (Filament badge) plus a domain helper (`isPubliclyVisible()`, `isOpen()`, `isResolved()`).

---

## 2b + 2c + 2d — Migrations

16 new migrations (15 app + Spatie's published `create_permission_tables`), all green on MySQL 8.4.

| Table | Notes |
|---|---|
| `makes` | name, slug ✚unique, country, logo_path, is_active, sort_order |
| `vehicle_models` | make_id FK cascade, ✚unique `(make_id, slug)` |
| `body_types` `fuel_types` `transmissions` | name, slug ✚unique, is_active, sort_order |
| `colors` | + `hex(9)` for swatches |
| `features` | + `icon`, `group_name` (indexed) |
| **`vehicles`** | full 2c schema below |
| `vehicle_images` | path + 800w/1600w variants, alt, is_cover, sort_order · ✚index `(vehicle_id, sort_order)` |
| `vehicle_features` | pivot, ✚unique `(vehicle_id, feature_id)` |
| `enquiries` | nullable vehicle/user, status, notes, ip · ✚index `(status, created_at)` |
| `vehicle_submissions` | JSON `data` + JSON `images`, status, notes, approved_vehicle_id, reviewed_by/at, ip |
| `saved_vehicles` | ✚unique `(user_id, vehicle_id, type)` · ✚index `(user_id, type)` |
| `settings` | ✚unique `key`, `group` indexed, `type` enum |
| `pages` | slug ✚unique, is_published indexed |

### `vehicles` columns (2c)

- **Identity:** `slug` ✚unique, `user_id` FK nullable, `source` enum
- **Taxonomy:** `make_id`, `model_id`, `body_type_id`, `fuel_type_id`, `transmission_id`, `exterior_color_id`, `interior_color_id` — all FK (RESTRICT on the lookups so a make holding vehicles cannot be silently deleted)
- **Specs:** `trim`, `year`, `mileage_km`, `price` decimal(12,2), `condition` enum, `description`
- **Provenance:** `owners_count`, `accident_history`, `warranty`, `last_service_date`
- **Finance:** `finance_deposit`, `finance_term_months`, `finance_apr` (fall back to `settings` at render time)
- **Contact override:** `phone`, `whatsapp`, `phone_display`
- **Other:** `location`, `vin`, `registration_number`
- **Flags:** `status` enum, `published_at`, `is_featured`, `views_count`
- **SEO:** `meta_title`, `meta_description`
- **Timestamps + soft deletes**

### Indexes (2d) — verified against `information_schema.statistics`

```
vehicles_status_published_at_index   (status, published_at)
vehicles_make_id_model_id_index      (make_id, model_id)
vehicles_year_index                  (year)
vehicles_price_index                 (price)
vehicles_mileage_km_index            (mileage_km)
vehicles_condition_index             (condition)
vehicles_is_featured_index           (is_featured)
vehicles_slug_unique                 (slug)
vehicles_vin_index                   (vin)
vehicles_registration_number_index   (registration_number)
vehicles_location_index              (location)
vehicles_source_index                (source)
vehicles_created_at_index            (created_at)
+ InnoDB auto-indexes for every FK column
```

---

## 2e — Models (14)

`Make` `VehicleModel` `BodyType` `FuelType` `Transmission` `Color` `Feature` `Vehicle` `VehicleImage` `Enquiry` `VehicleSubmission` `SavedVehicle` `Setting` `Page`

### `Vehicle` highlights

- **Relations:** `user`, `make`, `model`, `bodyType`, `fuelType`, `transmission`, `exteriorColor`, `interiorColor`, `features` (belongsToMany `vehicle_features`), `images` (ordered), `coverImage`, `enquiries`, `savedBy`
- **Casts:** all three enums, decimals, dates, booleans
- **Accessors (Attribute API):** `contactPhone` → vehicle override else `settings.contact.phone`; `contactWhatsApp` → override else settings else `contactPhone`; `formattedPrice` → `LKR 8,950,000`
- **Scopes:** `published()`, `publiclyVisible()` (published **or** sold, `published_at <= now`), `featured()`, `filter($filters)` (visibility + taxonomy + ranges + keyword + sort + eager loads)
- **Sorts:** `latest` (default) `newest` `price_asc` `price_desc` `year_asc` `year_desc` `mileage_asc`
- **Other:** `incrementViews()` (no touch/no events), `isPublished()`, `isSold()`, `getRouteKeyName() = slug`, auto slug from make+model+year (checked `withTrashed()`)

### `Setting`

Static cached key/value store: `Setting::get($key, $default)` · `Setting::set($key, $value, $group, $type)` · typed casting (boolean/integer/decimal) · `Cache::rememberForever('settings.all')`.

---

## 2f — Factories (14) + Seeders

`migrate:fresh --seed` → **exit 0**, ~9 s.

| Seed | Result |
|---|---|
| `RoleSeeder` | 16 permissions, 4 roles (`super_admin` `admin` `editor` `viewer`) |
| `SettingSeeder` | 8 settings (site, contact, finance defaults) |
| `LookupSeeder` | 14 makes · 55 models · 9 body types · 4 fuel types · 3 transmissions · 10 colors · 22 features in 4 groups |
| `UserSeeder` | `admin@monaralk.lk` (super_admin), editor, viewer, 3 demo users — password `password` |
| `VehicleSeeder` | **12 published vehicles** (4 featured) · 36 images (12 covers) · 78 feature links · **108 generated JPEG placeholders** |
| `PageSeeder` | 4 pages (About, Contact, Terms, Privacy) |

Demo vehicles are Sri-Lankan-market realistic (LKR 3.65 M – 24.9 M) with make/model pairs that actually exist in the lookup seed.

**Placeholder imagery** is generated with GD inside the seeder (original 2000w + 1600w + 800w per photo) so the storefront renders without real uploads. Outputs land in `storage/app/public/vehicles/` — **gitignored**, rebuilt by re-seeding (`php artisan storage:link` already wired as a junction).

---

## 2g — Policies (4)

| Policy | Abilities |
|---|---|
| `VehiclePolicy` | viewAny/view/create/update/delete/publish/restore/forceDelete → `vehicle.*` |
| `EnquiryPolicy` | viewAny/view/update/delete → `enquiry.*`, **`create` always allowed** (public form) |
| `SubmissionPolicy` | viewAny/view → `submission.view`, review/update/delete → `submission.review`, **`create` always allowed** |
| `UserPolicy` | viewAny/create/update/delete → `user.*`, users may always `view` themselves, may never delete themselves |

All four registered via `Gate::policy()` in `ModulesServiceProvider`. `User` now carries `HasRoles` + `vehicles()`, `enquiries()`, `submissions()`, `savedVehicles()`.

---

## Verification

| Check | Result |
|---|---|
| `php artisan migrate:fresh --seed` | ✅ exit 0 |
| Index audit on `vehicles` | ✅ 20 indexes present |
| Seed row counts | ✅ 14 / 55 / 9 / 4 / 3 / 10 / 22 lookups · 12 vehicles · 36 images · 78 pivots · 6 users · 4 roles · 16 permissions · 8 settings · 4 pages |
| `php artisan test` | ✅ **56 passed** (158 assertions), 13.5 s — 31 new |
| `php vendor/bin/pint --test` | ✅ passed |

### New tests

- `tests/Feature/Catalog/VehicleRelationsTest.php` — 9 tests: factory make/model consistency, taxonomy, features pivot, ordered images + cover, unique slug, contact fallbacks, formatted price
- `tests/Feature/Catalog/VehicleQueryTest.php` — 11 tests: `published` / `publiclyVisible` / `featured`, visibility filtering, taxonomy + range + keyword + sort filtering, featured flag, eager loading, pivot dedupe
- `tests/Feature/Policies/PolicyTest.php` — 11 tests: gate registration, flat-factory discovery, per-role vehicle abilities, public enquiry/submission creation, pipeline permissions, self-delete guard

---

## Gotchas hit (carry forward)

1. **Pivot table naming** — `belongsToMany(Feature::class)` guesses `feature_vehicle`. Both sides must pass `'vehicle_features'`.
2. **PowerShell 5.1 `Set-Content -Encoding UTF8` writes a BOM** → `Namespace declaration statement has to be the very first statement`. Strip with `[System.IO.File]::WriteAllBytes()` (or use the file-editing tools).
3. **Factory closure ordering matters.** `expandAttributes()` mutates the definition array in place, so a closure may only read keys declared *earlier* in the same array — `make_id` must precede `model_id` in `VehicleFactory`.
4. **`Gate::getPolicyFor()` returns a policy *instance*, not the class string** — assert with `get_class(...)`.
5. List-vs-map destructuring in seeders (`foreach ($map as $k => [$a, $b])` fails on associative arrays) — iterate `as $k => $v`.
6. Seeder attribute lists must include **every** NOT NULL column (`pages.title`).
7. `Status`/`condition` seeded as enum **cases**, not raw strings.

**Phase 2 complete. Phase 3 (Authentication, RBAC & Authorization) cleared to begin.**
