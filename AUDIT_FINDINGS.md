# HEIM Coffee POS & Inventory System — Audit Findings

**Date:** October 11, 2026  
**Status:** **PARTIALLY VERIFIED — ADDITIONAL TESTING REQUIRED**. Current checks confirm a local production-safe runtime (`APP_ENV=production`, `APP_DEBUG=false`), 237 passing automated tests, and a fresh isolated MySQL restore with exact table-count and foreign-key checks. A witnessed staff walkthrough and physical cash reconciliation remain outstanding.  
**Approach:** Inspect the current runtime and worktree, run the isolated regression suite, verify routes/migrations/assets and loopback UI behavior, repeat backup/restore in an isolated database, and record remaining operational acceptance gaps.

Sections before Phase 14 preserve earlier validation snapshots and counts; Phase 14 below supersedes their runtime and verification results.

---

## Executive Summary

The HEIM Coffee POS & Inventory system is **structurally sound** with a clear Laravel stack, role-based access control, POS workflows, and inventory integration. The system has been audited for critical bugs, business-logic integrity, and security risks.

**Key Status:**
- ✅ Local deployment configuration hardened: `APP_ENV=production` and `APP_DEBUG=false`
- ✅ Automated regression suite: **237 tests passed / 2,072 assertions**
- ✅ Laravel formatting: **Pint passed after correcting four reported files**
- ✅ Frontend production build: **Vite build passed**
- ✅ Local app: **login served on `127.0.0.1:8000`; debug mode off**
- ✅ Database recovery: **37 table counts matched; 52 foreign-key relationships had no orphan rows**
- ⚠️ A witnessed cashier/manager/owner walkthrough and physical cash reconciliation remain unverified

---

## Critical Findings

### 1. Saved Unpaid Split Orders & Inventory Timing — ✅ VERIFIED NOT A BUG

**File:** `app/Http/Controllers/PosController.php`, `app/Services/InventoryService.php`

**Finding:**
The app **intentionally** deducts inventory once when a split order is saved unpaid (status: `pending`), then reuses the same order without re-deducting stock later. This is the designed behavior.

**Verification:**
- Test: `tests/Feature/PosOrderTest.php` → `test_save_order_can_create_an_unpaid_split_order_then_continue_it_without_reducing_stock_twice`
- Status: **PASSES** ✅
- Assertion: Inventory consumed once, not twice.

**Impact:** None — working as designed.

**Recommendation:** No change needed. The current logic reserves stock at save-time, which is a valid business pattern.

---

### 2. Order Visibility Policy — ✅ BRANCH-SCOPED

**File:** `app/Policies/OrderPolicy.php`

**Decision:** Cashiers may view orders only within their assigned branch. Managers and owners retain their existing authorization path.

**Implementation:** Order viewing and cashier payment authorization require a non-null matching user/order branch. The named cashier must still match to record a payment. A cashier without a branch assignment is denied. The order detail, receipt, and POS success endpoints are protected.

**Regression coverage:** Same-branch cashier collaboration is allowed; cross-branch and unassigned-branch cashier access is denied. Managers/owners continue to use the existing authorization path.

---

### 3. Dashboard & Report Metrics Clarity — ✅ SEPARATED

**Files:** `app/Http/Controllers/ReportController.php`, `app/Http/Controllers/DashboardController.php`, `resources/views/reports/sales.blade.php`, `resources/views/dashboard/index.blade.php`

**Implementation:** The dashboard shows the count and remaining amount for pending and partially paid orders separately. The sales report and its CSV export show the same balances for orders created in the selected date range. Order creation date is the chosen report basis. Sales after discounts, tender by payment method, and completed refunds remain separately labeled.

**Regression coverage:** `DashboardTest` verifies the independent pending and partially paid balances. `ReportExportTest` verifies status separation, order-date filtering, and exported totals.

---

### 4. Debt Feature — ✅ RETIRED

**Files:** `database/migrations/2026_09_28_165528_create_debts_table.php`, `database/migrations/2026_10_06_000002_drop_debts_and_pay_later_tables.php`, `database/migrations/2026_10_05_000001_guard_retired_debt_data.php`

**Finding:** No debt or Pay Later references were found in application controllers, services, views, routes, or tests. An existing migration removes `debts`, `debt_payments`, and related cashier-shift/refund fields, but it had no historical-data check and an empty rollback. The live migration history showed this removal migration had already run; both tables were absent.

**Decision:** Retire the unused feature, as requested.

**Safety fix:** Added a preflight migration ordered before the existing removal migration. On databases where it runs before removal, it stops the migration run if debt/debt-payment rows, linked refund records, or non-zero debt/Pay Later shift totals exist. The removal migration rollback now restores the former schema. Regression tests verify populated debt rows are preserved when the guard stops and that rollback restores the schema.

**Live database outcome:** A backup of the current database was made and restored into a separate temporary database; the restore completed and contained 36 tables, 15 orders, and 39 migration rows. The temporary verification database was removed. Live migration status showed the removal migration was already applied and the new preflight was pending; running `php artisan migrate` applied only the preflight.

**Historical data limitation:** Because the existing drop migration had already run before this phase, the debt tables and related columns were gone before the backup. The backup therefore verifies recovery of the current database state but cannot recover or establish whether the prior migration deleted debt records. No historical debt rows were available for inspection.

---

## Secondary Findings

### Order Access & Authorization Flow — WORKING

**Files:** `app/Policies/OrderPolicy.php`, `app/Http/Middleware/RoleMiddleware.php`

- Role-based gating is in place (cashier, manager, owner).
- Authorization checks occur at controller level before data access.
- Server-side enforcement is present for payments and refunds.
- **Status:** ✅ Working as designed.

---

### Inventory Deduction Logic — WORKING

**Files:** `app/Services/InventoryService.php`, `app/Http/Controllers/PosController.php`

- Ingredients are deducted based on recipes and add-ons.
- Stock is locked during transactions to prevent race conditions.
- Refunds and voids restore stock via reverse transactions.
- **Status:** ✅ Working as designed.

---

### Branch Scoping — WORKING

**File:** `app/Models/Concerns/BelongsToBranch.php`

- Non-owner users are restricted to their assigned branch.
- Owners can cross-branch query.
- Global scope applied consistently across models.
- **Status:** ✅ Working as designed.

---

### Shift Management & Cash Reconciliation — WORKING

**File:** `app/Models/CashierShift.php`, `app/Http/Controllers/ShiftController.php`

- Shifts can only be closed by the assigned cashier (or manager).
- Expected cash is calculated from opening balance + sales + refunds.
- Actual cash is recorded during shift closure.
- **Status:** ✅ Working as designed.

---

### Security & Validation — WORKING

- CSRF protection via middleware.
- Password hashing with `bcrypt`.
- Input validation on all forms.
- Soft-delete for audit trail preservation.
- Audit logs record who did what, when, and why.
- **Status:** ✅ Working as designed.

---

## Module-by-Module Status

| Module | Status | Notes |
|--------|--------|-------|
| POS Order Entry | ✅ Working | Layout optimized; UI improved |
| Save & Hold Orders | ✅ Working | Inventory reserved at save-time (intentional) |
| Split Payment | ✅ Working | Partial payments tracked; balance updates correctly |
| Inventory Deduction | ✅ Working | Deducted once per order; refunds/voids restore |
| Refunds & Voids | ✅ Working | Authorization enforced; stock restored; audit logged |
| Shift Management | ✅ Working | Opening/closing flows functional; cash reconciliation clear |
| Reports & Dashboard | ✅ Working | Sales, tender, refunds, and pending/partially paid balances are shown separately; balance reports use order creation date |
| User Roles & Auth | ✅ Working | Server-side enforcement in place; role gating functional |
| Order Visibility | ✅ Working | Cashier order viewing/payment authorization require matching assigned branch |
| Debt Tracking | ✅ Retired | No application use; tables already absent; preflight migration recorded; prior historical contents cannot be verified |
| UI/UX Layout | ✅ Working | Cashier/customer labels updated; buttons repositioned |
| Security | ✅ Working | Auth, validation, audit logs in place |
| Database Backup | ✅ Documented and verified | See [DATABASE_BACKUP.md](./DATABASE_BACKUP.md); current MySQL backup restored successfully in an isolated database; repeat restore checks periodically |

---

## Recommended Priority Fixes

### Critical
- None identified at this time. Current tests pass.

### High
- None identified at this time.

### Medium
1. **Verify recovery operationally** — Repeat isolated restore checks periodically and record the results.

### Low
5. Clean up UI density and label consistency (non-blocking).

---

## Implementation Plan

### Phase 1: Confirmation (No Code Changes)
1. Debt retirement approved. The existing removal migration had already run before this continuation.

### Phase 2: Code Changes (Targeted & Safe)
1. Order visibility and balance-due reporting are implemented and covered by regression tests.
2. Debt retirement preflight implemented and recorded after a verified current-state backup. The historic effects of the previously applied removal migration cannot be determined from this database.

### Phase 3: Testing & Deployment
1. Run full test suite to ensure no regressions.
2. Manual testing on each module affected by changes.
3. Deploy with clear commit messages and change logs.

---

## Notes

- **Automated tests:** `php artisan test --no-coverage` passed: 230 tests, 1,954 assertions.
- **Live database changes:** The pending guard migration was run and recorded after a current-state backup; it made no schema or business-record changes because the old debt-removal migration had already run. No debt records were available to inspect.
- **Database configuration:** `.env.example` defaults to SQLite; the active local runtime uses MySQL on `127.0.0.1:3306`. All migrations are now recorded as run.
- **Backup guidance:** Procedures are in [DATABASE_BACKUP.md](./DATABASE_BACKUP.md). The current MySQL backup was restored and queried in an isolated temporary database; that temporary copy was removed afterward. Continue periodic restore drills.
- **Audit date:** October 10, 2026 (morning).

---

**Next Step:** No further debt migration is pending. Retain the verified backup. Historical debt data, if it existed before the earlier removal migration, cannot be confirmed from the current database.

---

## Phase 11 — Final Readiness and Risk Report

**Validation date:** October 10, 2026 (historical snapshot; superseded by Phase 14 below)  
**Decision at that time:** Partial verification. Automated regression and basic local-page checks passed, but staff acceptance and physical cash were not exercised. The runtime settings and test evidence in that snapshot are historical and superseded by the October 11 Phase 14 results below.

| Item | Issue | Fix | Files affected (exact paths) | Verification | Result | Remaining risk |
|---|---|---|---|---|---|---|
| Automated business regression | A change to payment, inventory, shift, or reporting behavior could regress related modules. | Existing fixes are covered by the current feature suite; no additional code change was made during this validation. | `app/Http/Controllers/PosController.php`; `app/Http/Controllers/OrderController.php`; `app/Http/Controllers/ReportController.php`; `app/Services/InventoryService.php`; `app/Services/OrderBalanceService.php`; `tests/Feature/PosOrderTest.php`; `tests/Feature/PhaseOneFeaturesTest.php`; `tests/Feature/DashboardTest.php`; `tests/Feature/ReportExportTest.php` | `php artisan test` — 230 tests passed, 1,954 assertions. Coverage includes duplicate-checkout protection, held/resumed and split orders, partial payments, Grab orders, shift in/out, refunds/voids, inventory reversal, and reporting. | **Passed** | The test suite uses an isolated in-memory SQLite database; it does not establish the behavior of a real cashier's full shift on the live MySQL database. |
| Role authorization and branch scope | Real Owner, Manager, and Cashier account behavior was not interactively exercised. | Server-side authorization, approval, and branch-isolation regression coverage is present; no live accounts were used. | `app/Policies/OrderPolicy.php`; `app/Http/Controllers/BranchController.php`; `app/Http/Controllers/UserController.php`; `app/Models/Concerns/BelongsToBranch.php`; `routes/web.php`; `tests/Feature/OrderAuthorizationTest.php`; `tests/Feature/BranchIsolationTest.php` | Full Laravel feature suite passed, including unauthorized cashier actions, manager approval, and cross-branch access checks. | **Passed (automated); unverified (live accounts)** | Have the client verify each role, direct URL access, denied form submissions, and branch assignment using dedicated non-demo accounts before handover. |
| Local interface and routes | Full POS transactions and all form states remain outside this read-only browser check. | No layout code change was needed. Rewrote the rough deployment note in `PORT-FORWARDING.md` and removed its trailing whitespace. | `resources/views/layouts/app.blade.php`; `resources/views/pos/index.blade.php`; `resources/views/dashboard/index.blade.php`; `resources/views/orders/index.blade.php`; `resources/views/inventory/index.blade.php`; `resources/views/reports/sales.blade.php`; `resources/views/reports/grab.blade.php`; `resources/views/reports/inventory.blade.php`; `resources/views/reports/shifts.blade.php`; `routes/web.php`; `PORT-FORWARDING.md` | Final read-only browser pass: Dashboard, POS, Sales, Grab, Inventory Report, Shifts Report, Stock, and Orders returned HTTP 200 with no horizontal overflow at 1366×768, 768×1024, and 390×844. Reports sidebar navigation worked by keyboard and by pointer when using coordinates adjusted for the app shell's 90% CSS zoom. `GET /up` returned HTTP 200. | **Passed for read-only page rendering and Reports navigation** | Transaction flows, modal behavior, authenticated form validation, search/filter/pagination, loading/error states, currency formatting, and cashier usability still need controlled operator testing. |
| Production frontend build | Frontend assets must compile for deployment. | No change required. | `package.json`; `vite.config.js`; generated assets under `public/build/` | `npm.cmd run build` completed successfully with Vite. | **Passed** | Rebuild assets after any subsequent frontend changes. |
| PHP formatting check | The initial repository-wide Pint check found formatting violations across project PHP code. | Ran Pint on the complete project PHP scope; formatting-only changes, no intended behavior changes. | `app/**/*.php`; `bootstrap/**/*.php`; `database/**/*.php`; `routes/**/*.php`; `tests/**/*.php` | `vendor\\bin\\pint` completed successfully; subsequent `vendor\\bin\\pint --test` passed repository-wide. | **Passed** | Formatting does not replace behavioral testing; the full regression suite was rerun and passed. |
| Database schema and recovery | This validation must not risk changing production data. A restore drill was not repeated in this final pass. | No live data or schema was changed during this validation. Earlier isolated MySQL restore evidence is recorded above; backup/restore steps are documented. | `database/migrations/2026_10_05_000001_guard_retired_debt_data.php`; `database/migrations/2026_10_06_000002_drop_debts_and_pay_later_tables.php`; `DATABASE_BACKUP.md` | Read-only `php artisan migrate:status` reported all migrations as applied. The earlier isolated restore was reported to contain 36 tables, 15 orders, and 39 migration rows. | **Passed (earlier restore); unverified (repeat in this pass)** | Repeat an isolated restore with a current backup before handover. The earlier removal of debt tables predates the safety guard; any historical debt records cannot be verified from the current database. |
| Runtime configuration and operational workflow | This checkout is not configured for production, and physical cash reconciliation plus the complete Login → Shift-In → POS → Payment → Receipt → Inventory → Reports → Shift-Out journey were not performed in this pass. | Clear startup, account, operating, backup, and recovery instructions are provided; no production settings were applied to this developer checkout. | `.env.example`; `CLIENT_HANDOVER.md`; `DATABASE_BACKUP.md` | `php artisan about --only=environment` reported `local` environment and debug enabled. `.env` is ignored and untracked. The full live business workflow and physical reconciliation were not performed. | **Unverified; handover blocked** | Before client use: configure production in the approved deployment environment (`APP_ENV=production`, `APP_DEBUG=false`), secure/provision individual role accounts, and perform the complete workflow in a controlled environment without real customer transactions. |

### Handover gate

Final rerun: `php artisan test` passed (230 tests, 1,954 assertions); `vendor\\bin\\pint --test` passed repository-wide; `npm.cmd run build` succeeded; 106 routes registered; migration status showed no pending migrations; `git diff --check` passed. Read-only browser checks for Dashboard, POS, Sales, Grab, Inventory, Shifts, Stock, and Orders returned HTTP 200 with no horizontal overflow at desktop, tablet, and phone widths. The earlier isolated restore has evidence of success but was not repeated in this final validation. Do **not** describe the application as error-free or production-ready until production configuration is verified, the outstanding live role/workflow checks are completed, and a current isolated restore drill succeeds. Use [CLIENT_HANDOVER.md](./CLIENT_HANDOVER.md) for the operating checklist and [DATABASE_BACKUP.md](./DATABASE_BACKUP.md) for recovery procedures.

---

## Phase 14 — October 11, 2026 Acceptance Continuation

- Runtime: `APP_ENV=production`, `APP_DEBUG=false`; local MySQL configuration; no production credentials recorded here.
- Full isolated suite: 237 tests passed, 2,072 assertions, using the testing environment.
- Formatting/build/routes/migrations: Pint passed; Vite build passed; 110 routes registered; all migrations applied.
- Browser check: `/login` returned HTTP 200, the CSRF field and same-origin form/assets were present, and no horizontal overflow was observed at 1366x768, 768x1024, or 390x844. A nonexistent route returned a generic 404 without detected debug markers.
- Recovery: backup `%USERPROFILE%\Documents\heim-db-backups\heim-20261011-132831.sql` restored into `heim_restore_verify_20261011-132831`; 37 base table counts matched and 52 foreign-key relationships had zero orphans. The live database was not modified.
- Formatting-only corrections were made to the four files reported by Pint. No application behavior change or new test was introduced in this continuation.
- Still unverified: in-person staff acceptance with actual client accounts, full operator walkthrough, and physical cash reconciliation. No real customer transaction or physical cash was used.

**Current decision:** **PARTIALLY VERIFIED — ADDITIONAL TESTING REQUIRED**. Automated workflow tests and recovery checks passed, but they do not substitute for operational acceptance. See [CLIENT_HANDOVER.md](./CLIENT_HANDOVER.md) and [DATABASE_BACKUP.md](./DATABASE_BACKUP.md) for current procedures and limitations.
