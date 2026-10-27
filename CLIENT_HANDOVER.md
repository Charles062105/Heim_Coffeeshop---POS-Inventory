# HEIM Coffee POS & Inventory: Client Handover

This guide covers routine use of the local application. It does not include
account passwords or database credentials. Have the system owner provision a
separate account for each staff member.

## Before opening the system

- Start the local MySQL service and confirm that the configured database is
  available. Keep the database on the local machine; do not expose its port to
  the internet.
- From the project directory, start Laravel bound to loopback:

  ```powershell
  php artisan serve --host=127.0.0.1 --port=8000
  ```

- Open `http://127.0.0.1:8000` in the browser. Stop the server with `Ctrl+C`.
- If the port is already in use, do not terminate an unknown process. Ask the
  administrator to identify it, or choose an approved unused local port and
  use that same port in both the server command and browser URL.
- For client operation, configure the application environment with
  `APP_ENV=production` and `APP_DEBUG=false`, then clear or rebuild cached
  configuration after changing environment settings. Do not distribute the
  development `.env` file. This checkout was verified on October 11, 2026
  with `APP_ENV=production` and `APP_DEBUG=false`; do not replace its active
  configuration with `.env.example`.
- This checkout's `.env.example` selects SQLite, while the verified local
  installation used MySQL. Confirm the intended database connection before
  installing or running migrations. Do not rerun setup or migrations against
  an existing business database without a verified backup and an approved
  change plan.

## Accounts and permissions

The application supports **Owner**, **Manager**, and **Cashier** roles. There
is no separate Supervisor role in the current application. Owners manage
accounts and branches; managers handle operational catalog, inventory, reports,
and approvals; cashiers use the POS and their assigned-branch workflows.
Access is enforced by server-side role checks, but the client should still
verify each staff account and branch assignment before use.

An owner can create individual accounts under **Users & Accounts** and assign
the appropriate role and active branch. Use unique passwords and never share
the owner account. The repository contains demo-account seeding code; do not
use seeded demo accounts in a client installation. Before deployment, the
responsible administrator must ensure any demo accounts are not active and
that the production owner account is secured.

## Products, ingredients, and recipes

1. An Owner or Manager creates categories and products, including sizes and
   add-ons where applicable.
2. Create the ingredients and units used by recipes.
3. Configure each product-size recipe and any add-on ingredient usage before
   selling the item.
4. Check that product availability and recipe quantities reflect the actual
   store's procedures. Product and recipe catalogs are shared across branches;
   inventory balances and transactions are branch scoped.

Do not assume a product deducts stock correctly merely because it appears in
the POS. Verify its configured recipe and perform a controlled test before
live sales.

## Receiving stock, adjustments, and waste

- Record deliveries through **Inventory → Stock-In**, entering the correct
  ingredient, quantity, cost/date fields, and branch.
- Use **Waste** for spoiled or discarded stock and include the reason.
- Use **Adjustments** only to correct a verified count discrepancy; record the
  reason and retain the supporting count or approval.
- Review the inventory transaction history and low-stock indicators after
  entries. Avoid correcting stock by directly editing database records.

These inventory functions are restricted to Owner and Manager accounts.

## Shifts and sales

1. A cashier opens the current shift using the opening cash amount.
2. In **POS**, add items, sizes, and add-ons; enter the customer/table
   information if applicable.
3. Use **Save Order** to hold an order for later processing. Retrieve it from
   saved orders when ready. Saving an order is not payment.
4. Use **PAY** to proceed with payment. For remaining balances or split
   payments, continue using the order's payment actions and verify the
   displayed balance after each payment.
5. Review the completed order and receipt before serving/closing the sale.
6. At shift end, enter the counted cash and complete the shift reconciliation.
   Investigate any difference; do not alter historical payments to force a
   match.

The POS supports regular and Grab orders. Review the order type and the separate
Grab report when reconciling platform orders.

Refunds, cancellations, and voids require authorized manager/owner approval.
Follow the reason/approval prompts and verify the resulting order, payment,
inventory, and audit records. Never use direct database edits to reverse a
sale.

## Reports and audit trail

Owner and Manager users can review sales, inventory, Grab, and shift reports.
Select the correct date range and branch before exporting or reconciling.
Branch staff are scoped to their assigned branch for operational records;
product and recipe catalogs remain shared. Owners can manage branches and view
cross-branch reporting. Audit logs are available to the management roles for
supported sensitive actions.

## Backup and recovery

Follow [DATABASE_BACKUP.md](./DATABASE_BACKUP.md) for the local MySQL and
SQLite backup/restore procedures. Store backups outside the repository on a
protected local or access-controlled drive. A backup is not verified until it
has been restored to an isolated recovery database/file and checked; never use
the live database as a restore test target.

Create and verify a backup before upgrades, migrations, or other database
changes. Record who made it, when it was made, the application/database
version, and the verification result. This project does not currently provide
an automatic backup schedule or retention policy.

## Common troubleshooting

- **Login unavailable:** confirm the application and database services are
  running, then ask the Owner to check that the account is active.
- **A page or action is forbidden:** confirm the user's role and branch
  assignment. Do not work around server-side authorization.
- **Products or stock are missing:** check product status, branch selection,
  stock-in records, and the ingredient's recipe configuration.
- **Unexpected order balance or shift discrepancy:** inspect the order's
  payment history and shift report; escalate discrepancies to the Owner or
  Manager for review.
- **An application error occurs:** preserve the error time and relevant order
  reference, and ask the administrator to inspect application logs. Do not
  expose debug pages or share `.env`, passwords, or customer/payment data.

## Verification status at handover

The current checkout is a local deployment, not a public cloud deployment. `php artisan about --only=environment` confirmed `APP_ENV=production` and debug mode off. The login page responded on `http://127.0.0.1:8000/login`; responsive checks at 1366x768, 768x1024, and 390x844 found no horizontal overflow on that page.

The automated suite passed in the isolated testing environment using SQLite: 237 tests and 2,072 assertions. Laravel Pint passed, the Vite production build passed, 110 routes registered, and all migrations were reported as applied. These tests cover business workflow behavior, but they are not a witnessed staff acceptance session or physical cash reconciliation.

A fresh MySQL backup was created on October 11, 2026 at `%USERPROFILE%\Documents\heim-db-backups\heim-20261011-132831.sql` (248,170 bytes; SHA-256 `0AEB3E74000ABEED742DAFC62123EFD66C09E90F919427F41E5638876EF28423`). It was restored to the new isolated database `heim_restore_verify_20261011-132831`. Exact row counts matched across all 37 base tables, and 52 foreign-key relationships had no orphan rows in either database. Key counts included users (7), branches (2), products (85), ingredients (72), inventory (144), orders (17), order items (33), payments (14), payment portions (4), shifts (7), inventory transactions (279), and audit logs (225). This restore test did not write to the live database.

The remaining acceptance gaps are an actual staff walkthrough against an approved non-production dataset, confirmation of client staff accounts and permissions in that walkthrough, and physical cash counting/reconciliation. Do not treat this automated and recovery validation as live-business sign-off.

Current status: `PARTIALLY VERIFIED - ADDITIONAL TESTING REQUIRED`.