# Phase 8 — Accounts: Favourites & Compare (Hybrid)

**Status:** ✅ Complete
**Date:** 2026-10-09
**Result:** One save control with two modes — guests persist in `localStorage` (Alpine), signed-in buyers persist in `saved_vehicles` (Livewire, optimistic UI) — plus login-time merge, `/favourites`, a 4-car `/compare` table and header count badges. **198 tests passing** (705 assertions), Pint clean.

---

## 8.1 Infrastructure (pre-existing, Phase 2)

Reused as-is: `saved_vehicles` migration (unique `(user_id, vehicle_id, type)`), `App\Modules\Accounts\Models\SavedVehicle`, `App\Modules\Accounts\Enums\SavedType`, `SavedVehicleFactory` + `compare()` state, `User::savedVehicles()` / `Vehicle::savedVehicles()`.

## 8.2–8.4 Service + modes + merge

| Piece | File |
|---|---|
| Service | `app/Modules/Accounts/Services/SavedVehicles.php` |
| Livewire toggle | `app/Livewire/SaveVehicle.php` + `resources/views/livewire/save-vehicle.blade.php` |
| Mode switch component | `resources/views/components/listing/save-actions.blade.php` |
| Controller | `app/Modules/Accounts/Http/Controllers/SavedVehicleController.php` |

**Routes**

| Method | URI | Name |
|---|---|---|
| GET | `/favourites` | `saved.favourites` |
| GET | `/compare` | `saved.compare` |
| POST | `/saved/merge` | `saved.merge` |
| DELETE | `/saved/{vehicle}/{type}` | `saved.destroy` |

- **Caps** — `SavedVehicles::COMPARE_CAP = 4`, `FAVOURITE_CAP = 100`, exported into JS as `window.Monaralk.cap` so the browser enforces the same limit.
- **`toggle()`** — `DB::transaction` + `lockForUpdate()`; delete → `{saved: false}`, cap exceeded → `{saved: false, message}` (no row written), otherwise create. Returns `{saved, message, counts}` so the component and the badge update from one source.
- **`counts()` / `state()`** — one `pluck('type')` query; `state()` seeds the Livewire component in `mount()`.
- **`merge(userId, favouriteIds, compareIds)`** — one transaction: de-dupes input, drops ids that no longer exist (`whereIn` before insert, so stale `localStorage` never trips a FK 500), honours the caps, then `attach()`.
- **Guest vs auth** — `<x-listing.save-actions>` is one component with two bodies: `@auth` renders `<livewire:save-vehicle>`, `@else` renders `x-data="saveActions(id)"`. Both paint identical buttons and flip state before the server answers (`$wire.entangle('favourite')` + `x-on:click` for auth, `Monaralk.toggle()` for guests).
- **Merge trigger** — `window.Monaralk` in `storefront.blade.php`, fired on `DOMContentLoaded` only when `<body data-saved-merge="1">` (set for signed-in users). `fetch('/saved/merge')` with `X-CSRF-TOKEN`; **localStorage is cleared and the badges re-announced only after a 200**, using the `counts` the server returns.

## 8.5 Pages

- **`/favourites`** — card grid of saved vehicles, header count chips with a link to `/compare`, empty state with *Browse cars*.
- **`/compare`** — side-by-side table, one column per car (max 4): cover, title, link, then 12 spec rows (price, year, mileage, condition, fuel, transmission, body, colour, trim, location, registration, owners) and an actions row with *View listing* / *Remove* (`DELETE saved.destroy`). Shows *Compare is full* at the cap.
- Save controls appear on every `<x-listing.card>` (the `$favourite` slot now defaults to save actions) and in the detail page header block under the price.

## 8.6 Badges

- Header `savedBadges(initial)` component: `initial` is `SavedVehicles::counts()` for signed-in users and `null` for guests (falls back to `localStorage` counts).
- Single `saved-changed` event: Livewire `$this->dispatch('saved-changed', …)` for auth, `window.dispatchEvent` from `Monaralk.announce()` for guests — both update the same two counters.
- Mobile menu also links Favourites and Compare.

---

## Verification

| Check | Result |
|---|---|
| `php artisan test` | ✅ **198 passed** (705 assertions) — +10 new |
| `php vendor/bin/pint` | ✅ passed |
| `npm run build` | ✅ CSS **84.50 kB** (15.69 gzip) · JS 51.52 kB (19.51 gzip) |
| Live `/` · `/vehicles` | ✅ 200, 12 guest `saveActions(` buttons each, `data-saved-merge="0"` |
| Live `/vehicles/{slug}` | ✅ 200, save actions in the header block |
| Live `/favourites` · `/compare` (guest) | ✅ **302 → `/login`** |

### Roadmap Verify list

| Requirement | Result |
|---|---|
| guest persistence ✓ | ✅ buttons render `saveActions(id)` backed by `monaralk.favourites` / `monaralk.compare`, no Livewire round-trip |
| merge-on-login no duplicates ✓ | ✅ posting the same payload twice returns identical counts (2 favourite + 4 compare) and leaves **6 rows** total |
| compare cap ✓ | ✅ 5th `toggle()` → `saved: false` + *"Compare is limited to 4 cars."*, no row; merge with 5 ids writes **4** |
| cross-user isolation ✓ | ✅ account B sees none of A's favourites, cannot unsave A's rows, and `state()` reports `false` for A's car |

### New tests

| File | Tests | Covers |
|---|---|---|
| `tests/Feature/Accounts/SavedVehiclesTest.php` | 10 | guest markup + storage keys · signed-in badge counts · Livewire favourite on/off + `saved-changed` · compare cap (service *and* Livewire message) · cross-user isolation on pages and component · guest → login redirects · compare table + cap + cover image · merge de-dupe/cap/422/401 · `saved.destroy` touches one account only |

---

## Gotchas hit (carry forward)

1. **`Js::from()` inside an attribute renders `JSON.parse('{\u0022key\u0022:2}')`.** Quotes are hex-escaped, not HTML-encoded, so `assertSee('favourite&quot;:2')` fails — assert `'savedBadges(JSON.parse'` plus `'…\u0022favourite\u0022:2'`.
2. **Merging clears the storage the badges read.** Announcing right after `set('favourite', [])` pushes zeroed counts into a signed-in user's header. Pass the server's `counts` to `announce(counts)` so the badge shows the real post-merge totals.
3. **Validation caps must be looser than business caps.** `max:4` on the merge payload returns 422 and hides the `COMPARE_CAP` truncation; use `max:50` on the input and let `SavedVehicles` enforce 4, so the cap path is actually exercised.
4. **`factory()->count(2)->create(['vehicle_id' => X])` violates the unique pair.** Pass a distinct vehicle per row, or let the factory relate its own.
5. **`auth` middleware wins.** Guests are redirected to `login` before any `abort_unless($user, 403)` in the controller runs — assert `assertRedirect(route('login'))`, not a 403.

**Phase 8 complete. Phase 9 (Localization `en` + `si`) cleared to begin.**
