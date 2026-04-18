# SFPA Voting — Master Test Plan

**Author:** QA Lead (15y exp.)
**System:** SFPA Electronic Voting Platform (Laravel 11 + PHP 8.3, modular monolith)
**Build under test:** branch `main` @ commit `33c3ef6`
**Automation status:** **167 passed · 2 skipped · 1 risky · 0 failed** (Pest 3, 392 assertions, 10.16s)
**Scope:** End-to-end — Web (admin + public), API v1 (Sanctum), data integrity, security, i18n.

---

## 1. Test Strategy

| Layer | Tooling | Coverage |
|---|---|---|
| Unit | Pest 3 / PHPUnit 11 | Domain rules (TOTS distribution, status transitions) |
| Feature | Pest + RefreshDatabase | Controllers, Actions, FormRequests, Policies |
| Integration | Pest HTTP client | Full route → DB round-trips |
| Security | Pest + manual | AuthN/AuthZ, IDOR, CSRF, XSS, SQLi, rate-limit |
| Manual / Exploratory | Browser (Chrome, Firefox, Safari iOS) | UX, RTL/LTR, a11y, print |
| Smoke (post-deploy) | curl / Lighthouse | `/up`, login, public campaigns, headers |

### Test environments
- **Local dev** — SQLite + `php artisan serve` (current).
- **Staging** — MySQL 8 + Nginx + HTTPS (target before pilot).
- **Production** — same as staging + monitoring (Sentry/Logtail recommended).

### Definitions
- **UC** = Use Case (business-level scenario)
- **TC** = Test Case (executable verification)
- **AC** = Acceptance Criteria
- **P0/P1/P2/P3** = Priority (P0 blocks release)

---

## 2. Actors & Roles

| Role | Permissions (high-level) |
|---|---|
| **Public voter** (unauth) | View published campaigns, verify identity, cast vote, view announced results |
| **super_admin** | Everything + user/role management |
| **committee** | Approve/reject campaigns, approve/announce results, resolve ties |
| **campaign_manager** | CRUD campaigns/categories/candidates, players, clubs, run imports |
| **auditor** | Read-only access to all admin views + export |

---

## 3. Module Map

| # | Module | Routes | Test files |
|---|---|---|---|
| 1 | **Auth** | `/login`, `/logout`, `/set-locale/{locale}` | AuthTest, SecurityTest, ExtendedSecurityTest |
| 2 | **Campaigns** | `/admin/campaigns/*`, `api/v1/campaigns/*` | CampaignLifecycleTest, CommitteeApprovalTest, CampaignStatusTransitionsTest, CampaignCreationFormTest, EditAndActivateTest |
| 3 | **Categories & Candidates** | `/admin/campaigns/{c}/categories`, `/admin/categories/{cat}` | CategoryAdminTest, CategoryRedirectAndPositionTest, MultipleChoiceTest |
| 4 | **Players** | `/admin/players/*`, `api/v1/players/*` | PlayerValidationTest, PlayersImportExportTest |
| 5 | **Clubs** | `/admin/clubs/*`, `api/v1/clubs/*` | ClubApiTest, ClubsImportExportTest |
| 6 | **Voting (public)** | `/vote/{token}/*`, `/campaigns` | PublicVotingTest, VoterVerificationTest, GeneratePublicTokenTest, CampaignAvailabilityTest |
| 7 | **Results** | `/admin/results/*`, `/results/*`, `api/v1/.../result*` | ResultsFlowTest, ResultsLifecycleAndPublicTest, TieBreakCommitteeTest |
| 8 | **Team of the Season** | `/admin/tos/*` | TeamOfSeasonFlowTest, AutoPopulateFromLeagueTest, TeamOfTheSeasonDistributionRuleTest |
| 9 | **Users & Roles** | `/admin/users/*`, `/admin/roles/*` | PermissionBoundariesTest |
| 10 | **Settings** (sports/leagues/general) | `/admin/settings/*` | SettingsTest |
| 11 | **Stats / Dashboard** | `/admin`, `/admin/campaigns/{c}/stats` | StatsEndpointTest |
| 12 | **Cross-cutting (security, i18n)** | All | SecurityTest, ExtendedSecurityTest |

---

## 4. Use Cases & Test Cases

> Convention: `UC-MOD-NN` for use cases, `TC-MOD-NN.k` for child test cases. **(✅ A)** = covered by automation. **(M)** = manual only.

### Module 1 — Authentication & Session

#### UC-AUTH-01 — Admin signs in
**Actor:** any admin user. **Pre:** account active.
**Flow:** open `/login` → enter email + password → submit → land on `/admin`.

| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-AUTH-01.1 | Happy path | Valid creds | 302 → `admin.landing`, session cookie set | ✅ A |
| TC-AUTH-01.2 | Invalid password | Wrong password | 422 + flash "credentials don't match" | ✅ A |
| TC-AUTH-01.3 | Inactive user | `status=inactive` user logs in | Logged out + flash "account is inactive" | ✅ A |
| TC-AUTH-01.4 | Email casing | `Admin@SFPA.SA` vs stored `admin@sfpa.sa` | Login succeeds (email lower-cased on key) | M |
| TC-AUTH-01.5 | CSRF missing | POST `/login` without `_token` | 419 page expired | (skipped — middleware off in test env, on in prod) |
| TC-AUTH-01.6 | Session fixation | After login, session ID rotates | Old session ID invalid | M |

#### UC-AUTH-02 — Brute-force protection
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-AUTH-02.1 | Throttle after 5 fails | 6× wrong password same email | 6th returns "Too many attempts. Try again in N seconds." | ✅ A (ExtendedSecurityTest) |
| TC-AUTH-02.2 | Throttle keyed per email+IP | Different emails from same IP not blocked | New email login proceeds | M |
| TC-AUTH-02.3 | Cooldown elapses | Wait 60s → retry | Login allowed again | M |

#### UC-AUTH-03 — Logout
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-AUTH-03.1 | POST /logout | Click "Sign out" | 302 → `/login`, session destroyed | ✅ A |
| TC-AUTH-03.2 | GET /logout | Manual GET | 405 Method Not Allowed | M |

#### UC-AUTH-04 — Locale switching
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-AUTH-04.1 | Switch to AR | `/set-locale/ar` | session.locale=ar, redirect back, dir=rtl | ✅ A |
| TC-AUTH-04.2 | Switch to EN | `/set-locale/en` | session.locale=en, dir=ltr | ✅ A |
| TC-AUTH-04.3 | Invalid locale | `/set-locale/xx` | Falls back to default; no error 500 | (skipped) |

---

### Module 2 — Campaigns

#### UC-CMP-01 — Create campaign
**Actor:** super_admin / campaign_manager.

| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CMP-01.1 | Create with valid data | Bilingual title + dates + type | 201, status=Draft, redirect to show | ✅ A |
| TC-CMP-01.2 | Missing title_ar | Submit without AR title | 422, error highlighted, form values preserved | ✅ A |
| TC-CMP-01.3 | end_at ≤ start_at | end before start | 422 "end date must be after start" | ✅ A (TC013) |
| TC-CMP-01.4 | Past start_at allowed for backdated | start_at in past, end_at future | Allowed (admin convenience) | ✅ A |
| TC-CMP-01.5 | Form preserves on validation fail | Add 3 categories, fail on title | All 3 questions repopulated via old() | ✅ A (TC014) |
| TC-CMP-01.6 | Invalid type enum | type=foobar | 422 | ✅ A |

#### UC-CMP-02 — Submit for committee approval
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CMP-02.1 | Draft → PendingApproval | Click "Submit for approval" on Draft | status=PendingApproval, activity log entry | ✅ A |
| TC-CMP-02.2 | Empty campaign rejected | Submit Draft with 0 categories | DomainException, flash "add at least one category" | ✅ A (TC016) |
| TC-CMP-02.3 | Already pending | Submit a PendingApproval campaign | Idempotent — error or no-op (no double-log) | ✅ A |
| TC-CMP-02.4 | Rejected → resubmit | After committee rejects, admin re-submits | Allowed (Rejected → PendingApproval) | ✅ A |

#### UC-CMP-03 — Committee approval / rejection
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CMP-03.1 | Approve PendingApproval | Committee user clicks Approve | status=Published, `committee_approved_at` set | ✅ A |
| TC-CMP-03.2 | Approve from Draft → forbidden | Try approving Draft | DomainException | ✅ A |
| TC-CMP-03.3 | Reject with note | Provide rejection note | status=Rejected, note stored | ✅ A |
| TC-CMP-03.4 | Reject without note | Empty note | 422 (note required) | ✅ A |
| TC-CMP-03.5 | Non-committee user denied | campaign_manager tries approve | 403 | ✅ A |

#### UC-CMP-04 — Activate / Close / Archive
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CMP-04.1 | Auto-activate at start_at | Scheduler tick after start_at | Published → Active | ✅ A |
| TC-CMP-04.2 | Auto-close at end_at | Scheduler tick after end_at | Active → Closed | ✅ A |
| TC-CMP-04.3 | Manual activate | Force activate Published | status=Active | ✅ A |
| TC-CMP-04.4 | Archive after close | Click Archive | status=Archived; hidden from public list | ✅ A |
| TC-CMP-04.5 | Status badge visible | All admin pages | Color-coded badge matches status | ✅ A (TC017) |

#### UC-CMP-05 — Edit / Delete
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CMP-05.1 | Edit Draft | Update title | 200, persisted | ✅ A |
| TC-CMP-05.2 | Edit Active | Update non-critical field | Allowed; critical (dates) → block | ✅ A |
| TC-CMP-05.3 | Delete with no votes | Click Delete | Removed | ✅ A |
| TC-CMP-05.4 | Delete with votes blocked | Has 1+ votes | DomainException unless `force=true` | ✅ A |
| TC-CMP-05.5 | Force delete cascades | force=true | Votes/categories/results all gone | ✅ A |

---

### Module 3 — Categories & Candidates

#### UC-CAT-01 — Add category to campaign
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CAT-01.1 | Single-choice category | type=single_choice, min=1 max=1 | Created | ✅ A |
| TC-CAT-01.2 | Multi-choice (top-3) | min=1 max=3 | Created | ✅ A |
| TC-CAT-01.3 | min > max | min=3 max=1 | 422 | ✅ A |
| TC-CAT-01.4 | Position filter | position_slot=goalkeeper | Only GK candidates listable | ✅ A |
| TC-CAT-01.5 | Inactive category skipped on submit | is_active=false | Vote submission ignores it | ✅ A |

#### UC-CAT-02 — Add candidate
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CAT-02.1 | Add player to category | Pick player | Linked, displayed in card grid | ✅ A |
| TC-CAT-02.2 | Duplicate candidate | Add same player twice | 422 / duplicate guarded | ✅ A (TC018) |
| TC-CAT-02.3 | Wrong-position player | GK to "Attack" category | 422 with reason | ✅ A |
| TC-CAT-02.4 | Remove candidate | Click Remove | DELETE 200, gone from grid | ✅ A |

---

### Module 4 — Players

#### UC-PLY-01 — CRUD player
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-PLY-01.1 | Create with all fields | Bilingual name + club + position + jersey + photo | 201 | ✅ A |
| TC-PLY-01.2 | Photo size > 4MB | 5MB upload | 422 "max:4096" | ✅ A |
| TC-PLY-01.3 | Photo wrong type | .gif upload | 422 | ✅ A |
| TC-PLY-01.4 | Client `accept` filter | File picker shows only png/jpeg/webp | Browser filter applies | M |
| TC-PLY-01.5 | National ID format | Saudi 10-digit | Accepted; non-numeric → 422 | ✅ A |
| TC-PLY-01.6 | Mobile format | 05XXXXXXXX | Accepted | ✅ A |
| TC-PLY-01.7 | Duplicate jersey within club | Two #10 in same club | 422 | ✅ A |
| TC-PLY-01.8 | Edit form (no nested forms) | Click Edit, then Delete | Delete works (forms not nested) | ✅ A (regression fix) |

#### UC-PLY-02 — Bulk import / export
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-PLY-02.1 | Import CSV (comma) | 100 rows | 100 created | ✅ A |
| TC-PLY-02.2 | Import CSV (semicolon, AR Excel) | 100 rows ; delimited | Auto-detect delim, 100 created | ✅ A |
| TC-PLY-02.3 | Import CSV with BOM | UTF-8 BOM at start | Stripped, parsed normally | ✅ A |
| TC-PLY-02.4 | Header row never inserted | First row contains "name_en" | Skipped, treated as header | ✅ A |
| TC-PLY-02.5 | Unknown club row | Row references missing club | Skipped + error in summary | ✅ A |
| TC-PLY-02.6 | Upsert by (name_en, club) | Re-import same file | Counts as updated, not duplicated | ✅ A |
| TC-PLY-02.7 | Export template | GET `/admin/players/export/template` | CSV with header only | ✅ A |
| TC-PLY-02.8 | Export full | GET `/admin/players/export` | CSV of all players | ✅ A |

---

### Module 5 — Clubs

#### UC-CLB-01 — CRUD club
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CLB-01.1 | Create with logo | Bilingual name + sports + logo | 201 | ✅ A |
| TC-CLB-01.2 | Duplicate AR name | Same `name_ar` | 422 with bilingual error | ✅ A |
| TC-CLB-01.3 | Duplicate EN name | Same `name_en` | 422 | ✅ A |
| TC-CLB-01.4 | Multi-sport club | Attach to football + basketball | Both relations stored | ✅ A |
| TC-CLB-01.5 | Toggle active | POST /toggle | Inverts `is_active` | ✅ A |
| TC-CLB-01.6 | Delete club with players | Has players | Block delete (FK guard) | ✅ A |

#### UC-CLB-02 — Import / Export
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-CLB-02.1 | Import CSV | Mixed delimiter | Auto-detect, upsert | ✅ A |
| TC-CLB-02.2 | Sport column resolves by EN name | "Football" matches sport | Linked | ✅ A |

---

### Module 6 — Public Voting

#### UC-VOT-01 — Browse open campaigns
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-VOT-01.1 | Anonymous lists active campaigns | GET `/campaigns` | Only Active campaigns shown | ✅ A |
| TC-VOT-01.2 | Draft/Pending hidden | Mix of statuses | Only Active visible | ✅ A |

#### UC-VOT-02 — Identity verification
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-VOT-02.1 | National ID match | Enter valid national_id of registered player | 302 → form, voter session marked | ✅ A |
| TC-VOT-02.2 | Mobile match | Enter valid mobile | 302 → form | ✅ A |
| TC-VOT-02.3 | Neither matches | Bogus values | 422 + back to verify | ✅ A |
| TC-VOT-02.4 | Both empty | Submit empty | 422 (one required) | ✅ A |
| TC-VOT-02.5 | Hashing | DB stores SHA-256 of identifier | Plaintext never persisted | ✅ A |
| TC-VOT-02.6 | Throttle: 10/min | 11 verify attempts | 11th = 429 | ✅ A |
| TC-VOT-02.7 | Autocomplete=off | Inspect inputs | national_id + mobile have autocomplete=off | M |

#### UC-VOT-03 — Submit ballot
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-VOT-03.1 | Single-choice happy | Pick 1 candidate per category | Vote stored, redirect to /thanks | ✅ A |
| TC-VOT-03.2 | Multi-choice within range | Pick 3 of 5 (max=3) | Stored | ✅ A |
| TC-VOT-03.3 | Picks below min | Pick 0 in required category | 422 | ✅ A |
| TC-VOT-03.4 | Picks above max | Pick 4 (max=3) | 422 | ✅ A |
| TC-VOT-03.5 | Duplicate identifier | Same voter resubmits | 422 "already voted" | ✅ A |
| TC-VOT-03.6 | Submit when campaign Closed | Try after end_at | 410/redirect "campaign ended" | ✅ A |
| TC-VOT-03.7 | Submit before Active | Published, not yet Active | 422/redirect "not yet open" | ✅ A |
| TC-VOT-03.8 | Submit throttle 20/min | Rapid resubmits | 21st = 429 | ✅ A |
| TC-VOT-03.9 | Inactive category ignored | Has inactive category | Doesn't block submit | ✅ A |
| TC-VOT-03.10 | TOTS: voter chooses formation 4-3-3 | Submit with custom formation | Accepted (3-3-4-1 isn't enforced — admin set is just default) | ✅ A |

#### UC-VOT-04 — Public token
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-VOT-04.1 | 32-char random token at creation | Create campaign | `public_token` is 32 chars | ✅ A |
| TC-VOT-04.2 | Wrong token 404 | GET `/vote/AAAAA...` | 404 | ✅ A |
| TC-VOT-04.3 | Token uniqueness | Two campaigns | Distinct tokens | ✅ A |

---

### Module 7 — Results

#### UC-RES-01 — Calculate results
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-RES-01.1 | Calculate after Closed | Click Calculate | Per-candidate vote counts stored | ✅ A |
| TC-RES-01.2 | Calculate before Closed | While Active | Block (or warn) | ✅ A |
| TC-RES-01.3 | Re-calculate idempotent | Click twice | Same numbers, no duplicates | ✅ A |
| TC-RES-01.4 | Tie detection | Two candidates equal top score | Result row flagged tie_pending | ✅ A |

#### UC-RES-02 — Tie-break by committee
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-RES-02.1 | Resolve tie picks winner | Committee selects one of tied IDs | Winner stored, tie_pending=false | ✅ A |
| TC-RES-02.2 | Non-tied result rejects resolve | Try resolve where no tie | 422 / DomainException | ✅ A |
| TC-RES-02.3 | Wrong winner ID | Pick non-tied candidate | 422 | ✅ A |

#### UC-RES-03 — Approve & announce
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-RES-03.1 | Approve calculated result | Committee approves | `approved_at` set | ✅ A |
| TC-RES-03.2 | Announce after approve | Click Announce | `announced_at` set, public visible | ✅ A |
| TC-RES-03.3 | Announce skips approve → blocked | Try announce non-approved | 422 | ✅ A |
| TC-RES-03.4 | Hide after announce | Click Hide | `announced_at` cleared, public hidden | ✅ A |

#### UC-RES-04 — Public results page
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-RES-04.1 | Announced results visible | GET `/results/{token}` | Renders ranks + counts | ✅ A |
| TC-RES-04.2 | Non-announced 404 | Result not announced | 404 | ✅ A |
| TC-RES-04.3 | Throttle 60/min | 61 GETs | 429 | ✅ A |

---

### Module 8 — Team of the Season

#### UC-TOS-01 — Admin defines TOTS campaign
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-TOS-01.1 | Create with default 3-3-4-1 | Save formation 3-3-4-1 | Created, lines wired | ✅ A |
| TC-TOS-01.2 | Auto-populate from league | Pick league → all active players added per line | Players land in correct lines (GK/DEF/MID/ATT) | ✅ A |
| TC-TOS-01.3 | Player in wrong line rejected | Attach DEF to ATT line | 422 | ✅ A |
| TC-TOS-01.4 | Player not duplicated across lines | Attach same player to two lines | 422 | ✅ A |

#### UC-TOS-02 — Voter submits TOTS
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-TOS-02.1 | Valid 3-3-4-1 | 1 GK + 3 DEF + 4 MID + 3 ATT | Accepted | ✅ A |
| TC-TOS-02.2 | Wrong attack count | 2 ATT instead of 3 | 422 | ✅ A |
| TC-TOS-02.3 | Custom formation 4-3-3-1 | Voter overrides shape | Accepted (rule = sum=11, valid distribution) | ✅ A |
| TC-TOS-02.4 | Custom formation 3-4-3 | Different shape | Accepted | ✅ A |
| TC-TOS-02.5 | Unknown payload key | Extra key in JSON | 422 (rejected) | ✅ A |

---

### Module 9 — Users & Roles (RBAC)

#### UC-USR-01 — User management
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-USR-01.1 | Create user | Name + email + password ≥10 mixed | 201 | ✅ A |
| TC-USR-01.2 | Weak password rejected | "12345678" | 422 (mixedCase, symbols, etc.) | ✅ A |
| TC-USR-01.3 | Duplicate email | Existing email | 422 | ✅ A |
| TC-USR-01.4 | Toggle status | POST /toggle | Active ↔ Inactive | ✅ A |
| TC-USR-01.5 | Cannot delete self | super_admin tries to delete own row | 422/redirect with error | ✅ A |
| TC-USR-01.6 | Last super_admin guarded | Delete only super_admin | Block | ✅ A |

#### UC-USR-02 — Role/Permission boundaries
| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-USR-02.1 | campaign_manager cannot approve | Hits `/approve` | 403 | ✅ A |
| TC-USR-02.2 | committee cannot create campaign | POST /admin/campaigns | 403 (per current policy) | ✅ A |
| TC-USR-02.3 | auditor sees but cannot mutate | DELETE any | 403 | ✅ A |
| TC-USR-02.4 | super_admin all-access | Any route | 200/302 | ✅ A |

---

### Module 10 — Settings (Sports / Leagues / General)

| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-SET-01 | Add sport | Bilingual name | Stored | ✅ A |
| TC-SET-02 | Edit sport | Update name | Stored | ✅ A |
| TC-SET-03 | Delete sport in use | Sport linked to club | Block | ✅ A |
| TC-SET-04 | Add league | Bilingual + sport_id | Stored | ✅ A |
| TC-SET-05 | List clubs in league | GET league.clubs | Pivot table read | ✅ A |
| TC-SET-06 | Update general settings | Site title / contact | Persisted in `settings` table | ✅ A |

---

### Module 11 — Stats / Dashboard

| TC | Title | Steps | Expected | Cov |
|---|---|---|---|---|
| TC-STA-01 | Dashboard counts | GET `/admin` | Cards show total campaigns / players / clubs / today's votes | ✅ A |
| TC-STA-02 | Campaign stats | GET `/admin/campaigns/{c}/stats` | Per-category counts, hourly timeline | ✅ A |
| TC-STA-03 | Pending approval banner | Has 2 PendingApproval | Shows ":n pending your approval" | ✅ A |

---

### Module 12 — Cross-cutting

#### UC-SEC-01 — Security headers
| TC | Steps | Expected | Cov |
|---|---|---|---|
| TC-SEC-01.1 | GET any page | Response has X-Frame-Options=SAMEORIGIN | ✅ A |
| TC-SEC-01.2 | GET any page | X-Content-Type-Options=nosniff | ✅ A |
| TC-SEC-01.3 | GET any page | Referrer-Policy=strict-origin-when-cross-origin | ✅ A |
| TC-SEC-01.4 | GET any page | Permissions-Policy locks camera/mic/geo/payment | ✅ A |

#### UC-SEC-02 — Authorization (IDOR)
| TC | Steps | Expected | Cov |
|---|---|---|---|
| TC-SEC-02.1 | Auditor DELETE campaign | 403 | ✅ A |
| TC-SEC-02.2 | Manipulate `{campaign}` ID to one in another tenant | n/a (single-tenant) | M |

#### UC-SEC-03 — Injection / XSS
| TC | Steps | Expected | Cov |
|---|---|---|---|
| TC-SEC-03.1 | Title with `<script>` | Render escapes | ✅ A |
| TC-SEC-03.2 | SQL `' OR 1=1 --` in search | Bound param, no leak | ✅ A |

#### UC-SEC-04 — Mass assignment
| TC | Steps | Expected | Cov |
|---|---|---|---|
| TC-SEC-04.1 | Send `is_admin=1` in user POST | Ignored ($fillable) | ✅ A |
| TC-SEC-04.2 | Send `committee_approved_at` in campaign update | Ignored | ✅ A |

#### UC-I18N-01 — Bilingual UI
| TC | Steps | Expected | Cov |
|---|---|---|---|
| TC-I18N-01.1 | All admin pages render in AR | toggle locale | RTL applied, all strings translated | M (visual) |
| TC-I18N-01.2 | All admin pages render in EN | toggle locale | LTR, English strings | M (visual) |
| TC-I18N-01.3 | Public voter pages bilingual | open campaign | AR/EN toggle works in voter flow | M |
| TC-I18N-01.4 | Date/number locale | hijri vs gregorian | Gregorian (per spec) | M |
| TC-I18N-01.5 | Missing translation falls back EN | new key not in ar.json | Falls back, no `[*]` markers | ✅ A |

#### UC-API-01 — REST API (Sanctum)
| TC | Steps | Expected | Cov |
|---|---|---|---|
| TC-API-01.1 | GET `/api/v1/campaigns` without token | 401 | M |
| TC-API-01.2 | GET with bearer token | 200 JSON | M |
| TC-API-01.3 | POST create requires permission | scope check | M |
| TC-API-01.4 | Pagination headers / links present | meta object | M |

---

## 5. Non-Functional Requirements

| ID | Requirement | Method | Target | Cov |
|---|---|---|---|---|
| NFR-PERF-01 | Public voting page TTI | Lighthouse mobile 4G | < 3s | M |
| NFR-PERF-02 | Admin dashboard load | Browser timing | < 1.5s p95 | M |
| NFR-PERF-03 | Vote submit endpoint | k6 / wrk | 200 RPS @ p95 < 400ms | M |
| NFR-AVAIL-01 | `/up` health check | curl from monitoring | 200 OK | ✅ A |
| NFR-A11Y-01 | Admin forms keyboard navigable | manual | All interactive controls reachable by Tab | M |
| NFR-A11Y-02 | Color contrast | axe-core | WCAG AA | M |
| NFR-A11Y-03 | Form labels | inspect | Every input has `<label for>` | M |
| NFR-COMPAT-01 | Browsers | manual matrix | Chrome 120+, Firefox 120+, Safari 17+ desktop & iOS | M |
| NFR-BACKUP-01 | DB backup runnable | mysqldump cron | Restorable to fresh server | M |
| NFR-LOG-01 | Activity audit | inspect logs | Every state change logged | ✅ A |

---

## 6. Test Data

| Set | Description |
|---|---|
| `seedRolesAndPermissions()` | Provides the 4 base roles |
| `makeDraftCampaign()` (test helper) | Draft campaign + 1 category |
| Faker — players/clubs | Used in PlayerValidationTest etc. |
| `tests/fixtures/players.csv` | Comma + semicolon variants |
| Manual: 2 super_admin, 2 committee, 2 campaign_manager, 1 auditor accounts |

---

## 7. Defect Severity / Exit Criteria

### Severity scale
- **S1 — Critical:** vote loss, data corruption, auth bypass, full outage
- **S2 — High:** module unusable, results miscalculation, visible to public
- **S3 — Medium:** UX broken in one flow, workaround exists
- **S4 — Low:** cosmetic / typo

### Exit criteria for production release
- 0 open S1 / S2 defects
- ≤ 3 open S3 (with mitigation)
- 100% P0 test cases passed
- ≥ 95% P1 test cases passed
- All security NFRs verified
- Backup + restore drill done once

---

## 8. Test Run Summary (this build)

```
Run date    : 2026-04-18
Build       : main @ 33c3ef6
Framework   : Pest 3 / PHPUnit 11
Php         : 8.3.30
DB driver   : SQLite (in-memory for tests)

Result      : 167 passed · 2 skipped · 1 risky · 0 failed
Assertions  : 392
Duration    : 10.16 s
```

Skipped (intentional, pre-existing):
- AuthTest › ignores invalid locales
- SecurityTest › CSRF enforced on login POST without token (middleware off in test env; on in prod)

Risky (informational, no failure): 1 in TeamOfSeasonFlowTest — assertion-counted edge case.

**Verdict:** ✅ Build is **green** and meets release criteria for staging deployment.

---

## 9. Recommended Next Test Activities

| # | Activity | Owner | Priority |
|---|---|---|---|
| 1 | Manual exploratory pass on AR locale (all 12 modules) | QA | P0 |
| 2 | Lighthouse run on `/campaigns` and `/vote/{token}` | QA | P0 |
| 3 | Load test `vote.submit` at 200 RPS for 5 min | DevOps | P1 |
| 4 | API token scope tests (Sanctum abilities) | QA | P1 |
| 5 | DR drill: restore from yesterday's backup to fresh DB | DevOps | P1 |
| 6 | a11y axe-core scan on admin + public layouts | QA | P2 |
| 7 | Tighten CSP once Vite bundle replaces CDN | Dev | P2 |
| 8 | Convert the 2 Skipped tests to env-aware (not skipped in prod-like env) | Dev | P3 |
