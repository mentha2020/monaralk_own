# Phase 7 — Enquiries & Public Submissions

**Status:** ✅ Complete
**Date:** 2026-10-09
**Result:** Public enquiry forms (detail + `/contact`) with honeypot, per-IP rate limiting, auth prefill and a queued team notification; a five-step `/submit` wizard that persists `vehicle_submissions` as `pending`; and admin moderation where **reject now demands a reason and emails it to the submitter**. **188 tests passing** (649 assertions), Pint clean.

---

## 7a — Detail enquiry form + general contact

| Piece | File |
|---|---|
| Form Request | `app/Http/Requests/StoreEnquiryRequest.php` |
| Controller | `app/Modules/Leads/Http/Controllers/EnquiryController.php` |
| Queued notification | `app/Modules/Leads/Notifications/EnquiryReceived.php` |
| Shared form partial | `resources/views/partials/enquiry-form.blade.php` |
| Contact page | `resources/views/contact/create.blade.php` |

**Routes**

| Method | URI | Name |
|---|---|---|
| GET | `/contact` | `contact` |
| POST | `/contact` | `contact.store` |
| POST | `/vehicles/{vehicle}/enquiry` | `vehicles.enquiry` |

- **Validation** — `name` / `email` / `message` required, `phone` optional but must pass the shared `ContactNumber` digit rule (Phase 6).
- **Honeypot** — an off-screen `website` input with `tabindex="-1"`. Deliberately **not validated**: when it is filled the controller writes nothing and returns the *same* success flash, so a bot cannot tell it was dropped.
- **Rate limit** — `RateLimiter::for('enquiries', …)` in `AppServiceProvider`, `Limit::perMinutes(10, 5)->by($request->ip())`, applied as `throttle:enquiries` on both POST routes. The 6th attempt inside the window gets a 429 before the controller runs.
- **Auth prefill** — the partial fills `name`/`email` from `auth()->user()`, and the stored row carries `user_id`.
- **Queued notification** — `EnquiryReceived implements ShouldQueue`; recipients are every user holding `enquiry.view` (admin + editor per `RoleSeeder`), resolved with `whereHas('roles'…permissions)` plus a direct-permission `orWhereHas`.
- **Detail page** — the partial is included in the *Talk to the seller* box directly under `<x-listing.contact-actions>`.
- **Discoverability** — nav (desktop + mobile) gained *Sell Your Car* → `/submit` and *Contact* → `/contact`; the footer gained both links too.

## 7b — `/submit` multi-step wizard

`app/Livewire/SubmitVehicleWizard.php` + `resources/views/livewire/submit-vehicle-wizard.blade.php`, hosted by `resources/views/submit/create.blade.php` (`Route::view('/submit', …)`, name `submit.create`).

**Steps:** `1 Details → 2 Specs → 3 Photos → 4 Contact → 5 Review → confirmation`

- **Details** — cascading Make → Model select (`updated()` resets `form.model_id` when the make changes), year, condition, location, description.
- **Specs** — mileage, price (LKR), transmission, fuel, body, colour, trim, registration, previous owners.
- **Photos** — Livewire `WithFileUploads`, 1–10 files, JPG/PNG/WebP, 5 MB each, per-photo remove button, cover badge on the first.
- **Contact** — name / email / phone (prefilled for a signed-in seller, `user_id` captured).
- **Review** — `summary()` renders a `label/value` list built from live taxonomy lookups; nothing is written until **Submit for review**.
- **Storage** — `VehicleSubmission::create(… status = Pending, ip = request()->ip())`, photos moved to `submissions/{id}` on the `public` disk and written back into `images`, then the confirmation panel shows `SUB-000123` (from `VehicleSubmission::reference()`).
- **Guard rails** — `next()` validates *only* the current step; `submit()` validates **all** rules, so a jumped (`->set('step', 5)`) or double-submitted wizard still cannot persist an invalid row, and `if ($this->submissionId !== null) return;` makes repeat submits idempotent.

## 7c — Moderation

`SubmissionResource` table actions (`approve` / `reject` / `needs changes`) already existed from Phase 4; this phase makes rejection a real decision:

```php
->form([Forms\Components\Textarea::make('reason')->required() …])
->action(fn (VehicleSubmission $record, array $data, SubmissionApprover $approver) => …
    $approver->decide($record, self::approver(), SubmissionStatus::Rejected, $data['reason']);
    $record->notify(new SubmissionRejected($record, $data['reason'])));
```

- `VehicleSubmission` now uses the `Notifiable` trait.
- `SubmissionRejected implements ShouldQueue` — subject *"Your listing submission was not approved"*, carries reason, `SUB-…` reference and a link back to `/submit`.
- Approve path unchanged: `SubmissionApprover::approve()` inside `DB::transaction` → `vehicles` with `source = public`, `status = draft`, images copied, back-reference written.

---

## Verification

| Check | Result |
|---|---|
| `php artisan test` | ✅ **188 passed** (649 assertions) — +20 new |
| `php vendor/bin/pint` | ✅ passed |
| `npm run build` | ✅ CSS **83.22 kB** (15.47 gzip) · JS 51.52 kB (19.51 gzip) |
| Live `/` · `/vehicles` | ✅ 200, nav shows *Sell Your Car* / *Contact*, no stray forms |
| Live `/vehicles/{slug}` | ✅ 200, honeypot + enquiry form + contact actions present |
| Live `/contact` | ✅ 200, honeypot + shared partial + WhatsApp/email block |
| Live `/submit` | ✅ 200, wizard step 1 renders |

### Roadmap Verify list

| Requirement | Result |
|---|---|
| validation ✓ | ✅ invalid email / empty message / 3-digit phone → errors, **0 rows** |
| rate-limit ✓ | ✅ 5 posts succeed, 6th → **429**, count stays **5** |
| approve creates exactly one vehicle ✓ | ✅ service test *and* admin-table test both assert `Vehicle::count() === 1` |
| reject leaves none ✓ | ✅ `Vehicle::count() === 0`, status `rejected`, reason stored, notification queued |

### New tests

| File | Tests | Covers |
|---|---|---|
| `tests/Feature/Contact/EnquiryFormTest.php` | 7 | stored + linked + queued to team · general contact (`vehicle_id = null`) · auth prefill/`user_id` · validation · silent honeypot drop · 5-then-429 rate limit · both forms render |
| `tests/Feature/Submissions/SubmitWizardTest.php` | 9 | page renders · step gating · make→model reset · phone rule · signed-in prefill · review summary · full run persists one pending submission + 2 photos on disk · signed-in link · triple-submit stays at 1 |
| `tests/Feature/Admin/SubmissionModerationTest.php` | 4 | reject stores reason + queues email + **0 vehicles** · missing reason refused · approve creates **exactly one** vehicle · `viewer` sees neither action |

---

## Gotchas hit (carry forward)

1. **Blade has no `@return`.** An unknown `@foo` is passed through as literal text, so the confirmation block printed `@return` on the page. Multi-branch views must be `@if … @else … @endif`, not "render then bail".
2. **`@error('photos.*')` never fires.** Validation stores array errors under the concrete key (`photos.0`), and `MessageBag::first()` does not expand wildcards. Loop `$errors->keys()` and `str_starts_with($key, 'photos')`.
3. **A Filament table action with a `form()` is already its own confirmation** — pairing it with `requiresConfirmation()` double-prompts. In tests, `callTableAction($name, $record, data: [...])` skips the modal but still runs validation, and a failed run leaves the action mounted so `assertHasTableActionErrors(['reason'])` works.
4. **InnoDB auto-increment counters are not rolled back.** `RefreshDatabase`'s transaction resets rows but the sequence keeps climbing, so a hard-coded `SUB-000001` passes file-alone and fails in the full suite — read `reference()` off the record you just created.
5. **Livewire reads `config('livewire.temporary_file_upload.disk')` at call time** (no `config/livewire.php` is published, so it falls back to `filesystems.default`). Set `config([... => 'public'])` + `Storage::fake('public')` in `beforeEach` and `Storage::forgetDisk('public')` in `afterEach`, otherwise temp uploads land on the real disk.
6. **Validate the whole payload in `submit()`, not just the current step.** `Livewire::test()->set('step', 5)` can jump the wizard — the guard is a full-rule re-validation on the write path, plus an idempotency check on `submissionId`.
7. **`NotificationFake` has no `assertSentOnQueue`.** Prove "queued" by asserting the notification class `toImplement(ShouldQueue::class)` alongside `assertSentTo()`.
8. **Honeypots must not be validated.** A `max:0` rule tells the bot exactly what tripped it; dropping silently and reusing the success flash keeps the trap invisible.

**Phase 7 complete. Phase 8 (Accounts: Favourites & Compare) cleared to begin.**
