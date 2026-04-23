# SFPA Voting — Architecture & Developer Guide

> Last updated: 2026-04-20 · matches commit `60054b4` · 216 tests passing

A single-page map of the system — what every folder does, how a request
flows through the code, and where to add new features without fighting
the existing patterns.

---

## 1. Stack

| Layer | Choice | Why |
|---|---|---|
| Framework | **Laravel 11** (PHP 8.3) | Maturity, strong ecosystem, fits Saudi hosting norms |
| Auth | Laravel session + Sanctum (API) + Spatie Permission | Cookie sessions for admin, tokens for `/api/v1/*` |
| DB | **MySQL 8** in prod, SQLite locally / in CI | Migrations are driver-aware where needed (ENUMs) |
| Frontend | **Tailwind CDN** + **Alpine.js 3** + tiny vanilla JS helpers | Zero build step; easy to host |
| Tests | **Pest 3** (PHPUnit 11) | 216 tests, ~20s full run |
| Architecture | **Modular monolith** | Separation by domain folder, single deploy unit |

No Node build is required — `public/js/datatable.js` and the Tailwind
CDN are all the frontend tooling.

---

## 2. Repo layout (birds-eye)

```
app/
├── Http/Controllers/
│   ├── Admin/                 ← every /admin/* controller
│   ├── Auth/                  ← Login, ForgotPassword
│   └── ProfileController.php
├── Models/
│   └── User.php               ← uses Spatie HasRoles + SoftDeletes
└── Modules/
    ├── Campaigns/             ← the core domain
    ├── Voting/                ← public-facing vote flow
    ├── Results/               ← ranking, approval, announce
    ├── Users/                 ← admin users (roles / invites)
    ├── Clubs/ · Players/ · Sports/ · Leagues/
    ├── Notifications/         ← editable email templates
    ├── Sms/                   ← SMS gateway (Twilio / Unifonic / Log)
    └── Shared/                ← cross-cutting helpers

resources/views/
├── layouts/admin.blade.php    ← sidebar + header for every admin screen
├── admin/                     ← one folder per module
├── components/admin/          ← Blade components (bulk-toolbar, brand-logo…)
├── auth/ · profile/           ← public-ish screens
└── partials/                  ← brand-head (fonts, tailwind utility classes)

routes/
├── web.php                    ← login + profile + password reset + delegates /admin
├── admin.php                  ← all authenticated admin routes (141 total)
├── api.php                    ← /api/v1 (Sanctum)
└── console.php                ← artisan scheduler entries
```

Each module directory is almost self-contained:

```
app/Modules/<Name>/
├── Models/        — Eloquent models
├── Enums/         — backed-enum state machines
├── Actions/       — one class per domain operation
├── Http/
│   ├── Controllers/   — thin: validate → dispatch action
│   ├── Requests/      — FormRequest classes
│   └── Resources/     — API JSON shape
├── Policies/      — authorization rules
├── database/
│   ├── migrations/    — scoped per module
│   └── seeders/ · factories/
├── routes/        — api.php / web.php loaded by ModulesServiceProvider
└── Services/      — ambient helpers (LiveVoterCountService, etc.)
```

---

## 3. How a request flows

Take **"admin submits a draft campaign for committee approval"**:

```
POST /admin/campaigns/17/submit-approval
  │
  ▼  routes/admin.php
AdminCampaignController@submitForApproval
  │  (authorize('submitApproval', $campaign))
  ▼
SubmitCampaignForApprovalAction::execute($campaign)
  │  1. assert status is Draft or Rejected
  │  2. assert campaign has categories
  │  3. update status = pending_approval
  │  4. LogActivityAction — audit trail row
  ▼
redirect()->route('admin.campaigns.show')->with('success', ...)
```

Four design rules hold everywhere:

1. **Thin controllers.** They call one Action; no business logic.
2. **Actions own the rules.** Each Action is a single-purpose class
   with an `execute()` method. They throw `DomainException` on
   user-visible rule violations — the global handler in
   `bootstrap/app.php` translates that to a 422 JSON or a flash error.
3. **Policies gate the permission side.** Every admin controller calls
   `$this->authorize('verb', $model)` → Spatie's permission system
   resolves via `<module>.<verb>`.
4. **Enums = state machines.** `CampaignStatus::canTransitionTo()`,
   `ResultStatus`, `ResultsVisibility` all forbid invalid jumps
   centrally so nothing can "jump" a state by accident.

---

## 4. Core domain — Campaigns & Voting

### 4.1 Campaign lifecycle

```
              ┌─ submit ─┐
Draft ────────────────────▶ PendingApproval
  ▲                             │
  │                             ├── approve → Published ─ activate ─▶ Active
  │                             │                │
  │                             │                └─ close ─▶ Closed
  │                             └── reject → Rejected ──────┐
  └──────────────────────────────────────── (edit & resubmit)
                                                            │
                                            Archived ◀── archive (any of above)
```

Defined in `app/Modules/Campaigns/Enums/CampaignStatus.php`. Each
edge is one Action — 16 actions total in the Campaigns module.

### 4.2 Campaign types

Three product flavours in `CampaignType`:

| Type | What the voter does | Domain bits |
|---|---|---|
| **IndividualAward** | Picks N players across one or more categories | Simple submit path |
| **TeamAward** | Picks clubs | Same submit path |
| **TeamOfTheSeason** | Builds a formation (GK=1 fixed, outfield=10 flexible, lines 2–6) | Dedicated `AdminTeamOfSeasonController`, own validator, own submit view |

Every campaign has a `public_token` (32 random chars). That token is
the entire voter-facing URL (`/vote/{token}`), so leaking a list of
campaign IDs doesn't help an attacker.

### 4.3 Voter journey

```
GET  /campaigns                    ← public list of Open / Upcoming
GET  /vote/{token}                 ← verify screen
POST /vote/{token}/verify          ← national_id OR mobile → session
GET  /vote/{token}/form            ← ballot; TOTS gets tos.blade.php
POST /vote/{token}/submit          ← writes Vote + VoteItems
GET  /vote/{token}/thanks          ← receipt
POST /vote/{token}/exit            ← clears session
```

Each endpoint is throttled individually — `throttle:10,1` on verify,
`throttle:20,1` on submit (see `app/Modules/Voting/routes/web.php`).

### 4.4 Vote-integrity guardrails

- **`PreventDuplicateVoteByPlayerAction`** — one vote per `(campaign, player_id)`
- **`voter_identifier`** on `votes` is a SHA-256 of national_id/mobile
- **Session TTL** — `CheckVoterSessionAction` drops entries after `voter_session.ttl_minutes` (default 15)
- **`isAcceptingVotes()`** on `Campaign` enforces Active + within date range

---

## 5. Results pipeline

```
Voting ends ──▶ admin clicks Recalculate
                        ▼
              CalculateCampaignResultsAction
                        ▼  (writes CampaignResult rows + items)
                 ResultStatus::Calculated
                        ▼
              Committee Approve ──▶ Approved (internal only)
                        ▼
              Committee Announce ──▶ Announced  →  Public can see it
```

Ties surface as a `pending_tie` flag on a category; committee resolves
via `ResolveTieAction`.

---

## 6. Supporting modules

### Notifications — editable email templates
- `email_templates` keyed by **(event, campaign_type, locale)**
- `TemplateRegistry` — single catalogue of 6 events + the `{variables}` each one allows
- `TemplateRenderer` — mustache-style `{dot.key}` substitution. No PHP eval, no Blade, no Twig
- Admin UI at `/admin/email-templates` shows a matrix and a live-preview editor
- Built-in cascade fallback: exact → generic → exact EN → generic EN → caller's hardcoded default

### SMS — 3-driver gateway
- `SmsDriverContract` — one-method interface (`send(to, message)`)
- Drivers: `LogDriver` (dev), `TwilioDriver`, `UnifonicDriver`
- `SmsService::send()` normalises numbers (`0501234567` → `+966501234567`) and logs one masked-phone line per attempt
- Admin configures the driver + secrets at `/admin/settings` (SMS tab); secrets encrypted via `MailConfig::encryptSafe()`

### Settings — key/value store
- One `settings` table, grouped (`general` / `mail` / `sms` / …)
- `SettingsService` caches the whole table for 60s
- Runtime mail config is overlaid from DB in `AppServiceProvider::boot()` via `MailConfig::apply()`

### Branding
- `platform_logo_path` + `app_name` settings drive the `<x-brand.logo>` component
- Appears in the sidebar, login, voter verify page, emails — single source

### Archive + soft delete
- `SoftDeletes` on User, Club, Player, Campaign
- Trait `Concerns/ArchivesResource` gives any admin controller 3 methods: `archive()`, `restore()`, `forceDelete()`
- Generic view `admin.shared.archive-list`
- Hub at `/admin/archive` aggregates trashed counts per module
- `forceDelete` is gated on a separate permission (super_admin only)

---

## 7. Authorization model

Every permission follows `<module>.<verb>`:

```
clubs.viewAny, clubs.view, clubs.create, clubs.update,
clubs.delete, clubs.restore, clubs.forceDelete
… same pattern for players, campaigns, results, sports, leagues

campaigns.publish / close / archive / approve   — lifecycle verbs
results.calculate / approve / hide / announce   — lifecycle verbs
users.manage / restore / forceDelete            — umbrella + archive
settings.update
```

Seeded roles:

| Role | What they can touch |
|---|---|
| `super_admin` | Everything, including all `*.forceDelete` |
| `committee` | Campaigns + Results; approve, hide, announce |
| `campaign_manager` | Clubs, players, campaigns (create / publish / close); NO forceDelete |
| `auditor` | Read-only across the board |

All policy checks pass through Laravel's gate (`$user->can('X')` or
`$this->authorize('verb', $model)`).

---

## 8. Security layers (what's already in place)

Collected from 3 security rounds:

- **Login**: rate-limited 5/min per (email+IP); 10-char passwords with symbols
- **CSRF**: default Laravel web middleware on every admin POST
- **Session**: `http_only`, `same_site=lax`, TTL 15-min on voter sessions
- **Headers**: `SecurityHeaders` middleware — X-Frame, X-Content-Type, Referrer, Permissions policies
- **Uploads**: PNG/JPG/WEBP only (no SVG — XSS risk), `max:4096` on player photos, `max:2048` on club/platform logos
- **CSV formula injection**: `App\Support\Csv::safe()` prefixes risky cells with `'`
- **PII masking**: player-export default masks national_id + mobile (`1012****78`)
- **Secrets**: SMTP password, SMS auth token, Unifonic AppSid all stored via `Crypt::encryptString()`
- **Global FK handler**: `bootstrap/app.php` converts `23000 / 1451 / 1452` into friendly 409/flash instead of 500
- **API parity**: `PUT /api/v1/campaigns/{id}` and `DELETE` enforce the same "Draft-or-Rejected" / vote-count rules the admin UI applies

---

## 9. Frontend model

**No bundler.** Everything loads from CDN:

- Tailwind Play CDN
- Alpine.js 3
- `public/js/datatable.js` — 120 lines of vanilla search + sort

Shared CSS helpers live in `resources/views/partials/brand-head.blade.php`:
- `.btn-save` / `.btn-edit` / `.btn-delete` / `.btn-brand` / `.btn-ghost` / `.btn-danger`
- `.field-input` / `.field-select` / `.field-textarea` / `.field-label` / `.field-error`
- `.card` / `.form-wrap` / `.badge-active` / `.badge-inactive`

Blade components worth knowing:

| Component | Purpose |
|---|---|
| `<x-brand.logo>` | Platform wordmark (logo image or initials) |
| `<x-admin.bulk-toolbar>` | Sticky bulk-action bar with confirm |
| `<x-admin.datatable-head>` | Search box + row counter for any `<table data-datatable>` |
| `<x-admin.campaigns.*>` | Status banner, campaign card, danger zone, etc. |
| `<x-admin.results.*>` | Action bar, ranking list, workflow timeline |

---

## 10. How to develop — playbook

### 10.1 Add a new **domain operation** (e.g. "duplicate campaign")

1. **Action** — `app/Modules/Campaigns/Actions/DuplicateCampaignAction.php`
2. **Policy verb** — add `campaigns.duplicate` to `RolesPermissionsSeeder` + re-seed
3. **Route** — `routes/admin.php`, `Route::post('{campaign}/duplicate', …)`
4. **Controller method** — `AdminCampaignController::duplicate` → calls the Action
5. **Button** — reuse `.btn-save` or `.btn-ghost` in `status-banner.blade.php`
6. **Test** — copy `tests/Feature/Campaigns/*` as a template

### 10.2 Add a new **module** (e.g. "Sponsors")

```
app/Modules/Sponsors/
├── Models/Sponsor.php
├── Enums/…
├── Actions/CreateSponsorAction.php
├── Http/{Controllers,Requests,Resources}/
├── Policies/SponsorPolicy.php
├── database/migrations/…_create_sponsors_table.php
└── routes/api.php
```

Then:
- Register routes prefix in `app/Providers/ModulesServiceProvider.php`
- Add perms (`sponsors.viewAny…`) to the seeder + TestHelpers
- Add sidebar entry in `layouts/admin.blade.php`
- For archive support: `use ArchivesResource;` trait + 4 hooks — done

### 10.3 Add a new **email template event**

Edit `app/Modules/Notifications/Support/TemplateRegistry.php` —
append a new entry to `EVENTS`, list the `{vars}` you'll expose.
The admin UI picks it up automatically. Seed defaults in
`EmailTemplatesSeeder::run()` if you want out-of-box content.

### 10.4 Add a new **SMS driver**

1. `app/Modules/Sms/Drivers/YourDriver.php` implementing `SmsDriverContract`
2. Add an option to the `UpdateSmsSettingsRequest::rules['sms_driver']` allowlist
3. Add the credential fields in the form + validation
4. Add the branch in `SmsService::resolveDriver()`

### 10.5 Add a new **setting**

1. Decide the group (`general` / `mail` / `sms` / new)
2. Add the key + default reader in `AdminSettingsController::readXxxSettings()`
3. Add fields to the settings view inside the correct tab
4. Add the `UpdateXxxSettingsRequest` if validation is non-trivial
5. Read via `app(SettingsService::class)->get('your_key')`; cache handles the rest

### 10.6 Add a **new voter restriction** (e.g. only verified phones)

Edit `VerifyVoterIdentityAction::execute()`. It already returns a
`(player, method, normalized_value)` tuple — add your OTP step or
extra guard before returning, then the rest of the pipeline is
unchanged because `CreateVoterSessionAction` just stores whatever
tuple you give it.

---

## 11. Tests & CI

Run everything:
```bash
php vendor/bin/pest --compact
```

Targeted:
```bash
php vendor/bin/pest --filter=SmsSettingsTest
php vendor/bin/pest --filter=Campaigns
```

Feature tests per folder (13 areas, 216 total). Each test seeds roles
+ permissions through `tests/TestHelpers.php::seedRolesAndPermissions()`.

Known-risky / known-skipped (intentional):
- 1 risky test in TOS flow (no-assertion branch)
- 2 skipped — locale validation and CSRF-on-login-POST (test middleware
  stack doesn't apply `VerifyCsrfToken`)

---

## 12. Deployment checklist

From `.env.production.example`:

```
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=warning
DB_CONNECTION=mysql
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
BCRYPT_ROUNDS=12
```

After `composer install --no-dev`:

```bash
php artisan key:generate           # fresh install only
php artisan migrate --force
php artisan db:seed --class=RolesPermissionsSeeder --force
php artisan db:seed --class=EmailTemplatesSeeder   --force
php artisan storage:link
php artisan config:cache route:cache view:cache
```

Reverse proxy (Nginx) terminates HTTPS + adds HSTS. App stays on
PHP-FPM. A single 4 vCPU / 8 GB VM handles the projected SFPA scale
with ample headroom (see `docs/qa/LOAD_TEST_REPORT.md`).

---

## 13. Quick reference — where things live

| I want to… | Go here |
|---|---|
| Change a campaign lifecycle rule | `Campaigns/Enums/CampaignStatus.php` → `canTransitionTo()` |
| Add a dashboard card | `Shared/Queries/DashboardData.php` + `views/admin/dashboard.blade.php` |
| Touch voter UI | `app/Modules/Voting/resources/views/` |
| Change admin sidebar | `resources/views/layouts/admin.blade.php` |
| Add a permission | `RolesPermissionsSeeder.php` + `TestHelpers.php` |
| Add a language string | `lang/ar.json` (key = English source text) |
| Add a new table | module's `database/migrations/` |
| Wire a new event → email | `TemplateRegistry::EVENTS` + caller hands vars to `TemplateRenderer::render()` |
| Send an SMS | `app(SmsService::class)->send($phone, $message)` |
| Configure SMTP / SMS / logo from DB | `app(SettingsService::class)->get(...)` / `set(...)` |

---

## 14. What's next (ideas, not commitments)

- Wire the voter-OTP flow end-to-end (infra is there — needs UI + SMS send + verify step)
- Per-campaign custom email templates (registry supports it)
- WebSockets / server-sent events for live result counts
- Queued notifications (right now they're sent synchronously via `MAIL_MAILER=log` by default)
- Move Tailwind + Alpine from CDN to a tiny Vite build — enables a strict CSP

All of these are additions, not rewrites — the module boundaries should
absorb them cleanly.
