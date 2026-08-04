# YM Construction Management System — Project State

Last updated: 2026-08-01

## Purpose of this document

This is the living source of truth for the current implementation, agreed architectural direction, development standards, pending decisions, and next work.

Future developers and AI agents must:

1. Read this file and the root `AGENTS.md` before changing the application.
2. Confirm actual code and database state before treating a planned item as implemented.
3. Update this file whenever a module, workflow, architectural decision, or project-wide convention materially changes.
4. Keep **Implemented**, **Planned**, and **Needs Business Confirmation** clearly separated.
5. Read and follow `docs/FINANCE_PROJECTS_OPERATIONS_IMPLEMENTATION_PLAN.md` before working on Accounts, Chart of Accounts, Projects, Sales, Purchases, Inventory, Banking, Payroll accounting-posting, Assets, or consolidated reporting.
6. Read and follow `docs/HR_WORKFORCE_IMPLEMENTATION_PLAN.md` before working on Departments, Employees, Employments, employee documents, Attendance, fingerprint/biometric attendance-machine integration, Leave, Employee Loans/Advances, Payroll calculations, performance, warnings, promotions/transfers, separation, employee asset custody, clearance, Final Settlement, or HR reporting.

## Product direction

The application manages four independent legal companies in one Laravel and Filament system:

```text
BMC Construction
YMC Construction
7 Orbit
7 Orbit Medical Billing
```

None is a parent or subsidiary inside this system. They have separate memberships, operational records, modules, roles, and reports; collective reporting is an explicit Super Admin scope, never a hierarchy or synthetic tenant.

Most company operations are expected to be similar. Shared functionality must use one implementation with company-specific configuration or workflow variants instead of duplicated modules or company-specific code branches.

Development is intentionally workflow-led and incremental:

1. The business provides the real workflow for a module.
2. The workflow is clarified with the relevant operational user.
3. The smallest complete workflow is designed and implemented.
4. Permissions, company isolation, audit logging, documents, and tests are included in the same implementation.
5. The next module or workflow is then discussed.

HR foundations are implemented. The remaining HR and workforce scope is governed by `docs/HR_WORKFORCE_IMPLEMENTATION_PLAN.md`, which includes a vendor-neutral attendance-ingestion foundation so the eventual fingerprint-machine connector can be added after its make, model, protocol, and deployment details are available. Accounts, Projects, Procurement, Inventory, Banking, Sales, Assets, and related reporting remain governed by `docs/FINANCE_PROJECTS_OPERATIONS_IMPLEMENTATION_PLAN.md`.

HR plan Phases HR-0 through HR-12, along with Employee Master, Payroll, Attendance, ZKTeco MB460+ ADMS push connector (HR-5), and PDF enhancements (photograph upload, cost center, project, payment method, IBAN hashing, 5 expanded allowances, financing sub-categories, late attendance status, statutory leave types seeder, performance chart widgets, and A4 Salary Slip PDF generation), are implemented and verified. HR-0 approved configuration-first architecture, safe synthetic evidence, approval boundaries, and production configuration gates. HR-1 delivered Department hierarchy, atomic company Employee codes, Employment lifecycle/type/location fields, Work Locations, and immutable Employment change evidence. HR-2 delivered controlled/configurable private HR document types, legacy mapping, separate identity/medical access, and required-document compliance status. HR-3 delivered effective-dated Attendance and Leave foundations, maker-checker evidence, immutable monthly Payroll inputs, and tenant workflow UI. HR-4 delivered the vendor-neutral Attendance Device registry, effective device-user mappings, immutable ingestion evidence, deterministic deduplication, quarantine/replay, normalized private CSV import, and an adapter boundary. HR-5 delivered the ZKTeco MB460+ ADMS HTTP Push Protocol connector (`/iclock/cdata`, `/iclock/getrequest`, `/iclock/devicecmd`), `ProcessZkTecoAdmsPushAction`, `ZkTecoAdmsController`, CSRF route exceptions, transport enum `zkteco_adms`, deduplication, quarantine for unmapped device users, and device health tracking. HR-6 delivered Employee Loans and Advances with maker-checker, schedules, immutable subledger, Treasury/GL integration, recovery/rescheduling/waiver/reversal, documents, and Payroll/Final-Settlement balance boundaries. HR-7 delivered effective Payroll calculation rules, approved Bonus/Incentive sources, finalized Attendance/Leave deductions, due Loan/Advance recovery, immutable source components, deterministic regeneration, extended GL/reversal integration, and company Payroll report foundations. HR-8 delivered configurable appraisals and warnings, effective Promotion/Transfer, Resignation/Termination, immutable Employment evidence, private attachments, and explicit access review. HR-9 delivered Fixed Asset custody issuance, acknowledgement, immutable movement/return/exception evidence, and approved-separation departmental clearance. HR-10 delivered source-reconciled Final Settlement, independent review/approval/posting, reversible GL and financing recovery, bounded Treasury payment/receipt, printable letter, documents, and tenant reconciliation reporting. HR-11 delivered the complete company report catalog/dashboard, private audited CSV/XLSX exports, sensitive-section gates, indexed aggregates, and authorized hierarchy-only Group HR reporting. HR-12 delivered controlled migration/rollback, private source reconciliation, recovery/readiness evidence, security/performance hardening, and pilot Attendance-to-Treasury UAT.

The user confirmed on 2026-07-28 that the application is in active development with no production deployment or production HR data. Until the first production baseline, unreleased migrations may be revised and the local database may be reset/reseeded when useful. This does not relax company isolation, authorization, private-document handling, immutable evidence, audit, accounting integrity, or test requirements. A production-safe migration baseline and rollout controls must be established before first deployment.

Do not invent accounting or statutory values. Phase 0 approved configuration-first defaults and synthetic transaction evidence now govern initial development; later official legal data, statutory rates, opening balances, and redacted operational samples must supersede placeholders/configuration before affected production use. Shared foundations such as companies, permissions, company bank accounts, documents, and audit history must be reused.

## Technology baseline

Current application information:

- PHP 8.4
- Laravel 13
- Filament 5
- Livewire 4
- PHPUnit 12
- Laravel Boost 2
- Laravel Pint 1
- SQLite in the current local environment

Important installed packages:

- `bezhansalleh/filament-shield` for role and permission management
- `spatie/laravel-permission` through Filament Shield
- `spatie/laravel-activitylog` for model activity history
- `spatie/laravel-settings` for application settings
- `jeffgreco13/filament-breezy` for profile, passkeys, sessions, and two-factor authentication features

No new dependency may be installed without explicit user approval.

## Implemented now

The original multi-company foundation, document platform, HR foundation, joining-letter workflow, compensation history, and payroll foundation are implemented. Finance plan Phases 1–10—organization provisioning, shared operational master data, company Chart of Accounts, the immutable double-entry ledger, controlled procurement, Goods Receipts/inspection, site inventory, Vendor Bills, Accounts Payable, treasury/bank reconciliation, Customer Invoices/Running Bills, customer receipts, trading COGS, Sales/Project profitability reporting, and Payroll accounting/settlement—are also implemented.

### Multi-company foundation

- Four deterministic independent company records are provisioned idempotently: BMC Construction, YMC Construction, 7 Orbit, and 7 Orbit Medical Billing
- Each provisioned company has its approved card logo at `public/images/company-logos`
- Companies with optional parent companies and soft deletion
- Circular company-hierarchy prevention
- User-to-company membership with active/inactive access
- Company access is direct active membership only; legacy descendant-access metadata is not used
- Filament company tenancy, searchable company switcher, and company registration
- Super-admin access to every active company
- Shared module catalog for Documents, HR, Accounts, and Projects
- Per-company module state (`enabled` or `disabled`), workflow variant, and settings
- Each company starts with Documents, HR, Accounts, and Projects enabled independently
- Provisioning does not create memberships or implicitly grant access to another company
- Company bank-account management
- Encrypted account numbers and IBANs with masked display unless the user has the sensitive-data permission
- One default payroll bank account per company
- Company membership management from the company resource
- Activity logging for the new foundation records and membership changes

### Shared operational master data and Projects foundation

- Company-scoped Parties use one record with one or several Customer, Vendor, Contractor, and Consultant roles
- Party codes are unique within a company; legal name, tax number, contact details, address, payment terms, and active state are configurable
- Repeatable Party Contacts support one primary contact and same-company enforcement
- Company-scoped Cost Centers, Units of Measure, Item Categories, and Items/Services are implemented
- Item codes are unique within a company
- Items reference a same-company category and unit of measure, with an optional active same-company default tax code
- Materials may track inventory; Services are prohibited from tracking inventory quantities
- Company-scoped Tax Codes support type, `decimal(9,4)` rate, inclusive/exclusive calculation method, effective dates, recoverability, notes, and active state
- Tax-code versions with the same company/code cannot overlap effective dates
- No tax code or statutory rate is active or provisioned by default
- Company-scoped Projects include code, name, customer client, optional consultant, location, planned/actual dates, contract value, PKR currency, contract-specific retention/mobilization terms, and status
- Running Bills, actual Cost, Revenue, and Profit are not editable Project columns; Phase 9 derives them from Customer Invoice and posted Journal records
- Project Sites/Stores belong to one same-company Project and may reference a same-company Cost Center
- Project Budgets are versioned per Project with cost-code lines, optional Cost Center and Item Category dimensions, and `decimal(19,4)` amounts
- Budget approval is transactional, requires a different approver from the preparer, snapshots the exact line total, and supersedes the prior approved version
- Approved and superseded budget headers and lines are immutable
- Parties, Projects, Sites, Cost Centers, UOMs, Item Categories, Items, Tax Codes, and Project Budgets have tenant-scoped Filament resources and explicit permissions
- Existing private Documents can relate to same-company Projects; cross-company Project links are rejected
- Company-owned HR document-type configuration covers CNIC, Educational Document, Experience Certificate, Appointment Letter, Medical Certificate, and Police Verification with applicability, sensitivity, issue/expiry, verification, approval, required/optional, and active metadata
- Employee/Employment document uploads use controlled type names, private immutable versions, type filtering, legacy-safe nullable mapping, and dedicated identity/medical permissions
- Required HR document configurations expose missing compliance items without fabricating documents or silently making any default type mandatory
- Operational master and Project changes use Activitylog; budget approval records actor, version, total, line count, and superseded budget IDs

### Accounting and Chart of Accounts foundation

- A controlled global 101-account template covers Assets, Liabilities, Equity, Revenue, Expenses, construction direct costs, and trading COGS
- Each company owns an immutable-at-provisioning-time account snapshot; later template edits do not silently overwrite company account history or customization
- Explicit configuration profiles activate Construction, IT Services, Medical Billing, Trading, or Generic accounts without company-name branches in domain actions
- Account code and system-key uniqueness is enforced per company
- Account trees reject cycles, cross-company parents, and incompatible parent/child account types
- Control and parent accounts prohibit manual posting; company leaf accounts explicitly control whether manual posting is allowed
- Twenty-one required system mappings cover cash, banks, receivables/payables, advances, taxes/WHT, payroll, retention, customer advances, GRNI, inventory/WIP, related companies, and year-result accounts
- Every Company Bank Account automatically receives a company-owned bank GL child and explicit mapping; encrypted account identifiers are not copied into the account name
- Each company has PKR, Asia/Karachi, July–June, precision 4/display 2, moving-weighted-average, and no-negative-inventory settings by default
- July–June financial years contain twelve non-overlapping monthly periods with Open, Closed, and Locked states
- Period close, lock, and reason-required reopen actions preserve actor/time evidence and activity records
- Fourteen company/type/financial-year voucher sequences use the approved prefixes and atomic reservation
- Tenant-scoped Filament resources manage company accounts, settings, mappings, financial years/periods, and voucher sequences; global account templates remain system-controlled
- All six companies are provisioned idempotently with 101 account snapshots, 12 periods, and 14 voucher sequences; no balances or statutory tax rates were invented

### Double-entry ledger, vouchers, and opening balances

- Company-owned Journal Entries and immutable Journal Lines are the only implemented financial source of truth
- Journal workflow states are Draft, Submitted, Approved, Posted, Rejected, and Reversed
- Journal, Payment, Receipt, Contra, Debit Note, and Credit Note voucher shells are available for controlled manual preparation; Purchase, Inventory, Treasury, Sales, Payroll, Depreciation, Fixed Asset, and Inter-company records generate linked entries
- Every line contains exactly one positive debit or credit using `decimal(19,4)` precision
- Posting requires at least two lines, equal positive debit/credit totals, an active leaf account, matching PKR company currency, and an Open period containing the transaction date
- Manual journals cannot use accounts that prohibit manual posting; system/opening/reversal sources may use configured control accounts with required dimensions
- Party dimensions are required for configured receivable/payable/advance/retention control accounts; construction direct-cost accounts require a Project
- Party, Project, Project Site, Cost Center, Employment, Company Bank Account, Fixed Asset, and related-company dimensions reject invalid cross-company references
- Account code/name snapshots are stored on every Journal Line
- Journal source links are polymorphic, explicit, and same-company validated
- The preparer cannot approve or post the same Journal Entry
- Submission, approval, rejection, posting, and reversal use policies, transactions, row locks, actor/timestamp evidence, and Activitylog events
- Company/type/fiscal-year voucher numbers are assigned atomically at posting; company idempotency keys and locked stale-request handling prevent double posting
- Posted Journals, their lines, and structurally used Accounts are immutable
- Reversal creates one linked opposite Journal Entry in an Open period; repeated reversal requests return the existing reversal
- Controlled Opening Balance batches are editable only in Draft, require independent validation, and post once as a normal `OB` Journal instead of creating a parallel balance store
- The local production-opening and migration tables are empty because no approved cutoff Trial Balance was supplied; strict private CSV dry-run/import/reconciliation/rollback tooling is available
- General Ledger, Trial Balance, Balance Sheet, and Profit & Loss report services derive only from Posted/Reversed journal history
- A tenant Financial Statements page exposes current-year Trial Balance, P&L, and Balance Sheet
- Journal documents reuse the private document platform and reject cross-company links
- Tenant-scoped Vouchers/Journals and Opening Balances resources expose line entry, workflow actions, snapshots, and evidence

### Purchase requisitions and purchase orders

- Company-scoped Purchase Requisitions capture Project/Site, required date, reason, material/service lines, estimated quantities/rates, and exact four-decimal totals
- Requisition submission validates same-company dimensions, approved Project Budget lines, cumulative current-document demand, and existing commitments
- PR and PO numbers are reserved atomically by company, document type, and calendar year
- Amount-based procurement approval rules are configurable per company and snapshot sequential approval steps into each submitted document
- A safe Finance approval step is used when no live amount rule matches
- The preparer cannot approve their own PR or PO; submit, approve, reject, resubmit, cancel, and issue actions retain actor/time/reason evidence
- Rejected documents preserve prior approval rounds and can be corrected and resubmitted
- Purchase Orders capture Vendor, Project/Site, payment terms, taxes, source PR lines, and exact subtotal/tax/grand totals
- Vendor, Project, Site, Party role, Item, UOM, Tax Code, Budget, and source PR relationships are rejected when they cross the active company boundary
- Final PO approval stores an immutable commercial JSON snapshot and SHA-256 hash
- Approved PR quantities may be split across several POs; draft/approval does not reserve quantity
- Issuing a PO transactionally reserves source PR quantities with row locks and rejects concurrent over-ordering
- Cancelling an unreceived issued PO releases its reserved PR quantities; received quantities prevent unsafe cancellation
- PO lines expose ordered, received, cancelled, and available-to-receive quantities for the Phase 6 receiving workflow
- PR/PO approval and issue deliberately create no Journal Entry
- Quotations and supporting evidence reuse private immutable Documents with same-company enforcement
- Tenant-scoped Filament resources manage PRs, POs, approval rules, line entry, workflow actions, approval evidence, and related Documents

### Material receiving, inspection, site inventory, and returns

- Company-scoped Goods Receipts link issued Purchase Orders, Vendor, Project/Site, delivery date/reference, and stock-tracked PO material lines
- Receive action atomically assigns `GRN-YYYY-*`, locks PO/lines, prevents cumulative over-receipt, records receiver/time, and updates partial/full PO receipt status
- Independent Inspect action requires a different actor, requires accepted plus rejected quantity to equal every received line, records result/notes/rejection reason, and calculates accepted value from the PO cost snapshot
- Rejected quantities never enter stock; rejected-material returns are quantity-bounded and reopen PO availability for replacement delivery
- Accounts Handover requires a third actor, applies accepted quantities to inventory, creates immutable stock movements, and posts balanced Site Inventory/GRNI accounting in an Open period
- Handover is distinct from Vendor Bill approval/payment; it only records accepted inventory and the GRNI accrual
- Site/item Inventory Balances store quantity on hand, total inventory value, and moving weighted-average unit cost at four-decimal precision
- Immutable Inventory Movements preserve company, site, counterparty site, Project, item, source, type/direction, quantity, unit cost/value, post-movement balance snapshots, actor, and timestamp
- Inventory Transactions support site transfers, Project material issues/returns, accepted vendor returns, and authorized increases/decreases
- Transfers preserve cost between sites and create no GL entry because the same company inventory control account is used
- Project issues and outbound adjustments use current moving-average cost; inbound receipts/adjustments recalculate the moving average
- Project issues/returns and adjustments require an explicit active same-company posting account; vendor returns reverse GRNI and accepted-return quantity evidence
- Accepted vendor returns reduce inventory, reopen PO receipt availability, and retain the original GRN/return link
- Negative inventory is prohibited and failed multi-line/posting operations roll back without partial balances, movements, PO quantities, or journals
- Inventory Transaction preparers cannot post their own transaction
- Atomic `INV-YYYY-*` numbering and idempotent accounting source links prevent duplicate operational postings
- Goods Receipts and Inventory Transactions support private immutable Documents for delivery challans, photos, inspection/lab evidence, and returns
- Tenant Filament resources provide Goods Receipts & Inspection, Inventory Transfers & Issues, read-only Site Inventory Balances, and read-only Inventory Stock Ledger views

### Vendor Bills, matching, taxes, and Accounts Payable

- Company-scoped Vendor Bills and Vendor Credit Notes link same-company Vendors, issued Purchase Orders, Projects/Sites, source lines, and private Documents
- Vendor Bills use Draft, Submitted, Reviewed, Approved, Posted, Rejected, and Reversed states with independent preparer, match reviewer, and approver actors
- Company/type/year Vendor Bill numbering is atomic; vendor invoice numbers are unique per company and Vendor
- Stock lines are allocated FIFO under row locks against accepted, Accounts-handed-over GRN quantities and cannot consume more than the remaining accepted quantity
- PO–GRN–invoice review stores immutable match evidence and its hash
- Company matching settings default to active zero tolerance; rate, tax, and direct-service quantity exceptions require a dedicated override permission and reason
- Effective same-company Tax Codes and configured WHT, retention, vendor advance, and other deductions drive calculation without inventing statutory rates
- Supplier/contractor classification is snapshotted at submission for AP reporting
- Posted stock invoices clear Phase 6 GRNI; direct services debit configured Project costs; recoverable input tax, deductions, AP control, and price variance create balanced Purchase/Credit Note journals
- Posting uses the existing open-period, source-idempotent journal engine; reversal uses the linked journal reversal and releases reversed GRN allocation capacity
- Vendor Credit Notes reference posted source bills/lines, enforce remaining credit capacity, and post the opposite financial effect
- Accounts Payable Aging, Vendor Ledger, Unmatched Receipt, and Unpaid Vendor Bill reports are implemented in a tenant AP Reports page
- Tenant-scoped Vendor Bill and AP Matching Settings resources, explicit policies/workflow permissions, related Documents, and same-company model validation are implemented
- One active zero-default AP matching setting is provisioned idempotently for each of the six companies; no Vendor Bill transaction exists locally because no live invoice source was supplied
- Posted Vendor Bill payment allocations now reduce unpaid-bill and AP-aging balances; recoverable inclusive tax on stocked lines remains blocked until an inventory-tax revaluation policy exists

### Treasury, cash/bank operations, and reconciliation

- Company-scoped Payment, Receipt, and same-company Cash/Bank Transfer records support settlement, advances, refunds, other cash operations, cheque/reference metadata, maker-checker approval, idempotent posting, linked reversal, audit evidence, and private Documents
- Cash and bank selections must use the transaction company's active accounting mappings; bank journal lines retain the specific Company Bank Account dimension
- Posted Vendor Bill settlements support partial/full allocation without exceeding the open balance and post Accounts Payable against mapped Cash/Bank; Vendor, Employee, and Customer advances use configured mappings in supported directions
- Posted Customer Invoice allocations support partial/full Customer receipts and mapped AR settlement; Payroll Entry allocations settle mapped Salary Payable through employee-specific Payments; cross-company value movements use the paired Inter-company workflow rather than same-company Cash/Bank Transfer
- Private normalized CSV statements use strict headers, row/date/decimal limits, running and closing balance validation, row fingerprints, file checksums, and full rollback on invalid import
- Reconciliation supports partial match/unmatch to posted same-bank GL activity, authorized bank-adjustment journals, full-match and GL-balance proof before close, locked evidence, and reasoned authorized reopening
- Tenant resources exist for Payments/Receipts/Transfers, Bank Statements, and Bank Reconciliations; Cash Book, Bank Book, Treasury Position, and Unreconciled Bank Item reports are implemented
- Phase 8 added six applied migrations and explicit CRUD/workflow/report permissions; seeders remain idempotent and no live treasury or statement transaction was fabricated
- Verified with 22 focused/adjacent tests and 121 assertions plus the complete 177-test/849-assertion suite

### Customer Invoices, running bills, receipts, and project profitability

- Company-scoped Customer Invoices and Customer Credit Notes support Construction Running Bills, IT/Medical service invoices, and Trading Sales without company-name branches
- Atomic company/category/year numbering uses `RB`, `SI`, `TS`, and `SCN` prefixes
- Draft, Submitted, Approved, Posted, Rejected, and Reversed workflows enforce maker-checker authorization, commercial snapshot hashes, audit evidence, Open periods, idempotent journals, and linked reversals
- Running Bills require Project and certificate evidence, reconcile work plus variations to lines, track prior certification and contract value, and calculate configured retention, WHT, and mobilization recovery
- Service invoices require same-company Service items; Sales/WHT uses only active effective configured Tax Codes and no statutory rate is fabricated
- Customer Credit Notes reference posted source invoices/lines, enforce cumulative quantity capacity, and post opposite AR/Revenue/tax/deduction effects
- Trading Sales issue stock at moving weighted-average cost, prohibit negative inventory, and post balanced Revenue, AR, COGS, and Inventory entries; reversal restores stock and reverses the linked journal
- Phase 8 Treasury allocations now support posted Customer Invoice receipts, partial/full settlement, over-allocation prevention, and mapped AR/Cash or Bank posting
- AR Aging, Customer Ledger, Unpaid Customer Invoice, Project Profitability, and Project Budget-vs-Actual reports are implemented and tenant scoped
- Customer Invoices reuse private immutable Documents and reject cross-company evidence links
- Five Phase 9 migrations and explicit CRUD/workflow/report permissions are applied; two-run permission provisioning is idempotent
- Verified with 9 focused tests/61 assertions, 26 focused-adjacent tests/137 assertions, and the complete 186-test/910-assertion suite
- No Customer Invoice, line, adjustment, Sales sequence, or Customer receipt allocation exists locally because no approved live source data was supplied

### Authentication and user management

- Filament admin panel at the configured `/admin` path
- User model and user management resource
- Soft-deletable users
- Login, profile management, sessions, passkeys, and two-factor support through Filament Breezy
- Password reset support from the Laravel foundation

### Roles and permissions

- Filament Shield role management
- Spatie roles and permissions tables
- Policies for users, roles, activity records, companies, modules, company-module settings, and company bank accounts
- Existing generated permission keys follow the current format such as `ViewAny:User`
- Super-admin and panel-user concepts are configured
- Custom actions such as resetting a password, updating settings, managing company members, and viewing full bank details have explicit permissions
- Role permissions remain global reusable role templates; company membership and policies determine which companies a user may enter
- Spatie team-scoped roles are intentionally not enabled at this stage because no confirmed workflow requires different roles for the same user in different companies
- `DatabaseSeeder` delegates to an idempotent production-data seeder that refreshes the approved company, accounting, permission, application-setting, role, baseline-user, and company-membership configuration
- Existing baseline-user passwords are preserved; a missing baseline user receives a random unusable password and must use the password-reset workflow before signing in
- Production seeding deliberately excludes operational transactions, audit history, sessions, private documents/files, and local password hashes

### Audit and settings

- Spatie Activitylog tables and a read-only Filament Activity resource
- General application settings
- Configurable brand name, logo, primary color, and favicon

### Document platform

- Company-scoped document categories
- Configurable default sensitivity, retention days, expiry requirement, verification requirement, approval requirement, and active state per category
- Company-scoped document records with title, reference number, category, sensitivity, issue/expiry dates, description, and extensible metadata
- Private file storage on the Laravel `local` disk
- Secure generated storage names with the original file name stored separately as metadata
- Explicit MIME type, user-supplied extension, and 10 MB size validation
- SHA-256 checksum for every stored file version
- Immutable document-version history; uploading a replacement creates the next version instead of overwriting a file
- New versions reset previous verification, approval, and rejection state
- Verification, approval, and rejection workflows with actor and timestamp evidence
- Rejection reason capture
- Restricted and confidential documents are excluded from list queries unless the user has the sensitive-document permission
- Separate permissions for preview, download, upload version, verify, approve, reject, and sensitive-document access
- Short-lived private preview URLs for PDF and image files
- Authorized file downloads using the original display name
- Audit events for uploads, version creation, verification, approval, rejection, preview, and download
- Expired-document filtering based on the expiry date
- Polymorphic related-record fields support Company, Employee, Employment, Project, Journal, Purchase Requisition, Purchase Order, Goods Receipt, Inventory Transaction, and Vendor Bill links; Payroll links remain future scope
- Idempotent default category provisioning for newly created and existing active companies: Company Registration, Employee Document, Contract, Financial Document, and General Document

### HR foundation

- Global Employee profiles remain separate from authenticated Users
- Optional Employee-to-User link exists for a future employee portal; Employees still cannot log in
- Company-specific Employment records allow one Employee to work for one or several companies
- Employee codes are unique within a company, not globally
- Company-scoped Departments and Designations
- Employment joining/ending dates, category, status, reporting manager, department, designation, and work schedule
- Reporting lines reference another Employment and reject self-reporting, indirect cycles, and cross-company managers
- Director, Administrative Staff, and Project Staff are typed employment categories
- Employee CNIC is normalized, encrypted at rest, and supported by a keyed hash for exact matching
- Sensitive Employee values are excluded from activity-log properties
- Separate permissions cover identity, private contact, medical, sensitive-data editing, private HR notes, and HR verification
- Interviewer and document-verifier records must reference Users who can access the Employment company
- Creating an Employee atomically creates the first Employment in the active company
- Existing Employees can receive another Employment in another company
- Employee and Employment queries and policies enforce active-company isolation
- Employee deletion is blocked while any Employment exists
- Department and Designation deletion is blocked while used by an Employment
- Documents can relate to the active Company, an Employee working in that company, or an Employment in that company; cross-company links are rejected
- Repeatable Emergency Contacts with one primary contact per Employee
- Repeatable Qualifications with institution, field, completion year, grade, and notes
- Repeatable Previous Experience with company, designation, dates, legacy duration text, reason for leaving, and notes
- Previous Experience rejects an ending date earlier than its starting date
- Repeatable Employee Bank Accounts with encrypted account number and IBAN
- Employee Bank Accounts are masked without a dedicated sensitive-bank permission
- One primary payroll bank account is maintained per Employee
- Emergency-contact mobile/address and bank identifiers are excluded from activity-log properties
- Emergency Contacts, Qualifications, Experience, and Bank Accounts each have separate list, view, create, update, delete, and restore permissions
- Employee and Employment pages have dedicated document relation managers
- HR document relation managers support private upload, policy-filtered listing, preview, download, and opening the full document workflow
- HR document uploads default to Restricted sensitivity and create immutable Document Versions

### HR joining letters

- Company-specific Joining Letter Templates with safe, documented placeholders
- One idempotent standard template is provisioned for every newly created or existing active company
- Templates can vary by company without duplicating the joining-letter module
- Joining Letters belong to a company and one company-specific Employment
- Cross-company Employment and Template references are rejected at the model boundary
- Draft generation snapshots employee, employment, company, schedule, effective date, and compensation values from the selected template
- Unknown template placeholders are rejected instead of being executed or silently retained
- Generated letter body and compensation are encrypted at rest
- Viewing the protected letter snapshot, viewing compensation, and managing compensation use separate permissions
- Workflow states are Draft, Pending Approval, Approved, Rejected, Issued, and Accepted
- Regeneration, submission, approval, rejection, issue, and acceptance recording each have separate permissions and server-side authorization
- Workflow actions use transactions, row locks, current database state, actor evidence, timestamps, and audit events
- Rejected letters retain a reason and can be corrected, regenerated, and resubmitted
- Issuing stores a SHA-256 checksum of the fixed subject and body snapshot
- Issued content is immutable; only the initial acceptance record may be added
- Accepted letters are fully immutable
- Letter body and compensation are excluded from activity-log properties
- Employee acceptance currently records the accepting name, time, notes, and staff actor; it is not an electronic or digital signature
- Digital signatures and certificate signing remain explicitly deferred

### Employment compensation history

- Company-scoped compensation records belong to one company-specific Employment
- Effective-from and optional effective-to dates preserve salary history instead of overwriting the Employment record
- Monthly Basic Salary, House & Travel Allowance, Food Allowance, and Other Allowance are captured separately
- Gross Salary is derived from the components instead of being independently editable
- Salary and allowance amounts and private notes are encrypted at rest and excluded from activity-log properties
- Viewing salary amounts and managing salary amounts use separate permissions from general record access
- Creating or editing compensation requires both the normal resource capability and the sensitive amount-management permission
- Compensation workflow states are Draft, Pending Approval, Approved, and Rejected
- Submission, approval, and rejection each use separate permissions, transactions, row locks, actor evidence, timestamps, and audit events
- Rejection requires a reason; rejected compensation can be corrected and resubmitted
- Approved salary values are immutable
- Approving a later compensation record automatically closes the previous active approved period one day before the new effective date
- Approval rejects overlap with an approved period beginning on or after the proposed effective date
- Reusable Approved and Effective-On query scopes provide the salary lookup foundation for payroll generation
- Cross-company Employment references and invalid effective date ranges are rejected at the model boundary

### Payroll foundation

- Company-scoped Payroll Runs are unique by company and payroll period
- Employee Payroll Entries are generated from active Employments and approved effective-dated Compensation records
- Generation is transactional and stops without partial entries when an eligible employee lacks approved compensation
- Employee name, code, designation, employment category, compensation source, and all monetary values are historical snapshots
- Joining and ending dates determine payable days within the payroll period
- Basic pay is prorated by payable days; allowance-proration rules remain a business-confirmation item
- Basic, payable basic, House & Travel, Food, Other Allowance, Gross, deductions, Net, Bank, Cash, and remarks are captured
- Gross and Net salary are derived automatically instead of independently edited
- Absence, Loan & Advance, and Other deductions are separate
- Bank plus Cash allocation must equal Net Salary before submission
- Payroll entries group Directors, Administrative Staff, and Project Staff through the existing Employment Category
- Payroll amounts and remarks are encrypted at rest and excluded from normal activity-log values
- Viewing payroll totals, viewing entries, and editing entries have dedicated permissions
- Generate/Refresh Entries, Submit for Review, Approve, Reject, Mark Paid, and Lock each have separate permissions
- Workflow states are Draft, Under Review, Approved, Paid, Locked, and Rejected
- Workflow transitions use transactions, row locks, actor/timestamp evidence, and audit events
- Entries cannot change after submission; locked payroll runs are immutable
- Rejected payroll can be corrected and regenerated
- Attendance/Leave integration and formal Loan/Advance recovery are implemented through HR-7 and HR-6; exports and printable Payroll approval sheets remain planned
- Approved Payroll Runs support explicit, idempotent Post to Accounts and linked reversal actions
- Company payroll mappings route Basic Salary, allowances, absence offsets, and Other Deductions to validated Expense/Liability accounts; Employee Advance recovery uses the required system mapping
- Administrative/Director payroll posts component expenses; Project Staff require Project/optional Site/Cost Center allocations that exactly equal Gross less Absence Deduction
- Payroll posting credits Salary Payable per Employment, Employee Advances for recoveries, and configured Other Deduction liabilities without duplicating the later cash/bank settlement
- Treasury Payments allocate against posted Payroll Entries, prevent over-allocation, debit Salary Payable, and retain the employee dimension
- A Payroll Run can only be marked Paid after its Accounts posting exists and all Payroll Entries are settled by posted Treasury Payments
- Payroll/GL/settlement reconciliation and Employee Advance subledger views are available in the tenant Payroll & Advances report

### HR workforce decision and evidence gate

- HR-0 Business rules, samples, and approval matrix is implemented and verified
- Company Employee codes will use atomic company sequences with initial `EMP-00001` format, configurable prefix/padding, no year/reset, and preservation/collision skipping for existing codes
- Employment retains Probation, Active, On Leave, and legacy Ended while adding Resigned and Terminated; legacy Ended is not guessed into a separation reason
- Employment Type uses Permanent, Contract, Daily Wages, and Internship
- Work Locations will be controlled company records with an optional same-company Project Site
- Departments remain company-specific and will receive optional same-company hierarchy with cycle prevention
- Attendance, Leave, Loan/Advance, Bonus/Incentive, appraisal, separation, and Final Settlement values are effective-dated/configuration-led; no live numeric policy was invented
- Attendance-machine integration is split into a vendor-neutral ingestion foundation and a later actual-device adapter
- The default biometric boundary stores device-user mappings and raw punch evidence, not fingerprint templates
- Production Attendance finalization, Leave, Loan/Advance, Payroll deductions, Final Settlement, and machine synchronization are blocked until their applicable configuration/evidence gates are satisfied
- Approved synthetic source layouts and scenarios cover Employee codes, Department hierarchy, normal/overnight punches, duplicates, unknown device users, Leave, Loan/Advance recovery, Payroll posting, resignation, clearance, and Final Settlement
- A complete future permission/approval matrix preserves company scope, sensitive-data permissions, maker-checker separation, audit evidence, immutable source snapshots, existing GL posting, Treasury settlement, and Fixed Asset boundaries
- HR-0 changed documentation and architecture decisions only; no application schema, code, configuration, permission, or operational transaction data was changed

### Performance, discipline, movement, and separation

- Company KPI libraries and Appraisal Cycles configure score scales without seeded business values
- Appraisals retain encrypted goals, KPI scores/comments, outcome and acknowledgement, exact weight/checksum evidence, independent review/approval, and immutable submitted data
- Configurable Warning Letter Templates and sensitive Employee Warnings support issue, response, acknowledgement, closure, separate sensitive access, audit, and private Document attachments
- Effective Promotion/Transfer requests snapshot before/target Employment terms and update Department, Designation, manager, Work Location, or Employment Category only after controlled approval/application
- Compensation linked to a movement must be a separately approved, same-Employment, same-effective-date Compensation record
- Resignation and Termination retain notice, reason, authority, protected/handover notes, documents, acceptance/withdrawal/rejection/approval, and independent actor evidence
- Approved separation updates Employment ending date and status. Attendance, Leave, Payroll, clearance, and Final Settlement enforce that boundary
- Linked application Users are not silently disabled; approved separation exposes a pending/complete access-review decision with actor/time evidence
- Tenant Filament resources, workflow actions, policies, factories, migrations, 69 permissions, encryption, company isolation, row locks, audit, and focused regression coverage are included

### Final Settlement

- One company/Employment Final Settlement is prepared from an approved separation only after mandatory clearance is completed; the approved last-working date is the cutoff
- Salary, Leave Encashment, Notice Pay/Recovery, Bonus, Incentive, Gratuity, benefits, Loan/Advance, Asset, and other recoveries are explicit source-backed components with encrypted amounts/evidence, references, checksums, mappings, and deterministic keys
- Draft refresh reconciles exact active Loan/Advance balances and clearance recovery recommendations; submission refuses new, missing, or changed sources
- Preparer, reviewer, approver, and Finance poster are independent actors; submitted inputs are immutable and rejection/reversal retains full evidence
- Posting creates an idempotent balanced Payroll voucher in an Open period and updates Loan/Advance recovery subledgers only after posting
- Net payable uses Salary Payable; net receivable and financing recovery reuse Employee Advances; dedicated company component mappings cover all other settlement accounts
- Treasury Payment/Receipt allocation enforces company, Employment, direction, and posted-open limits and supports reversal before Final Settlement reversal
- Tenant resources provide settlement/account mapping management, source lines, private Documents, approved-separation preparation, printable approved letters, amount permissions, and Final Settlement/GL/Treasury reconciliation

### HR reports, dashboard, exports, and group reporting

- A tenant HR Reports & Dashboard page provides Employee List, Department-wise Employees, Designation-wise Employees, Employee Loan, Employee Advance, Increment History, Attendance Summary, and Leave Summary reports
- Existing Salary Register, Payroll Summary, Project-wise Payroll, Payroll reconciliation, and Final Settlement reports remain the authoritative Payroll/settlement views and now support exports
- Dashboard cards expose company-scoped unique people, Employment records, active/probation, On Leave, current-month joiners/exits, pending Leave, and Attendance exceptions
- Company reporting never includes identity, bank, medical, warning, termination-reason, or private HR-note fields
- Compensation, Payroll, Financing, Attendance, Leave, and Final Settlement sections retain their existing separate view/amount permissions in addition to report access
- Every report supports CSV and XLSX through private/no-store streamed responses; XLSX request-temporary files are deleted after streaming
- Every export records actor, company/root, report, format, row count, and company/group scope in Activitylog
- Group HR uses the authorized active-company set and requires access to every included company
- Group reports explicitly distinguish unique people from company Employment counts and compare status, Attendance, Leave, Payroll cost, Loans/Advances, joiners, and exits
- Group Payroll and Financing amounts remain hidden/null without their existing sensitive permissions
- HR reporting creates no synthetic “All Companies” tenant, consolidated transaction company, snapshot ledger, Payroll, Journal, or Treasury data
- Reporting indexes support company/date, Department, Designation, approved Compensation, and Payroll Entry lookups

### HR migration, recovery, and rollout hardening

- Seven exact-header private CSV adapters cover Department hierarchy, Employees/Employments, HR document metadata, Leave opening balances, approved Loan/Advance schedules, issued Fixed Asset custody, and finalized historical monthly Attendance summaries
- Prepare/dry-run, validation, and import require independent actors; row errors, checksums, company-safe reference resolution, duplicate protection, source counts/amounts, and imported-record checksums remain auditable
- Controlled rollback retains immutable source/row evidence and refuses reversal after downstream Payroll, Treasury, document-version, financing-transaction, or custody use
- Historical document metadata does not fabricate a file/version, historical financing does not invent disbursement/GL entries, and raw fingerprint-machine backfill remains unavailable until HR-5
- Tenant HR Data Migrations and Operational Readiness pages expose migration workflow, configuration/reconciliation gates, device-offline fallback, rollout status, and limitations
- The HR recovery manifest hashes 24 HR tables at row and aggregate level and detects post-manifest mutation without exposing source content
- Readiness checks cover company isolation, duplicate integrity, Payroll/Final Settlement/financing reconciliation, configuration gates, device continuity, and realistic-volume query behavior
- Pilot UAT verifies finalized Attendance input through Payroll components, balanced GL posting, Treasury settlement, and net expense-account reconciliation
- Eight dedicated permissions, company policy enforcement, private storage, Activitylog evidence, transaction locks, and full regression coverage protect the workflow

### Attendance and Leave foundations

- Company Work Calendars define ISO working weekdays, timezone, effective dates, active state, and calendar-specific paid holidays
- Company Work Shifts support day and overnight schedules, breaks, effective non-overlapping Employment assignments, and same-company validation
- Effective-dated Attendance Rules configure grace, late rounding, half-day, absence, minimum overtime, and missing-punch treatment without provisioning live numeric business rules
- Manual Attendance Punches retain separate immutable evidence, direction, reason, creator, independent approver/rejector, and decision time
- Daily Attendance Records derive from the effective schedule/calendar/rule, approved punches, approved Leave, joining/ending dates, holidays, rest days, and overnight windows
- Daily finalization calculates scheduled/worked/late/overtime minutes, status, evidence checksum, finalizer, and timestamp; finalized records are immutable
- Attendance Corrections retain before/proposed snapshots, reason, independent decision evidence, and apply only approved fields without deleting the source punches
- Monthly Attendance Summaries aggregate finalized daily evidence and approved unpaid Leave, retain a source checksum, and become immutable Payroll inputs after finalization
- Leave Types configure day/hour unit, paid/unpaid classification, Payroll impact, attachment requirement, and active state
- Effective Leave Policies define entitlement metadata, carry-forward metadata, negative-balance and encashment flags, and reject overlapping active versions
- The append-only Leave Ledger records opening/accrual/adjustment/consumption/reversal units with source, reason, actor, and effective date
- Leave follows Draft → Requested → Manager Approved → HR Approved, with independent actors, policy snapshot, balance validation, cancellation reversal, rejection reasons, and Activitylog evidence
- Required Leave attachments reuse the existing private, versioned Document platform through a Leave Request upload tab
- Work Calendars, Holidays, Shifts, Assignments, Attendance Rules/Records/Punches/Corrections/Summaries, Leave Types/Policies/Ledger/Requests have tenant-scoped Filament resources and dedicated CRUD/workflow permissions
- HR-3 provisions no calendars, shifts, attendance rules, Leave policies, balances, punches, Attendance, or Leave transactions; production use remains gated by approved company configuration
- HR-3 manual punches remain distinct from HR-4 machine-source punches and immutable raw device evidence

### Attendance-machine ingestion foundation

- Company-owned Attendance Devices support optional same-company Work Location, code/identifier, exact IANA timezone, transport capability, a non-secret connection-profile reference, active state, health, cursor, last-sync/last-seen, and safe error status
- Effective-dated device-user mappings attach one external machine user to one same-company Employment without overlapping ranges
- Import/sync batches retain source/checksum, cursor boundaries, safe metadata, source file reference, counts, actor/times, and failure summary as immutable evidence
- Raw Attendance events retain the original local value, timezone, normalized UTC time, direction, safe payload, source ID, fingerprint, processing state, and error without storing fingerprint templates
- Company/device/source-ID and deterministic event-fingerprint uniqueness prevent duplicate raw events; every machine punch links one-to-one to its raw event
- Unknown device users are quarantined, missing directions require review, and finalized daily Attendance is not silently rewritten
- Authorized replay after correcting a mapping is safe and idempotent; retained failed CSV batches preserve immutable row-level errors and create a separate replay attempt
- The exact private CSV boundary is `device_code,external_user_id,punched_at_local,timezone,direction,source_event_id`; exact headers, device/timezone/company validation, and a 10 MB limit are enforced
- CSV and the vendor-neutral future adapter contract use the same ingestion action and replay-safe HR-3 normalization
- Machine events create approved machine-source punches without a manual creator, reuse/create draft daily Attendance, and respect overnight assignment dates
- Tenant Filament resources manage Devices and mappings and expose read-only batches, raw events, and row errors with authorized import/reprocess actions and separate raw-payload access
- HR-4 adds 25 permission capabilities and no seeded Devices, mappings, batches, raw events, or punches

### Fixed assets, inter-company accounting, consolidation, closing, and migration

- Company asset categories map cost, accumulated-depreciation, and depreciation-expense accounts; the register supports controlled manual/Vendor Bill capitalization, straight-line schedules, assignment transfers, disposal, reversal, private evidence, and Asset/GL reconciliation
- Inter-company transactions create one due-from/due-to pair across two separate books in one database transaction; the preparer cannot approve or post, each company requires a different approver, and both periods/mappings must be valid before either journal posts
- Related-company Journal Line dimensions support counterparty reconciliation and internal eliminations while keeping each legal-company ledger independent
- Paired posting and reversal are idempotent, row-locked, fully linked, and rejected atomically when either company side is invalid or out of balance
- Authorized consolidation uses the explicit active-company set, requires access to every included company, maps accounts by the controlled template/reporting keys, and never creates a synthetic consolidation ledger
- Consolidated Trial Balance, Balance Sheet, and P&L include internal due-from/due-to eliminations and explicit mismatch indicators
- Year-end closing snapshots Revenue/Expense balances with a SHA-256 checksum, requires an independent approver/poster, rechecks the books before posting, transfers the annual result to mapped Retained Earnings, and closes the final period
- A posted close is immutable; reversal requires an explicitly reasoned authorized period reopen and creates the normal linked reversal instead of editing history
- Production opening migration accepts only the normalized seven-column CSV layout, stores private source evidence/checksum, resolves company accounts and optional Party/Project/Cost Center dimensions, and records row-level dry-run errors
- Migration preparation, validation, and import require three different actors; imported debit/credit totals must reconcile exactly to the approved source and normal Opening Balance journal
- Migration rollback uses the controlled journal reversal and retains the immutable source, validation, actor, and reason evidence
- Group CSV export, accounting-integrity checks, and deterministic recovery manifests support management reporting and backup/restore verification
- Tenant Filament resources expose Inter-company, Year-end Closing, Opening Migration, and Group Consolidation workflows without manual mutation of posted or validated evidence

### Tests

- PHPUnit tests exist for user/role widgets, profile/settings pages, and activity-resource authorization
- PHPUnit tests cover independent company access, inactive access, bank encryption/default selection, module settings, tenant isolation, sensitive-data authorization, and Filament foundation pages
- PHPUnit tests cover private document creation, checksums, version history, immutable versions, workflow transitions, company isolation, confidential-document filtering, category protection, Filament uploads, and action-level authorization
- PHPUnit tests cover HR encryption, audit redaction, multi-company Employment, reporting cycles, company isolation, sensitive permissions, Filament creation flows, and Employee/Employment document links
- PHPUnit tests cover repeatable employee details, primary-contact/account enforcement, encrypted employee bank data, relation-manager permissions, cross-company denial, document visibility, and private HR document uploads
- PHPUnit tests cover HR document-type provisioning, optional defaults, company/applicability/date/sensitivity validation, dedicated identity/medical permissions, immutable legacy mapping/version replacement, compliance gaps, tenant configuration, and protected deletion
- PHPUnit tests cover joining-letter company isolation, safe rendering, encrypted snapshots, workflow permissions and transitions, rejection/regeneration, audit redaction, checksums, and immutability
- PHPUnit tests cover encrypted compensation, gross calculation, effective periods, automatic prior-period closure, overlap denial, workflow authorization, immutability, audit redaction, and company isolation
- PHPUnit tests cover payroll generation, missing-compensation rollback, joining-date proration, encrypted snapshots, deduction recalculation, Project allocation reconciliation, idempotent Accounts posting, linked reversal, Employee Advance recovery, Treasury settlement, workflow permissions, post-submission freeze, reports, and final locking
- PHPUnit tests cover company-independent automatic Employee codes and collision skipping, Department parent isolation/cycle rejection, Employment lifecycle/date/location validation, immutable before/after Employment snapshots, and authorization for the new tenant masters/history
- PHPUnit tests cover HR-3 factory validity, company isolation, effective-rule/schedule overlap rejection, overnight shifts, holidays, rest days, missing punches, Employment lifecycle dates, maker-checker manual punches/corrections, immutable evidence, Leave balance consumption/negative-balance policy, cancellation reversal, unpaid Leave classification, finalized monthly-summary immutability, and tenant workflow page rendering
- PHPUnit tests cover HR-4 file/event idempotency, quarantine and safe replay, missing-direction review, failed-batch row errors/reprocessing, shared adapter ingestion, immutable raw evidence, company/location boundaries, overnight machine events, and tenant UI isolation
- PHPUnit tests cover HR-6 maker-checker, exact schedule reconciliation, due-as-of balances, tenant isolation, submitted-term/installment immutability, Treasury/GL disbursement and recovery, idempotency, rescheduling, early payoff, principal waiver, and reversal history
- PHPUnit tests cover Phase 2 role-combined Parties, primary contacts, cross-company master-data rejection, tax effective dates, tenant resources, Project Documents, maker-checker budget approval, exact totals, supersession, audit evidence, and immutability
- PHPUnit tests cover Phase 3 company COA idempotency and snapshots, profile activation, dynamic bank mappings, tree/manual-posting rules, period workflows, and sequential voucher numbering
- PHPUnit tests cover Phase 4 balance/company/source validation, maker-checker, idempotent stale posting, four-decimal precision, period locks, immutability, reversals, opening balances, tenant permissions, Filament creation, and reconciled financial reports
- PHPUnit tests cover Phase 5 exact/cumulative budget controls, configurable sequential approvals, rejection/resubmission evidence, maker-checker, tenant isolation, Filament PR/PO creation, approved snapshot immutability, partial/concurrent ordering, cancellation quantity release, company/document boundaries, and zero premature GL posting
- PHPUnit tests cover Phase 6 accepted/rejected GRNs, over-receipt rollback, independent inspection/handover, rejected/accepted returns, PO reopening, weighted-average valuation, transfers, Project issues, adjustments, vendor returns, negative-stock rollback, GL reconciliation, tenant UI, permissions, and private document boundaries
- PHPUnit tests cover Phase 7 exact PO–GRN–invoice matching, hard accepted-quantity bounds, configurable tolerances and authorized exceptions, effective GST/WHT/retention posting, reversals, Credit Notes, AP-control reconciliation, aging/unpaid/unmatched reports, tenant UI, permissions, and private document boundaries
- PHPUnit tests cover Fixed Asset capitalization/depreciation/transfer/disposal/reversal, tenant isolation, private evidence, reporting, and GL reconciliation
- PHPUnit tests cover independent paired-company approvals, atomic posting/reversal, counterpart reconciliation, mapped consolidation/elimination, CSV export, reproducible year close/reopen, private migration dry-run/import/rollback, recovery manifests, permissions, and tenant isolation
- The pre-HR-3 complete verified baseline contained 199 tests and 999 assertions
- HR-3 focused verification passes 11 tests/62 assertions; the broader affected HR, Payroll, Document, and Filament regression passes 65 tests/327 assertions
- HR-4 focused verification passes 8 tests/38 assertions; its broader affected HR, Document, and Filament regression passes 37 tests/189 assertions
- Fresh post-HR-6 migrate/seed leaves 6 companies, 735 unique permissions, 36 optional HR Document Types, and zero Attendance Devices, mappings, batches, raw events, punches, Employment, financing, Treasury, Payroll, or Journal transactions
- HR-10 focused verification passes 4 tests/40 assertions; affected HR-6/HR-9, Payroll, Treasury, and Document regression passes 35 tests/228 assertions
- Fresh post-HR-10 migrate/seed plus two repeated seed runs leave 6 companies, 887 unique permissions, 36 optional HR Document Types, and zero Final Settlement, Journal, or Treasury transactions
- HR-11 focused verification passes 4 tests/33 assertions; broader affected HR, Attendance, Leave, Financing, Payroll, Final Settlement, Filament, consolidation, and accounting regression passes 72 tests/434 assertions
- Fresh post-HR-11 migration and two repeated seed runs leave 6 companies, 893 unique permissions, 36 optional HR Document Types, and zero Employment, Payroll, Attendance-summary, Leave, Financing, Final Settlement, or HR export-audit operational rows
- HR-12 focused migration/hardening plus Payroll accounting/calculation verification passes 11 tests/100 assertions; the complete suite passes 263 tests/1,429 assertions with a 512 MB PHPUnit process limit
- Fresh post-HR-12 migration and repeated permission seeding leave 6 companies, 901 unique permissions, 36 optional HR Document Types, and zero HR migration, Employment, Payroll, Attendance-summary, financing, Final Settlement, Journal, or Treasury operational rows
- A post-HR-3 full-suite command was attempted, but its PHPUnit child process retained the existing 128 MB limit and exhausted memory during Document MIME detection; the affected Document test files pass in the broader focused command

## Not implemented yet

The following are planned only:

- Printable/PDF joining-letter export
- Electronic or digital signing
- HR/workforce application scope remaining: the actual fingerprint-machine connector only
- Fingerprint attendance-machine connector; HR-5 is **Blocked** pending the actual machine make/model/firmware, protocol/SDK/export capability, network topology, redacted users/events, timezone/clock behavior, and credential model
- Foreign currency (PKR-only remains the approved initial scope)
- Actual production source onboarding, statutory configuration, and infrastructure backup scheduling/restore execution; application validation, integrity, rollback, and recovery-manifest controls are implemented

## Agreed architectural direction

### One application and one shared database

Use one application and shared database unless a future regulatory, performance, contractual, or isolation requirement justifies a different deployment.

Company-owned operational data must still be explicitly scoped and authorized. A shared database must never imply shared access.

### Independent company access

The confirmed four-company baseline does not expose or use parent/child relationships. Access requires an active direct membership for the selected company; legacy hierarchy fields are retained temporarily for a controlled future schema cleanup only.

Consolidated reporting uses the explicit authorized active-company set. Do not create a fake "All Companies" database record.

### Company context in Filament

Filament should remain the primary management UI.

Operational resources work in an explicit Filament tenant context with a searchable company switcher. Shared system resources such as Users, Roles, Activities, Companies, and the Module catalog are deliberately unscoped and policy-controlled.

Every custom query, custom page, widget, relation manager, export, and action must be reviewed for cross-company data leakage. UI visibility is not an authorization boundary.

### Shared modules with controlled variation

Do not duplicate HR, Accounts, Projects, or other modules for each company.

Use:

- A shared module catalog
- Per-company enabled/disabled state
- Independent per-company configuration
- Per-company configuration
- Named workflow variants only when business requirements genuinely differ

Avoid scattered checks such as `if company is Company A`. Business variation belongs in configuration, policies, templates, or explicit domain strategies.

### Employee and system user are separate

An Employee is a person recorded by HR. A User is an authenticated system account.

Employees will not receive login accounts initially. A future optional one-to-one Employee-to-User link will enable an employee portal without moving HR data into the users table.

Never place passwords or authentication state on the Employee model.

### Employee and Employment are separate

Personal identity belongs to Employee. Company-specific work data belongs to Employment.

An employee may have one or several employments across companies. Each employment may have its own:

- Employee code
- Joining and ending dates
- Department and designation
- Reporting manager
- Employment category
- Work schedule
- Compensation
- Employment status

The reporting manager should reference the manager's Employment when the reporting line is company-specific.

Salary changes must be versioned by effective date. Do not overwrite salary history.

## HR direction currently captured

The business has supplied an employee information form, a joining-letter sample, and a payroll spreadsheet layout.

### Employee-level information

- Full name
- Father's or husband's name
- CNIC
- Date of birth
- Gender
- Marital status
- Nationality
- Blood group
- Addresses
- Mobile, alternate contact, and email
- Emergency contacts
- Qualifications
- Previous work experience
- Employee bank accounts

Repeatable information such as qualifications, experience, emergency contacts, and documents should use related records instead of JSON blobs or numbered columns.

### Employment-level information

- Company
- Employee code
- Joining date
- Designation
- Department
- Reporting manager
- Employment category, such as Director, Administrative Staff, or Project Staff
- Interviewed by
- Documents verified by
- Appointment-letter status
- HR verification information

Recommended default: employee codes are unique within a company, not globally.

### Compensation and payroll direction

Compensation must be effective-dated and associated with an Employment.

The first payroll design is expected to capture:

- Payroll company and period
- Payable days
- Basic or prorated basic
- House and travel allowance
- Food allowance
- Other allowances
- Gross payable
- Absence deduction
- Loan or advance deduction
- Other deductions
- Net salary
- Bank-paid amount
- Cash-paid amount
- Remarks
- Prepared, reviewed, approved, paid, and locked states

Director, Administrative Staff, and Project Staff are report groupings, not separate payroll tables.

The business must still confirm exactly what "Pay For" means and how salary proration, absence deductions, and allowances are calculated.

Approved or paid payroll must not be silently edited. Corrections must use an adjustment or reversal workflow with full audit history.

## Accounts direction

Accounts and its connected Projects, Procurement, Inventory, Banking, Sales, Payroll-posting, Assets, and consolidated-reporting work are governed by the controlling phased implementation plan at `docs/FINANCE_PROJECTS_OPERATIONS_IMPLEMENTATION_PLAN.md`. Finance plan Phases 0–12 are implemented and verified.

The plan records the initial Chart of Accounts direction, company-account provisioning, project-cost dimensions, double-entry ledger, voucher workflows, material receiving/inspection, three-way matching, project billing, payroll posting, assets, and consolidation. Before implementing affected phases, Phase 0 must confirm at least:

- Current chart of accounts and account groups
- Voucher types and numbering
- Journal, payment, receipt, contra, and transfer workflows
- Approval stages
- Cash and bank reconciliation
- Customer and supplier ledgers
- Project or cost-center allocation
- Inter-company transactions
- Advances, retentions, taxes, and deductions
- Payroll posting into Accounts
- Opening balances and migration from the existing process
- Financial periods and closing/locking rules
- Required statements and management reports
- Existing spreadsheet/software exports and source documents

The discussion must produce sample real transactions, source documents, postings, approvals, exceptions, and reports. Do not finalize accounting behavior based only on menu names or the planned account list.

Company bank accounts belong to the shared company foundation. Payroll and Accounts should reference the same company-bank records.

The implementation plan is mandatory cross-chat state. Only one numbered phase may be in progress, later phases cannot bypass incomplete dependencies, and each completed phase must update both the plan and this project-state document with actual implementation and verification evidence.

## Document management direction

Documents are an implemented platform capability intended for Company, HR, Payroll, Accounts, Projects, and future modules.

Implemented document metadata includes:

- Owning company or authorized shared scope
- Related entity, such as Company, Employee, Employment, Payroll, Voucher, or Project
- Document category
- Original file name and generated storage name
- MIME type and size
- Private storage path
- Cryptographic checksum
- Version
- Classification or sensitivity
- Issue and expiry dates
- Status
- Uploaded by on each immutable file version
- Verified by and verification date
- Approved by and approval date
- Optional metadata

Document files must use private storage. Do not expose permanent public URLs for HR, payroll, account, identity, bank, or signed documents.

Downloads and previews pass policy authorization. Previews use short-lived temporary URLs and currently support PDF and image files.

Upload validation covers extension, MIME type, size, generated file names, company path ownership, and allowed document types. Malware scanning is not implemented yet and must be evaluated before production use.

Document versions are immutable and traceable. Replacing a document creates a new version and resets its review state instead of silently changing historical evidence.

Retention days are configuration metadata only. Automated retention deletion is intentionally not implemented until the business confirms legal retention rules. Expiry is evaluated from the stored date and exposed as a filter; no scheduled expiry notification exists yet.

Examples:

- A CNIC copy belongs to an Employee and is highly restricted.
- A company registration certificate belongs to a Company.
- A company-specific appointment letter belongs to an Employment.
- A payroll approval sheet belongs to a Payroll Run.
- An invoice or voucher attachment will belong to an Accounts record after that workflow is designed.

## Permission standard

### Non-negotiable rule

Every backend business capability must be explicitly authorized.

This includes:

- Viewing lists and individual records
- Creating and editing records
- Archiving, restoring, and permanently deleting
- Uploading, replacing, viewing, downloading, verifying, and deleting documents
- Viewing sensitive fields
- Importing and exporting
- Generating documents
- Submitting, reviewing, approving, rejecting, reopening, locking, and unlocking
- Issuing or cancelling letters
- Signing or applying a company seal
- Posting, reversing, paying, and reconciling financial records
- Bulk actions

Hiding a Filament button is not sufficient. The server-side action, policy, or gate must authorize the operation.

Custom Filament actions must call authorization explicitly because custom actions are not automatically protected merely by existing on an authorized resource.

Harmless UI interactions such as searching, sorting, pagination, opening tabs, or applying a filter inherit the underlying View permission. They do not need separate permission records unless they expose otherwise restricted data.

### Permission naming

Use stable machine keys in code and human-readable labels in the role-management UI.

Long-term business-domain examples:

```text
employees.view_any       -> View employee list
employees.view           -> View employee profile
employees.create         -> Add employee
employees.update         -> Edit employee
employees.archive        -> Archive employee
employees.view_salary    -> View employee salary
employees.view_cnic      -> View employee CNIC
employee_documents.view  -> View employee documents
joining_letters.generate -> Generate joining letter
joining_letters.approve  -> Approve joining letter
joining_letters.issue    -> Issue joining letter
payroll.generate         -> Generate payroll
payroll.review           -> Review payroll
payroll.approve          -> Approve payroll
payroll.mark_paid        -> Mark payroll as paid
payroll.unlock           -> Unlock approved payroll
documents.sign           -> Sign document
documents.apply_seal     -> Apply company seal
```

Permission labels must use business language. Avoid showing developers' method names such as `ViewAny:EmploymentCompensation` directly to company staff.

The current implementation retains Filament Shield-compatible machine keys such as `ViewAny:CompanyBankAccount` to avoid maintaining a parallel authorization system. Role management presents business-readable labels, including `Manage Company Users` and `View Sensitive Details`. New custom actions must receive a stable machine key and a plain-language label when added.

Company membership controls data scope; roles control capabilities. Roles are currently reusable global templates. If the business later confirms that one user needs materially different roles in different companies, team-scoped role assignments must be designed and migrated as a separate change.

### Permission registry

Every new workflow should have a permission matrix before implementation.

For each Filament resource or custom action, record:

- Machine permission key
- Business-facing label
- Scope: global, company, or own record
- Whether it exposes sensitive data
- Roles receiving it by default
- Whether it requires fresh MFA or a second approver
- Audit event emitted

Role templates may provide sensible defaults, but permissions must remain individually configurable.

### Sensitive-field permissions

The following should not automatically follow a generic employee View permission:

- CNIC and identity documents
- Salary and payroll details
- Bank accounts
- Medical or blood-group information where restricted
- Private contact information
- Signature assets and signing credentials

## Audit standard

Permissions control whether an action may happen. Audit history records what actually happened. Both are required.

At minimum, log:

- Actor
- Company context
- Action
- Affected record
- Old and new values where safe
- Timestamp
- Request or correlation identifier
- IP address and user agent for sensitive actions
- Approval, rejection, issue, payment, reversal, document download, and signing events

Do not log plaintext secrets, passwords, signing private keys, full tokens, or other protected credentials.

Audit records should remain read-only in Filament. Destructive access to audit history should not be available as a normal business permission.

## Electronic and digital signing direction

### Important distinction

A signature-pad image is not by itself a secure digital signature.

The project must distinguish:

1. **Signature image** — a drawn or uploaded visual mark.
2. **Application attestation** — an authenticated user explicitly approves a fixed document and the application stores evidence.
3. **Certificate-based digital signature/eSeal** — cryptographic signing that establishes document integrity and certificate identity.

### Security requirements

Permissions alone are not sufficient for owner or authorized-signatory signing.

A secure signing action should require:

- Dedicated Sign or Apply Company Seal permission
- Company scope authorization
- Fresh password or MFA confirmation
- Explicit display of the final document being signed
- One-time confirmation of signing intent
- Separation between document preparation and approval where required
- Immutable final PDF
- SHA-256 or stronger document hash
- Signer identity, timestamp, IP address, and user agent
- Audit event and non-editable signing record
- Private file storage
- Key rotation and revocation procedure
- No access to reusable raw signature or private key through ordinary Filament forms

Never store a certificate private key as plaintext in the database or repository. Prefer a managed signing provider, HSM/KMS, or properly protected certificate service.

### Current package research

No signing package is installed.

The reviewed Filament signature-pad packages primarily capture a PNG/base64 drawing. The packages found did not clearly support this project's Laravel 13 and Filament 5 combination. Even if made compatible, a drawing field would only solve visual capture, not identity, intent, integrity, auditability, or certificate signing.

Current provider options worth evaluating after the business confirms its requirement:

- Official Docusign PHP SDK and embedded signing workflow
- Official Dropbox Sign PHP SDK and embedded signing workflow
- Pakistan ECAC certificate, timestamp, eSignature, or eSeal services
- A carefully designed internal application-attestation workflow for lower-risk internal documents

Pakistan's Electronic Transactions Ordinance, 2002 distinguishes electronic signatures and advanced electronic signatures. ECAC offers PKI, timestamp, certificate, and eSeal infrastructure. Legal acceptance for a particular employment, accounting, corporate, or regulatory document must be confirmed with qualified Pakistani legal/compliance advice before treating an internal drawn signature as legally sufficient.

### Recommended staged approach

1. Build document generation, versioning, approval, hashing, and audit trail without reusable signature images.
2. Confirm whether the requirement is internal approval, employee acceptance, a visual signature, or legally relied-upon certificate signing.
3. Evaluate provider cost, data location, embedded user experience, API support, certificate ownership, and Pakistani legal requirements.
4. Add a provider-independent signing contract so a vendor can be changed later.
5. Implement the chosen provider or certificate approach with sandbox testing and explicit approval.

## General development standards

- Prefer Filament resources, relation managers, actions, widgets, and pages for management workflows.
- Use custom Laravel domain actions/services where business operations span multiple records or require transactions.
- Keep business logic out of Filament form schemas and Blade views.
- Use policies or gates for every business action.
- Scope all company-owned records at the query and policy levels.
- Validate all input with explicit rules.
- Use database transactions for multi-record state changes.
- Make approvals, payroll generation, voucher posting, and signing idempotent where repeat requests are possible.
- Preserve historical snapshots for issued letters, approved payroll, posted accounts records, and signed documents.
- Use private storage for sensitive documents.
- Encrypt sensitive values at rest where practical and mask them in the UI.
- Avoid speculative abstractions, but do not hard-code company identities into workflows.
- Add factories and PHPUnit feature tests with each domain.
- Tests must cover permitted access, denied access, company isolation, happy paths, failure paths, and important edge cases.
- Run the narrowest relevant PHPUnit tests after changes.
- Run `vendor/bin/pint --dirty --format agent` after modifying PHP files.
- Do not create new documentation files unless explicitly requested.

## Recommended implementation order

### Foundation

Completed on 2026-07-23:

1. Retained stable Filament Shield machine keys and added business-readable labels.
2. Implemented companies and company hierarchy.
3. Implemented user-to-company access and company switching.
4. Added company-aware policies, membership scope, and isolation tests.
5. Added module enablement and company settings.
6. Added company bank accounts.

### Document platform

Completed on 2026-07-24:

1. Implemented document categories and metadata.
2. Implemented private uploads and authorized download/preview.
3. Implemented Company links and a polymorphic foundation for later domain records.
4. Implemented immutable versioning, verification, approval, rejection, expiry filtering, and audit events.

Deferred:

5. Malware scanning and confirmed retention automation require production/business decisions.
6. Signing-provider abstraction requires confirmed signing requirements. Signature work remains explicitly deferred.

### HR

Completed on 2026-07-24:

1. Employee profiles and sensitive-field permissions.
2. Employment records and multi-company association.
3. Departments, designations, reporting lines, and work schedules.
4. Connected the document platform to Employee and Employment records with company-isolation validation.
5. Qualifications, experience, emergency contacts, and employee bank accounts.
6. Dedicated Employee and Employment document relation managers.
7. Joining-letter templates, safe snapshot generation, approval, rejection, issue, checksum, and acceptance recording.
8. Effective-dated compensation history with encrypted amounts, approval, overlap protection, and immutable approved values.
9. Payroll-run generation, employee snapshots, deductions, payment allocation, approval, payment, and final locking.

Completed on 2026-07-28 under HR-1:

10. Optional same-company Department hierarchy with cycle prevention and protected parents.
11. Configurable, transactional, collision-safe company Employee-code sequences with automatic allocation and stable existing codes.
12. Employment Type, Resigned/Terminated statuses, probation/confirmation/notice fields, and controlled company Work Locations with optional same-company Project Site links.
13. Immutable, actor-aware, effective-dated Employment before/after history exposed through a read-only relation manager.
14. Tenant resources, filters, policies, permissions, factories, migrations, and focused regression coverage for the HR-1 changes.

Completed on 2026-07-28 under HR-2:

15. Six controlled company HR document types with configurable applicability, sensitivity, compliance, review, and date requirements.
16. Nullable legacy-safe Document mapping, type-aware Employee/Employment upload tabs and filters, and immutable version reuse.
17. Dedicated identity/medical document access layered over sensitive-document authorization.
18. Required-document compliance summaries, deterministic optional type provisioning, tenant configuration UI, policies, permissions, and regression coverage.

Completed on 2026-07-28 under HR-3:

19. Effective company calendars/holidays, day/overnight shifts, non-overlapping Employment assignments, and configurable Attendance rules.
20. Approved manual punch evidence, daily calculation/finalization, correction snapshots, and immutable monthly Attendance Payroll inputs.
21. Leave Types/Policies, append-only balance ledger, request/manager/HR approval, over-consumption control, cancellation reversal, and paid/unpaid Payroll-impact snapshots.
22. Leave Request private attachments, tenant resources, workflow actions, policies, 89 HR-3 permission capabilities, factories, migrations, audit evidence, and focused regression coverage.

Completed on 2026-07-28 under HR-4:

23. Company Attendance Device registry, optional Work Location assignment, IANA timezone, transport/health/cursor state, and non-secret connection reference.
24. Effective device-user-to-Employment mappings, immutable batches/raw events/row errors, deterministic checksums/fingerprints, quarantine, replay, and company boundaries.
25. Private normalized CSV import and vendor-neutral adapter contract feeding the same HR-3 normalization, with tenant UI, 25 permissions, factories, migrations, audit evidence, and focused regression coverage.

Completed on 2026-07-28 under HR-6:

26. Separate tenant-safe Employee Loan/Advance requests with explicit terms, maker-checker workflow, versioned schedules, immutable recovery/reversal ledger, and private documents.
27. Treasury-linked disbursement/direct recovery, automatic Employee Advances receivable GL posting, approved principal-waiver Journal, rescheduling, partial recovery, early payoff, and reversal-safe schedule restoration.
28. Exact outstanding and due-as-of installment boundaries for HR-7 Payroll and later clearance/Final Settlement, tenant UI/policies, 19 permissions, factories, five migrations, audit evidence, and focused regression coverage.

Completed on 2026-07-29 under HR-7:

29. Effective-dated company Payroll Calculation Rules, exact finalized Attendance/Leave consumption, and immutable source components with deterministic keys, encrypted amounts/evidence, checksums, and revisioned Payroll generation.
30. Maker-checker Bonus/Incentive sources and active due-as-of Loan/Advance recovery components, with recovery applied only on Payroll posting and fully restored through linked Payroll/Journal reversal.
31. Extended Bonus, Incentive, unpaid Leave, late, and half-day GL mappings/posting while preserving Salary Payable, Employee Advances, Project allocation, Treasury settlement, locking, and revisioned reposting.
32. Tenant configuration/evidence UI, source-backed Payroll review, Salary Register, Payroll Summary, Project-wise Payroll foundations, 19 permissions, six migrations, factories, audit, and deterministic accounting/reversal tests.

Completed on 2026-07-29 under HR-8:

33. Configurable KPI libraries/Appraisal Cycles and encrypted Appraisal goals, scoring, review, approval, acknowledgement, rejection, checksums, and maker-checker audit.
34. Configurable Warning Letter Templates and sensitive Warning issue/response/acknowledgement/closure with immutable evidence and private attachments.
35. Effective Promotion/Transfer with before/target snapshots, separately approved Compensation linking, one-time application, and immutable effective-dated Employment Change history.
36. Resignation/Termination with notice, authority/protected/handover evidence, private documents, acceptance/withdrawal/approval, Employment lifecycle propagation, Leave boundary, and explicit access review.

Completed on 2026-07-29 under HR-9:

37. One-live-custodian Fixed Asset issue, employee acknowledgement, transfer, return, damage/loss, encrypted recovery recommendation, and immutable custody/transfer evidence.
38. Approved-separation clearance aggregating outstanding Assets, Loans, Advances, Leave, handover, and company-configured HR/Manager/IT/Admin/Store/Finance/Loans/Assets obligations.
39. Department-specific decisions, blocking/waiver/recovery controls, independent completion, private attachments, tenant UI, policies, 42 permissions, and an explicit no-GL/no-Treasury boundary until HR-10.

Completed on 2026-07-29 under HR-10:

40. Approved-separation Final Settlement with exact last-working-date cutoff, completed mandatory clearance, source-backed earnings/recoveries, encrypted evidence, checksum reconciliation, and draft source refresh.
41. Independent preparation/review/approval/Finance posting, dedicated component mappings, balanced period-aware Journal posting, financing subledger recovery, linked reversals, and immutable workflow evidence.
42. Bounded Treasury Payment/Receipt settlement, payable/receivable state, private Documents, printable approved letter, tenant resources, 22 permissions, and Final Settlement/GL/Treasury reconciliation reporting.

Next work is controlled by `docs/HR_WORKFORCE_IMPLEMENTATION_PLAN.md`.

- Overall HR/workforce plan status: **In Progress**.
- HR-0 Business rules, samples, and approval matrix is **Implemented and Verified**.
- HR-1 Organization and Employment master enhancements is **Implemented and Verified**.
- HR-2 Typed employee documents and compliance metadata is **Implemented and Verified**.
- HR-3 Attendance and Leave foundations is **Implemented and Verified**.
- HR-4 Attendance-machine ingestion foundation is **Implemented and Verified**.
- HR-5 is **Blocked** after the user's explicit request because actual machine evidence is unavailable. The live database has zero configured Attendance Devices, and the repository/configuration contains no vendor connector, device fixture, or credential provider.
- HR-6 Employee Loans and Advances is **Implemented and Verified**.
- HR-7 Payroll calculation and accounting integration is **Implemented and Verified**.
- HR-8 Performance, discipline, promotion, transfer, and separation is **Implemented and Verified**.
- HR-9 Employee asset custody and clearance is **Implemented and Verified**.
- HR-10 Final Settlement is **Implemented and Verified**.
- HR-11 HR reports, dashboard, exports, and group consolidation is **Implemented and Verified**.
- HR-12 Migration, UAT, security, performance, and rollout hardening is **Implemented and Verified**.
- Implementation starts only when the user requests the relevant phase.
- The fingerprint-machine model is intentionally not assumed. HR-4 provides vendor-neutral device/user mapping, immutable raw punches, deduplication, import batches, replay-safe normalization, and a normalized CSV/manual contract. HR-5 adds the actual device adapter after machine evidence is available.

### Finance, Projects, and operations

Finance plan Phases 0–12 are implemented and verified. The authoritative order, dependencies, decision gates, acceptance criteria, and phase statuses are maintained in `docs/FINANCE_PROJECTS_OPERATIONS_IMPLEMENTATION_PLAN.md`.

The plan contains:

1. Business decisions and source evidence.
2. Legal-company topology and module governance.
3. Parties, Projects, sites, and operational master data. **Implemented and verified.**
4. Chart of Accounts and accounting foundation. **Implemented and verified.**
5. Double-entry ledger, vouchers, periods, and opening balances. **Implemented and verified.**
6. Purchase requisitions and purchase orders. **Implemented and verified.**
7. Material receiving/inspection, site inventory, and returns. **Implemented and verified.**
8. Vendor Bills, three-way matching, taxes, and Accounts Payable. **Implemented and verified.**
9. Payments, cash/bank operations, and reconciliation. **Implemented and verified.**
10. Sales/running bills and project profitability. **Implemented and verified.**
11. Fixed assets and depreciation. **Implemented and verified.**
12. Inter-company accounting, consolidation, closing, migration, and hardening. **Implemented and verified.**

## Next decisions

The next planning session should confirm:

- Official legal and tax details for the four confirmed companies
- Which admin users may access which companies
- Live statutory tax/retention/mobilization rules, approval amount limits, opening balances, and production migration cutoff
- Official customer/vendor/contractor/consultant, Project, Item, UOM, Site, Cost Center, and tax-code master data
- Required first document categories and retention expectations
- Whether joining-letter signing means internal approval, employee acceptance, visual signature, ECAC/certificate signing, or an external e-sign provider
- Company-specific Employee-code prefix/padding overrides, if the approved `EMP-00001` default should vary
- Payroll meaning of "Pay For" and exact calculation formulas
- Whether multiple-company employments are separately paid by each company
- Attendance shifts, holidays, grace period, late/half-day/absence/overtime rules, and Leave policies
- Fingerprint attendance-machine make/model, API/SDK/protocol/export options, network reachability, timezone behavior, and sample punch data when the device arrives
- Loan/Advance terms, Bonus/Incentive rules, separation/clearance requirements, and Final Settlement formulas

## External signing references reviewed

- Pakistan Electronic Transactions Ordinance, 2002: <https://pakistancode.gov.pk/pdffiles/administratordbc98dd49f2df3b1d07bb986dcceb9a3.pdf>
- ECAC PKI repository: <https://ecac.pki.gov.pk/>
- ECAC eSeal information: <https://ecac.pki.gov.pk/repository/eSeal_Certificate.html>
- Official Docusign PHP SDK: <https://github.com/docusign/docusign-esign-php-client>
- Official Dropbox Sign PHP SDK: <https://github.com/hellosign/dropbox-sign-php>
