# Manual QA Checklists

Automation covers what can be asserted in a test run: heading structure, `alt`
text, accessible names, skip links, dark-mode variants, responsive classes,
WCAG contrast ratios, query counts, asset weight and the full listing journey.
These checklists cover the parts that need a human with a real browser and a
real screen: layout at three viewport widths, visual dark-mode consistency,
cross-engine rendering and 44x44 touch targets.

Run these against a seeded database (`php artisan migrate --seed`) with built
assets (`npm run build`) and the dev server (`php artisan serve`).

Automated counterparts live in:

- `tests/Feature/Qa/AccessibilityMarkupTest.php`
- `tests/Unit/ContrastTest.php`
- `tests/Feature/Qa/EndToEndSmokeTest.php`
- `tests/Feature/Qa/PerformanceBudgetTest.php`

---

## 17.3 Responsive QA - 375 / 768 / 1440

Check each page at **375px** (iPhone SE / small Android), **768px** (iPad
portrait) and **1440px** (desktop). The storefront grids are `sm:` and `lg:`
driven, so 768px is expected to already be multi-column.

| # | Check | 375 | 768 | 1440 |
|---|---|---|---|---|
| 1 | Home hero search stack is readable and the button is full width | | | |
| 2 | Home featured / latest / brand grids collapse to one column on 375 | | | |
| 3 | `/vehicles` filters collapse behind a toggle; results stay visible | | | |
| 4 | Listing cards never overflow horizontally | | | |
| 5 | Detail page gallery is full width and thumbnails scroll | | | |
| 6 | Detail finance calculator fields stay tappable | | | |
| 7 | Compare table scrolls horizontally instead of squashing | | | |
| 8 | `/submit` wizard steps and progress dots fit | | | |
| 9 | Header nav becomes the hamburger menu at 375 | | | |
| 10 | Footer columns stack without orphaned links | | | |
| 11 | Contact page phone / WhatsApp / email rows wrap cleanly | | | |
| 12 | No horizontal scrollbar on any page at any width | | | |
| 13 | `/admin` Filament tables scroll rather than clip | | | |

---

## 17.4 Dark-mode QA - public + admin

The theme is class-based (`.dark` on `<html>`), driven by
`localStorage['monaralk-theme']` with a `prefers-color-scheme` fallback, and is
toggled from the header button on the storefront and from the button in the
Breeze guest / account layouts.

| # | Check | Light | Dark |
|---|---|---|---|
| 1 | Home renders with no white flash or invisible text | | |
| 2 | `/vehicles` filter panel, chips and selects all invert | | |
| 3 | Listing card background, border and text all invert | | |
| 4 | Detail gallery frame, price and spec rows all invert | | |
| 5 | Finance calculator inputs are readable | | |
| 6 | `/submit` wizard fields, dropzone and previews all invert | | |
| 7 | `/contact` form and info panel all invert | | |
| 8 | `/login`, `/register` card and gradient background invert | | |
| 9 | `/dashboard` and `/profile` invert | | |
| 10 | `/favourites` and `/compare` invert | | |
| 11 | 404 / 429 / 500 error pages invert | | |
| 12 | Theme choice persists across a reload and across pages | | |
| 13 | `prefers-color-scheme: dark` is honoured on a first visit | | |
| 14 | Filament `/admin` uses its own dark theme and does not fight the storefront | | |

---

## 17.5 Tap targets (manual pass)

Automation cannot measure rendered pixel size. Every primary control carries
`h-11` / `min-h-11` (44px) or larger, and inputs are at least 40px tall with
8-10px vertical padding; walk the list once by hand.

| # | Control | >= 44px |
|---|---|---|
| 1 | Header favourites / compare badges and dark-mode toggle | | |
| 2 | Mobile hamburger menu items | | |
| 3 | Listing card "Save" and "Compare" buttons | | |
| 4 | Detail page Call / WhatsApp / Share row | | |
| 5 | Detail page Save / Compare buttons | | |
| 6 | `/vehicles` filter selects and the clear-filters button | | |
| 7 | `/submit` wizard Next / Back / Submit buttons | | |
| 8 | `/contact` submit button | | |
| 9 | Auth page primary buttons | | |
| 10 | Language switcher EN / SI | | |

Known exceptions, accepted deliberately:

- Desktop nav links and the language switcher are compact on wide screens where
  a mouse is expected; they remain well above the WCAG 2.5.8 AA 24px minimum.
- Filament admin controls use Filament's own sizing.

---

## 17.6 Browser QA - Chrome / Safari / Firefox / Edge

Record the engine version used for each sign-off.

| # | Check | Chrome | Safari | Firefox | Edge |
|---|---|---|---|---|---|
| 1 | Home renders, fonts load, no console errors | | | | |
| 2 | `/vehicles` filters update results without a full reload | | | | |
| 3 | Detail gallery arrows / dots / keyboard work | | | | |
| 4 | Share dropdown opens and "copy link" fires the toast | | | | |
| 5 | `tel:` link opens the dialler; `wa.me` opens WhatsApp | | | | |
| 6 | Dark-mode toggle applies instantly and persists | | | | |
| 7 | `/submit` photo upload previews appear and delete works | | | | |
| 8 | Locale switch to `si` renders Sinhala without tofu | | | | |
| 9 | Detail spec-sheet PDF downloads | | | | |
| 10 | `/sitemap.xml` and `/robots.txt` render | | | | |
| 11 | `/admin` login, vehicle table and image upload work | | | | |
| 12 | Skip link appears on Tab from the first control | | | | |

Known engine notes:

- Safari: confirm the `<picture>` / WebP fallback still paints if WebP is
  unavailable.
- Firefox: confirm the `x-cloak` Alpine elements never flash unstyled.
- Edge: confirm the sticky header backdrop blur does not cause jank on scroll.

---

## Sign-off

| Section | Owner | Date | Result |
|---|---|---|---|
| 17.3 Responsive | | | |
| 17.4 Dark mode | | | |
| 17.5 Tap targets | | | |
| 17.6 Browsers | | | |
