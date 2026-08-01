# Finance, Projects, Procurement, and Operations Implementation Plan

Last updated: 2026-07-29

Overall status: **Implemented and Verified**

## Purpose

This is the controlling implementation and handoff document for the Accounts, Chart of Accounts, Projects, Sales, Purchases, Inventory, Banking, Payroll-posting, Assets, and consolidated-reporting work.

It exists so implementation can continue safely across context compaction and separate chats without relying on conversation memory. It records:

- confirmed business structure and architectural decisions;
- boundaries between implemented foundations and future work;
- phase order, scope, dependencies, acceptance criteria, and verification;
- unresolved business decisions that must block affected work;
- what was actually implemented in each completed phase;
- deviations, migrations, tests, and follow-up work.

This plan does not itself authorize implementation to start. Work starts when the user asks to begin a phase.

## Mandatory execution protocol

Every agent working on any scope covered by this plan must follow these rules:

1. Read the root `AGENTS.md`, `docs/PROJECT_STATE.md`, and this entire file before changing code.
2. Inspect the repository and database before trusting a phase status or prior summary.
3. Work on only one numbered phase at a time. At most one phase may have status **In Progress**.
4. Do not start a later phase while an earlier dependency is incomplete, blocked, or unverified.
5. Before starting a phase:
   - confirm its prerequisites;
   - resolve or explicitly block on its required business decisions;
   - change its status from **Planned** to **In Progress**;
   - add a dated entry to the Progress Ledger.
6. A phase is complete only when its complete scope and acceptance criteria are implemented, relevant PHPUnit tests pass, company isolation and authorization are verified, and required formatting/static checks pass.
7. When a phase completes:
   - change its status to **Implemented and Verified**;
   - replace planned assumptions with the actual implementation;
   - record migrations, models, actions, resources, policies, permissions, documents, reports, tests, and commands run;
   - record deviations and remaining limitations;
   - update the Phase Status Index and Progress Ledger;
   - update `docs/PROJECT_STATE.md` in the same change;
   - stop before beginning the next phase unless the user explicitly requests continuation.
8. Never mark a phase complete because files exist. Verify behavior and tests.
9. If implementation differs from this plan, update the plan with the reason before or alongside the code. Do not let code and plan silently diverge.
10. If context is compacted or work continues in a new chat, re-read all three required files, inspect the current diff and database, and resume the one **In Progress** phase. Never reconstruct status from memory.
11. If the user changes business requirements, update the relevant decisions, affected phases, and `docs/PROJECT_STATE.md` before implementing the changed design.
12. Do not bulk-mark future phases complete. Each phase needs its own implementation evidence.

## Status vocabulary

Only these values may be used:

- **Planned** — approved planning scope, implementation not started.
- **In Progress** — implementation is actively underway.
- **Blocked** — cannot continue without a recorded business decision or external dependency.
- **Implemented and Verified** — all acceptance criteria and verification requirements passed.
- **Reopened** — previously completed work requires a material correction; record why.

## Phase Status Index

| Phase | Name | Status | Depends on |
| --- | --- | --- | --- |
| 0 | Business decisions and source evidence | Implemented and Verified | Existing foundation |
| 1 | Legal-company topology and module governance | Implemented and Verified | Phase 0 decisions applicable to company records |
| 2 | Shared parties, projects, sites, and operational master data | Implemented and Verified | Phases 0–1 |
| 3 | Accounting foundation and company Chart of Accounts | Implemented and Verified | Phases 0–2 |
| 4 | Double-entry ledger, vouchers, periods, and opening balances | Implemented and Verified | Phase 3 |
| 5 | Purchase requisitions and purchase orders | Implemented and Verified | Phases 2–4 |
| 6 | Material receiving, inspection, site inventory, and returns | Implemented and Verified | Phase 5 |
| 7 | Vendor bills, three-way matching, taxes, and Accounts Payable | Implemented and Verified | Phases 4–6 |
| 8 | Payments, cash/bank operations, and reconciliation | Implemented and Verified | Phase 7 |
| 9 | Sales, running bills, receipts, retention, and project profitability | Implemented and Verified | Phases 2–4 and 8 foundations |
| 10 | Payroll posting, employee advances, and project payroll allocation | Implemented and Verified | Phases 4 and 8 |
| 11 | Fixed assets and depreciation | Implemented and Verified | Phases 4 and 8 |
| 12 | Inter-company accounting, consolidation, closing, migration, and hardening | Implemented and Verified | Phases 1–11 |

## Current verified baseline

As of 2026-07-27:

- Laravel 13, Filament 5, Livewire 4, PHP 8.4, and PHPUnit 12 are in use.
- The local database is SQLite.
- Multi-company tenancy, direct company access, module configuration, policies, audit logging, and company bank accounts are implemented.
- Private documents with immutable versions, verification, approval, and rejection are implemented.
- HR, joining letters, effective-dated compensation, and payroll-run workflows are implemented.
- Existing workflow actions use policies, database transactions, row locks, actor/timestamp evidence, and activity events.
- The company Chart of Accounts, immutable General Ledger, manual voucher workflow, reversals, controlled opening migration, financial and consolidated reports, purchase requisitions/orders, Goods Receipts, inspection, site inventory, inventory movements, Vendor Bills, three-way matching, configurable taxes/deductions, AP posting/reports, treasury transactions, bank reconciliation, Customer Invoices/Running Bills, customer receipts, trading COGS, Sales/Project profitability, Payroll accounting/settlement, Fixed Assets, paired inter-company accounting, and controlled year close are implemented.
- The local database contains the six confirmed organization companies provisioned in Phase 1.
- The repository has existing uncommitted work. Agents must preserve unrelated user changes.

## Confirmed company topology

The confirmed organization contains these six company records:

```text
7-Orbit
├── 7-Orbit IT
└── 7-Orbit Medical Billing

YM Construction
BMC
BMC Trading
```

Confirmed:

- `7-Orbit` is the parent company.
- `7-Orbit IT` is a direct child of `7-Orbit`.
- `7-Orbit Medical Billing` is a direct child of `7-Orbit`.

Not yet confirmed:

- Whether `YM Construction`, `BMC`, or `BMC Trading` has another parent.
- Whether `BMC Trading` is legally or operationally a child of `BMC`.
- Whether `7-Orbit` posts its own operational transactions or is a holding/reporting parent only.

The confirmed legal-company baseline is BMC Construction, YMC Construction, 7 Orbit, and 7 Orbit Medical Billing. All four are independent; names must never be used to infer parentage.

Each legal company has its own:

- accounting periods and books;
- Chart of Accounts instance;
- voucher numbering;
- bank/cash accounts;
- customers, vendors, projects, inventory, payroll postings, and assets;
- permissions and tenant-isolated operational data.

Company access never merges ledgers. Consolidated reporting uses the explicit authorized active-company set and is not a fake “All Companies” transaction tenant.

## Architecture decisions

### Shared application, isolated company books

Use one application and one shared database. Every operational and accounting record must have explicit company ownership and must be protected at query, relationship-validation, policy, action, export, report, and test levels.

Modules must be shared implementations. Do not create separate Accounts or Projects code for individual companies. Variations belong in per-company settings, mappings, templates, or named strategies.

### Standard account template plus company accounts

Use two layers:

1. A controlled standard Chart of Accounts template that supplies group-wide codes, reporting classifications, and consolidation mapping.
2. Company-owned accounts provisioned from that template and extendable within controlled code ranges.

Recommended core records:

```text
account_templates
- id
- parent_id
- code
- name
- account_type
- reporting_group
- normal_balance
- system_key (nullable, stable)
- is_control_account
- allows_manual_posting
- is_active

accounts
- id
- company_id
- parent_id
- account_template_id (nullable for approved company-specific additions)
- code
- name
- account_type
- reporting_group
- normal_balance
- system_key (nullable)
- is_control_account
- allows_manual_posting
- is_active
```

Rules:

- Account code is unique within a company, not globally.
- The same standard code may exist in every company.
- Posted accounts cannot be deleted or structurally moved without a controlled migration.
- Parent/group accounts cannot receive postings.
- Accounts Receivable, Accounts Payable, inventory-control, tax-control, payroll-payable, and similar accounts can be configured as non-manual control accounts.
- Company-specific accounts must map to a reporting group for consolidation.
- Do not create one GL account for every customer, vendor, employee, or project.

### Subledgers and accounting dimensions

Customer, vendor, employee, project, site, asset, and bank details belong in subledgers or dimensions. Journal lines reference them where required.

Core dimensions:

- company;
- project;
- project site/store;
- cost center/department;
- party/customer/vendor;
- employee/employment when relevant;
- item/material when generated from inventory;
- fixed asset when generated from the asset register.

Project cost must be derived from posted journal lines tagged with `project_id`; a separate set of Cement/Steel/etc. GL accounts must not be created per project.

### Double-entry is the only financial source of truth

Financial reports must derive from posted journal lines. Operational records generate accounting entries through explicit, idempotent posting actions.

Every posted journal must:

- belong to one company and one open financial period;
- have equal debit and credit totals;
- use active posting accounts from the same company;
- contain required dimensions for configured accounts;
- retain immutable source snapshots and actor/timestamp evidence;
- be reversible, never silently edited or deleted.

### Operational state is separate from accounting state

Examples:

- A Purchase Order can be approved without creating a GL entry.
- A Goods Receipt can affect inventory or GRNI depending on the confirmed accounting policy.
- A Vendor Bill is not financially effective until posted.
- Payroll being approved, paid, or locked is distinct from whether its journal has been posted.

Use an explicit accounting link/status such as unposted, posted, or reversed. Do not overload operational status.

### Money and precision

- Store monetary values as fixed-precision decimals, never binary floats.
- Confirm required precision during Phase 0; default planning assumption is `decimal(19, 4)` for calculations and quantities, with display rounding based on currency.
- Every transaction has a currency code even while the initial operating currency is PKR.
- Foreign-currency recognition and revaluation are deferred until Phase 0 confirms a requirement.

### Historical integrity

Approved and posted financial source records must preserve snapshots of names, codes, rates, quantities, taxes, and amounts needed to reproduce historical output.

Corrections use:

- rejection while still in review;
- reversal after posting;
- a replacement/adjustment transaction;
- a complete audit trail linking original, reversal, and replacement.

### Existing foundations to reuse

- `Company` tenancy and descendant-access rules.
- `CompanyModule` state, inheritance, variant, and settings.
- `CompanyBankAccount` as the physical bank-account source; link it to a company GL bank account instead of duplicating bank details.
- Document platform for quotations, purchase orders, delivery challans, invoices, inspection evidence, vouchers, bank statements, running bills, and asset documents.
- Existing Action-class workflow style with authorization, `DB::transaction()`, `lockForUpdate()`, and activity events.
- Filament resources, relation managers, policies, Shield permissions, factories, seeders, and PHPUnit feature tests.

## Standard Chart of Accounts direction

This is a controlled baseline, not a final tax opinion. Phase 0 must confirm tax, retention, and construction-contract treatment with the accountant.

```text
1000 Assets
  1100 Current Assets
    1110 Cash in Hand
      1111 Head Office Cash
      1112 Site Petty Cash
      1113 Director Cash / Director Advance (classification to confirm)
    1120 Bank Accounts
      Dynamic child account per CompanyBankAccount
    1130 Accounts Receivable (control)
    1140 Employee Advances (control)
    1150 Vendor Advances (control)
    1160 Security Deposits Paid
    1170 Other Receivables
    1180 Retention Receivable
    1185 WHT Receivable
    1190 Input GST / Sales Tax
    1195 Prepayments
    1196 Project / Site Inventory (control)
    1197 Construction Work in Progress (if confirmed)
    1198 Due from Related Companies (control)
    1199 Contract Asset / Unbilled Revenue (if confirmed)
  1200 Fixed Assets
    1210 Land
    1220 Building
    1230 Furniture
    1240 Computers
    1250 Servers
    1260 Machinery
    1270 Vehicles
    1280 Office Equipment
    1290 Accumulated Depreciation (contra-asset parent)
      Separate child contra-account per depreciable asset class

2000 Liabilities
  2100 Current Liabilities
    2110 Accounts Payable (control)
    2120 Contractor Payable (optional control group)
    2130 Supplier Payable (optional control group)
    2140 Salary Payable (control)
    2150 WHT Payable
    2160 Output GST / Sales Tax Payable
    2170 Accrued Utilities
    2180 Accrued Rent
    2190 Security Deposits Received
    2191 Retention Payable
    2192 Customer / Mobilization Advances
    2193 Goods Received Not Invoiced (GRNI)
    2194 Other Accrued Expenses
    2195 Due to Related Companies (control)
  2200 Long-Term Liabilities
    2210 Bank Loans
    2220 Director Loans
    2230 Partner Loans
    2240 Vehicle Loans

3000 Equity
  3100 Paid-up Capital
  3200 Retained Earnings
  3300 Current Year Profit / Loss (system-calculated closing result)
  Additional drawings/contribution accounts if the legal structure requires them

4000 Revenue
  4100 Construction Revenue
  4200 IT Services Revenue
  4300 Medical Billing Revenue
  4400 Trading Sales
  4500 Consultancy Income
  4600 Rental Income
  4700 Other Income

5000 Operating and Administrative Expenses
  5100 Salaries
  5200 Fuel
  5300 Office Rent
  5400 Utilities
  5500 Internet
  5600 Vehicle Rent
  5700 Printing
  5800 Stationery
  5900 Repairs and Maintenance
  6000 Vehicle Maintenance
  6100 Depreciation
  6200 Marketing
  6300 Legal and Professional
  6400 Audit
  6500 Consultancy
  6600 Software Subscriptions
  6700 Entertainment
  6800 Travelling and Conveyance
  6900 Miscellaneous

7000 Direct Project Costs / Cost of Revenue
  7100 Cement
  7110 Steel
  7120 Sand
  7130 Crush
  7140 Bricks
  7150 Electrical
  7160 Plumbing
  7170 Paint
  7180 Tiles
  7190 Labour
  7200 Machinery Rental
  7210 Excavation
  7220 Concrete Pump
  7230 Shuttering
  7240 Safety Equipment
  7250 Site Office
  7260 Site Utilities
  7270 Site Security
  7280 Project Transportation
  7290 Subcontracting and other approved direct-cost children
```

Required corrections to the original supplied chart:

- Code `4000` is Revenue, not Capital.
- Current Year Profit/Loss is calculated/closed by the system and not an ordinary manual posting account.
- Accumulated Depreciation is a contra-asset and needs asset-class children.
- Asset-side and liability-side security deposits must have unambiguous names.
- AR/AP are control accounts backed by party subledgers.
- Project accounts represent cost type; projects are dimensions.

### Company activation profiles

The group template may contain all reporting accounts, but a company should only activate accounts relevant to its operations:

- YM Construction: construction revenue, running bills, retention, mobilization, site inventory, and direct project costs.
- 7-Orbit IT: IT services, consultancy, project/cost-center tracking where needed.
- 7-Orbit Medical Billing: medical billing service revenue and relevant operating costs.
- BMC Trading: trading sales, inventory, cost of goods sold, purchases, and supplier/customer subledgers.
- BMC: activity profile remains a Phase 0 business decision.
- 7-Orbit parent: holding-only versus operational profile remains a Phase 0 decision.

## Target navigation

Filament company switcher remains the entry point; companies are not duplicated as separate module menus.

```text
Dashboard

Master Data
├── Chart of Accounts
├── Customers and Vendors
├── Materials / Items
├── Units of Measure
├── Tax Codes
├── Cost Centers
├── Projects
├── Project Sites / Stores
└── Accounting Mappings

Transactions
├── Sales
├── Purchases
├── Inventory
├── Payroll
├── Banking
└── Assets

Accounting
├── Journal Vouchers
├── Payment Vouchers
├── Receipt Vouchers
├── Contra / Bank Transfers
├── Financial Periods
├── Opening Balances
└── Bank Reconciliation

Approvals
Reports
Administration
```

Navigation visibility follows module state and permissions, but server-side authorization remains mandatory.

## Cross-cutting completion standard for every phase

Each phase must include, where relevant:

- migrations with foreign keys, company-scoped unique constraints, and query-supporting indexes;
- enums for stable states and types;
- models with explicit relationships, casts, validation, and activity-log redaction;
- factories and idempotent seeders/provisioning actions;
- tenant-aware Filament resources and relation managers;
- policies for CRUD and every custom business action;
- business-readable Shield permission labels;
- domain Action classes for multi-record/state-changing work;
- transactions, row locks, idempotency, and concurrency tests;
- private document links and cross-company link rejection;
- company module-state enforcement;
- audit events with actor, company, transition, and safe metadata;
- PHPUnit happy-path, failure, authorization, cross-company, immutability, and edge-case coverage;
- narrow test execution followed by appropriate broader regression tests;
- Pint after PHP changes;
- updates to this plan and `docs/PROJECT_STATE.md`.

## Phase 0 — Business decisions and source evidence

Status: **Implemented and Verified**

Started: 2026-07-25

Completed: 2026-07-25

### Objective

Turn assumptions into approved accounting and operational rules before schema decisions become expensive.

### Required inputs

- legal names, registration/tax identifiers, currencies, and timezones for the four independent companies;
- fiscal year and period-closing rules;
- current Chart of Accounts and opening trial balance per active company;
- real samples of journal, receipt, payment, contra, purchase, vendor bill, customer bill/running bill, payroll, bank statement, and project-cost reports;
- voucher types, numbering rules, approval levels, and segregation-of-duties requirements;
- tax codes and rules for WHT, GST/sales tax, retention, mobilization, and adjustments;
- accrual policy at GRN versus vendor-bill posting;
- inventory valuation method and negative-stock policy;
- revenue-recognition policy for construction, IT, medical billing, and trading;
- required currencies and exchange-rate behavior;
- roles and company access requirements;
- data-migration sources and cutoff date.

### Deliverables

- decision register in this file;
- approved example debit/credit entries;
- permission matrix;
- sample-data inventory with sensitive files kept outside Git;
- confirmed Phase 1–4 schema decisions.

### Acceptance criteria

- Every decision marked “Blocks Phase 1–4” in the Decision Register is resolved.
- Accountant/business owner approves representative postings and reports.
- No production-sensitive document is committed to the repository.

### Evidence audit — 2026-07-25

Verified repository and database state:

- The local database contains one active company: `YM Construction`.
- Its current legal name is also `YM Construction`; registration and tax numbers are blank.
- Its currency is PKR and timezone is Asia/Karachi.
- The confirmed 7-Orbit parent/child companies, BMC, and BMC Trading are not yet database records.
- Documents, HR, Accounts, and Projects exist in the shared module catalog.
- No per-company module configuration currently exists in the local database.
- No Accounts, Projects, parties, materials, inventory, journal, voucher, financial-period, tax-code, purchase, sales, or asset tables exist.
- No current accounting spreadsheet, opening Trial Balance, voucher, invoice, running bill, purchase order, delivery challan, bank statement, or project-cost sample was found in the repository.
- `CompanySeeder` currently creates four random factory companies and is not suitable for the confirmed production topology. It will be replaced or refactored during Phase 1 after the blocking company decisions are resolved.
- Existing company, document, HR, compensation, and payroll changes are largely uncommitted. Future phase work must preserve them and must not treat Git tracking state as implementation status.

### Approved Phase 0 defaults

The business owner approved the recommended configuration-first approach on 2026-07-25 because current operational samples and statutory details are not yet available.

| Area | Approved default |
| --- | --- |
| Company topology | BMC Construction, YMC Construction, 7 Orbit, and 7 Orbit Medical Billing are independent companies |
| Transaction capability | All four companies may post their own transactions |
| Temporary legal data | Use the confirmed display names as legal names until official details are supplied; registration/tax fields remain null |
| Currency/timezone | PKR and Asia/Karachi for all six companies unless legal books require otherwise |
| Monetary precision | Store amounts/quantities with `decimal(19,4)`; display PKR at 2 decimals |
| Fiscal year | July 1 through June 30, configurable per company before transactions exist |
| Financial periods | Monthly periods; Finance Approver closes and only a dedicated Reopen Period permission may reopen with a required reason and audit event |
| Voucher numbering | Separate sequence by company, voucher type, and fiscal year, for example `JV-2026-000001`; numbering is assigned atomically |
| Approval control | Maker cannot approve/post the same transaction; a dedicated segregation override requires explicit permission, reason, and audit evidence and is unassigned by default |
| Posted records | Immutable; correction only through linked reversal and replacement |
| Inventory valuation | Moving weighted average |
| Negative inventory | Disallowed, except a separately authorized and audited correction workflow |
| Material receipt accounting | Accrue accepted stock at GRN to GRNI, then clear GRNI when the Vendor Bill posts |
| Construction revenue | Post from approved/certified running bills; retention and mobilization tracked separately |
| IT/medical-billing revenue | Post from approved service invoices |
| Trading revenue/cost | Post sales with inventory and cost-of-goods-sold integration |
| Foreign currency | PKR-only initially; add foreign currency only when a real requirement and rate source are confirmed |
| Opening balances | Local/new books start at zero; controlled opening-balance import remains available in Phase 4 for later migration |
| BMC operating profile | Generic transaction-capable profile; activate business-specific accounts through configuration when its activity is supplied |

### Voucher types and numbering prefixes

| Voucher type | Prefix | Initial purpose |
| --- | --- | --- |
| Journal | JV | Approved general and adjustment journals |
| Payment | PV | Cash/bank payments |
| Receipt | RV | Cash/bank receipts |
| Contra / Bank Transfer | CV | Transfers between company cash/bank accounts |
| Purchase / Vendor Bill | PUR | Vendor invoices |
| Sales / Customer Bill | SAL | Service, trading, and construction bills |
| Debit Note | DN | Approved debit adjustments |
| Credit Note | CN | Approved credit adjustments |
| Opening Balance | OB | Controlled migration/opening entry |
| Payroll | PAY | Payroll posting |
| Depreciation | DEP | Fixed-asset depreciation |
| Inventory Adjustment | IA | Authorized inventory gains/losses |
| Reversal | REV | System-linked reversal |
| Inter-company | IC | Paired inter-company workflow introduced in Phase 12 |

### Tax and statutory-data safety rule

No numeric GST/sales-tax, WHT, retention, mobilization, or other statutory rate will be invented or treated as authoritative.

- Phase 2 creates configurable company tax-code records with effective dates and active/inactive state.
- No statutory tax code is active by default.
- Tax-inclusive/exclusive calculation behavior is tested with synthetic rates only.
- Construction retention and mobilization are project/contract terms, not global hard-coded percentages.
- An authorized accountant must configure and approve applicable codes/rates before a live taxable transaction can be posted.
- Later legal names, NTN/tax identifiers, addresses, opening balances, and go-live dates are operational configuration/migration inputs, not schema blockers.

### Approved synthetic source layouts

Real samples are unavailable. The following safe synthetic layouts are the reference for schemas and tests until superseded by redacted business samples:

| Source | Required synthetic fields |
| --- | --- |
| Purchase Requisition | Company, project/site, requester, required date, item/service, UOM, quantity, estimated rate, reason, budget reference |
| Purchase Order | Company, PO number/date, vendor, project/site, currency, item lines, quantity, rate, tax code, payment terms, approvers, immutable approved snapshot |
| GRN / Delivery | PO, vendor delivery reference, site/store, receiver/time, item, ordered/previous/received quantity, accepted/rejected quantity, inspector/time, result, rejection reason, Accounts handover |
| Vendor Bill | Vendor invoice number/date, PO/GRN links, lines, quantities, rates, tax codes, WHT, retention/advance deductions, due date, match status, approvals |
| Payment | Payee, bank/cash account, value date, allocated documents, amount, instrument/reference, preparer, approver/poster |
| Running Bill | Project, client, certificate number/date, work value, variation, retention, mobilization recovery, tax/WHT codes, certified amount, due date, approvals |
| Service/Trading Sale | Customer, invoice/date, service/item lines, project optional, quantities, rates, tax code, due date, approvals |
| Payroll Posting | Payroll run, period, salary/direct-labour allocation, salary payable, deductions, bank/cash settlement totals |
| Bank Statement | Company bank account, statement period, transaction date, value date, description, bank reference, debit, credit, balance |
| Financial Reports | Company/scope, period, account code/name, opening, debit, credit, closing, project/party dimensions where applicable |

### Approved synthetic posting scenarios

Amounts are test data only and do not approve any tax rate.

#### S-001 Material receipt, bill, issue, and payment

An approved PO requests 100 units at PKR 1,200. The site accepts 90 and rejects 10.

```text
Accepted GRN:
Dr Project/Site Inventory       108,000
    Cr GRNI                                108,000

Matched Vendor Bill (no tax in this synthetic case):
Dr GRNI                         108,000
    Cr Accounts Payable                    108,000

Issue 60 units to a construction project:
Dr Cement / Direct Project Cost 72,000
    Cr Project/Site Inventory               72,000

Vendor payment:
Dr Accounts Payable            108,000
    Cr Company Bank                        108,000
```

Rejected quantity does not enter available inventory or GL.

#### S-002 Construction running bill

For synthetic testing only: certified work PKR 1,000,000, contract retention PKR 50,000, and WHT PKR 30,000.

```text
Dr Accounts Receivable          920,000
Dr Retention Receivable          50,000
Dr WHT Receivable                30,000
    Cr Construction Revenue               1,000,000
```

Actual percentages and sales tax require configured effective-dated codes/contract terms.

#### S-003 Service invoice and receipt

```text
Approved IT/Medical service invoice:
Dr Accounts Receivable          100,000
    Cr Service Revenue                     100,000

Receipt:
Dr Company Bank                 100,000
    Cr Accounts Receivable                 100,000
```

#### S-004 Trading sale and cost of goods sold

```text
Sale:
Dr Accounts Receivable          150,000
    Cr Trading Sales                       150,000

Inventory consumption:
Dr Cost of Goods Sold             90,000
    Cr Trading Inventory                     90,000
```

#### S-005 Payroll posting and settlement

```text
Post approved payroll:
Dr Salaries / Direct Labour       500,000
    Cr Salary Payable                        470,000
    Cr WHT / Other Payables                   30,000

Settlement:
Dr Salary Payable                 470,000
    Cr Company Bank                          450,000
    Cr Cash in Hand                          20,000
```

#### S-006 Company bank transfer

```text
Dr Destination Company Bank       200,000
    Cr Source Company Bank                    200,000
```

Both bank accounts must belong to the same company; inter-company transfers use Phase 12.

#### S-007 Reversal

A reversal reproduces every original line with debit/credit exchanged, links both journal headers, uses an open period, and cannot be posted twice.

### Phase 0 permission matrix

Roles are reusable templates; company membership controls tenant scope. Exact role assignment remains configurable.

| Capability | Default role/template | Sensitive or segregation rule | Audit requirement |
| --- | --- | --- | --- |
| Maintain company/module settings | Super Admin / Company Admin | Company scope; hierarchy changes separately authorized | Old/new settings and actor |
| Maintain master data | Master Data Manager | Same-company relationships only | Create/update/archive |
| Prepare operational documents | Procurement/Sales/Accounts Preparer | Cannot approve own record | Creator and changes |
| Receive material | Store Receiver | Cannot inspect unless separately permitted | Quantities, time, delivery reference |
| Inspect material | Site Inspector / Engineer | Must not alter received quantity silently | Result, accepted/rejected, reason |
| Handover GRN to Accounts | Store Supervisor | Accepted/rejected quantities fixed | Actor and timestamp |
| Review/match Vendor Bill | Accounts Reviewer | PO/GRN mismatch requires dedicated override | Match evidence and exception reason |
| Approve transactions | Finance Approver | Maker-checker enforced | Approval/rejection and reason |
| Post/reverse journals | Finance Poster / Controller | Cannot post own prepared transaction without unassigned override | Posting/reversal link, period, actor |
| Pay/receive funds | Cashier/Treasury | Separate approval/post permission | Allocation, bank/cash, instrument |
| Close periods | Finance Approver | Reopen requires dedicated permission and reason | Close/reopen actor and reason |
| Reconcile banks | Bank Reconciler | Reopen reconciliation separately authorized | Matches, adjustments, close/reopen |
| View consolidated reports | Group Finance / Auditor | Explicit descendant-company reporting scope | Export/view of sensitive report |
| Configure statutory tax codes | Authorized Accountant | No active default rates; effective-dated | Old/new rate and effective date |
| Segregation override | Unassigned by default | Explicit permission, reason, high-severity audit | Actor, reason, affected record |

### Deferred operational configuration

These items are not phase blockers because the approved design keeps them configurable, but they remain mandatory before production use of the affected capability:

- official legal names, registration numbers, NTN/tax numbers, and addresses;
- active statutory tax/WHT codes and rates;
- company-specific retention and mobilization contract terms;
- production go-live/cutoff date;
- opening Trial Balance and outstanding subledger balances;
- real bank-statement import layout;
- real approval limits by amount;
- redacted operational samples if later available.

### Phase 0 completion record

Implementation completed:

- Date: 2026-07-25
- Implemented by: Codex with business-owner approval
- Status changed from: Blocked
- Status changed to: Implemented and Verified

Actual implementation:

- Migrations/tables: None; Phase 0 is a decision and evidence gate.
- Models/enums: None.
- Actions/services: None.
- Filament resources/pages/relation managers: None.
- Policies/permissions: Permission matrix approved for later implementation.
- Seeders/provisioning: Confirmed company and configuration defaults recorded for Phase 1.
- Documents/audit: Synthetic source layouts and safe-data rules recorded.
- Reports/exports: Required financial report layouts recorded.

Verification:

- Focused tests: Not applicable; no application code changed.
- Broader tests: Not run; no application code changed.
- Formatting/static checks: Markdown/diff whitespace validation passed.
- Manual verification: Repository/database evidence audit completed; business owner approved the recommended default approach.

Decisions and deviations:

- Decisions resolved: D-001 through D-015 as recorded below.
- Differences from planned design: Real operational samples were unavailable, so approved synthetic layouts and postings are the temporary design evidence.
- Reason: The system is new and current business information is not yet available.
- Data migration/backfill: None; opening balance tooling remains Phase 4 and production migration remains Phase 12.
- Known limitations: Statutory rates, official legal data, actual opening balances, and live approval limits remain unconfigured.
- Follow-up explicitly assigned to later phase: Phase 1 company provisioning; Phase 2 tax-code/master structures; Phase 4 opening-balance tooling; later transaction phases enforce live-configuration gates.

## Phase 1 — Legal-company topology and module governance

Status: **Implemented and Verified**

Started: 2026-07-25

Completed: 2026-07-25

### Scope

- Provision or safely reconcile the six confirmed companies.
- Set 7-Orbit as parent of its two confirmed children.
- Leave unconfirmed companies as independent roots.
- Preserve existing YM Construction data and identifiers.
- Configure inherited/explicit module states for Documents, HR, Accounts, and Projects.
- Define whether parent access to 7-Orbit descendants is granted per membership.
- Make provisioning idempotent and safe for existing databases.

### Acceptance criteria

- Hierarchy is correct and circular relationships remain impossible.
- Re-running provisioning does not duplicate or re-parent incorrectly.
- Company switching and descendant access are tested.
- No user gains access merely because a company is a descendant.
- Database reality and `PROJECT_STATE.md` match this file.

### Phase 1 completion record

Implementation completed:

- Date: 2026-07-25
- Implemented by: Codex
- Status changed from: In Progress
- Status changed to: Implemented and Verified

Actual implementation:

- Migrations/tables: No new schema was required; existing `companies`, `modules`, `company_modules`, and `company_user` constraints were reused.
- Models/enums: Existing `Company`, `Module`, `CompanyModule`, and `CompanyModuleState` behavior was reused.
- Actions/services: Added `ProvisionOrganizationCompaniesAction`, which transactionally reconciles canonical company identities, hierarchy, active/default values, and missing module configurations.
- Filament resources/pages/relation managers: Existing company tenant switcher, Companies resource, and Company Modules resource were retained.
- Policies/permissions: Existing company and company-module policies remain authoritative. Provisioning does not create memberships or grant descendant access.
- Seeders/provisioning:
  - `CompanySeeder` now provisions the real organization instead of four random factory companies.
  - `CompanyModuleSeeder` delegates to the same idempotent organization action instead of creating random configuration.
  - `DatabaseSeeder` invokes `CompanySeeder` in the correct order.
  - Shared module descriptions reflect the approved Finance/Projects scope.
  - Existing document-category and joining-letter-template provisioning runs for every active provisioned company.
- Documents/audit: Every company has the five existing default document categories and one standard joining-letter template.
- Reports/exports: Not applicable to this phase.

Verified database result:

```text
YM Construction                 root, existing ID preserved
7-Orbit                         root
├── 7-Orbit IT                  inherits module states
└── 7-Orbit Medical Billing     inherits module states
BMC                             root
BMC Trading                     root
```

- Six active companies.
- Four shared module configurations per company, 24 total.
- Root companies have Documents, HR, Accounts, and Projects explicitly Enabled.
- 7-Orbit children have all four module states set to Inherit.
- Thirty default document categories and six joining-letter templates.
- A second local seeder run left all counts unchanged.

Verification:

- Focused tests:
  - `php artisan test --compact tests/Feature/OrganizationCompanyProvisioningTest.php` — 3 passed, 30 assertions.
  - `php artisan test --compact tests/Feature/CompanyFoundationTest.php` — 5 passed, 19 assertions.
  - `php artisan test --compact tests/Feature/Filament/CompanyTenantAuthorizationTest.php` — 7 passed, 26 assertions.
- Broader tests: Full suite not run; the three directly affected suites passed.
- Formatting/static checks: `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.
- Manual verification: Laravel Boost read-only queries verified hierarchy, module states, and counts after two seeder runs.

Decisions and deviations:

- Decisions resolved: The documented topology was corrected from five to six records; the listed structure always contained six companies.
- Differences from planned design: No schema migration was necessary because existing hierarchy, module, and membership structures met the phase requirements.
- Reason: The smallest coherent implementation was an idempotent domain action plus deterministic seeders and tests.
- Data migration/backfill: Existing YM Construction ID and supplied legal/tax/custom fields are preserved. Missing confirmed companies and configurations were added.
- Known limitations: Legal identifiers remain unset where unavailable; company-specific operating profiles remain later configuration.
- Follow-up explicitly assigned to later phase: Phase 2 shared parties, Projects, sites, items, tax-code structure, and operational master data.

## Phase 2 — Shared parties, projects, sites, and operational master data

Status: **Implemented and Verified**

Started: 2026-07-25

Completed: 2026-07-25

### Scope

Create company-scoped foundations used by multiple later modules:

- parties with customer, vendor, contractor, consultant, and combined roles;
- company-specific party codes, contacts, tax details, payment terms, and active state;
- projects;
- project sites/stores;
- cost centers;
- materials/items/services;
- item categories and units of measure;
- tax-code master after Phase 0 confirmation;
- project budget headers and cost-code lines sufficient for cost control.

### Project model direction

```text
Project
- company
- code (unique per company)
- name
- client party
- consultant party (optional)
- location
- planned and actual start dates
- planned and actual completion dates
- budget
- contract value
- retention terms
- mobilization terms
- currency
- status
```

Running Bills, actual Cost, Revenue, and Profit are derived values, not editable project columns.

### Acceptance criteria

- Cross-company relationships are rejected at model/action boundaries.
- Projects, parties, items, sites, and cost centers are tenant-isolated and permission-protected.
- One party can hold multiple roles without duplicate records.
- Project budgets preserve approved versions or immutable snapshots as decided in Phase 0.
- Existing documents can safely relate to Projects and approved new operational record types.

### Phase 2 completion record

Implementation completed:

- Date: 2026-07-25
- Implemented by: Codex
- Status changed from: In Progress
- Status changed to: Implemented and Verified

Actual implementation:

- Migrations/tables:
  - Added company-scoped `parties`, `party_contacts`, `cost_centers`, `units_of_measure`, `item_categories`, `tax_codes`, `items`, `projects`, `project_sites`, `project_budgets`, and `project_budget_lines`.
  - Added company/code/version uniqueness, query indexes, foreign keys, soft deletion where appropriate, and `decimal(19,4)` monetary storage.
- Models/enums:
  - Added typed party roles, project status/site type, item type, tax type/calculation method, and project-budget status.
  - Added cross-company validation at model boundaries, active scopes, relationships, audit configuration, tax effective-date overlap protection, and material/service inventory rules.
  - One Party record holds multiple customer/vendor/contractor/consultant roles.
- Actions/services:
  - Added transactional `ApproveProjectBudgetAction` with authorization, row locks, maker-checker enforcement, exact line-total snapshot, prior-version supersession, actor evidence, and an audit event.
- Filament resources/pages/relation managers:
  - Added tenant-scoped Master Data resources for Parties, Cost Centers, Units of Measure, Item Categories, Tax Codes, Items, Projects, Project Sites/Stores, and Project Budgets.
  - Added Party Contacts and Project Budget Lines relation managers.
  - Added project-document relation management and project selection to the existing secure document workflow.
- Policies/permissions:
  - Added reusable company-scoped policy behavior plus resource-specific deletion and budget immutability controls.
  - Added CRUD/restore permissions for Phase 2 resources, line-level permissions, and `Approve:ProjectBudget`.
  - Resource queries, forms, actions, and relationship selections remain within the active company tenant.
- Seeders/provisioning:
  - Extended `FoundationPermissionSeeder` idempotently.
  - No parties, items, tax rates, projects, or budgets were invented or seeded.
- Documents/audit:
  - Existing private, versioned Documents can relate to a same-company Project; cross-company Project links are rejected.
  - Operational masters log dirty non-sensitive changes; budget approval records version, total, line count, actor, and superseded IDs.
- Reports/exports:
  - No financial reports or exports were introduced; actual project cost/revenue/profit remain derived from later posted ledger entries.

Verification:

- Pre-phase full suite: `php artisan test --compact` — 94 passed, 455 assertions.
- Focused/adjacent suites: 29 passed, 110 assertions.
- Post-phase full suite: `php artisan test --compact` — 108 passed, 507 assertions.
- Formatting/static checks: PHP syntax checks, `vendor/bin/pint --dirty --format agent`, and `git diff --check` passed.
- Database verification:
  - All 11 Phase 2 migrations are applied locally.
  - Laravel Boost schema inspection verified company/project/party/item/budget foreign keys and indexes.
  - Six companies remain present.
  - `Approve:ProjectBudget` exists exactly once.
  - No active tax code exists by default.

Decisions and deviations:

- Decisions resolved: A single company-specific Party can carry multiple roles; project budgets use immutable approved versions; later approval supersedes the prior approved version without changing its snapshot.
- Differences from planned design: Project budget is derived from the current approved version rather than stored as an independently editable Project amount.
- Reason: This prevents the Project header from diverging from approved cost-code lines and preserves historical versions.
- Data migration/backfill: None; the application has no supplied live parties, projects, items, tax codes, or budgets to migrate.
- Known limitations: Official master data and statutory rates remain unavailable; all tax codes are inactive until accountant configuration; budget approval is currently one maker-checker step; real approval limits and source samples remain deferred configuration.
- Follow-up explicitly assigned to later phase: Phase 3 uses these dimensions for company Chart of Accounts mappings; later procurement, inventory, billing, and ledger phases consume the same Party, Project, Site, Item, Tax Code, Cost Center, and approved Budget records.

## Phase 3 — Accounting foundation and company Chart of Accounts

Status: **Implemented and Verified**

Started: 2026-07-25

Completed: 2026-07-25

### Scope

- Account-template hierarchy and reporting classifications.
- Company account trees provisioned idempotently from the template.
- Account types, normal balances, control-account flags, and manual-posting rules.
- Company accounting settings and system-account mappings.
- Dynamic mapping between each `CompanyBankAccount` and its GL account.
- Financial years and periods with open/closed/locked states.
- Company-specific voucher sequence configuration.
- Account import/provisioning preview with validation and audit evidence.

### Required system mappings

At minimum:

- default cash;
- each company bank account;
- Accounts Receivable;
- Accounts Payable;
- employee and vendor advances;
- input/output tax;
- WHT receivable/payable;
- salary payable;
- retention receivable/payable;
- customer/mobilization advances;
- GRNI;
- site inventory;
- work in progress if enabled;
- due from/to related companies;
- retained earnings and current-year result.

### Acceptance criteria

- Tree integrity and company ownership are enforced.
- Codes are unique within company.
- Parent and non-posting/control accounts reject prohibited manual postings.
- Template changes do not silently mutate posted company history.
- Relevant company profiles activate appropriate accounts without hard-coded company-name branches.

### Completion record

Implementation completed:
- Date: 2026-07-25
- Implemented by: Codex
- Status changed from: In Progress
- Status changed to: Implemented and Verified

Actual implementation:
- Migrations/tables: `account_templates`, company `accounts`, `accounting_settings`, `accounting_mappings`, `financial_years`, monthly `financial_periods`, and `voucher_sequences`, with company/code/system-key uniqueness, foreign keys, and query indexes.
- Models/enums: typed account classifications, normal balances, company profiles, required mapping keys, financial-period states, valuation method, and fourteen approved voucher types/prefixes; same-company tree, overlap, posting, and mapping invariants are enforced at the model boundary.
- Actions/services: idempotent global-template provisioning, non-mutating company account snapshots, provisioning preview/conflict validation, company accounting setup, dynamic bank-to-GL synchronization, period close/lock/reopen, and atomic voucher-number reservation.
- Filament resources/pages: tenant-scoped Chart of Accounts, settings, mappings, financial years/periods, and voucher sequences; controlled global template resource; account provisioning and period workflow actions.
- Policies/permissions: tenant-scoped CRUD policies plus explicit preview/provision, period close/lock/reopen, and sequence reservation permissions.
- Seeders/provisioning: 101 controlled template accounts and 21 required system mappings; every confirmed company receives 101 account snapshots, profile activation, July–June settings, twelve monthly periods, and fourteen voucher sequences. Provisioning ran twice without duplicates.
- Documents/audit: provisioning and financial-period transitions create activity evidence.
- Reports/exports: deferred to ledger/reporting phases because Phase 3 contains no posted balances.

Verification:
- Focused tests: 7 tests, 21 assertions for idempotency, snapshot preservation, profile activation, bank mappings, company/tree/manual-posting rules, tenant authorization, period workflows, and voucher numbering.
- Broader tests: complete suite passed with 115 tests and 528 assertions.
- Formatting/static checks: Pint passed; application route discovery passed; migrations ran successfully.
- Manual verification: Boost schema inspection confirmed constraints/indexes; database queries confirmed 101 templates, six company setups with 101 accounts/12 periods/14 sequences each, and two-run seeder idempotency.

Decisions and deviations:
- Decisions resolved: accounting profile is explicit configuration; known-company profile selection exists only in the seeder, not in domain provisioning logic.
- Differences from planned design: import is delivered as an idempotent preview/provision conflict workflow; external spreadsheet import remains unnecessary until a real source chart is supplied.
- Reason: no legacy Chart of Accounts or opening-balance file is currently available.
- Data migration/backfill: all six current companies were provisioned; no financial balances were invented.
- Known limitations: no vouchers, journal lines, postings, opening balances, or financial statements exist yet; bank mapping was automated and tested, while the current local database contains no company bank accounts.
- Follow-up explicitly assigned to later phase: Phase 4 adds the immutable balanced ledger, voucher workflow, posting-date enforcement, reversals, and opening balances.

## Phase 4 — Double-entry ledger, vouchers, periods, and opening balances

Status: **Implemented and Verified**

Started: 2026-07-26

Completed: 2026-07-26

### Scope

- immutable posted journal headers and lines;
- draft, submitted, approved, posted, rejected, and reversed workflow;
- journal, payment, receipt, contra/transfer, and approved adjustment types;
- explicit source links to operational records;
- project, party, cost-center, bank, employee, and asset dimensions where required;
- balanced-entry validation and debit/credit positivity rules;
- open-period enforcement and period close/lock authorization;
- idempotent posting and reversal actions;
- controlled opening-balance import and validation;
- General Ledger, Trial Balance, Balance Sheet, and Profit & Loss baseline reports.

### Acceptance criteria

- Unbalanced or cross-company journals cannot be posted.
- Duplicate action requests cannot double-post.
- Posted journals cannot be edited or deleted.
- Reversal preserves and links to the original.
- Reports reconcile to journal lines and opening balances.
- Period locks block all prohibited backdated postings.
- Tenant, permission, concurrent-posting, and rounding edge cases are tested.

### Completion record

Implementation completed:
- Date: 2026-07-26
- Implemented by: Codex
- Status changed from: In Progress
- Status changed to: Implemented and Verified

Actual implementation:
- Migrations/tables: added `journal_entries`, immutable `journal_lines`, controlled `opening_balance_batches`, and `opening_balance_lines`; constraints cover company/year voucher uniqueness, company idempotency keys, one linked reversal, line ordering, source lookup, dimensions, and report query indexes.
- Models/enums: added Draft, Submitted, Approved, Posted, Rejected, and Reversed journal states plus Draft, Validated, and Posted opening-balance states. Model boundaries enforce company/year/period ownership, exact debit-or-credit lines, company dimensions/sources, workflow transitions, posted immutability, account snapshots, and structural protection for posted accounts.
- Actions/services: added exact four-decimal validation, submission, maker-checker approval/rejection, open-period posting, atomic voucher reservation, stale-request-safe idempotency, linked opposite-entry reversal, opening-balance validation/posting, and baseline General Ledger, Trial Balance, Balance Sheet, and Profit & Loss report services.
- Filament resources/pages: added tenant-scoped Vouchers/Journals and Opening Balances resources with repeatable dimensioned lines, workflow actions, evidence views, and a tenant financial-statements page.
- Policies/permissions: added tenant-scoped journal/opening-balance CRUD plus explicit Submit, Approve, Reject, Post, Reverse, Validate Opening Balances, Post Opening Balances, and View Accounting Reports capabilities with business-readable role labels.
- Seeders/provisioning: permission registry was extended idempotently; no financial transaction or opening balance was seeded.
- Documents/audit: Documents can link safely to a same-company journal; journal and opening-balance workflow transitions emit actor/company/amount/source-safe audit evidence.
- Reports/exports: date-aware report services derive exclusively from Posted and Reversed journal history; the Filament page exposes current-year Trial Balance, P&L, and Balance Sheet. Export remains a later hardening/reporting concern.

Verification:
- Focused tests: 14 tests, 49 assertions cover balanced and unbalanced entries, cross-company accounts/sources, maker-checker, stale duplicate post requests, idempotency-key uniqueness, four-decimal precision, closed periods, immutable posted history, one linked reversal, opening-balance validation/posting, report reconciliation, Filament creation, tenant isolation, permissions, and report-page access.
- Broader tests: complete suite passed with 129 tests and 577 assertions.
- Formatting/static checks: Pint, route discovery, and `git diff --check` passed.
- Manual verification: all four migrations ran; Boost schema inspection confirmed foreign keys, uniqueness, source/dimension indexes, and reversal links; all eight custom permissions exist.

Decisions and deviations:
- Decisions resolved: maker cannot approve or post the same journal; no segregation override is assigned or silently applied. Posted correction is reversal-only and numbering remains company/type/fiscal-year atomic.
- Differences from planned design: manual Phase 4 UI exposes Journal, Payment, Receipt, Contra, Debit Note, and Credit Note voucher shells; Purchase, Sales, Payroll, Depreciation, Inventory, and Inter-company documents are generated by their later operational phases.
- Reason: later phases own their subledger allocation, matching, inventory, payroll, asset, and paired-company rules; Phase 4 supplies their shared posting engine without inventing those workflows.
- Data migration/backfill: schema and permissions were applied, but the local ledger and opening-balance tables remain empty by design because no approved Trial Balance/cutoff data exists.
- Known limitations: foreign currency, operational allocations, report exports, year-end closing, consolidation, and production migration are not part of Phase 4.
- Follow-up explicitly assigned to later phase: Phase 5 begins purchase requisitions and purchase orders; subsequent phases generate their accounting through this posting engine.

## Phase 5 — Purchase requisitions and purchase orders

Status: **Implemented and Verified**

Started: 2026-07-26

Completed: 2026-07-26

### Scope

- project/site purchase requisitions;
- material/service lines, required dates, quantities, estimated rates, and budget checks;
- quotation/document links where supplied;
- purchase orders with vendor, company, project/site, taxes, payment terms, and approved snapshots;
- configurable approval path and authorization limits;
- partial ordering and cancellation rules;
- no GL posting merely from requisition or PO approval.

### Workflow

```text
Draft → Submitted → Approved → Ordered → Partially Received → Received → Closed
                   ↘ Rejected                                  ↘ Cancelled
```

### Acceptance criteria

- Approval and changes are fully audited.
- An approved PO snapshot cannot be silently altered.
- Vendor, project, site, item, and tax selections belong to the same company.
- Ordered quantities and later receipt availability remain consistent under concurrent requests.

### Phase 5 completion record

Implementation completed:

- Date: 2026-07-26
- Implemented by: Codex
- Status changed from: In Progress
- Status changed to: Implemented and Verified

Actual implementation:

- Migrations/tables: Added company/year document sequences, configurable procurement approval rules and immutable approval-step evidence, Purchase Requisition headers/lines, and Purchase Order headers/lines. Company ownership, source links, quantity fields, actor evidence, uniqueness, lookup indexes, and restrictive foreign keys are explicit.
- Models/enums: Added typed PR, PO, approval, and document statuses with factories and relationships. Model boundaries reject cross-company project, site, vendor, item, UOM, tax, budget, and source-requisition links; submitted/approved evidence and approved PO snapshots are immutable.
- Actions/services: Added atomic PR/PO numbering; exact budget and commitment checks; configurable sequential approval snapshots; maker-checker submit, approve, reject, resubmit, cancel, and issue workflows; partial PO creation from approved PR lines; concurrency-safe ordered-quantity reservation and release; and approved commercial snapshot hashing.
- Filament resources/pages/relation managers: Added tenant-scoped Purchase Requisition, Purchase Order, and Procurement Approval Rule resources with line repeaters, workflow actions, approval evidence, and existing private Document relation managers.
- Policies/permissions: Added company-scoped policies for PR/PO headers and lines, approval rules, and approval evidence. Added resource permissions plus explicit submit, approve, reject, cancel, issue, and two configurable procurement approval-level permissions.
- Seeders/provisioning: Extended `FoundationPermissionSeeder`; two consecutive runs were idempotent and every Phase 5 permission exists exactly once.
- Documents/audit: Quotations and other evidence reuse immutable private Documents with same-company enforcement. Workflow records retain actor, timestamp, reason, approval round, decision evidence, budget snapshot, and approved PO snapshot/hash.
- Reports/exports: No new report/export was required. Phase 6 consumes the exposed per-line ordered, received, cancelled, and available-to-receive quantities.

Verification:

- Focused tests: 15 tests, 72 assertions cover budget limits and cumulative commitments, sequential approvals, rejection/resubmission history, maker-checker, company boundaries, Filament creation/tenant isolation, approved snapshot immutability, partial and competing orders, cancellation/release rules, document isolation, and the absence of premature GL posting.
- Broader tests: complete suite passed with 144 tests and 649 assertions.
- Formatting/static checks: Pint and `git diff --check` passed; route discovery and PHP loading completed successfully.
- Manual verification: all seven migrations ran; Boost schema inspection confirmed PR/PO and approval-rule/evidence columns, foreign keys, uniqueness, and indexes; permission queries confirmed one row per permission; local PR, PO, approval, and Journal tables remain empty.

Decisions and deviations:

- Decisions resolved: PR/PO approval is company-configurable by amount and permission, defaults to a Finance approval step when no rule matches, and always prohibits self-approval. Draft/approval does not reserve quantity; PO issue is the reservation boundary. PR/PO approval or issue creates no Journal Entry.
- Differences from planned design: Receiving statuses belong to Phase 6 GRN/inspection records rather than being manually edited on the PO. Phase 5 stores the quantity controls and availability needed by that workflow.
- Reason: Inventory availability must arise from verified receipt movements, while procurement approval must remain free of inventory and GL side effects.
- Data migration/backfill: Schema and permissions were applied. No requisitions, orders, approval rules, approval decisions, quantities, or financial entries were fabricated because source data and live approval limits are unavailable.
- Known limitations: Live approval limits are not configured; the safe default approval path is used. Quotation comparison, receiving/inspection, inventory valuation, Vendor Bills, matching, AP posting, and payment are later phases.
- Follow-up explicitly assigned to later phase: Phase 6 implements GRNs, receiving/inspection, accepted/rejected/returned quantities, site inventory movements, and material handover to Accounts.

## Phase 6 — Material receiving, inspection, site inventory, and returns

Status: **Implemented and Verified**

Started: 2026-07-27

Completed: 2026-07-27

### Scope

- Goods Receipt Notes against approved POs, including controlled exceptions;
- delivery challan and receiving evidence;
- received, accepted, rejected, and returned quantities;
- receiver, inspector/verifier, and Accounts-handover actors/timestamps;
- inspection results, notes, photos, lab reports, and rejection reasons;
- site/store inventory movements;
- inter-site transfers, material issues to projects, adjustments, and returns;
- valuation according to Phase 0 decision;
- negative-stock prevention unless explicitly authorized.

### Workflow

```text
Material Purchase / PO
        ↓
Vendor Delivery
        ↓
Project Site / Store
        ↓
Received By
        ↓
Checked and Verified By
        ↓
Accepted / Partially Accepted / Rejected
        ↓
Receiving Handover to Accounts
```

Handover to Accounts is an explicit state with actor evidence; it does not mean the Vendor Bill is automatically approved or paid.

### Accounting direction

If accrual at receipt is approved:

```text
Dr Project/Site Inventory
    Cr Goods Received Not Invoiced
```

If accounting is deferred until invoice, GRN remains an inventory/operational record until Phase 7 posting. This is a Phase 0 decision.

Material issue:

```text
Dr Direct Project Cost (project dimension required)
    Cr Project/Site Inventory
```

### Acceptance criteria

- Rejected quantities never become available inventory.
- Partial receipt/inspection/return math is concurrency-safe.
- Every inventory movement has a source, actor, timestamp, company, site, item, quantity, and valuation evidence.
- Inventory and relevant GL control balances reconcile when receipt accounting is enabled.

### Phase 6 completion record

Implementation completed:

- Date: 2026-07-27
- Implemented by: Codex
- Status changed from: In Progress
- Status changed to: Implemented and Verified

Actual implementation:

- Migrations/tables: Added Goods Receipt headers/lines, unique company/site/item inventory balances, Inventory Transaction headers/lines, and immutable Inventory Movements. Six migrations define company/source/dimension foreign keys, quantity/value precision, actor evidence, company numbering, stock-ledger indexes, and one-to-one accounting links.
- Models/enums: Added typed receipt, inspection, inventory transaction, movement, and direction states. Same-company PO/vendor/Project/site/item/UOM/account/source boundaries, receipt/inspection totals, return limits, draft-only editing, posted immutability, tracked-item requirements, and non-negative balance constraints are enforced.
- Actions/services: Added atomic GRN/Inventory numbering; concurrency-safe Receive, independent Inspect, rejected-material return, and three-actor Accounts Handover; moving-weighted-average balance application; immutable stock movements; PO quantity/status reopening after returns; transfers, Project issues/returns, vendor returns, and increases/decreases; automatic balanced inventory/GRNI/direct-cost journals in open periods.
- Filament resources/pages/relation managers: Added tenant-scoped Goods Receipts & Inspection and Inventory Transfers & Issues transaction resources. Added read-only Site Inventory Balances and Inventory Stock Ledger resources. GRN/Inventory records reuse private Document relation management.
- Policies/permissions: Added company-scoped CRUD and workflow policies, separate Receive, Inspect, Handover, Return Rejected, and Post Inventory permissions, and view-only balance/movement permissions. Receiving, inspection, Accounts handover, and inventory posting enforce server-side actor separation.
- Seeders/provisioning: Extended `FoundationPermissionSeeder`; two consecutive runs were idempotent and each Phase 6 permission exists exactly once. No inventory master, opening stock, receipt, movement, or journal data was invented.
- Documents/audit: Delivery challans, photos, lab reports, inspection evidence, return evidence, and inventory support documents reuse immutable private Documents with same-company enforcement. Receipt/inspection/handover/return/post transitions log actor, company, source, quantities/value, and accounting evidence.
- Reports/exports: Current balance and immutable stock-ledger Filament views expose site/item quantity, moving-average cost, inventory value, movement source, running quantities/values, and actors. Exports remain later hardening scope.

Verification:

- Focused tests: 12 tests, 75 assertions cover accepted/rejected receipt accounting, partial/over-receipt rollback, independent inspection, rejected and accepted returns, PO quantity reopening, moving weighted average, transfers, Project issues, adjustments, vendor returns, negative stock, maker-checker, tenant resources, read-only ledgers, authorization, and private evidence isolation.
- Broader tests: complete suite passed with 156 tests and 724 assertions.
- Formatting/static checks: Pint, route discovery, PHP application loading, and `git diff --check` passed.
- Manual verification: all six migrations ran; Boost schema inspection confirmed columns, decimal precision, company/source foreign keys, uniqueness, and stock-ledger indexes; permission queries confirmed one row per permission; local Goods Receipt, Inventory Transaction, balance, movement, and Journal tables remain empty.

Decisions and deviations:

- Decisions resolved: accepted stock becomes available only at Accounts handover, where it posts Dr Site Inventory / Cr GRNI. Rejected stock never enters inventory. Material issues post at current moving-average cost. Transfers preserve value without a GL entry because both sites use the same company inventory control account.
- Differences from planned design: Receipt and inspection evidence is recorded before inventory/GL effect; the explicit Accounts handover atomically creates both so inventory and its control account do not drift. Rejected and accepted vendor returns reopen PO receipt availability for replacement deliveries.
- Reason: A single inventory/accounting boundary provides traceability and reconciliation while retaining separate receiver, inspector, and Accounts actors.
- Data migration/backfill: Schema and permissions were applied. No opening stock exists because no approved stock count/valuation source was supplied.
- Known limitations: Negative inventory has no override workflow and remains prohibited. Inventory transactions require an explicitly selected same-company cost/adjustment account where no dedicated system mapping exists. Vendor Bills, three-way matching, AP clearing, and bill/payment reports remain Phase 7/8.
- Follow-up explicitly assigned to later phase: Phase 7 consumes handed-over accepted quantities and GRNI evidence for Vendor Bills, PO–GRN–Invoice matching, taxes, deductions, AP posting, and reconciliation reports.

## Phase 7 — Vendor bills, three-way matching, taxes, and Accounts Payable

Status: **Implemented and Verified**

Started: 2026-07-27

Completed: 2026-07-27

### Scope

- vendor bills and credit notes;
- PO–GRN–Invoice three-way matching;
- quantity/rate/tax tolerance rules;
- controlled mismatch exceptions with permission and audit reason;
- contractor/supplier classification in the AP subledger;
- GST/sales tax, WHT, retention, advances, and deductions after confirmation;
- posting and reversal;
- AP aging, vendor ledger, unmatched receipt, and unpaid-bill reports.

### Typical posting

For stocked material after GRN accrual:

```text
Dr GRNI
Dr Input GST / Sales Tax
    Cr Accounts Payable
    Cr WHT Payable
```

For approved direct consumption:

```text
Dr Direct Project Cost (project required)
Dr Input GST / Sales Tax
    Cr Accounts Payable
    Cr WHT Payable
```

### Acceptance criteria

- A bill cannot consume more accepted quantity than permitted without authorized exception.
- Posting is balanced, idempotent, period-aware, and reversible.
- Vendor subledger reconciles to AP control accounts.
- Cross-company PO, receipt, vendor, account, and project references are impossible.

### Actual implementation record

- Migrations and data:
  - Added company-unique AP matching settings, Vendor Bill headers, lines, receipt allocations, deductions, and a snapshotted supplier/contractor classification.
  - Provisioned one active, zero-tolerance matching setting for each of the six companies. Re-running both foundation seeders remains idempotent.
  - No statutory tax/WHT rate and no Vendor Bill transaction was fabricated; the local Vendor Bill count remains zero.
- Domain model and workflow:
  - Added Vendor Bills and Vendor Credit Notes with Draft, Submitted, Reviewed, Approved, Posted, Rejected, and Reversed states.
  - Bill numbers are reserved atomically by company/type/year. Vendor invoice numbers are unique by company and vendor.
  - Submission validates an issued same-company PO, Vendor, Project/Site, PKR currency, lines, dates, and source evidence, then calculates configured taxes and deductions.
  - Stocked lines are allocated FIFO against accepted, Accounts-handed-over GRN lines under row locks. Cumulative allocation may never exceed available accepted quantity.
  - Review snapshots PO, GRN, rate, tax, and allocation evidence and stores its SHA-256 hash. Rate/tax and direct-service quantity tolerances are company-configurable; exceeding them requires the dedicated override permission and a reason.
  - Maker, match reviewer, and approver are independent actors. Submit, review/override, approve, reject, post, and reverse actions are policy-controlled, transactional, auditable, and idempotent where financial effects occur.
  - Credit Notes reference an eligible posted original bill and source lines, enforce remaining credit capacity, post opposite accounting, and are independently reversible.
- Accounting:
  - Stock invoices clear the GRNI value accrued in Phase 6; direct-service invoices debit the selected same-company cost account with required Project dimensions.
  - Configured recoverable input tax, WHT, retention, vendor advance, other deductions, AP control, and price variance accounts generate balanced Purchase/Credit Note journals through the Phase 4 posting engine.
  - Posting is open-period aware and stores the source journal link. Reversal uses the linked journal reversal and releases reversed receipt-allocation capacity.
  - Supplier/contractor classification is snapshotted on submission for the AP subledger.
- Filament, documents, permissions, and reports:
  - Added tenant-scoped Vendor Bills and AP Matching Settings resources with line/deduction entry, workflow actions, evidence views, and private related Documents.
  - Expanded the reusable related-document manager to support Vendor Bills while retaining same-company validation for Projects, journals, PRs, POs, GRNs, and inventory transactions.
  - Added explicit CRUD and workflow permissions, including review, mismatch override, posting, reversal, and AP-report access, with model policies and tenant isolation.
  - Added Accounts Payable Aging, Vendor Ledger, Unmatched Receipt, and Unpaid Vendor Bill reports plus the tenant AP Reports page.
- Verification:
  - Six focused workflow/report tests cover exact matching, hard quantity bounds, configurable tolerance/authorized exception, GST/WHT/retention posting, reversal, Credit Notes, AP reconciliation, aging, unpaid bills, and unmatched receipts.
  - Three Filament/authorization tests cover tenant isolation, resource creation, workflow/report permissions, and private document company boundaries.
  - The accounting-foundation provisioning test confirms one zero-default AP setting per company.
  - Pint completed successfully. The complete suite passed with **167 tests and 781 assertions**.
  - All Phase 7 migrations are applied; schema/FK/index inspection passed. Permission and accounting seeders passed two-run idempotency checks. Database verification found six settings for six companies, all active at zero tolerance, and zero fabricated Vendor Bills.

### Decisions, deviations, and remaining boundaries

- Accepted stock quantity is a hard upper bound and cannot be overridden. Tolerance applies to rate/tax mismatches and direct-service PO quantity variance, with explicit permission and reason.
- Tax and WHT calculations consume only active, effective, same-company configuration. Live statutory rates remain a business-data gate.
- FIFO receipt allocation is automatic to keep GRN consumption deterministic and concurrency-safe.
- Recoverable inclusive tax works for direct-service lines. It is deliberately rejected for stocked lines because Phase 6 accrues stock/GRNI at the PO gross cost and no inventory-tax revaluation workflow exists yet; configured exclusive recoverable tax is supported for stock.
- Unpaid-bill and AP-aging balances include posted Phase 8 Vendor Bill settlements.
- A Vendor Credit Note reverses AP/GRNI or direct-cost accounting; restoring physical stock remains the Phase 6 Vendor Return workflow.

## Phase 8 — Payments, cash/bank operations, and reconciliation

Status: **Implemented and Verified**

Started: 2026-07-27

Completed: 2026-07-27

### Scope

- payment, receipt, cash transfer, bank transfer, and approved contra workflows;
- allocation against vendor/customer/employee/open items;
- partial allocations, advances, refunds, and reversals;
- cheque/reference metadata if required;
- use existing company bank accounts mapped to GL accounts;
- bank statement import format after Phase 0 confirmation;
- reconciliation sessions, matched/unmatched lines, authorized adjustments, and closing;
- cash/bank books and unreconciled-item reporting.

### Typical vendor payment

```text
Dr Accounts Payable (vendor subledger)
    Cr Company Bank / Cash
```

### Acceptance criteria

- Payment allocations cannot exceed valid open balances except approved advances.
- Bank and cash accounts belong to the transaction company.
- Posted payments are immutable and reversible.
- Reconciliation locks and reopening are explicitly authorized and audited.
- Company bank details remain encrypted/masked under existing sensitive-data permissions.

### Actual implementation record

- Migrations and domain model:
  - Added company-scoped treasury transactions and polymorphic open-item allocations, private bank-statement headers/lines, reconciliation sessions, and partial match evidence.
  - Added explicit Payment, Receipt, and Contra types; settlement, advance, refund, and other purposes; instrument/reference metadata; workflow actors; linked posting/reversal journals; statement checksums; and reconciliation close/reopen evidence.
  - Added company, account, bank, Party/Employment, source-record, status, amount, date, uniqueness, index, and foreign-key validation. Submitted/posted treasury records, imported statements, closed reconciliations, and their evidence are immutable outside controlled actions.
- Treasury workflow and accounting:
  - Added submit, approve, reject, post, and reverse actions with separate maker/approver/poster enforcement, row locks, policies, audit events, and idempotent source journals.
  - Vendor Bill settlement supports partial/full allocation without exceeding the posted open balance and posts Dr Accounts Payable / Cr mapped Cash or Bank. AP aging and unpaid-bill reports deduct posted settlements.
  - Vendor, Employee, and Customer advances post through configured mappings in the supported payment/receipt direction. Refund/other operations require an explicit active same-company offset account.
  - Same-company cash/bank transfers post balanced Contra journals between mapped liquid accounts. Cross-company or mismapped liquid accounts are rejected.
- Bank statements and reconciliation:
  - Added private normalized CSV import with exact seven-column headers, 10 MB/10,000-row limits, strict dates/decimals, debit-or-credit validation, running/closing balance reconciliation, row fingerprints, file SHA-256, and transaction rollback on any invalid row.
  - Added partial match/unmatch against posted same-bank GL lines with direction, date, cumulative-amount, company, and open-session controls.
  - Added authorized bank adjustments to explicit manual-posting accounts, balanced journal evidence, automatic matching, full-match close, GL-to-statement closing-balance proof, locked statements, and reasoned authorized reopening.
- Filament, documents, permissions, and reports:
  - Added tenant-scoped Payments/Receipts/Transfers, Bank Statements, and Bank Reconciliations resources plus workflow actions and private related Documents.
  - Added explicit CRUD, submit/approve/reject/post/reverse, import, match/unmatch, adjust, close/reopen, and Treasury report permissions with tenant-scoped policies.
  - Added Cash Book, Bank Book, Treasury Position, and Unreconciled Bank Items reports and a tenant Treasury & Banking report page.
- Verification:
  - Phase 8 focused and adjacent regression verification passed with **22 tests and 121 assertions**, covering payments, receipts, transfers, maker-checker, idempotency, reversals, Vendor Bill settlement, AP reports, CSV rollback, match/unmatch, bank adjustment, close/reopen, treasury reports, tenant isolation, authorization, private-document boundaries, and Filament fields.
  - Pint, route discovery, migrations, schema/FK/index inspection, and two consecutive seeder runs passed. All required Phase 8 permissions exist once; the local database retains zero treasury transactions, allocations, statements, and reconciliations because no live source data was supplied.
  - The complete PHPUnit suite passed with **177 tests and 849 assertions**. The Laravel compact runner reached the environment's 128 MB limit, so the identical PHPUnit suite was verified directly with a 512 MB PHP memory limit.

### Decisions, deviations, and remaining boundaries

- No live bank export/layout was available. Import therefore uses the recorded normalized CSV contract: `transaction_date,value_date,description,reference,debit,credit,balance`. Bank-specific adapters remain a later migration/hardening task when redacted samples arrive.
- The generic allocation schema is ready for additional open-item adapters, but Phase 8 implements only posted Vendor Bill allocation because customer invoices and salary-payable sources do not exist until Phases 9 and 10. Customer/Employee advances are supported now; their later settlement adapters belong to those source phases.
- Inter-company transfers are deliberately excluded. Phase 8 transfers are same-company liquid-account movements; due-to/due-from balancing and consolidation remain Phase 12.
- Reopening a reconciliation unlocks match maintenance through the reconciliation state while retaining the imported statement file and rows as locked evidence.

## Phase 9 — Sales, running bills, receipts, retention, and project profitability

Status: **Implemented and Verified**

Started: 2026-07-27

Completed: 2026-07-27

### Scope

- customer/service invoices and credit notes across company profiles;
- construction running bills/certificates;
- contract value, variations, certified amount, retention, mobilization recovery, taxes, and WHT;
- customer receipts and allocation;
- AR aging and customer ledger;
- project revenue and direct-cost reporting;
- project budget-versus-actual and profitability derived from posted journal lines;
- trading sales and inventory/COGS posting if required by BMC Trading;
- service-revenue profiles for 7-Orbit IT and 7-Orbit Medical Billing.

### Typical construction bill

Exact tax treatment is confirmed in Phase 0. Conceptually:

```text
Dr Accounts Receivable
Dr Retention Receivable (when separated)
Dr WHT Receivable (when deducted at source)
    Cr Construction Revenue
    Cr Output GST / Sales Tax
```

Mobilization receipt:

```text
Dr Bank
    Cr Customer / Mobilization Advance
```

### Acceptance criteria

- Running-bill totals, retention, and mobilization recovery follow approved contract rules.
- Customer subledger reconciles to AR control accounts.
- Project Cost, Revenue, and Profit are derived and traceable to posted journal lines.
- Reports cannot include unauthorized descendant-company data.

### Actual implementation record

- Migrations and domain model:
  - Added company/type/year Sales sequences, Customer Invoice/Credit Note headers, revenue lines, Running Bill adjustments, commercial snapshot/hash evidence, workflow actors, journal/reversal links, and Customer Invoice sources on immutable Inventory Movements.
  - Added Running Bill, service-invoice, trading-sale, Credit Note, adjustment, workflow-status, and inventory-movement enums with exact four-decimal monetary and quantity fields.
  - Enforced PKR, same-company Customer/Project/Site/Item/UOM/account/tax boundaries, Project-customer ownership, effective tax configuration, draft-only commercial editing, source Credit Note capacity, and posted-record immutability.
- Sales, certification, and posting workflows:
  - Added atomic `RB`, `SI`, `TS`, and `SCN` numbering; submit, independent approve/reject, post, and linked reversal actions; maker-checker enforcement; row locking; activity evidence; idempotent source journals; and Open-period validation.
  - Construction Running Bills require Project/certificate evidence, reconcile work plus explicit variations to revenue lines, track prior certified work and contract value, reject base work above contract value, and calculate configured retention, WHT, and mobilization recovery.
  - Service Invoices require configured Service items. IT and Medical Billing profiles post to their active revenue accounts without company-name branches.
  - Trading Sales reduce stock at moving weighted-average cost, prohibit negative inventory, post Sales plus COGS/Inventory, preserve source movements, support source-bounded Credit Note stock returns, and reverse accounting/inventory atomically.
  - Customer Credit Notes reference a posted original invoice/line, enforce cumulative quantity capacity, and post opposite Revenue, tax, deduction, AR, and COGS effects.
- Customer receipts and subledger:
  - Extended Phase 8 polymorphic allocations to posted Customer Invoices for partial/full Customer receipts.
  - Settlement submission prevents over-allocation and requires full transaction allocation; posting credits mapped Accounts Receivable and debits mapped Cash/Bank.
  - Posted receipts and Credit Notes reduce invoice open amounts, AR aging, and unpaid-invoice results.
- Filament, documents, permissions, and reports:
  - Added tenant-scoped Customer Invoices & Credit Notes with category-aware lines/deductions, workflow actions, approved snapshot evidence, and private related Documents.
  - Extended Treasury allocation entry for Vendor payments and Customer receipts, and extended the reusable document platform with same-company Customer Invoice scope.
  - Added explicit company-scoped CRUD/workflow/report policies and permissions, including submit, approve, reject, post, reverse, and Sales-report access.
  - Added AR Aging, Customer Ledger, Unpaid Customer Invoice, Project Profitability, and Project Budget-vs-Actual services plus a tenant Sales & Project Profitability page. Revenue, direct cost, profit, and actual cost derive only from posted/reversed company journal lines.
- Verification:
  - Nine Phase 9 focused tests passed with **61 assertions**, covering service Sales, maker-checker/idempotent posting, partial Customer receipts, certified Running Bill deductions, Customer Credit Notes, weighted-average trading COGS, negative-stock rollback, reversal, AR/customer reports, Project budget/profit reports, tenant isolation, Filament creation, permissions, and private document boundaries.
  - Twenty-six focused and adjacent regression tests passed with **137 assertions**.
  - Pint, PHP syntax, route discovery, migration status, `git diff --check`, schema/FK/index inspection, and two-run permission-seeder idempotency passed.
  - The complete suite passed with **186 tests and 910 assertions**.
  - All five Phase 9 migrations are applied. Every required permission exists once. The local database retains zero Customer Invoices, lines, adjustments, Sales sequences, and Customer receipt allocations because no approved live source data was supplied.

### Decisions, deviations, and remaining boundaries

- Live statutory Sales Tax/WHT rates and contract-specific deduction percentages remain configuration/business data; implementation uses only active, effective same-company Tax Codes and explicit approved adjustment amounts.
- Running Bill base work is contract-capped while variations remain explicit and separately evidenced. Credit Notes inherit source certification context rather than increasing certified work.
- Project profitability treats Project-dimension Revenue and Expense journal lines as revenue and direct cost. Finer cost-code/category reporting remains available through budget lines and journal dimensions and can be expanded during Phase 12 exports/hardening.
- Trading returns are implemented through source Customer Credit Notes. Credit Note reversal is blocked if later stock activity changed moving-average valuation and requires a controlled inventory adjustment in that exceptional case.
- Inter-company Sales/settlement remains Phase 12. Payroll open-item settlement remains Phase 10. No live invoice, running-bill, receipt, tax, or contract data was fabricated.

## Phase 10 — Payroll posting, employee advances, and project payroll allocation

Status: **Implemented and Verified**

Started: **2026-07-27**

Completed: **2026-07-27**

### Scope

- explicit `Post to Accounts` and reversal for eligible payroll runs;
- salary/allowance/deduction mappings;
- employee loan and advance subledger;
- bank/cash settlement links without duplicating payroll amounts;
- project/cost-center allocation for Project Staff;
- reconciliation between payroll totals, journal posting, and payment records.

### Typical posting

```text
Dr Salaries / Direct Project Labour
    Cr Salary Payable
    Cr WHT Payable
    Cr Employee Loan / Advance
```

Settlement:

```text
Dr Salary Payable
    Cr Bank
    Cr Cash
```

### Acceptance criteria

- A payroll run cannot be double-posted.
- Reversal and reposting preserve links and audit evidence.
- Project allocations sum to the payroll amount being allocated.
- Payroll and GL totals reconcile by company and period.
- Existing locked payroll immutability is preserved.

### Implemented result

- Added company-unique Payroll Account Mappings for Basic Salary, House/Travel, Food, Other Allowance, Absence Deduction, and Other Deduction. Mapping validation requires active same-company posting accounts and enforces Expense versus Liability account types.
- Added Payroll Project Allocations with same-company Project, optional Site and Cost Center, explicit direct-labour Expense account, positive amount validation, and post-submission immutability. Project Staff submission and posting both require allocations to equal Gross Salary less Absence Deduction.
- Added explicit authorized `Post to Accounts` and `Reverse Posting` actions. Posting uses the Payroll voucher sequence, an open Financial Period, row locks, revisioned source idempotency keys, linked Journal evidence, maker/poster separation, and per-Employment dimensions. A reversed run may be re-posted as a new revision while the original Journal-to-reversal chain remains immutable.
- Payroll journals debit component salary expenses or Project direct labour, credit Salary Payable, credit Employee Advances for deductions, and credit a configured Other Deduction liability when applicable.
- Extended Treasury open-item allocation to posted Payroll Entries for Employment counterparties. Salary Payments debit mapped Salary Payable and credit the selected mapped Cash/Bank account; approved allocations reserve the open balance and only posted Payments count as settlement.
- `Mark Paid` now requires a live Payroll Accounts posting and zero posted open salary. Locked Payroll remains immutable; payroll reversal is restricted to posted, unpaid runs and requires settlement reversals first.
- Added tenant-scoped Payroll Account Mapping management, Project allocation entry controls, workflow actions, Payroll/GL/settlement reconciliation, and an Employee Advance subledger.
- HR-6 subsequently added formal company/Employment Loan and Advance origination, versioned recovery schedules, an immutable operational subledger, Treasury-linked disbursement/direct recovery/reversal, and posted principal-waiver Journals against the existing Employee Advances control mapping. Zero-charge Loans and Advances are supported; finance-bearing disbursement remains gated until a dedicated interest-income/control-account design is approved.
- HR-7 now consumes exact finalized Attendance/Leave summaries, approved Bonus/Incentive sources, and active due-as-of Loan/Advance schedules through immutable Payroll components and deterministic source checksums. Posting extends the Phase-10 Journal for Bonus, Incentive, unpaid Leave, late, and half-day mappings; financing recovery is recorded idempotently only when Payroll posts, and reversal restores the installment subledger. Salary Payable, Treasury settlement, Project allocation, posting revision, locked-run, and reconciliation behavior remain intact.
- Added three applied migrations, explicit workflow/configuration/report permissions, and idempotent permission provisioning. No live Payroll Run, Entry, mapping, or Project allocation was fabricated because approved source data was unavailable.
- Verification passed: two Phase 10 focused tests with **18 assertions**, 15 focused/adjacent Payroll, Treasury, and Filament tests with **89 assertions**, and the complete **188-test / 928-assertion** suite. Pint, route discovery, migration status, schema/FK/index inspection, two-run permission seeding, permission uniqueness, and zero-live-payroll-data checks also passed.

## Phase 11 — Fixed assets and depreciation

Status: **Implemented and Verified**

Started: **2026-07-27**

Completed: **2026-07-27**

### Scope

- asset categories mapped to cost, accumulated-depreciation, and depreciation-expense accounts;
- asset register with company, custodian, location/project, acquisition, useful life, method, residual value, and status;
- acquisition from vendor bills or controlled manual capitalization;
- depreciation runs with review, posting, reversal, and locked historical schedules;
- transfers, disposal, gain/loss, and document links;
- asset and GL reconciliation reports.

### Acceptance criteria

- Asset cost and accumulated depreciation reconcile to GL control accounts.
- Depreciation cannot double-run for the same asset/period.
- Disposal is period-aware, balanced, reversible where allowed, and audited.

### Implemented result

- Added company-scoped Asset Categories with controlled mappings for cost, accumulated depreciation, depreciation expense, and disposal gain/loss accounts. Mapping types and same-company posting eligibility are validated, and accounting terms lock once a related asset leaves draft.
- Added the Fixed Asset Register with unique company asset numbers, vendor-bill or manual acquisition source, custodian, Project/Site/Cost Center, location, acquisition and available-for-use dates, residual value, useful life, straight-line method, accumulated depreciation, private notes, documents, workflow actors, and audit history.
- Added maker-checker submit/approve/reject/capitalize actions. Manual capitalization creates and posts an idempotent balanced Journal; vendor-bill capitalization requires an exact posted Vendor Bill line already debited to the category cost account and does not duplicate that acquisition posting.
- Added period-unique depreciation runs and asset-unique schedule rows. Generation snapshots dimensions, accounts, opening/closing accumulated depreciation and carrying values; posting creates balanced per-asset depreciation Journal lines; posted runs and schedules are immutable and reversal restores the snapshotted opening balances only when no later asset activity exists.
- Added audited non-financial transfers and period-aware disposal approval/posting/reversal. Disposal removes cost and accumulated depreciation, records proceeds and calculated gain/loss, links the Journal/reversal evidence, and restores the asset to Active after an authorized reversal.
- Extended Journal Lines with a Fixed Asset dimension, journal reversal copying, and the private document platform with same-company Fixed Asset links.
- Added tenant-scoped Filament resources for categories, assets, depreciation schedules, and disposals; workflow actions; read-only historical schedule details; Asset Register and grouped control-account GL reconciliation reports; policies; and explicit CRUD/workflow/report permissions.
- Added seven applied migrations with foreign keys, company uniqueness, idempotency constraints, and query indexes. No live Asset Category, Fixed Asset, Depreciation Run, Transfer, or Disposal data was fabricated because approved source data is unavailable.
- Verification passed: three focused workflow/report/tenant tests with **32 assertions**, 15 focused-adjacent accounting/document tests with **77 assertions**, and the complete **191-test / 960-assertion** suite. Pint, route discovery, migration status, schema/FK/index inspection, permission two-run idempotency, permission uniqueness, and zero-live-asset-data checks also passed. The full suite requires a PHP CLI memory limit above the local 128 MB default; `php -d memory_limit=512M vendor/bin/phpunit` passed.

## Phase 12 — Inter-company accounting, consolidation, closing, migration, and hardening

Status: **Implemented and Verified**

### Scope

- paired due-from/due-to inter-company transactions with independent company approvals;
- mismatch and out-of-balance controls;
- authorized group consolidation using reporting mappings;
- inter-company elimination design where required;
- year-end closing into retained earnings;
- controlled reopen rules;
- production data migration with dry-run, validation, reconciliation, and rollback strategy;
- exports and management reports;
- performance/index review using realistic data volumes;
- end-to-end security, tenant-isolation, concurrency, audit, backup, and recovery testing;
- operational runbooks only if explicitly requested.

### Acceptance criteria

- Inter-company balances reconcile across counterparties.
- Consolidated Trial Balance, Balance Sheet, and P&L reconcile to company books.
- Closing entries are reproducible and reversible only through explicit authority.
- Migrated opening balances reconcile to approved source reports.
- All phases are marked **Implemented and Verified**, with no hidden planned scope represented as implemented.

Implementation completed:
- Date: 2026-07-27
- Implemented by: Codex
- Status changed from: Planned → In Progress
- Status changed to: Implemented and Verified

Actual implementation:
- Migrations/tables: Added `intercompany_transactions`, `year_end_closings`, `opening_balance_migrations`, and immutable source rows; related-company journal dimensions; private migration source paths; foreign keys, company/idempotency uniqueness, pair/status/date indexes, and recovery-safe evidence links.
- Models/enums: Added explicit inter-company direction/status, year-close status, migration status, guarded workflow models, relationships, and reusable factories.
- Actions/services: Added independent two-company submission/approval/rejection, atomic paired posting and reversal; reproducible checksum-based year-close preparation/approval/posting and reasoned reopen/reversal; strict seven-column CSV dry-run, source resolution, independent validation/import, exact reconciliation, and rollback.
- Filament resources/pages/relation managers: Added tenant-scoped Inter-company, Year-end Closing, and Opening Migration resources with workflow-only mutation; a Group Consolidation page; reconciliation/integrity indicators; and streamed consolidated Trial Balance export.
- Policies/permissions: Added origin/counterparty-aware tenant policies, full-scope consolidation authorization, maker/checker/third-actor separation, explicit workflow/report/export permissions, and no manual edit path for closing or migration evidence.
- Seeders/provisioning: Extended the idempotent permission catalog; existing due-from, due-to, retained-earnings, current-result mappings and `IC` voucher sequences are reused without company-name branches.
- Documents/audit: Private migration source storage retains filename/path/checksum; all inter-company, closing, import, failure, posting, reversal, and rollback transitions retain actor/time/reason Activitylog evidence.
- Reports/exports: Added reporting-mapping-based group Trial Balance, Balance Sheet, and P&L; internal control eliminations; counterparty reconciliation; CSV export; accounting integrity audit; and deterministic recovery manifests for pre/post backup-restore comparison.

Verification:
- Focused tests: 8 Phase 12 workflow/report/migration/authorization tests passed with 39 assertions.
- Broader tests: Complete 199-test / 999-assertion suite passed using `php -d memory_limit=512M vendor/bin/phpunit`.
- Formatting/static checks: Pint passed; 224 application routes discovered; all six Phase 12 migrations applied; schema/FK/unique/index inspection passed; indexed consolidation query plan confirmed; permission seeder passed twice with 585 unique permissions and zero duplicates.
- Manual verification: Local Phase 12 transaction, closing, and migration tables remain empty; no live accounting data was fabricated.

Decisions and deviations:
- Decisions resolved: Every company remains transaction-capable; paired transactions require different company-side approvers; the posting actor needs access to both books; consolidation is an authorized reporting scope, not a synthetic company ledger.
- Differences from planned design: No operational runbook was created because the scope permits one only when explicitly requested.
- Reason: The user requested implementation, not a deployment/runbook artifact.
- Data migration/backfill: No production source was available. The normalized private CSV dry-run/import/reconciliation/rollback path is implemented and tested; live import remains data/operator initiated.
- Known limitations: Foreign currency remains deliberately outside the approved PKR-only scope. Actual infrastructure backup scheduling and restore execution remain deployment responsibilities; the application now supplies integrity checks and deterministic recovery manifests to verify restored books.
- Follow-up explicitly assigned to later phase: None. Future live statutory configuration and source-data onboarding are operational inputs, not hidden implementation phases.

## Decision Register

Update decisions in place. Do not delete historical decisions; mark superseded entries and link replacements.

| ID | Decision | Status | Blocks | Current direction / evidence |
| --- | --- | --- | --- | --- |
| D-001 | 7-Orbit hierarchy | Confirmed 2026-07-25 | None | 7-Orbit is parent; 7-Orbit IT and 7-Orbit Medical Billing are direct children |
| D-002 | YM Construction parent | Confirmed default 2026-07-25 | None | Independent root until the business explicitly changes it |
| D-003 | BMC and BMC Trading relationship | Confirmed default 2026-07-25 | None | Both are independent roots until explicitly changed |
| D-004 | 7-Orbit parent operational vs holding-only | Confirmed 2026-07-25 | None | 7-Orbit is transaction-capable |
| D-005 | Fiscal year and period locks | Confirmed default 2026-07-25 | None | July–June, monthly periods, controlled audited reopen |
| D-006 | Voucher types and numbering | Confirmed default 2026-07-25 | None | Company/type/fiscal-year sequences with approved prefixes |
| D-007 | Monetary/quantity precision | Confirmed default 2026-07-25 | None | `decimal(19,4)`, PKR display at 2 decimals |
| D-008 | GRN accounting timing | Confirmed default 2026-07-25 | None | Accepted inventory accrues against GRNI |
| D-009 | Inventory valuation and negative stock | Confirmed default 2026-07-25 | None | Moving weighted average; negative stock prohibited |
| D-010 | Tax, WHT, GST, retention, mobilization rules | Architecture confirmed; live configuration deferred 2026-07-25 | Live taxable posting only | Effective-dated configurable codes/contract terms; no invented active rates |
| D-011 | Revenue recognition per company | Confirmed default 2026-07-25 | None | Certified running bills, approved service invoices, and trading sale plus COGS |
| D-012 | Foreign currency | Confirmed default 2026-07-25 | None | PKR-only initially |
| D-013 | Approval limits and maker/checker separation | Architecture confirmed 2026-07-25 | Live amount limits only | Maker-checker required; override unassigned by default; limits configurable |
| D-014 | Opening balances and cutoff date | Tooling implemented 2026-07-27 | Live source/cutoff only | New/local books remain zero; normalized dry-run/import/reconciliation/rollback tooling is implemented, while actual source data and cutoff remain business inputs |
| D-015 | BMC operating profile | Confirmed default 2026-07-25 | None | Generic transaction-capable, configuration-led profile |

## Phase Completion Record Template

Copy this subsection beneath the completed phase and fill every applicable field:

```text
Implementation completed:
- Date:
- Implemented by:
- Status changed from:
- Status changed to:

Actual implementation:
- Migrations/tables:
- Models/enums:
- Actions/services:
- Filament resources/pages/relation managers:
- Policies/permissions:
- Seeders/provisioning:
- Documents/audit:
- Reports/exports:

Verification:
- Focused tests:
- Broader tests:
- Formatting/static checks:
- Manual verification:

Decisions and deviations:
- Decisions resolved:
- Differences from planned design:
- Reason:
- Data migration/backfill:
- Known limitations:
- Follow-up explicitly assigned to later phase:
```

## Progress Ledger

Append entries; do not rewrite history except to correct a factual error with a note.

| Date | Phase | Status change | Summary | Verification / blocker |
| --- | --- | --- | --- | --- |
| 2026-07-25 | Plan | Created | Recorded confirmed 7-Orbit hierarchy and established the complete phased implementation/handoff plan | Documentation-only; implementation not started |
| 2026-07-25 | Phase 0 | Planned → In Progress | Audited repository/database evidence, recorded missing source artifacts, and proposed defaults for business approval | Waiting for company, accounting, tax, approval, migration, and sample-document decisions |
| 2026-07-25 | Phase 0 | In Progress → Blocked | Completed the available evidence audit; no safe accounting schema or posting implementation can continue from repository evidence alone | Requires the business inputs listed in Phase 0 |
| 2026-07-25 | Phase 0 | Blocked → Implemented and Verified | Business owner approved the recommended configuration-first defaults; synthetic source layouts, postings, permission matrix, and deferred live-data gates were recorded | Documentation/diff validation passed; no application code changed |
| 2026-07-25 | Phase 1 | Planned → In Progress | Began idempotent company-topology and module-governance implementation after verifying the existing YM Construction record and empty company-module state | Focused implementation and tests in progress |
| 2026-07-25 | Phase 1 | In Progress → Implemented and Verified | Provisioned six deterministic company records, hierarchy, module governance, and existing foundation defaults without granting memberships | 15 focused/relevant tests passed with 75 assertions; Pint, diff check, two-run seeder idempotency, and database queries passed |
| 2026-07-25 | Phase 2 | Planned → In Progress | Began shared operational master-data implementation after the complete post-Phase-1 suite passed | Full suite passed: 94 tests, 455 assertions |
| 2026-07-25 | Phase 2 | In Progress → Implemented and Verified | Added company-scoped parties, Projects/sites, cost centers, items/UOM/categories, effective tax codes, immutable approved budget versions, permissions, Filament resources, audit, and Project documents | 29 focused/adjacent tests and the 108-test full suite passed; Pint, syntax, diff, migration, schema, permission, and zero-active-tax verification passed |
| 2026-07-25 | Phase 3 | Planned → In Progress | Began company Chart of Accounts, system mappings, financial-year/period, and voucher-sequence foundations after verifying Phase 2 | Pre-phase full suite passed: 108 tests, 507 assertions |
| 2026-07-25 | Phase 3 | In Progress → Implemented and Verified | Added controlled COA templates, company snapshots/profile activation, system and bank mappings, July–June periods, voucher sequences, tenant resources, permissions, workflows, and audit evidence | 7 focused tests/21 assertions and full 115-test/528-assertion suite passed; Pint, routes, migrations, schema checks, two-run seed, and data counts passed |
| 2026-07-26 | Phase 4 | Planned → In Progress | Began immutable double-entry journals, maker-checker posting, reversals, opening balances, and baseline financial reports after verifying Phase 3 | Pre-phase full suite passed: 115 tests, 528 assertions |
| 2026-07-26 | Phase 4 | In Progress → Implemented and Verified | Added immutable company journals/lines, maker-checker workflow, open-period posting, atomic numbering, linked reversals, controlled opening balances, financial reports, tenant UI, permissions, document links, and audit evidence | 14 focused tests/49 assertions and full 129-test/577-assertion suite passed; Pint, routes, migrations, schema/permission checks, zero invented balances, and diff validation passed |
| 2026-07-26 | Phase 5 | Planned → In Progress | Began company-scoped purchase requisitions and purchase orders with budget control, configurable maker-checker approval paths, immutable approved snapshots, partial ordering, cancellation, documents, and concurrency-safe quantities | Pre-phase full suite passed: 129 tests, 577 assertions |
| 2026-07-26 | Phase 5 | In Progress → Implemented and Verified | Added company-scoped PR/PO lines, exact budget commitments, atomic numbering, snapshotted sequential approvals, immutable approved POs, partial/concurrent ordering, issue/cancellation quantity controls, tenant UI, permissions, and private evidence links without premature GL posting | 15 focused tests/72 assertions and full 144-test/649-assertion suite passed; Pint, diff, routes, migrations, two-run permission seed, schema/index/FK checks, and zero fabricated transactions passed |
| 2026-07-27 | Phase 6 | Planned → In Progress | Began PO-linked Goods Receipts, independent inspection, Accounts handover with GRNI accrual, moving-weighted-average stock, transfers, project issues/returns, adjustments, vendor returns, and negative-stock controls | Pre-phase full suite passed: 144 tests, 649 assertions |
| 2026-07-27 | Phase 6 | In Progress → Implemented and Verified | Added three-stage GRN/inspection/handover, accepted-stock GRNI accrual, immutable weighted-average stock ledger, transfers, Project issues/returns, adjustments, vendor/rejected returns with PO reopening, tenant UI, permissions, documents, and read-only balance/ledger views | 12 focused tests/75 assertions and full 156-test/724-assertion suite passed; Pint, diff, routes, six migrations, two-run permission seed, schema/index/FK and zero-fabricated-data checks passed |
| 2026-07-27 | Phase 7 | Planned → In Progress | Began Vendor Bills and credit notes, PO–GRN–invoice matching with zero-default configurable tolerances, controlled mismatch exceptions, configurable taxes/deductions, AP posting/reversal links, vendor subledger, and reconciliation reports | Pre-phase verification and implementation in progress |
| 2026-07-27 | Phase 7 | In Progress → Implemented and Verified | Added tenant-scoped Vendor Bills/Credit Notes, FIFO GRN allocation, independent matching and approval, configurable tolerance exceptions, effective taxes/deductions, balanced AP posting/reversal, documents, policies, and AP reports | 14 focused/relevant tests passed with 70 assertions and the full suite passed with 167 tests/781 assertions; Pint, migrations, schema/FK/index checks, routes, two-run seed idempotency, six-company zero defaults, permission uniqueness, and zero-fabricated-bill checks passed |
| 2026-07-27 | Phase 8 | Planned → In Progress | Began company-scoped payment, receipt, cash/bank transfer, advance/refund, open-item allocation, bank-statement import, reconciliation, adjustment, closing/reopening, and treasury reporting workflows | Pre-phase full suite passed with 167 tests and 781 assertions; implementation in progress |
| 2026-07-27 | Phase 8 | In Progress → Implemented and Verified | Added maker-checker treasury transactions, mapped cash/bank and Vendor Bill settlement accounting, normalized private statement import, partial reconciliation matching, authorized adjustments, close/reopen evidence, tenant UI/documents, permissions, and treasury reports | 22 focused/adjacent tests passed with 121 assertions and full suite passed with 177 tests/849 assertions; Pint, routes, six migrations, schema/FK/index checks, two-run seed idempotency, permission uniqueness, and zero fabricated treasury/bank data passed |
| 2026-07-27 | Phase 9 | Planned → In Progress | Began shared customer invoices/credit notes, certified construction running bills, service invoices, trading sale/COGS, customer receipt allocation, AR/customer ledgers, and Project profitability reporting | Pre-phase full suite passed with 177 tests and 849 assertions; implementation in progress |
| 2026-07-27 | Phase 9 | In Progress → Implemented and Verified | Added tenant-scoped Customer Invoices/Credit Notes, certified Running Bills, service and trading Sales, weighted-average COGS, Customer receipt allocation, private evidence, permissions, and AR/Project reports | 9 focused tests/61 assertions, 26 focused-adjacent tests/137 assertions, and full 186-test/910-assertion suite passed; Pint, routes, five migrations, schema/FK/index checks, two-run permission seed, permission uniqueness, and zero-fabricated-Sales-data checks passed |
| 2026-07-27 | Phase 10 | Planned → In Progress | Began explicit payroll posting/reversal, salary and deduction account mapping, Employee Advance recovery, Project/Cost Center payroll allocation, and Treasury-linked salary settlement | Previous verified full suite: 186 tests and 910 assertions; current local database contains zero Payroll Runs and Payroll Entries |
| 2026-07-27 | Phase 10 | In Progress → Implemented and Verified | Added configuration-first payroll mappings, exact Project Staff allocations, revisioned idempotent Payroll journals/reversals/reposting, Employee Advance recovery, Payroll Entry Treasury settlement, paid-state enforcement, reconciliation/subledger reports, tenant UI, and permissions | 2 focused tests/18 assertions, 15 focused-adjacent tests/89 assertions, and full 188-test/928-assertion suite passed; Pint, routes, three migrations, schema/FK/index checks, two-run permission seed, permission uniqueness, and zero-fabricated-payroll-data checks passed |
| 2026-07-28 | Cross-plan HR-6 | Finance Phase 10 extension recorded | Formalized Loan/Advance origination, schedules and immutable subledger; reused Treasury and Employee Advances GL mapping for zero-charge disbursement/recovery/reversal and posted principal waiver; exposed due-as-of source for HR-7 | HR-6 focused 5 tests/32 assertions and broader affected 44 tests/243 assertions passed; finance-bearing disbursement remains blocked by the unapproved interest-income/control-account design |
| 2026-07-29 | Cross-plan HR-7 | Finance Phase 10 extension recorded | Added immutable Payroll source components, finalized Attendance/Leave and approved Bonus/Incentive calculations, due Loan/Advance recovery on posting, reversal-safe schedule restoration, extended account mappings, and Salary/Payroll/Project report foundations | HR-7 and existing Payroll accounting tests passed 11 tests/64 assertions; broader HR/Filament regression 47 tests/249 assertions; fresh seed, schema, routes, permission uniqueness, and zero-fabricated-data checks passed |
| 2026-07-29 | Cross-plan HR-12 | Finance Phase 10 reconciliation hardening recorded | Corrected Payroll reconciliation to compare calculated expense basis with net expense-account movement so Attendance-deduction credits reconcile correctly; posting entries and mappings are unchanged | Pilot Attendance → Payroll components → balanced GL → Treasury settlement passed, and the full suite passed 263 tests/1,429 assertions |
| 2026-07-27 | Phase 11 | Planned → In Progress | Began company-scoped asset categories/register, vendor/manual capitalization, straight-line depreciation, transfers, disposal, linked accounting/reversals, private evidence, and reconciliation reporting | Previous verified full suite: 188 tests and 928 assertions; local Journal and Vendor Bill tables are empty |
| 2026-07-27 | Phase 11 | In Progress → Implemented and Verified | Added controlled asset categories/register, manual and posted-Vendor-Bill capitalization, immutable straight-line depreciation schedules, transfers, disposal/reversal, Fixed Asset journal dimensions/documents, tenant UI, permissions, and Asset/GL reconciliation | 3 focused tests/32 assertions, 15 focused-adjacent tests/77 assertions, and full 191-test/960-assertion suite passed; Pint, routes, seven migrations, schema/FK/index checks, two-run permission idempotency, and zero-live-asset-data checks passed |
| 2026-07-27 | Phase 12 | Planned → In Progress | Began paired inter-company approvals/posting, mapped consolidation/eliminations, controlled year close/reopen, normalized production opening migration, exports, integrity/recovery, and tenant hardening | Pre-phase full suite passed: 191 tests and 960 assertions; local inter-company, closing, and migration tables did not yet exist |
| 2026-07-27 | Phase 12 | In Progress → Implemented and Verified | Added atomic paired inter-company books/reversals, authorized mapped consolidation and eliminations, reconciliation/export/integrity/recovery reports, checksum-reproducible year close/reopen, and independently validated private opening migration with rollback | 8 focused tests/39 assertions and full 199-test/999-assertion suite passed; Pint, 224 routes, six migrations, FK/unique/index/query-plan checks, two-run permission idempotency, 585 unique permissions, and zero-fabricated-Phase-12-data checks passed |
| 2026-07-29 | Cross-plan HR-9 | Fixed Asset custody extension started | Began employee issuance, acknowledgement, transfer, return, loss/damage and separation-clearance evidence around the existing Fixed Asset register without changing capitalization, depreciation, disposal, or GL behavior | Finance Phase 11 and HR-8 are Implemented and Verified; baseline contains zero Fixed Assets, Asset Transfers, Employments, Separations, and Financings |
| 2026-07-29 | Cross-plan HR-9 | Fixed Asset custody extension implemented and verified | Added one-live-custodian Employee issuance, acknowledgement, immutable transfer/return/damage-loss evidence, and separation clearance around the existing capital-asset register | Finance Phase 11 capitalization/depreciation/disposal/GL behavior remains unchanged; HR-9 recovery is recommendation-only with zero Journal/Treasury posting, focused workflow tests and Fixed Asset regression passed |
| 2026-07-29 | Cross-plan HR-10 | Final Settlement accounting extension started | Began source-backed Final Settlement posting/reversal and employee payment/receipt allocation through the existing immutable Journal and Treasury engines | HR-6 through HR-9 and Finance Phases 4, 8, and 10 are Implemented and Verified; baseline contains zero Final Settlement, Journal, and Treasury transactions |
| 2026-07-29 | Cross-plan HR-10 | Final Settlement accounting extension implemented and verified | Added source-backed earning/recovery lines, dedicated mappings, balanced open-period Payroll vouchers, Employee Advances/Salary Payable balancing, financing recovery/reversal, and bounded Treasury Payment/Receipt settlement | HR-10 focused 4 tests/40 assertions and affected Finance/HR regression 35 tests/228 assertions passed; fresh/repeated seed, Pint, routes, schema/index/FK, permission uniqueness, and zero fabricated Final Settlement/Journal/Treasury data verified |

## Whole-plan completion rule

The overall status at the top may change to **Implemented and Verified** only when:

- all numbered phases have that status;
- every phase contains an actual completion record;
- the Decision Register has no open item that affects delivered behavior;
- `docs/PROJECT_STATE.md` matches the repository and database;
- final cross-company, financial-reconciliation, authorization, audit, and regression verification has passed.
