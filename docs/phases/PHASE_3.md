# Phase 3 — Authentication, RBAC & Authorization

**Status:** ✅ Complete
**Date:** 2026-10-08
**Result:** Staff-only admin panel, Spatie middleware aliases, 4 policies wired, and every Breeze view re-themed onto brand tokens with a working light/dark toggle. **77 tests passing**, Pint clean.

---

## 3.1 Spatie middleware aliases

`bootstrap/app.php` → `withMiddleware()`:

```php
$middleware->alias([
    'role'          => Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission'    => Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
]);
```

Roles + the 16 permissions were seeded in Phase 2 (`RoleSeeder`); this phase registers the aliases so routes can actually use them.

## 3.2 `HasRoles` on `User`

Already applied in Phase 2 — verified by `the staff role list matches the seeded roles`.

## 3.3 Filament `canAccessPanel()` restricted to staff roles

`App\Models\User` now implements `Filament\Models\Contracts\FilamentUser`:

```php
public const STAFF_ROLES = ['super_admin', 'admin', 'editor', 'viewer'];

public function isStaff(): bool { return $this->hasAnyRole(self::STAFF_ROLES); }

public function canAccessPanel(Panel $panel): bool { return $this->isStaff(); }
```

Without the `FilamentUser` contract Filament lets **any** authenticated user into `/admin`.

## 3.4 Gate registrations

Already wired in Phase 2 through `ModulesServiceProvider::boot()` — re-asserted by `every module policy is registered on the gate`.

## 3.5 Re-theme Breeze auth views (brand, dark mode)

### Brand tokens — `resources/css/app.css`

```css
@theme {
    --font-sans: 'Figtree', ...;
    --color-brand-50 … --color-brand-950;   /* teal-emerald marketplace palette */
    --color-accent-400/500/600;             /* amber CTAs + Filament accent */
}
```

`@custom-variant dark (&:where(.dark, .dark *))` was already registered in Phase 1, so `dark:` utilities compile.

### Logo

`components/application-logo.blade.php` replaced the Laravel "mountain" mark with a Monaralk mark — brand rounded-square + white "M" — plus an optional `wordmark` slot (`<x-application-logo wordmark />`).

### Theme mechanism (no Alpine dependency)

Both layouts emit:

1. an inline `<head>` script that applies `localStorage['monaralk-theme']` (falling back to `prefers-color-scheme`) **before paint** — no flash;
2. a bottom-of-body script bound to `[data-theme-toggle]` / `#theme-toggle` that flips `.dark` on `<html>` and persists the choice.

Toggles live in the guest layout (top-right) and the auth'd nav bar.

### Views updated

| Area | Change |
|---|---|
| `layouts/guest` | brand gradient background, wordmark lockup + tagline, card with ring/shadow, dark surfaces, toggle |
| `layouts/app` | dark page/header surfaces, theme script |
| `layouts/navigation` | wordmark logo, dark nav bar, toggle button, dark dropdown/hamburger/responsive panels |
| `components/*` | `text-input`, `input-label`, `primary-button`, `secondary-button`, `danger-button`, `input-error`, `auth-session-status`, `nav-link`, `responsive-nav-link`, `dropdown-link`, `dropdown`, `modal` — all carry `dark:` variants; primary actions moved from gray/indigo → **brand-600** |
| `auth/*` (login, register, forgot-password, confirm-password, verify-email) | indigo focus rings → brand, dark text |
| `profile/*` + `dashboard` | dark headings, body copy and cards |

`welcome.blade.php` was left untouched — it ships with its own inlined stylesheet and is replaced wholesale by the public home page in Phase 5.

## 3.6 Password reset / email verification verified

Breeze's `PasswordResetTest` + `EmailVerificationTest` already passed; this phase adds render coverage so a Blade regression in those screens fails the build:

- `/forgot-password` 200 · `PasswordResetLinkSent` dispatched for a valid email
- `/reset-password/{token}?email=…` 200
- `/email/verify` 200 for an **unverified** user
- `/login`, `/register` 200 branded

---

## Verification

| Check | Result |
|---|---|
| `npm run build` | ✅ CSS 91.68 kB (16.78 gzip) · JS 51.52 kB (19.51 gzip) |
| `php artisan test` | ✅ **77 passed** (206 assertions) — +21 new |
| `php vendor/bin/pint --test` | ✅ passed |
| Live `/login` `/register` `/forgot-password` | ✅ 200, brand mark + toggle present |
| Live `/admin` guest → `/admin/login` | ✅ 302 |
| Live `/admin` staff (4 roles) | ✅ 200 |
| Live `/admin` non-staff | ✅ 403 |

### New tests

- `tests/Feature/Auth/RbacTest.php` (13) — middleware aliases registered · staff list vs seeded roles · guest 302 to panel login · all 4 staff roles reach `/admin` · non-staff **403** · `role:` allows/denies · `auth`+`role:` sends guests to `/login` · bare `role:` answers guests 403 · `permission:` granularity · `can:publish` **200/403/302**
- `tests/Feature/Auth/AuthViewsTest.php` (7) — branded login/register/forgot/reset/verify/dashboard renders, reset-link event, dark-mode hooks

---

## Gotchas hit (carry forward)

1. **Spatie's `role:`/`permission:` middleware do NOT redirect guests** — they throw `UnauthorizedException::notLoggedIn()` → **403**. Always stack `auth` before them (`Route::middleware(['web', 'auth', 'role:editor'])`) when you want a login redirect.
2. **`can:ability,{param}` resolves the model through implicit binding, which only runs for the `web` group and only for type-hinted closure parameters.** A route registered as `Route::middleware('can:publish,vehicle')` with `fn () => …` receives the raw **slug string**, the policy denies, and you get a mysterious 403. Fix: include the `web` group *and* type-hint the parameter (`fn (Vehicle $vehicle) => …`).
3. **Laravel 12 ships `Illuminate\Auth\Events\PasswordResetLinkSent` — not `PasswordResetLinkCreated`** (that name doesn't exist in `vendor/laravel/framework`). Verify the event class exists before faking it.
4. **Breeze's `UserFactory` sets `email_verified_at => now()`** — a test that expects the verification-notice screen must pass `['email_verified_at' => null]`.
5. **`Gate::getPolicyFor()` returns an instance, not a class string** (carried from Phase 2).
6. **Re-run `npm run build` after any Blade edit** — Tailwind v4 scans templates at build time, so a `dark:` class added after the build is silently missing from the CSS.
7. **Shared Pest helpers** (`staff()`) belong in `tests/Pest.php`, not inside an individual test file — Pest loads every test file into one process and duplicate function declarations are fatal.

**Phase 3 complete. Phase 4 (Admin Panel — Filament 3) cleared to begin.**
