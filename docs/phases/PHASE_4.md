# Phase 4 — Admin Panel (Filament 3)

**Status:** ✅ Complete
**Date:** 2026-10-08
**Result:** 15 Filament resources (vehicles + 7 lookups, enquiries, submissions, users, roles, settings, pages), image pipeline with GD variants, submission approval inside a transaction, XLSX export/import, DomPDF spec sheet and a live dashboard. **132 tests passing**, Pint clean.

---

## 4a `VehicleResource`

`app/Modules/Catalog/Filament/Resources/VehicleResource.php` — tabs built from the Phase 2 schema:

| Tab | Fields |
|---|---|
| Basic | make · model · year · mileage · price · condition · trim · location · description |
| Specs | body type · fuel · transmission · exterior/interior colour |
| Provenance | VIN · registration · owners · accident history · warranty · last service |
| Finance | deposit · term · APR |
| Contact override | phone · whatsapp · phone display (placeholder = global setting, Phase 6) |
| Images | repeater: path · alt · cover · sort |
| Features | `CheckboxList` over `vehicle_features` |
| SEO | meta title · meta description · slug |

Plus a **Publishing** section outside the tabs (status · `published_at` · `is_featured`).

Table: status badge · make/model · price · year · views · featured toggle · filters (status, make, condition, featured, price/year/mileage range, keyword) · actions.

**Actions**

- row: `publish` (`vehicle.publish`) · `markSold` · `feature`/`unfeature` · `specSheet` (PDF link) · edit · delete
- bulk: `publish` (visible only with `vehicle.publish`) · delete
- header: `Create` · **Export XLSX** · **Import XLSX**
- nav badge = published count (`getNavigationBadge()`)

`CreateVehicle::mutateFormDataBeforeCreate()` stamps `user_id`, `source=admin`, `views_count=0`, `published_at`; `EditVehicle::mutateFormDataBeforeSave()` keeps `published_at` consistent. `UserResource`'s `mutateFormData*` live on the **Page classes** — Filament 3.3 does not honour them on the Resource.

## 4b Lookup resources

Abstract `TaxonomyResource` (form = shared fields + `is_active` + `sort_order`; table = leading name/slug + extras + active + order + created, `is_active` filter, edit/delete, default sort `sort_order`) with seven one-file subclasses:

`MakeResource` · `VehicleModelResource` · `BodyTypeResource` · `FuelTypeResource` · `TransmissionResource` · `ColorResource` · `FeatureResource`

Each has `List`/`Create`/`Edit` pages under `<Resource>/Pages/`. `ColorResource` adds a `hex` `ColorPicker` + swatch badge column; `FeatureResource` adds `icon` + `group_name` (grouped on the detail page); `VehicleModelResource` adds a required `make` select.

## 4c `VehicleImageService` + observer

`app/Modules/Catalog/Services/VehicleImageService.php`

- `store()` → writes to the `public` disk under `vehicles/`, downscales the original to `ORIGINAL_WIDTH = 2000`, then creates the `VehicleImage` row (all inside a `DB::transaction`).
- `generateVariants()` → `-800w` / `-1600w` JPEGs, persisted onto `path_800w` / `path_1600w` with `saveQuietly()` (no event loop).
- `purge()` / `deleteFiles()` → deletes original + both variants, wrapped in a transaction.
- `enforceSingleCover()` → clears `is_cover` on every sibling row.

`VehicleImageObserver` wires them up: `created` → variants + cover enforcement · `updated` → regenerate on `path` change (files read from `getOriginal()`), cover enforcement on `is_cover` change · `deleting` → purge. Registered by `ModulesServiceProvider::registerObservers()`.

## 4d `EnquiryResource`

Read-only inbound fields (name/email/phone/vehicle/message disabled), editable follow-up section (`status` select + internal notes). Table actions: **Mark contacted** · **Resolve** · **Spam** (all gated on `enquiry.update`) · edit · delete; bulk **Mark resolved** gated on the `enquiry.update` permission. Nav badge = open enquiries. No create page — enquiries arrive from the public form in Phase 7.

## 4e `SubmissionResource` + `SubmissionApprover`

`app/Modules/Submissions/Services/SubmissionApprover.php`:

- `approve()` — wraps **everything** in `DB::transaction()`:
  1. `mapAttributes()` validates `year` + `price` and resolves taxonomy by **id or name** (creating missing lookups);
  2. `Vehicle::create()` with `source=public`, `status=draft`, `views_count=0`;
  3. `VehicleImage::create()` per photo (first one becomes the cover);
  4. marks the submission `approved`, links `approved_vehicle_id`, stamps `reviewed_by`/`reviewed_at`.
- `decide()` — reject / needs-changes with reviewer + timestamp.

Table actions **Approve** · **Reject** · **Needs changes** are visible only through the `submission.review` policy (admin + super_admin), plus a `status` badge and an `images`/`data` read-only preview (thumbnails via `Placeholder::allowHtml()`).

## 4f Accounts, settings & content

| Resource | Notes |
|---|---|
| `UserResource` | name/email, roles `Select` (multiple, preloaded), password (`required` **only when `$record === null`**), role filter on the table, delete hidden for self |
| `RoleResource` | name/guard + `CheckboxList` of permissions; super_admin only (`role.manage`) |
| `SettingResource` | key/group/type/value; `handleRecordCreation/Update` call `Setting::flushCache()` so edits are live immediately |
| `PageResource` | title/slug/content + publish toggle + meta |

Policies added this phase: `VehicleTaxonomyPolicy`, `PagePolicy`, `SettingPolicy`, `RolePolicy` — all registered in `ModulesServiceProvider::registerPolicies()`.

`AdminPanelProvider` now declares `navigationGroups(['Catalog', 'Leads', 'Accounts', 'Settings'])`.

## 4g Excel + DomPDF

| File | Purpose |
|---|---|
| `Catalog/Exports/VehicleExport.php` | `FromQuery` + eager loads · 20 `COLUMNS` headings · maps relations to names |
| `Catalog/Imports/VehicleImport.php` | `ToModel` + `WithHeadingRow` + `WithValidation` + `WithUpserts` (`uniqueBy = slug`) + `WithUpsertColumns` + `SkipsOnFailure` |
| `Catalog/Pdfs/VehicleSpecSheet.php` | `Pdf::loadView('pdf.vehicle-spec')->download('{slug}-spec-sheet.pdf')` |
| `resources/views/pdf/vehicle-spec.blade.php` | A4, brand header, identity/specs/provenance tables, features, description |

Routes (in `routes/web.php`) are registered under `admin.` and guarded by **`Filament\Http\Middleware\Authenticate`** so a guest is sent to `/admin/login` and a non-staff user gets a 403:

- `GET /admin/inventory/export` → `admin.inventory.export`
- `GET /admin/vehicles/{vehicle}/spec-sheet` → `admin.vehicles.spec-sheet`

Import creates unknown makes/models/colours on the fly, matches existing rows on `slug`, and skips (never aborts) rows that fail validation — failures are collected on `$import->failures()`.

## 4h Dashboard widget

`app/Modules/Catalog/Filament/Widgets/StatsOverviewWidget.php` — Published · Drafts · New enquiries · Pending submissions. Discovered automatically; `protected static bool $isLazy = false;` so the numbers render on the first paint (default `true` ships a Livewire placeholder and `assertSee` finds nothing).

---

## Verification

| Check | Result |
|---|---|
| `php artisan test` | ✅ **132 passed** (358 assertions) — +55 new |
| `php vendor/bin/pint --test` | ✅ passed |
| `npm run build` | ✅ CSS 118.76 kB (20.62 gzip) · JS 51.52 kB (19.51 gzip) |
| Live `/admin` guest → `/admin/login` | ✅ 302 |
| Live `/admin/inventory/export` staff | ✅ 200 + XLSX attachment |
| Live `/admin/inventory/export` non-staff | ✅ 403 |
| Live `/admin/vehicles/{slug}/spec-sheet` | ✅ 200 + `application/pdf` |

### New tests

| File | Tests | Covers |
|---|---|---|
| `Feature/Admin/VehicleResourceTest.php` | 7 | list for 4 roles · create editor-only · edit policy · guest → panel login |
| `Feature/Admin/TaxonomyResourceTest.php` | 15 | all 7 list + create pages · viewer read-but-not-create |
| `Feature/Admin/VehicleImageTest.php` | 5 | original + `-800w`/`-1600w` widths · observer variants · single cover · delete purges · path change retires old files |
| `Feature/Admin/SubmissionApproverTest.php` | 5 | approve → exactly 1 draft vehicle + 2 images + link back · **mid-flight failure rolls back** · missing price refused · reject leaves catalogue untouched · double approval refused |
| `Feature/Admin/VehicleExcelTest.php` | 5 | headings + row round-trip · export → force-delete → import restores identical data · import twice = 1 row · unknown lookups created · bad rows skipped with `failures()` |
| `Feature/Admin/InventoryToolsTest.php` | 4 | XLSX + PDF downloads for staff · 403 non-staff · `/admin/login` for guests |
| `Feature/Admin/PanelResourcesTest.php` | 10 | enquiries/submissions per role · users permission matrix · roles super_admin-only · settings/pages · dashboard widgets · non-staff 403 |

---

## Gotchas hit (carry forward)

1. **`Filament\Http\Middleware\Authenticate` is the right guard for custom `/admin/*` routes.** Plain `auth` redirects guests to the Breeze `/login` instead of `/admin/login`. Using Filament's middleware gives `/admin/login` for guests **and** 403 for non-staff, for free.
2. **Filament widget `isLazy` defaults to `true`.** The initial HTML only contains a placeholder, so `assertSee('Published')` fails on a plain `GET /admin`. Set `protected static bool $isLazy = false;` for stats you want on first paint.
3. **`->relationship()` on a `SelectFilter` must point at a real relationship.** `->relationship('role', 'name')` on `User` returns `null` (Spatie exposes `roles()`) and blows up inside `Filament\Support\Services\RelationshipJoiner::prepareQueryForNoConstraints()`. Use `->relationship('roles', 'name')->multiple()->preload()` for `BelongsToMany`.
4. **`->authorize('publish', Model::class)` on a bulk action calls the policy with the class string, which Laravel strips — leaving the required model argument missing.** Use `->visible(fn (): bool => auth()->user()?->can('vehicle.publish') === true)` for bulk actions instead.
5. **Laravel Excel's `ModelManager` does not fire Eloquent events when `WithUpserts` is used** — the `creating` hook that generates a slug never runs. Compute the slug in `model()` yourself (make `Vehicle::uniqueVehicleSlug()` public for this).
6. **`SkipsOnFailure` has an `onFailure(Failure ...$failures)` method to implement**, and validation only runs when `WithValidation` is also implemented. `WithUpserts` in v4 only declares `uniqueBy()` — the update-column list comes from the separate `WithUpsertColumns::upsertColumns()` interface (there is no `updateOnlyColumns()`).
7. **PowerShell here-strings mangle `` ``$resource `` `** — the backtick is the escape character and `$resource` expands to empty. Generating Filament page stubs is far safer in a throwaway PHP script (`strtr()` on a nowdoc) than in PowerShell.
8. **A `guest → assertRedirect` assertion placed after `actingAs()` in the same test method runs as the authenticated user.** Split guest and non-staff assertions into separate tests.
9. **PowerShell 5.1 `Get-Content -TotalCount 5 -Raw` is invalid** — the parameters are mutually exclusive and the resulting errors bury the actual output.

**Phase 4 complete. Phase 5 (Public Storefront) cleared to begin.**
