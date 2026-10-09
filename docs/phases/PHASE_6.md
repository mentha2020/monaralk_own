# Phase 6 — Contact Actions: Call · WhatsApp · Share

**Status:** ✅ Complete
**Date:** 2026-10-09
**Result:** Call / WhatsApp / Share wired into every listing card and the detail seller panel, with per-listing → global setting fallback, normalised `tel:` and `wa.me` links, an Alpine share dropdown (Facebook + copy-link toast) and Filament validation for contact numbers. **168 tests passing** (500 assertions), Pint clean.

---

## 6.1 Settings + Filament validation

`SettingResource::form()` now validates `value` when `key` is `contact.phone` or `contact.whatsapp`, and only then shows the helper text:

```php
->rules(fn (Forms\Get $get): array => in_array($get('key'), ['contact.phone', 'contact.whatsapp'], true)
    ? [new ContactNumber]
    : [])
```

`app/Modules/Shared/Rules/ContactNumber.php` is a `ValidationRule` that strips non-digits and requires **8–15 digits** (see gotcha 1 for why Laravel's own `digits_between:8,15` cannot be used).

`SettingSeeder` already ships `contact.phone`, `contact.whatsapp`, `contact.email`.

## 6.2 Vehicle columns + admin Contact section

The `vehicles.phone` / `whatsapp` / `phone_display` columns and the Filament **Contact override** tab landed in Phases 2 and 4. This phase adds:

- the same `ContactNumber` rule on `phone` and `whatsapp` (nullable — empty means "use the global fallback");
- `->placeholder(fn () => Setting::get('contact.phone'))` / `contact.whatsapp`, so the admin sees exactly what the listing will inherit.

## 6.3 Accessors

`app/Modules/Catalog/Vehicle.php` (the `contactPhone` / `contactWhatsApp` cascade already existed):

| Accessor | Behaviour |
|---|---|
| `telHref` | `contactPhone` → strip formatting → `tel:+94771234567` (keeps a leading `+` only if the source had one) |
| `whatsappNumber` | `contactWhatsApp` → digits only → local `0…` rewritten to `94…` |
| `whatsappUrl` | `https://wa.me/{digits}?text=` + `rawurlencode('interested in "{title}" - {price} LKR {url}')` |
| `listingTitle()` | `make + model + trim` — now shared by the card, the detail heading and the WhatsApp message |

Cascade: **per-listing value → global setting → (WhatsApp only) the contact phone → empty.**

## 6.4 `<x-listing.contact-actions>`

`resources/views/components/listing/contact-actions.blade.php` — `@props(['vehicle', 'variant' => 'card'])`.

- Renders **nothing** unless a call or WhatsApp number resolves (spec: *renders only when a number exists*).
- `variant="card"` → `border-t … p-3` footer wrapper; `variant="hero"` → `mt-4`.
- Wrapper carries `data-contact-actions="card|hero"` for assertions.
- Call uses `phone_display` as its label, falling back to **Call**.

## 6.5 Call

Solid high-contrast `bg-brand-700` / white, `min-h-11` (**44 px**), `font-semibold`, `whitespace-nowrap`, visible focus ring. Plain `tel:` — no `target="_blank"` (a telephone handler is not a navigation).

## 6.6 WhatsApp

Outlined `border-2 border-slate-900` button, `target="_blank" rel="noopener noreferrer nofollow"`, `href` built by `whatsappUrl`.

## 6.7 Share

Alpine dropdown inside the component's own `x-data` (`open`, `copied`, `copyLink()`):

- **Facebook** → `https://www.facebook.com/sharer/sharer.php?u={rawurlencode(route('vehicles.show'))}` (conflict E: share the URL, `og:image` supplies the preview).
- **Copy link** → `navigator.clipboard.writeText()` when available, `execCommand('copy')` fallback, then a fixed-position **"Link copied"** toast for 2.5 s.

## 6.8 Wiring

| Location | Variant |
|---|---|
| `<x-listing.card>` footer (home featured + latest, `/vehicles` grid, detail "Similar cars") | `card` |
| Detail page → **Talk to the seller** panel | `hero` |

The card keeps its `@isset($contactActions)` override slot; the component is the `@else` default.

## 6.9 Link attributes

`target="_blank" rel="noopener noreferrer nofollow"` on both external links (WhatsApp, Facebook sharer).

---

## Verification

| Check | Result |
|---|---|
| `php artisan test` | ✅ **168 passed** (500 assertions) — +10 new |
| `php vendor/bin/pint --test` | ✅ passed |
| `npm run build` | ✅ CSS **81.06 kB** (15.18 gzip) · JS 51.52 kB (19.51 gzip) |
| Live `/` | ✅ 200 + `data-contact-actions="card"` |
| Live `/vehicles` | ✅ 200 + `data-contact-actions="card"` |
| Live `/vehicles/{slug}` | ✅ 200 + `hero` · `tel:` · `wa.me` · facebook sharer · `copyLink()` |

### New tests

| File | Tests | Covers |
|---|---|---|
| `tests/Feature/Contact/ContactActionsTest.php` | 10 | normalised `tel:` + hero variant · settings fallback · per-listing precedence · WhatsApp digit-stripping **and** full message encoding · share URL encoding + `rel` + copy handler · hidden when nothing configured · card variant on home + `/vehicles` · `phone_display` label · `ContactNumber` digit counting · Filament create rejects/accepts |

---

## Gotchas hit (carry forward)

1. **Laravel's `digits_between:8,15` requires the value to be *all* digits** — `preg_match('/^[0-9]+$/')` first, so `+94 77 123 4567` (the exact value `SettingSeeder` writes) fails. The spec's intent is "8–15 digits", so `App\Modules\Shared\Rules\ContactNumber` strips non-digits, counts them, then applies 8–15. Never put raw `digits_between` on a phone field that may carry `+`, spaces or brackets.
2. **`x-data="…"` is a double-quoted HTML attribute — JS string literals inside it cannot use `"`, and Blade's `@js()` / `Js::from()` emit `"`.** The share URL therefore lives on a hidden `<a x-ref="shareUrl" href="…">` and `copyLink()` reads `getAttribute('href')`. Trying to inline `@js($shareUrl)` silently breaks the attribute and the whole `x-data` object.
3. **`Livewire::test()` on a Filament resource page returns a `Testable` with `instance() === null` until `mount()` succeeds.** `CreateRecord::mount()` calls `authorizeAccess()`, which `abort(403)`s for a guest — the failure is swallowed and the next `fillForm()` blows up with *"Attempt to read property `form` on null"*. Seed `RoleSeeder` and `actingAs(staff('admin'))` first, exactly like the HTTP admin tests already do.
4. **`wa.me` needs the full international number.** `wa.me/0771234567` silently does nothing on device, so `whatsappNumber` rewrites a leading `0` to `94` (Sri Lanka — this build is LKR-only). Keep the country assumption in one accessor so Phase 9 can parameterise it.
5. **`tel:` assertions are ambiguous on the storefront** — the footer independently renders `Setting::get('contact.phone')` as a `tel:` link, so `assertSee('tel:+94…')` passes even if the component is broken. Assert `wa.me/…` (component-only) when testing per-listing precedence.
6. **`@source '../../storage/framework/views/*.php'` (added in Phase 1) leaked Filament's admin classes into the public bundle and made the CSS size depend on which views happened to be compiled.** Removing it: **118.03 kB → 81.06 kB**, `fi-*` occurrences → 0. Compiled views are a cache derived from sources that are already scanned (`../**/*.blade.php`) or explicitly sourced (Laravel/Livewire pagination) — never point `@source` at `storage/framework/views`.
7. **Rebuild the CSS after any Blade edit** (Tailwind v4 scans at build time), and re-check after removing a `@source`: a smaller bundle is only good if the classes you added (`min-h-11`, `-translate-x-1/2`, `aspect-[4/3]`…) survived.

**Phase 6 complete. Phase 7 (Enquiries & Public Submissions) cleared to begin.**
