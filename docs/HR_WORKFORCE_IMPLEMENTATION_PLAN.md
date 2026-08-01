# HR and Workforce Implementation Plan

Last updated: 2026-07-29

Overall status: **In Progress**

## Purpose

This is the controlling cross-chat implementation and handoff document for the remaining HR and workforce scope:

- Department hierarchy and Employee/Employment enhancements;
- typed employee documents;
- Attendance and Leave Management;
- fingerprint/biometric attendance-machine integration;
- Employee Loans and Advances;
- attendance-driven Payroll calculations;
- bonus, incentive, and deduction components;
- performance, warning, promotion, transfer, resignation, and termination workflows;
- employee asset issuance and return;
- clearance and Final Settlement;
- company HR reports, group-level consolidated HR reporting, and the HR Dashboard.

It records the verified baseline, decisions, phase order, dependencies, acceptance criteria, actual implementation evidence, and progress across separate chats and context compactions.

This plan does not authorize implementation by itself. A phase starts only when the user explicitly requests it.

## Mandatory execution protocol

Every agent planning or changing scope covered by this document must:

1. Read the root `AGENTS.md`, `docs/PROJECT_STATE.md`, this entire plan, and the relevant current code and database state.
2. Also read `docs/FINANCE_PROJECTS_OPERATIONS_IMPLEMENTATION_PLAN.md` before changing Payroll accounting-posting, Employee Loan/Advance accounting, Treasury settlement, Assets, Final Settlement accounting, or consolidated reporting.
3. Verify repository status, applied migrations, live schema, existing records, permissions, and relevant tests before trusting a prior summary.
4. Work on only one HR phase at a time. At most one phase in this plan may be **In Progress**.
5. Do not start a later phase until all listed dependencies are **Implemented and Verified**.
6. Before implementation starts:
   - confirm the phase prerequisites and business decisions;
   - change its status to **In Progress**;
   - add a dated Progress Ledger entry;
   - update `docs/PROJECT_STATE.md` if the start changes current project status or architecture.
7. If a blocking decision is unavailable:
   - complete all safe in-scope work that does not depend on it;
   - mark the phase **Blocked** only when the remaining acceptance criteria cannot be met;
   - record the exact decision, affected behavior, and safe default that was rejected or deferred.
8. A phase may be marked **Implemented and Verified** only after its complete acceptance criteria pass. File presence or partial CRUD is not completion.
9. When completing a phase, update this plan in the same change with:
   - actual migrations and data migration/backfill;
   - models, enums, actions/services, resources, pages, relation managers, policies, and permissions;
   - audit and document behavior;
   - reports and exports;
   - tests and exact verification commands;
   - deviations, known limitations, and follow-up;
   - Phase Status Index and Progress Ledger.
10. Update `docs/PROJECT_STATE.md` in the same change. Move delivered behavior into **Implemented now**, remove or narrow the matching **Not implemented yet** entries, update decisions, and record the new verified test baseline where material.
11. If a phase changes accounting, Assets, Treasury, or consolidated-report behavior, update `docs/FINANCE_PROJECTS_OPERATIONS_IMPLEMENTATION_PLAN.md` in the same change so the two plans cannot diverge.
12. Preserve existing company isolation, authorization, audit, private-document, maker-checker, immutability, posting, reversal, and historical-snapshot standards.
13. Stop after completing one phase unless the user explicitly requests continuation.
14. After context compaction or in a new chat, re-read the complete controlling documents and inspect current reality before resuming the one **In Progress** phase.

## Development lifecycle policy

The user confirmed on 2026-07-28 that this project is in active development and has no production deployment or production HR data.

- Agents may revise unreleased migrations, rebuild the local database with `migrate:fresh`, and reseed deterministic development/reference data when that is the clearest implementation path.
- Backward-compatible production backfills, zero-downtime migration choreography, and preservation of disposable local seed/test data are not phase blockers before the first production baseline.
- Do not fabricate business transactions, statutory values, attendance rules, leave balances, financial terms, or biometric evidence merely because resetting/seeding is allowed.
- Company isolation, authorization, private storage, immutable evidence, auditability, accounting integrity, and automated tests remain mandatory development requirements.
- Before the first production deployment, establish and record a migration baseline and reinstate production-safe forward-only migration and rollout controls through HR-12 and the deployment process.

## Status vocabulary

Only these phase statuses may be used:

- **Planned** — approved planning scope; implementation has not started.
- **In Progress** — implementation is actively underway.
- **Blocked** — acceptance criteria cannot be completed without a recorded decision or external dependency.
- **Implemented and Verified** — complete acceptance criteria and relevant verification passed.
- **Reopened** — a completed phase requires a material correction; record the reason and affected delivered behavior.

## Phase Status Index

| Phase | Name | Status | Depends on |
| --- | --- | --- | --- |
| HR-0 | Business rules, samples, and approval matrix | Implemented and Verified | Verified existing HR/Payroll foundation |
| HR-1 | Organization and Employment master enhancements | Implemented and Verified | HR-0 decisions required for codes/statuses |
| HR-2 | Typed employee documents and compliance metadata | Implemented and Verified | HR-1 |
| HR-3 | Attendance and Leave foundations | Implemented and Verified | HR-0–HR-1 |
| HR-4 | Attendance-machine ingestion foundation | Implemented and Verified | HR-3 |
| HR-5 | Device-specific fingerprint-machine connector | Blocked | HR-4 and actual machine evidence |
| HR-6 | Employee Loans and Advances | Implemented and Verified | HR-0–HR-1 and Finance Phases 4, 8, 10 |
| HR-7 | Payroll calculation and accounting integration | Implemented and Verified | HR-3–HR-6 |
| HR-8 | Performance, discipline, promotion, transfer, and separation | Implemented and Verified | HR-1–HR-3 |
| HR-9 | Employee asset custody and clearance | Implemented and Verified | HR-8 and Finance Phase 11 |
| HR-10 | Final Settlement | Implemented and Verified | HR-6–HR-9 |
| HR-11 | HR reports, dashboard, exports, and group consolidation | Implemented and Verified | HR-1–HR-10 as applicable |
| HR-12 | Migration, UAT, security, performance, and rollout hardening | Implemented and Verified | HR-1–HR-11 |

## Verified baseline — 2026-07-28

Repository, database schema, routes, migrations, and focused tests were inspected before this plan was created.
This section is the historical pre-plan baseline; completed phase records and the Phase Status Index are authoritative for current implementation state.

### Implemented and reusable

- Four independent companies with explicit direct membership.
- Global Employee identity separated from company-specific Employment.
- Company-scoped Departments, Designations, Employments, compensation, joining letters, and Payroll Runs.
- Employee code stored on Employment and unique within a company.
- Employment joining/ending dates, category, status, reporting manager, Department, Designation, and work schedule.
- Reporting-line cycle and cross-company protection.
- Encrypted CNIC and Employee bank identifiers with separate sensitive-data permissions.
- Repeatable emergency contacts, qualifications, experience, bank accounts, and private HR documents.
- Private, versioned employee and employment document uploads with verification/approval workflow.
- Effective-dated approved compensation history.
- Payroll generation, encrypted historical snapshots, review, approval, rejection, payment, locking, and project allocations.
- Explicit Payroll posting/reversal through the immutable double-entry ledger.
- Salary Payable settlement through Treasury Payments and mapped Bank/Cash accounts.
- Employee Advance accounting mapping and a journal-derived advance ledger.
- Fixed Asset register with Employment custodian and audited transfers.
- Company and group financial reporting foundations, permission system, and Activitylog audit history.

### Verified gaps

- Employee code is entered manually; no company employee-code sequence exists.
- Departments do not have parent/child hierarchy.
- Employment Type, Probation Period, Confirmation Date, Notice Period, and Work Location are absent.
- Employment status does not separately identify Resigned and Terminated.
- HR documents use free-form titles/categories; required HR document types are not controlled metadata.
- Attendance, holidays, shifts, raw punches, attendance corrections, and monthly attendance summaries do not exist.
- Leave types, policies, balances, accruals, requests, and payroll effects do not exist.
- No attendance-machine/device registry or ingestion mechanism exists.
- Payroll uses calendar joining/ending-date proration; attendance/leave/late/half-day deductions are not calculated.
- Loan/Advance deduction is an editable aggregate; no formal loan origination or recovery schedule exists.
- Bonus and Incentive are not explicit Payroll components.
- Performance, warnings, promotions, transfers, resignation, termination, clearance, and Final Settlement are absent.
- Fixed Assets can have a custodian, but an HR issuance/acknowledgement/return/clearance workflow is absent.
- Requested HR reports and group-level consolidated HR reporting are not implemented.

### Verification evidence

- Local database: 6 companies and zero Employee, Employment, Department, Designation, Payroll Run, Payroll Entry, or Journal transaction records.
- Focused verification command:

```text
php -d memory_limit=512M artisan test --compact \
  tests/Feature/HrFoundationTest.php \
  tests/Feature/HrEmployeeDetailsTest.php \
  tests/Feature/PayrollWorkflowTest.php \
  tests/Feature/PayrollAccountingWorkflowTest.php
```

- Result: 18 tests passed with 73 assertions.
- No application code or live transaction data was changed while preparing this plan.

## Architecture decisions

### Employee identity versus company Employment

Personal identity remains on the global Employee record. Company-owned lifecycle data remains on Employment.

Employee code, Employment Type, Employment Status, Department, Designation, manager, location, probation, confirmation, notice period, attendance, leave, loans, advances, payroll, assets, and separation workflows must reference the company-specific Employment.

Do not place one global employment status on Employee. The same person may have independent Employments in different companies.

### Historical HR changes

Approved historical values must not be silently overwritten.

- Salary changes use existing effective-dated compensation.
- Promotion, Department transfer, Designation change, location transfer, confirmation, resignation, and termination must retain effective-dated history and approval evidence.
- Attendance corrections preserve raw punches and create adjustment evidence.
- Approved leave, loan schedules, payroll calculations, clearance, and Final Settlement become immutable except through controlled reversal/correction workflows.

### Vendor-neutral attendance-machine boundary

The fingerprint-machine make, model, protocol, SDK, and deployment topology are currently unknown. The core design must not depend on one vendor.

Use two layers:

1. **Attendance ingestion foundation**
   - company-owned attendance devices;
   - device identifier, company, site/location, timezone, active state, and connection configuration reference;
   - Employment-to-device-user mapping with effective dates;
   - immutable raw punch events;
   - import/sync batches, cursors, checksums, source metadata, errors, and actor/system evidence;
   - normalized punch direction when supplied, while preserving the vendor payload;
   - deterministic deduplication;
   - replay-safe normalization into attendance records.
2. **Device adapter**
   - a contract implemented later for the actual machine;
   - supported transports may include vendor API, local SDK/agent, push/webhook, TCP/IP pull, SFTP/file export, or normalized CSV;
   - vendor credentials and secrets stay outside ordinary model/audit properties;
   - adapter output must enter the same ingestion service as manual/CSV imports.

Unknown machine details block only HR-5. They do not block Attendance rules, Leave, raw-punch storage, manual entry, normalized CSV import, or Payroll design.

### Biometric privacy boundary

The application should store attendance identifiers and punch events, not fingerprint templates, unless the actual vendor integration makes template storage unavoidable and the business explicitly approves it after security/legal review.

- Prefer biometric enrollment and matching on the machine.
- Store the machine's user/enrollment identifier mapped to Employment.
- Never log biometric templates, device passwords, access tokens, or raw secrets.
- Protect device mappings, raw payloads, manual corrections, and exports with dedicated permissions.
- Define retention and deletion rules before production synchronization.

### Punch ingestion and deduplication

Each accepted raw punch should preserve at least:

- company and device;
- mapped Employment when resolvable;
- vendor user identifier;
- punch timestamp and device timezone;
- normalized UTC timestamp plus original local value;
- punch direction/status when provided;
- source type and source event identifier;
- import/sync batch;
- vendor payload or safe metadata;
- deterministic fingerprint/checksum;
- processing status and error;
- received and processed timestamps.

A unique company/device/source-event key should be used when reliable. Otherwise, deduplicate using a stable hash of device identity, vendor user ID, timestamp, direction, and vendor-specific differentiators.

Repeated imports, polling retries, network retries, and device clock corrections must never create duplicate attendance.

### Attendance calculation boundary

Raw punches are evidence; daily attendance is a calculation.

- Never edit or delete raw machine punches through the normal UI.
- Corrections create manual adjustment records with reason, approver, and audit evidence.
- Daily attendance derives from approved schedules, holidays, leave, raw/manual punches, and effective-dated rules.
- A finalized monthly attendance summary is the source used by Payroll.
- Recalculating attendance after Payroll submission must not silently mutate Payroll; it requires Payroll reversal/regeneration or an adjustment in a later run.

### Payroll components and accounting

Do not keep adding unrelated values to one `other_deduction` field.

Introduce traceable Payroll components with source record, quantity, rate, amount, debit/credit behavior, and accounting mapping. Planned components include:

- payable basic;
- existing allowances;
- unpaid absence;
- unpaid leave;
- late-coming deduction;
- half-day deduction;
- loan installment;
- advance recovery;
- bonus;
- incentive;
- final/other approved adjustment.

Payroll generation snapshots source evidence. Approved Payroll continues to post through the existing journal engine:

```text
Dr Salary / Direct Project Labour / Bonus / Incentive Expense
    Cr Salary Payable
    Cr Employee Loan Receivable
    Cr Employee Advance Receivable
    Cr Other configured liabilities
```

Settlement remains:

```text
Dr Salary Payable
    Cr Company Bank / Cash
```

No operational module may write directly to balances outside Posted/Reversed Journal Entries.

### Assets and employee custody

Reuse the Fixed Asset register for laptops, vehicles, mobiles, and other capital assets. Do not create a duplicate HR asset master.

Add custody issuance, acknowledgement, transfer, return, condition, loss/damage, and clearance evidence around the existing Fixed Asset and Employment relationships. Non-capital consumable issuance may require a separately confirmed inventory workflow.

### Multi-company HR and group reporting

Each Employment and operational HR record belongs to one legal company.

Group HR reporting is an authorized explicit active-company reporting scope, not a synthetic “All Companies” tenant. A user must have access to every included company or be a Super Admin.

Employee identity may be de-duplicated for group headcount only with clear report semantics; company Employment counts must remain independently visible.

## Cross-cutting completion standard

Every phase must include, where relevant:

- company ownership, same-company foreign keys, uniqueness, and query indexes;
- enums for stable states/types;
- model relationships, casts, historical integrity, and sensitive-field redaction;
- factories and idempotent provisioning/seeders without fabricated operational transactions;
- tenant-scoped Filament resources, pages, relation managers, actions, and exports;
- policies for CRUD and every custom workflow capability;
- business-readable permission labels;
- maker-checker and segregation-of-duties controls;
- domain Actions for transactional workflows, row locks, idempotency, and concurrency handling;
- private Document links and cross-company link rejection;
- Activitylog events with safe actor, company, state, reason, and source evidence;
- PHPUnit happy-path, failure, edge, authorization, tenant-isolation, immutability, idempotency, and concurrency coverage;
- focused tests, appropriate broader regression tests, Pint after PHP changes, and diff validation;
- updates to this plan and `docs/PROJECT_STATE.md`;
- updates to the Finance plan when accounting, Treasury, Assets, or consolidated reporting changes.

## HR-0 — Business rules, samples, and approval matrix

Status: **Implemented and Verified**

Started: 2026-07-28

Completed: 2026-07-28

### Objective

Turn HR and Payroll assumptions into approved, effective-dated configuration and test scenarios.

### Required decisions

- Employee code prefix, year behavior, padding, reset rules, and collision handling.
- Whether Probation remains an Employment Status or is derived from probation/confirmation fields.
- Employment Status migration for existing `probation` and `ended` values.
- Employment Type definitions.
- Probation duration, extension, confirmation approval, and notice-period units.
- Work Location relationship: free-text, controlled location master, Project Site, or a combination.
- Department hierarchy depth and whether Department/Designation stay company-specific.
- Workweek, shifts, overnight shifts, grace period, holidays, late rounding, missing-punch behavior, overtime scope, and half-day/absence thresholds.
- Leave types, accrual, carry-forward, expiry, negative balance, attachment, encashment, unpaid treatment, and approval path.
- Machine installation topology, when known: network reachability, vendor software, API/SDK/export options, timezone, user ID format, and sample punches.
- Loan versus Advance definitions, eligibility, limits, interest, installment order, rescheduling, waiver, and early settlement.
- Bonus/Incentive source and approval.
- Appraisal cycles, KPI scoring, warning levels, and promotion/transfer approval.
- Resignation, termination, notice, clearance, benefit, gratuity, leave encashment, and Final Settlement formulas.
- Group HR report definitions and authorized roles.
- Required export formats and printable documents.

### Deliverables

- Decision Register updates in this file.
- Redacted sample attendance exports/punches when available.
- Approved synthetic attendance, leave, loan, payroll, separation, and settlement scenarios until real samples arrive.
- Permission and approval matrix.
- Confirmed production-retention and biometric privacy decisions before device sync.

### Acceptance criteria

- Every decision needed by HR-1–HR-4 is confirmed or explicitly configuration-led.
- Machine-specific unknowns are recorded without blocking vendor-neutral attendance work.
- No statutory, gratuity, tax, interest, leave, or deduction rule is invented.

### Approved configuration-first defaults

The user authorized HR-0 implementation on 2026-07-28. Because live workforce rules, real samples, and the attendance-machine model are not yet available, the following architecture-first defaults are approved. Synthetic values below are test evidence only and are not production HR policy.

| Area | Approved direction |
| --- | --- |
| Employee code | Allocate atomically per company as `EMP-00001`, `EMP-00002`, and so on. Prefix and padding are company configuration, but the initial default is `EMP` plus five digits. Do not include a year and do not reset the sequence. Preserve existing manual codes and skip collisions. |
| Employee versus Employment | Employee retains global personal identity. Code, status, type, Department, Designation, manager, Work Location, Attendance, Leave, Payroll, Loan/Advance, asset custody, and separation remain company-specific Employment data. |
| Employment Type | Use Permanent, Contract, Daily Wages, and Internship as the initial shared enum. Company workflow differences belong in configuration, not new tables per company. |
| Employment Status | Retain Probation, Active, On Leave, and legacy Ended; add Resigned and Terminated. New records cannot select legacy Ended. Existing Ended records remain readable and require authorized classification instead of being guessed. Confirmation normally moves Probation to Active. |
| Probation and confirmation | Store explicit probation start/end dates, optional extension evidence, Confirmation Date, and approval actors. Do not invent a default duration. The company must configure or explicitly enter the applicable duration. |
| Notice period | Store a positive integer number of calendar days on Employment or its approved terms snapshot. No default notice period is provisioned. |
| Work Location | Use a controlled company-owned Work Location master with optional same-company Project Site link. Preserve a text address/description on the location rather than free-texting the Employment. |
| Departments | Departments and Designations remain company-specific. Department receives an optional same-company parent with unlimited practical depth, cycle prevention, and safe deletion constraints. |
| HR documents | Reuse private versioned Documents and add controlled HR document-type metadata. All requested document types remain optional unless a company later configures a workflow requirement. |
| Work calendars and shifts | Company-owned and effective-dated. Support normal and overnight shifts, rest days, holidays, grace periods, missing punches, and schedule assignment. No live shift or numeric rule is seeded. |
| Attendance rules | Grace minutes, late rounding, half-day/absence thresholds, overtime, and missing-punch treatment are effective-dated company configuration. Attendance finalization is blocked until the applicable rule and schedule exist. |
| Leave | Leave types, units, accrual, carry-forward, expiry, negative balance, attachments, encashment, and paid/unpaid treatment are configurable. No statutory or company leave balance is invented or seeded. |
| Attendance machine | HR-4 builds the vendor-neutral registry, mappings, raw punches, batches, deduplication, and normalized CSV/manual ingestion. Only HR-5 waits for the actual machine make/model/protocol. |
| Biometric privacy | Fingerprint templates remain on the machine by default. The application stores the external device-user ID and punch evidence, not fingerprint templates. Production sync is blocked until retention and device-security configuration are approved. |
| Loans | Employee Loan is a separate approved receivable and schedule. Every loan explicitly records principal, approved interest method/rate where applicable, installments, and recovery order. No interest rate is assumed. |
| Advances | Employee Advance is separate from Loan and reuses the existing Employee Advance control account/Treasury foundation. It receives an approved recovery schedule rather than a free-form Payroll deduction. |
| Bonus and Incentive | Separate approved Payroll components with source, period, amount, approver, and account mapping. They must not be hidden inside Other Allowance. |
| Payroll calculation | Finalized Attendance/Leave summaries, due Loan/Advance schedule rows, Compensation, Bonus, and Incentive are immutable calculation sources. Submitted Payroll never changes silently when a source changes. |
| Payroll accounting | Preserve the implemented Payroll journal, Salary Payable, Project allocation, reversal, and Treasury settlement engine. New components extend mappings and source detail; they do not create a second ledger. |
| Appraisal and warnings | Shared configuration-led workflows with private evidence, maker/reviewer separation, acknowledgement, and effective outcomes. No scoring scale or warning levels are seeded until approved. |
| Promotion and transfer | Effective-dated Employment history; Compensation changes continue through effective-dated Compensation approval. Never overwrite approved historical assignments. |
| Separation and clearance | Resignation and Termination are distinct workflows. Approved last working date drives later Attendance/Payroll eligibility. Clearance checks Assets, Loans, Advances, Leave/handover, and configurable departmental obligations. |
| Final Settlement | Source-backed components only. Salary, notice, leave encashment, gratuity/benefits, Bonus/Incentive, Loan/Advance, and asset recovery require explicit approved rules or amounts. GL posting and Treasury settlement reuse existing engines. |
| Asset custody | Reuse Fixed Assets for capital items. Non-capital/consumable employee issuance remains a later business boundary and must not be forced into the Fixed Asset register. |
| Group HR reporting | Show both unique-person headcount and company-Employment count with explicit labels. Group scope uses the authorized active-company set; no fake All Companies tenant. |
| Exports | CSV/XLSX are the default data-export targets. PDF is reserved for approved formal letters, Payroll approval sheets, clearance, and Final Settlement documents. Export/download permissions and audit remain mandatory. |

### Production configuration gates

The following do not block HR-1 through HR-4 schema/workflow implementation, but they block affected production actions:

- Attendance finalization requires an active Work Calendar, Shift assignment, holiday calendar, and effective Attendance rule.
- Leave requests require configured Leave Types and applicable policies/balances.
- Payroll attendance deductions require an approved finalized Attendance summary and effective calculation rules.
- Loan/Advance approval requires configured eligibility, approval path, accounting mapping, and explicit financial terms.
- Bonus/Incentive processing requires an approved source and account mapping.
- Final Settlement approval requires approved separation, completed mandatory clearance, and configured component/accounting treatment.
- Attendance-machine production sync requires approved retention, device security, network topology, and actual adapter evidence.
- Machine-specific synchronization remains unavailable until HR-5 is implemented and verified against the real device.

### Approved synthetic source layouts

Real business samples remain unavailable. These normalized safe layouts are approved for schema design and automated tests until superseded by redacted actual samples.

#### Employment and organization

```text
Company Code
Employee Code
Full Name
Employment Type
Employment Status
Joining Date
Department Code
Designation Code
Reporting Manager Employee Code
Work Location Code
Probation Start
Probation End
Confirmation Date
Notice Period Days
```

#### Normalized attendance punch CSV v1

```text
device_code,external_user_id,punched_at_local,timezone,direction,source_event_id
```

Rules:

- UTF-8 CSV with an exact header.
- `device_code` resolves within one company.
- `external_user_id` resolves through an effective-dated Device User Mapping.
- `punched_at_local` uses `YYYY-MM-DD HH:MM:SS`.
- `timezone` uses an IANA name such as `Asia/Karachi`.
- `direction` may be `in`, `out`, `break_out`, `break_in`, or blank when the machine does not supply it.
- `source_event_id` is preferred for deduplication; otherwise the stable raw-punch fingerprint is used.
- Every import stores filename, checksum, source, row count, accepted count, duplicate count, error count, actor, and timestamps.
- A repeated file or source event must not create a second raw punch.

#### Leave opening balance/import

```text
company_code,employee_code,leave_type_code,as_of_date,opening_units,source_reference
```

Opening units require an approved source and a controlled import; they are never fabricated by provisioning.

#### Loan and Advance

```text
company_code,employee_code,type,request_date,principal,interest_method,interest_rate,
installment_count,first_due_date,disbursement_method,approved_source_reference
```

Interest fields are explicit and may be zero only when the approved Loan terms say so.

#### Bonus and Incentive

```text
company_code,employee_code,component_type,earning_period,amount,project_code,
source_reference,approved_by,approved_at
```

#### Separation and Final Settlement

```text
company_code,employee_code,separation_type,request_date,last_working_date,
notice_days_required,notice_days_served,clearance_status,settlement_component,
source_reference,amount
```

### Approved synthetic scenarios

All dates, minutes, units, and monetary values in these scenarios are test data only.

#### HR-S001 Automatic Employee code and Department hierarchy

- Two concurrent Employments are created in the same company.
- The company sequence allocates `EMP-00001` and `EMP-00002` exactly once.
- A different company may independently allocate `EMP-00001`.
- Department `Finance Operations` may belong to `Finance`, but a Department cannot be its own ancestor or use a parent from another company.

#### HR-S002 Normal shift with late arrival

- Effective test shift: 09:00–18:00.
- Effective synthetic grace: 10 minutes.
- Raw punches: 09:14 in and 18:03 out.
- The day is flagged Late using the configured synthetic rule.
- The raw punches remain immutable; an approved correction changes only the derived attendance result/evidence.

This does not approve a 10-minute production grace period.

#### HR-S003 Overnight shift

- Effective test shift: 20:00–05:00.
- Raw punches: 19:56 on day one and 05:08 on day two.
- Both punches resolve to one scheduled Attendance day.
- Re-importing either event is detected as duplicate.

#### HR-S004 Unknown machine user and replay

- Device user `9007` has no effective Employment mapping.
- Its punches remain quarantined and create no Employee Attendance.
- HR maps `9007` to a same-company Employment and reprocesses the batch.
- Replaying the original file produces zero additional raw punches or attendance records.

#### HR-S005 Paid and unpaid Leave

- One approved paid Leave day preserves payable Attendance under the configured policy.
- One approved unpaid Leave day creates a source-backed Payroll deduction component.
- Cancellation after Attendance finalization requires an authorized recalculation.
- If Payroll was already submitted, no silent mutation occurs; Payroll must be reversed/regenerated or adjusted later.

#### HR-S006 Loan and Advance recovery

- Synthetic no-interest Loan principal: PKR 60,000 in six approved PKR 10,000 installments.
- Synthetic Advance: PKR 20,000 in two approved PKR 10,000 recoveries.
- A Payroll period consumes only the due open rows.
- Duplicate Payroll generation does not recover the installment twice.
- Early settlement closes remaining schedule rows through an authorized Treasury allocation/reconciliation workflow.

These values do not approve zero-interest Loans or recovery terms.

#### HR-S007 Payroll calculation and posting

- Approved Compensation and synthetic finalized Attendance produce payable salary.
- Source-backed unpaid Leave, Late/Half Day, Loan, Advance, Bonus, and Incentive components are included as configured.
- Payroll approval snapshots the exact component sources.
- Payroll posting remains balanced:

```text
Dr Salary / Direct Labour / Bonus / Incentive Expense
    Cr Salary Payable
    Cr Employee Loan Receivable
    Cr Employee Advance Receivable
    Cr Other configured liabilities
```

- Salary settlement remains:

```text
Dr Salary Payable
    Cr Company Bank / Cash
```

#### HR-S008 Resignation, clearance, and Final Settlement

- An Employee resigns with an approved last working date.
- Clearance detects one issued laptop and an outstanding Advance.
- Settlement cannot be approved until the asset is returned or an authorized recovery is approved.
- Final Settlement snapshots salary through the last working date, configured benefits/recoveries, open Loan/Advance balances, clearance evidence, GL posting, and Treasury settlement.

### Approval and permission matrix

Role names are reusable templates; company membership determines data scope. No permission below is automatically granted merely because a role name exists.

| Capability | Recommended role/template | Segregation and sensitivity rule | Minimum audit evidence |
| --- | --- | --- | --- |
| Maintain Departments, Designations, Work Locations, Shifts, and HR configuration | HR Administrator | Same-company records; configuration changes separately authorized | Old/new values, actor, effective date |
| Create Employee/Employment | HR Officer | Sensitive fields require dedicated permissions | Actor, company, generated code, safe field changes |
| View/edit identity, medical, contact, and bank data | HR Sensitive Data Manager | Separate permissions; never implied by generic View | Actor and sensitive access/change event without plaintext protected values |
| Upload/verify/approve HR documents | HR Officer / HR Verifier / HR Approver | Verification and approval may require different actors; private storage | Version checksum, classification, actors, timestamps |
| Configure Attendance device and user mappings | Attendance Administrator | Device secrets excluded; same-company mapping | Device/mapping identifiers, actor, effective dates |
| Import/synchronize punches | Attendance Integration identity / Attendance Administrator | Import permission does not grant Employee edit | Batch checksum/counts, source, actor/system, errors |
| View raw punches | Attendance Auditor | Raw payload and identifiers treated as sensitive | View/export event where applicable |
| Create attendance correction | Attendance Officer | Cannot approve own correction | Original evidence, proposed correction, reason |
| Approve/finalize Attendance | Attendance Approver / HR Manager | Maker-checker; finalized period immutable | Rule version, totals, exceptions, actor |
| Request Leave | Employee proxy / HR Officer | Own/proxy scope recorded | Requester, Employment, dates, type |
| Manager approval for Leave | Reporting Manager | Cannot approve own request | Decision, reason, balance snapshot |
| HR final approval/cancellation for Leave | HR Approver | Policy/balance validation required | Decision, policy version, balance effect |
| Prepare Loan/Advance | HR/Finance Preparer | Cannot approve or disburse own request | Terms snapshot, source documents |
| Approve Loan/Advance | Finance Approver | Maker-checker; financial terms explicit | Approval, schedule checksum, reason |
| Disburse/reverse Loan/Advance | Treasury Poster | Existing Treasury/posting controls | Transaction/journal/allocation links |
| Generate Payroll | Payroll Preparer | Source exceptions visible; cannot approve own run | Source revision/checksum and totals |
| Review Payroll | Payroll Reviewer | Separate from preparer where configured | Review result and exceptions |
| Approve Payroll | Payroll Approver | Maker-checker | Approved snapshot and actor |
| Post/reverse Payroll | Finance Poster | Existing maker/poster and open-period rules | Journal/reversal link |
| Settle Payroll | Treasury | Existing payment approval/posting separation | Allocations and Bank/Cash evidence |
| Manage KPI/appraisal or warning | Manager / HR Reviewer | Private performance/discipline permissions | Participants, scores/decision, acknowledgement |
| Approve promotion/transfer | HR Approver / Management Approver | Effective-dated; compensation approval remains separate | Old/new assignment snapshot and effective date |
| Prepare/approve separation | HR Officer / HR Approver | Resignation and Termination separately authorized | Request, reason, dates, decision |
| Complete departmental clearance | Configured Clearance Officer | Officer can clear only assigned area | Checklist item, result, notes, actor |
| Prepare/approve Final Settlement | HR Settlement Preparer / Finance Approver | Maker-checker; source reconciliation required | Component snapshot, clearance, decision |
| Post/pay/reverse Final Settlement | Finance Poster / Treasury | Existing posting and settlement separation | Journal, payment/receipt, reversal links |
| View/export company HR reports | HR Reporting | Salary/sensitive reports require extra permissions | Scope, filters, export/download |
| View/export group HR reports | Group HR / Auditor | Authorized hierarchy and access to included companies | Included companies, filters, export |
| Segregation override | Unassigned | Dedicated permission, required reason, high-severity audit; not used unless separately approved | Actor, reason, affected record and actions |

### Approval workflow defaults

These are workflow shapes, not hard-coded role assignments:

```text
Attendance correction:
Prepare → Independent Approve/Reject → Recalculate → Finalize period

Leave:
Request → Reporting Manager decision → HR final decision

Loan/Advance:
Prepare/Request → HR review → Finance approval → Treasury disbursement

Payroll:
Generate → Review → Approve → Finance post → Treasury settle → Lock

Promotion/Transfer:
Prepare → Management/HR approval → Apply effective-dated change

Resignation/Termination:
Prepare/Request → HR/Management approval → Clearance → Final Settlement

Final Settlement:
Prepare → HR review → Finance approval → GL post → Treasury payment/receipt
```

Company configuration may add approval steps, but it may not silently remove server-side authorization, company scope, or required maker-checker controls.

### HR-0 completion record

Implementation completed:

- Date: 2026-07-28
- Implemented by: Codex with user authorization to implement HR-0
- Status changed from: Planned → In Progress → Implemented and Verified

Actual implementation:

- Migrations/tables: None; HR-0 is a decision, evidence, and safety gate.
- Data migration/backfill: None. Local Employee, Employment, Department, Designation, Payroll, and Journal transaction tables remain empty.
- Models/enums: None.
- Actions/services/connectors: None.
- Filament resources/pages/relation managers: None.
- Policies/permissions: Approved the reusable role/capability, sensitive-data, maker-checker, device-ingestion, Payroll, clearance, settlement, and group-report matrix for later phases.
- Seeders/provisioning: None. No Attendance, Leave, Loan, Advance, Bonus, schedule, rule, or transaction data was fabricated.
- Documents/audit: Confirmed typed HR Documents will reuse the existing private immutable platform; approved safe audit boundaries for biometric/device evidence.
- Reports/exports: Confirmed requested report catalog, explicit unique-person versus Employment semantics, CSV/XLSX data exports, and formal-document PDF direction.
- Accounting/Treasury/Asset integration: Confirmed reuse of the existing immutable Journal, Salary Payable, Treasury, Project allocation, Employee Advance, and Fixed Asset foundations. No financial behavior changed.

Verification:

- Focused tests: Existing focused HR/Payroll baseline passed 18 tests with 73 assertions before HR-0 completion.
- Broader tests: Not run because no application code, schema, configuration, or operational data changed.
- Formatting/static checks: Markdown/diff whitespace validation passed.
- Schema/database verification: All existing migrations are applied; database contains 6 companies, 585 unique permissions, and zero local Employee, Employment, Department, Designation, Payroll Run, Payroll Entry, or Journal records.
- Manual/device/UAT verification: Repository, schema, routes, code, existing plans, and tests were reviewed. Actual device UAT is correctly deferred to HR-5.

Decisions and deviations:

- Decisions resolved: HR-D001 through HR-D015 are either confirmed architecture/defaults or explicitly deferred live configuration with a production gate.
- Differences from planned design: Real HR forms, policy documents, payroll calculations, leave balances, Loan terms, Final Settlement examples, and machine samples were unavailable.
- Reason: The application has no live HR transactions and the fingerprint machine has not arrived. Configuration-first architecture and approved synthetic evidence allow safe implementation without inventing business policy.
- Known limitations: Live Attendance/Leave values, Loan/Advance terms, appraisal scales, separation benefits, retention periods, and machine adapter details remain operational configuration/evidence gates.
- Follow-up: Stop after HR-0. HR-1 may start only when the user explicitly requests it.

Project-state update:

- Sections changed in `docs/PROJECT_STATE.md`: Product direction/HR state, Implemented now, Not implemented yet, HR implementation order, and Next decisions.
- Newly implemented behavior: Documentation/decision gate only; no application feature is represented as implemented.
- Remaining planned behavior: HR-1 through HR-12.
- Test baseline/state changes: No code change; existing 18-test/73-assertion focused baseline retained.

Cross-plan update:

- Finance plan: Not changed because HR-0 confirmed future integration boundaries but changed no Payroll posting, Treasury, Asset, or consolidated-report behavior.

## HR-1 — Organization and Employment master enhancements

Status: **Implemented and Verified**

Started: 2026-07-28

Completed: 2026-07-28

Depends on: HR-0 decisions required for Employee codes, statuses, and fields.

### Scope

- Parent Department with same-company validation and cycle prevention.
- Company employee-code sequences and atomic automatic allocation.
- Employment Type: Permanent, Contract, Daily Wages, Internship.
- Employment Status support for Active, On Leave, Resigned, and Terminated with approved handling of Probation.
- Probation duration/start/end, confirmation date, notice period, and Work Location.
- Effective-dated Employment changes/history where overwriting would destroy evidence.
- Forms, tables, filters, policies, permissions, factories, migrations, backfill, and tests.

### Acceptance criteria

- Concurrent Employee creation cannot allocate duplicate company codes.
- Existing codes remain stable.
- Department cycles and cross-company parents are rejected.
- Status and date combinations are validated.
- Multi-company Employments retain independent codes and lifecycle state.
- Historical approved changes are reproducible.

### Delivered implementation

- Added optional same-company `parent_department_id`, parent/child relationships, UI visibility, cross-company rejection, direct/indirect cycle prevention, and child-aware deletion protection.
- Added one configurable Employee-code sequence per company. New Employments allocate codes transactionally with a locked sequence row, retry protection, database uniqueness, collision skipping, and stable existing/manual codes. Default format is `EMP-00001`; prefix and three-to-twelve-digit padding are configurable, while sequence rollback is rejected.
- Added controlled Employment Type values: Permanent, Contract, Daily Wages, and Internship.
- Retained Probation, Active, On Leave, and the read-only legacy Ended status; added Resigned and Terminated without guessing legacy outcomes. Resigned, Terminated, and legacy Ended require an ending date.
- Added probation start/end, confirmation date, positive calendar-day notice period, and controlled company-owned Work Location. A Work Location may optionally reference a same-company Project Site.
- Added immutable, effective-dated Employment change records containing changed fields and before/after snapshots for all material organization, lifecycle, location, and schedule fields. UI history is read-only and records the authenticated actor or System.
- Updated Employee and Employment creation/edit forms, infolists, tables, filters, tenant resources, policies, permission seeding, factories, and focused PHPUnit coverage.

### Data and migration result

- Migrations:
  - `2026_07_28_164917_create_work_locations_table.php`
  - `2026_07_28_164918_create_employee_code_sequences_table.php`
  - `2026_07_28_164919_create_employment_changes_table.php`
  - `2026_07_28_164925_add_hierarchy_to_departments_table.php`
  - `2026_07_28_164926_add_lifecycle_fields_to_employments_table.php`
- Existing Employee codes are not rewritten. Sequence rows are created lazily and skip any existing company code collisions.
- Existing Employment rows receive the approved Permanent type default; existing status values remain unchanged.
- Local database after migration: 6 companies; 0 Employments, Work Locations, Employment Changes, or Employee-code sequences; 598 unique permissions. No synthetic HR transactions were inserted.

### Verification and limitations

- `vendor/bin/pint --dirty --format agent` — passed after applying formatting fixes.
- `git diff --check` — passed.
- Focused HR, Joining Letter, Compensation, Payroll, and Filament authorization regression: **58 tests, 294 assertions passed**.
- Full `php artisan test --compact` was attempted twice but the accumulated process exhausted the environment's fixed 128 MB PHP memory limit in the pre-existing Employee document upload test. The same test and all affected HR/Payroll files pass in the focused regression command; this is recorded as a test-runner limitation, not an HR-1 functional failure.
- Automatic allocation is protected by a company sequence row lock, transaction retry, monotonically increasing sequence, collision scan, and the existing unique `(company_id, employee_code)` database constraint. The SQLite test environment verifies independent and collision-skipping allocation; production-engine contention testing remains part of HR-12 rollout hardening.
- HR-1 records actual master changes with their effective recording date. HR-8 will add future-effective approval workflows for promotion, transfer, and separation while reusing this immutable evidence model.

Project-state update:

- Updated `docs/PROJECT_STATE.md` implemented behavior, tests, current HR phase status, local database state, and remaining planned scope.
- Newly implemented behavior: Department hierarchy, Employee-code sequencing, Employment lifecycle/type/location fields, Work Location master, and immutable Employment history.
- Remaining planned behavior: HR-2 through HR-12.

Cross-plan update:

- Finance plan: Not changed because HR-1 changes no Payroll accounting posting, Treasury, Asset, or consolidated-report behavior.

## HR-2 — Typed employee documents and compliance metadata

Status: **Implemented and Verified**

Started: 2026-07-28

Completed: 2026-07-28

Depends on: HR-1.

### Scope

Reuse the private Document platform and add controlled HR document types:

- CNIC;
- Educational Document;
- Experience Certificate;
- Appointment Letter;
- Medical Certificate;
- Police Verification.

Include optional issue/expiry dates, document number/reference, verification/approval requirements, sensitivity defaults, and upload-tab filtering.

### Acceptance criteria

- All listed documents remain optional unless company configuration makes one required for a workflow.
- Medical and identity documents have restricted defaults and separate permissions.
- Replacements create immutable Document Versions.
- Cross-company links and unauthorized preview/download are denied.
- Existing free-form HR Documents remain readable and can be mapped without data loss.

### Delivered implementation

- Added company-owned `hr_document_types` configuration with the controlled codes CNIC, Educational Document, Experience Certificate, Appointment Letter, Medical Certificate, and Police Verification.
- Each type configures Employee-versus-Employment applicability, default sensitivity, issue/expiry requirements, verification, approval, optional compliance requirement, active state, and audit history.
- Provisioning creates all six types idempotently for every active company without overwriting later company configuration. All six default to optional; no fake Employee document records are seeded.
- Added nullable `documents.hr_document_type_id`, preserving all existing free-form Documents and immutable Document Versions. Legacy records can be mapped later without replacing or deleting versions.
- Extended Document creation/model validation to reject cross-company or wrong-owner types, apply at least the configured sensitivity, and enforce configured issue/expiry metadata.
- CNIC and Police Verification require both generic sensitive-document access and the dedicated identity-document permission. Medical Certificate additionally requires the dedicated medical-document permission.
- Employee and Employment document tabs now require a relevant controlled document name/type for new uploads, display and filter by type, retain private storage/versioning, and continue to authorize preview/download through Document policy actions.
- Added company-scoped HR Document Type configuration pages and compliance summaries showing required types that are missing from each Employee profile or Employment.
- Existing category verification/approval workflows now also honor HR document-type verification requirements.

### Data and migration result

- Migrations:
  - `2026_07_28_170211_create_hr_document_types_table.php`
  - `2026_07_28_170212_add_hr_document_type_id_to_documents_table.php`
- Deterministic provisioning/seeding created 36 configuration rows: six types for each of six companies.
- All 36 types remain optional (`is_required = false`) and the local Documents table remains empty.
- Permission registry now contains 607 unique permissions, including type configuration plus separate identity/medical document access.

### Verification and limitations

- `vendor/bin/pint --dirty --format agent` — passed.
- `git diff --check` — passed.
- Focused typed-document/Filament suite: **25 tests, 134 assertions passed**.
- Broader HR, Documents, Joining Letter, Compensation, Payroll, and Filament authorization regression: **78 tests, 401 assertions passed**.
- Live schema verified the company/type uniqueness, applicability/active and compliance indexes, nullable Document mapping, same-company foreign keys, and restricted deletion.
- Full-suite rerun was not repeated because the previous HR-1 attempts established the environment's cumulative 128 MB runner limit; every affected Document/HR/Payroll test file was included in the passing broader focused command.
- Malware scanning and automated legal retention deletion remain outside HR-2 and must be decided before production use.
- `is_required` exposes compliance gaps and can be consumed by later workflows; HR-2 does not invent a workflow that blocks hiring, Payroll, or Attendance solely because a type is marked required.

Project-state update:

- Updated `docs/PROJECT_STATE.md` product direction, implemented Document/HR behavior, tests, remaining plan scope, phase status, and development-only migration policy.
- Newly implemented behavior: controlled/configurable HR document types, nullable legacy mapping, type-aware private uploads, separate identity/medical authorization, and required-document compliance status.
- Remaining planned behavior: HR-3 through HR-12.

Cross-plan update:

- Finance plan: Not changed because HR-2 changes no Payroll posting, Treasury, Asset, or consolidated-report behavior.

## HR-3 — Attendance and Leave foundations

Status: **Implemented and Verified**

Started: 2026-07-28

Completed: 2026-07-28

Depends on: HR-0–HR-1.

### Scope

- Company work calendars, holidays, shifts, schedule assignments, and effective-dated attendance rules.
- Daily attendance records and states.
- Manual punches and corrections with approval/reason.
- Monthly attendance summaries.
- Leave types, policies, balances/ledger, requests, attachments, approvals, cancellation, and adjustments.
- Paid/unpaid leave classification and Payroll-impact flags.

### Acceptance criteria

- Overnight shifts, holidays, rest days, missing punches, joining/ending dates, and On Leave status are handled.
- Leave balance cannot be over-consumed except through an explicitly configured rule.
- Approved leave and attendance are company-isolated and audited.
- Raw/manual punch evidence is separate from derived daily attendance.
- Finalized monthly summaries are immutable inputs for Payroll.

### Actual implementation

- Added 13 company-owned tables for Work Calendars, Holidays, Work Shifts, Shift Assignments, Attendance Rules, Attendance Records, manual Attendance Punches, Attendance Corrections, monthly summaries, Leave Types, Leave Policies, the Leave Ledger, and Leave Requests.
- Added stable enums and audited models with same-company references, effective-date and overlap validation, soft deletion for configuration masters, and immutable punch/correction/ledger/finalized-summary evidence.
- Daily finalization resolves the effective Employment assignment and Attendance rule, supports overnight shifts, approved manual punches, holidays, rest days, approved paid/unpaid Leave, joining/ending dates, missing-punch treatment, late rounding, half-day/absence thresholds, overtime thresholds, evidence checksums, and finalizer evidence.
- Manual punch and correction decisions enforce independent maker-checker actors. Approved corrections retain before/proposed snapshots and update derived Attendance without rewriting punch evidence.
- Monthly summary generation aggregates finalized daily records and approved unpaid Leave into checksum-protected draft evidence; finalization rejects unfinished daily records and makes the summary an immutable future Payroll input.
- Leave submission snapshots the effective policy and paid/Payroll-impact flags, enforces configured attachment requirements, and follows Request → Manager Approve → HR Approve.
- HR approval locks and validates the append-only Leave balance, blocks over-consumption unless the effective policy explicitly allows a negative balance, and writes a source-linked consumption entry. Approved cancellation writes a separate reversal entry.
- Leave attachments reuse private, versioned Documents through a Leave Request relation manager and same-company Document scope.
- Added tenant-scoped Filament resources for all HR-3 configuration, evidence, summary, ledger, and request records. Workflow actions call the transactional domain actions; system decision/finalization fields are not directly editable.
- Added 13 policies and 89 HR-3 permission capabilities, including independent punch/correction approval, Attendance finalization, monthly generate/finalize, Leave submit/manager approval/HR approval/reject/cancel, and Leave balance adjustment.
- Added usable factories for every HR-3 model. No production calendars, rules, policies, balances, punches, Attendance, or Leave transactions were seeded.

### Migrations

- `2026_07_28_171915_create_work_calendars_table.php`
- `2026_07_28_171916_create_company_holidays_table.php`
- `2026_07_28_171917_create_work_shifts_table.php`
- `2026_07_28_171918_create_shift_assignments_table.php`
- `2026_07_28_171919_create_attendance_rules_table.php`
- `2026_07_28_171920_create_attendance_records_table.php`
- `2026_07_28_171921_create_attendance_punches_table.php`
- `2026_07_28_171922_create_attendance_corrections_table.php`
- `2026_07_28_171923_create_attendance_monthly_summaries_table.php`
- `2026_07_28_171924_create_leave_types_table.php`
- `2026_07_28_171925_create_leave_policies_table.php`
- `2026_07_28_171926_create_leave_ledger_entries_table.php`
- `2026_07_28_171927_create_leave_requests_table.php`

### Verification and limitations

- `php artisan migrate:fresh --seed --force` — passed; all migrations and deterministic production/reference seeders completed.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php -d memory_limit=512M artisan test --compact tests/Feature/AttendanceLeaveFoundationTest.php` — **11 tests, 62 assertions passed**.
- Broader HR, Payroll, Document, and Filament authorization regression — **65 tests, 327 assertions passed**.
- Route discovery exposed all HR-3 tenant resources; Livewire tests rendered the five workflow lists.
- Laravel Boost verified the SQLite Attendance/Leave schema, six companies, 691 permissions, and zero seeded Attendance Punch, Attendance Record, Leave Request, or Leave Ledger transactions.
- A full-suite command was attempted, but the PHPUnit child process retained the existing 128 MB limit and exhausted memory in MIME detection during `DocumentManagementAuthorizationTest`; the affected Document test files pass separately.
- HR-3 supports authorized manual punches only. Device registry, external user mappings, raw machine events, sync/import batches, deduplication, replay, and the normalized device boundary remain exclusively HR-4.
- No live Attendance thresholds, calendar, shift, Leave entitlement, accrual cadence, carry-forward rule, attachment mandate, or opening balance was invented. Companies must configure approved values before operational use.

Project-state update:

- Updated `docs/PROJECT_STATE.md` product direction, implemented Attendance/Leave behavior, tests, remaining plan scope, and phase status.
- Newly implemented behavior: HR-3 effective configuration, Attendance evidence/calculation/corrections/finalization, Leave ledger and approvals, attachments, tenant UI, permissions, and audit.
- Remaining planned behavior: HR-4 through HR-12.

Cross-plan update:

- Finance plan: Not changed because HR-3 creates no Payroll component, accounting posting, Treasury settlement, Asset workflow, or consolidated report behavior.

## HR-4 — Attendance-machine ingestion foundation

Status: **Implemented and Verified**

Started: 2026-07-28

Completed: 2026-07-28

Depends on: HR-3.

Machine model is not required for this phase.

### Scope

- Attendance Device registry and site/location assignment.
- Device-user-to-Employment mappings with effective dates.
- Import/sync batches, cursors, checksums, source metadata, and error reporting.
- Immutable raw punch events with deterministic deduplication.
- Normalized CSV/manual import contract for testing and initial operations.
- Replay-safe normalization into the HR-3 attendance calculation.
- Permissions for device configuration, sync/import, mapping, raw-punch viewing, reprocessing, and error resolution.
- Device health/last-sync status without storing credentials in ordinary fields.

### Acceptance criteria

- Re-importing the same file or events creates no duplicate punches.
- Unknown device users are quarantined for mapping and do not silently attach to Employees.
- Device/company/site/timezone boundaries are validated.
- Raw punches cannot be edited or normally deleted.
- Failed batches retain row-level errors and can be safely reprocessed.
- Manual/CSV and future device adapters feed the same ingestion service.

### Delivered implementation

- Added company-owned Attendance Device registry records with optional same-company Work Location, stable code/identifier, IANA timezone, transport capability, non-secret connection-profile reference, active flag, health state, cursors, last-sync/last-seen times, and safe error summary.
- Added effective-dated device-user-to-Employment mappings. The same device/external-user ranges cannot overlap, and all Device, Employment, Company, and Work Location references are company validated.
- Added immutable import/sync batches, immutable raw Attendance events, and immutable row-level import errors. Batch checksums and device-event fingerprints/source IDs enforce deterministic idempotency.
- Added the exact normalized CSV contract `device_code,external_user_id,punched_at_local,timezone,direction,source_event_id`, private retained source files, 10 MB limit, exact header checking, safe row metadata, outcome counts, and replay into a new attempt without rewriting prior evidence.
- Added a shared normalized ingestion service and vendor-neutral adapter contract. CSV and adapter events use the same validation, deduplication, quarantine, and HR-3 normalization path.
- Unknown external users are quarantined without an Employee/Employment guess. Creating the correct effective mapping and explicitly reprocessing the raw event safely produces the machine punch once.
- Missing direction and already-finalized daily Attendance records require review rather than unsafe inference or silent mutation.
- Normalized machine punches are approved source evidence linked one-to-one to the immutable raw event, have no manual creator, and create/reuse a draft HR-3 daily Attendance record. Overnight assignments attach early-morning events to the prior Attendance date.
- Added tenant-scoped Filament resources for Devices, Device User Mappings, Import Batches, Raw Events, and Row Errors. Batches/events/errors are read-only; authorized CSV import and replay/reprocess actions call the controlled domain actions. Raw safe payload visibility has a separate permission.
- Added 25 explicit HR-4 permission capabilities for device/mapping CRUD, device sync, CSV import/batch replay, raw-event/payload viewing and reprocessing, and row-error viewing. No fingerprint templates, device credentials, operational mappings, or punch data are seeded.

### Data and migration result

- Migrations:
  - `2026_07_28_183648_create_attendance_devices_table.php`
  - `2026_07_28_183649_create_attendance_device_user_mappings_table.php`
  - `2026_07_28_183650_create_attendance_import_batches_table.php`
  - `2026_07_28_183651_create_attendance_raw_events_table.php`
  - `2026_07_28_183652_create_attendance_import_row_errors_table.php`
  - `2026_07_28_183653_add_ingestion_source_to_attendance_punches_table.php`
- Fresh development migrate/seed completed successfully. The local database contains 6 companies, 716 unique permissions, 36 optional HR Document Types, and zero Attendance Devices, mappings, batches, raw events, or punches.

### Verification and limitations

- `vendor/bin/pint --dirty --format agent` — passed after formatting fixes.
- `php artisan route:list --except-vendor` — passed.
- `git diff --check` — passed.
- HR-4 focused verification: **8 tests, 38 assertions passed**.
- Broader affected HR/Document/Filament regression: **37 tests, 189 assertions passed**.
- Boost schema inspection verified company/device uniqueness, effective mapping lookup indexes, batch idempotency, raw fingerprint/source uniqueness, row-error lookup, foreign keys, and nullable machine-ingestion linkage.
- Focused tests cover same-file/event idempotency, quarantine and post-mapping replay, missing-direction review, failed-batch retained errors/replay, adapter reuse, immutable evidence, cross-company Device/Employment/Work Location rejection, overnight mapping, and tenant UI isolation.
- The actual machine make/model/protocol, credentials, network transport, SDK/API behavior, clock characteristics, and real-device UAT remain exclusively HR-5. HR-4 does not represent a live fingerprint-machine connection as implemented.

Project-state update:

- Updated `docs/PROJECT_STATE.md` implemented behavior, tests, local database state, current phase status, and remaining vendor-specific scope.
- Newly implemented behavior: Vendor-neutral Attendance Device registry, effective mappings, immutable ingestion evidence, deterministic deduplication, quarantine/replay, normalized private CSV import, adapter contract, and HR-3 punch normalization.
- Remaining planned behavior: HR-5 through HR-12.

Cross-plan update:

- Finance plan: Not changed because HR-4 only creates Attendance evidence; attendance-based Payroll consumption and Payroll accounting remain later controlled phases.

## HR-5 — Device-specific fingerprint-machine connector

Status: **Blocked** (partially unblocked — device identity confirmed 2026-07-30)

Requested: 2026-07-28

Blocked: 2026-07-28

Partially unblocked: 2026-07-30

Depends on: HR-4 and actual machine evidence.

### Confirmed device evidence (2026-07-30)

The user confirmed the attendance device on 2026-07-30:

- **Manufacturer and model:** ZKTeco K50 fingerprint attendance terminal.
- **Biometric methods:** Optical fingerprint, Password/PIN, optional RFID/ID card. Biometric enrollment and matching happen entirely on the device; the application receives only punch events (user ID, timestamp, direction) — no fingerprint templates are transmitted or stored.
- **Capacity:** 1,000–3,000 fingerprint templates; 80,000–100,000 transaction log records.
- **Communication:** TCP/IP (Ethernet, default port `4370`) and WiFi. USB-Host for manual flash-drive export.
- **ADMS/Cloud Server:** The user physically inspected the device menu (M/OK → Comm.) on 2026-07-30 and confirmed that the K50 unit does **not** have the Cloud Server Setting or ADMS option. Push mode is not available on this device.
- **Supported integration mode:** Direct TCP Pull only — the Laravel server (or a scheduled command) connects to the device IP on port `4370` and requests attendance logs. Requires same LAN, VPN, or port forwarding.
- **No built-in REST API.** The device uses ZKTeco's proprietary binary TCP protocol on port `4370`.
- **SDK:** ZKTeco Standalone SDK (`zkemkeeper.dll` for Windows/C#). For PHP/Laravel, community Packagist libraries exist: `jmrashed/zkteco` (PHP 8.1+, Laravel 11+), `0mithun/php-zkteco` (TCP/UDP with TCPMUX), `rats/zkteco` (older).
- **Data format (ATTLOG):** Each punch contains `dwEnrollNumber` (numeric user ID), timestamp (Y-m-d H:i:s), verification mode (fingerprint/password/card), and In/Out State (0=Check-in, 1=Check-out, 2=Break-out, 3=Break-in, 4=OT-in, 5=OT-out).
- **Time sync:** In TCP Pull mode, the connecting client can push server time to the device.
- **Credential model:** Optional 4-digit connection password on the device; must be stored encrypted in `AttendanceDevice` config and never logged.

### Field mapping to HR-4 ingestion contract

| ZKTeco K50 field | HR-4 `AttendanceEventData` field | Notes |
| --- | --- | --- |
| `dwEnrollNumber` | `external_user_id` | Numeric user ID enrolled on device |
| Device identifier | `device_code` | From `AttendanceDevice.device_identifier` |
| Timestamp (Y-m-d H:i:s) | `punched_at_local` | Device-local time |
| Device timezone | `timezone` | From `AttendanceDevice.timezone` |
| In/Out State 0 | `direction` = `In` | Check-in |
| In/Out State 1 | `direction` = `Out` | Check-out |
| In/Out State 2 | `direction` = `BreakOut` | Break start |
| In/Out State 3 | `direction` = `BreakIn` | Break end |
| Hash(device+user+timestamp) | `source_event_id` | Deterministic deduplication key |

The existing `AttendancePunchDirection` enum (`In`, `Out`, `BreakOut`, `BreakIn`) and `AttendanceDeviceTransport::TcpPull` value align with the K50 data model. No schema changes are required.

### Remaining blockers before moving to In Progress

The device identity and protocol are confirmed. The following operational evidence is still required:

1. **PHP library approval:** A ZKTeco PHP library (e.g., `jmrashed/zkteco` or `0mithun/php-zkteco`) must be approved as a new Composer dependency before implementation.
2. **Physical connectivity verification:** Connect the K50 to the development network and confirm its IP address, successful TCP connection on port `4370`, and basic SDK operations (read device info, read users, read attendance logs).
3. **Sample raw punch capture:** Capture one real pull from the connected device to verify exact field formats, In/Out State codes, timestamp precision, and any device-specific quirks.
4. **Device timezone confirmation:** Confirm the K50 is set to `Asia/Karachi` (UTC+5) and measure any observable clock drift.
5. **Device connection password:** Note the configured connection password (if any) and approve its encrypted storage location.
6. **Production network topology decision:** Determine whether the production Laravel server will be on the same LAN as devices, require VPN, or require port forwarding (UDP `4370`).

Once items 1–5 are resolved, change HR-D006 to confirmed, move HR-5 from **Blocked** to **In Progress**, and implement only the ZKTeco K50 TCP Pull adapter against the HR-4 `AttendanceDeviceAdapter` contract.

### Required external evidence (updated 2026-07-30)

- ~~Manufacturer, model, firmware, and serial number.~~ ✅ Confirmed: ZKTeco K50.
- ~~Vendor documentation and supported API/SDK/protocol/export formats.~~ ✅ Confirmed: TCP/IP port 4370, PHP Packagist libraries, ATTLOG format.
- Network/deployment diagram: LAN confirmed for development; production topology pending.
- Sample device users and redacted raw punch data: pending physical connectivity.
- Clock/timezone behavior: assumed Asia/Karachi, pending device confirmation.
- Credential/security model: device connection password to be confirmed.

### Scope

- Implement the smallest supported adapter for the selected machine.
- Secure connection/credential configuration.
- Incremental sync cursor, retry/backoff, timeout, offline recovery, and health monitoring.
- Vendor event mapping into the HR-4 normalized ingestion contract.
- Controlled manual resync and safe historical backfill.
- Connector contract tests using recorded safe fixtures and integration/UAT against the actual device.

### Acceptance criteria

- No duplicate or missing events across retries and restarts.
- Offline periods recover without overwriting raw evidence.
- Device clock drift and timezone behavior are visible and controlled.
- Unsupported/ambiguous vendor events are quarantined.
- Credentials and biometric templates never enter logs or exports.
- Actual machine UAT reconciles device event counts to imported raw punches and daily attendance.

## HR-6 — Employee Loans and Advances

Status: **Implemented and Verified**

Started: 2026-07-28

Completed: 2026-07-28

Depends on: HR-0–HR-1 and implemented Finance ledger/Treasury/Payroll foundations.

### Scope

- Separate Employee Loan and Employee Advance records.
- Request, review, approval, rejection, disbursement, cancellation, rescheduling, settlement, waiver/write-off if approved, and closure.
- Loan installment and Advance recovery schedules.
- Treasury disbursement and reversal.
- Employee Loan and Employee Advance control-account mappings.
- Payroll due-installment source records and balance reconciliation.
- Company/Employment subledger and documents.

### Acceptance criteria

- Disbursement and recovery reconcile with Posted/Reversed Journals.
- Payroll cannot recover more than the approved due/open amount.
- Partial recovery, early payoff, and reversal preserve schedule history.
- A maker cannot approve their own Loan/Advance.
- Separation and Final Settlement can retrieve an exact outstanding balance.

### Implemented result

- Added company/Employment-scoped Loan and Advance requests with separate types, atomic references, explicit PKR principal/finance charge/total terms, maker-checker submission/approval/rejection, cancellation, and private related documents.
- Added versioned installment schedules and an immutable financing transaction ledger. Recovery is allocated deterministically by due date, finance charge before principal, and supports partial recovery, exact early payoff, approved principal waiver, rescheduling with superseded history, and reversal evidence.
- Linked disbursements and direct recoveries to the existing Treasury workflow. Posted Payments debit the mapped Employee Advances receivable and credit Cash/Bank; posted Receipts reverse that receivable; Treasury reversals restore the financing balance and schedule state without deleting history.
- Added a posted waiver Journal that debits a selected same-company Expense and credits the mapped Employee Advances receivable. Open Financial Period, account, actor, reason, and source links are preserved.
- Exposed a due-as-of approved-installment amount for HR-7 Payroll consumption and exact outstanding balance for separation, clearance, and Final Settlement. Payroll recovery application supports an idempotent source transaction but remains invoked by HR-7, which owns Payroll calculation/snapshot integration.
- Added tenant Filament management, read-only schedule/subledger relation managers, policies, 19 permissions, factories, five migrations, audit events, and cross-company/immutability controls.
- Finance-bearing Loan schedules can be explicitly recorded and approved, but disbursement and finance-charge waiver are configuration-gated until a dedicated interest-income/control-account design is approved. Zero-charge Loans and Advances are operational through the existing Employee Advances mapping; no accounting treatment was invented.
- Verification passed after fresh migration/seed: 5 HR-6 tests with 32 assertions and a broader 44-test/243-assertion HR, Payroll, Treasury, Filament, document, Attendance, and Leave regression; Pint, route discovery, migration status, schema/FK/index inspection, diff validation, and idempotent permission seeding passed. The clean database has 6 companies, 36 optional HR Document Types, 735 unique permissions, and zero Employment, financing, Treasury, Payroll, or Journal transactions.

## HR-7 — Payroll calculation and accounting integration

Status: **Implemented and Verified**

Started: 2026-07-29

Completed: 2026-07-29

Depends on: HR-3–HR-6.

### Scope

- Traceable Payroll Entry components and source snapshots.
- Attendance, unpaid leave, late, and half-day calculations.
- Loan installments and Advance recovery.
- Bonus and Incentive components.
- Effective-dated configurable calculation rules.
- Calculation preview, exception list, regeneration, review, approval, posting, reversal, and later adjustment.
- Existing Project/Site/Cost Center allocation.
- Payroll mapping extensions and report reconciliation.
- Salary Register, Payroll Summary, and Project-wise Payroll foundations.

### Acceptance criteria

- Every non-manual component links to its source and calculation evidence.
- Approved/finalized attendance changes cannot silently mutate submitted Payroll.
- Payroll generation is deterministic and idempotent for the same source revision.
- Debit/credit totals reconcile with Payroll components and settlement.
- Existing Salary Payable and Treasury settlement remain intact.
- Locked Payroll stays immutable.

### Actual implementation

- Added effective-dated, non-overlapping company Payroll Calculation Rules for finalized-Attendance requirements, allowance proration, absence/unpaid-Leave/half-day factors, and minute-based late deductions. No production formula values are seeded.
- Added maker-checker Bonus and Incentive source records with independent approval, encrypted values, source references/checksums, project context, immutable approved terms, tenant UI, policy capabilities, and Activitylog evidence.
- Added immutable Payroll Entry Components for salary, allowances, Bonus/Incentive, Attendance/Leave deductions, and Loan/Advance recovery. Each component preserves its source morph, quantity, rate, amount, mapping component, checksum, evidence snapshot, and deterministic idempotency key.
- Payroll generation now consumes the exact effective compensation/rule, exact-period finalized Attendance summary when required, approved variable earnings, and active due-as-of financing schedules in one transaction. Regeneration replaces only editable draft/rejected evidence and reproduces the same source checksum and component keys for an unchanged source revision.
- Payroll Entry aggregate fields remain encrypted compatibility/report snapshots, but submission validates every source-backed total against immutable components. Direct editing of Attendance, Bonus/Incentive, and financing aggregates is disabled; project allocation, payment split, and an explicitly manual Other Deduction remain controlled draft inputs.
- Loan/Advance recovery is not applied at preview time. Posting creates the existing balanced Payroll Journal and then records idempotent Payroll-recovery subledger transactions against approved installments. Payroll reversal creates linked financing reversals and restores installment/outstanding state without deleting history.
- Extended Payroll accounting mappings and posting lines for Bonus, Incentive, unpaid Leave, late, and half-day deductions while preserving Basic/Allowance/Absence, Salary Payable, Employee Advances, Project allocation, Treasury settlement, reversal/reposting, and locked-run behavior.
- Added tenant Calculation Rule and Bonus/Incentive resources, read-only Payroll component/source evidence, calculation revision/checksum displays, source-backed Payroll Entry review fields, and Salary Register, Payroll Summary, Project-wise Payroll, Payroll/GL/settlement reconciliation, and Employee Advance subledger sections.
- Added 19 idempotently provisioned HR-7 permissions, policies, factories, audit events, six migrations, and focused deterministic calculation/accounting/reversal tests.

### Migrations

- `2026_07_28_193228_create_payroll_calculation_rules_table.php`
- `2026_07_28_193229_create_payroll_variable_components_table.php`
- `2026_07_28_193230_create_payroll_entry_components_table.php`
- `2026_07_28_193231_add_hr7_calculation_fields_to_attendance_monthly_summaries_table.php`
- `2026_07_28_193232_add_hr7_calculation_fields_to_payroll_runs_table.php`
- `2026_07_28_193233_add_hr7_component_totals_to_payroll_entries_table.php`

### Verification and limitations

- `php artisan migrate:fresh --seed --no-interaction` and two additional `php artisan db:seed --no-interaction` runs passed.
- `vendor/bin/pint --dirty --format agent` and `php artisan route:list --except-vendor` passed.
- HR-7 plus existing Payroll workflow/accounting tests passed: **11 tests, 64 assertions**.
- Broader HR-1–HR-6 and Filament authorization regression passed: **47 tests, 249 assertions**.
- Laravel Boost verified the HR-7 schema/FKs/indexes, 6 companies, 754 unique permissions, and zero seeded Payroll Rule, Bonus/Incentive, or Payroll Component transactions.
- A complete-suite attempt with `php -d memory_limit=512M artisan test --compact` reached the existing PHPUnit child-process 128 MB limit in MIME detection during `DocumentManagementTest`; the affected HR/Document and Payroll suites pass in focused batches.
- Draft generation is the calculation preview and transactional validation failures are the current exception feedback. Separate persisted exception queues and supplemental off-cycle adjustment runs are not introduced; approved posted corrections use the existing reversal, regenerate, approve, and repost chain. Final Settlement remains HR-10.
- No statutory/business Payroll formula, Attendance threshold, Bonus/Incentive value, Loan/Advance term, account mapping, or operational transaction was invented or seeded.

Project-state update:

- Updated `docs/PROJECT_STATE.md` with HR-7 source-backed calculation, accounting, reporting, permissions, schema, tests, and status.
- Newly implemented behavior: finalized Attendance/Leave consumption, approved Bonus/Incentive sources, due financing recovery, deterministic evidence snapshots, extended GL posting/reversal, and company Salary/Payroll/Project report foundations.
- Remaining planned behavior: HR-8 through HR-12; HR-5 remains blocked on actual machine evidence.

Cross-plan update:

- Updated Finance Phase 10’s implemented result and Progress Ledger with HR-7 component-level Payroll posting, financing recovery/reversal, extended mappings, and reconciliation evidence.

## HR-8 — Performance, discipline, promotion, transfer, and separation

Status: **Implemented and Verified**

Started: 2026-07-29

Completed: 2026-07-29

Depends on: HR-1–HR-3.

### Scope

- KPI libraries, appraisal cycles, goals, scoring, review, acknowledgement, and approved outcomes.
- Warning Letter templates, levels, response, acknowledgement, and closure.
- Promotion and transfer requests with effective-dated Department, Designation, manager, Work Location, Employment Category, and compensation changes.
- Resignation request, notice, proposed/approved last working date, handover, approval, withdrawal, and acceptance.
- Termination workflow with reason, authority, effective date, protected notes, and documents.

### Acceptance criteria

- Promotion/transfer does not overwrite historical Employment evidence.
- Compensation changes reuse effective-dated compensation approval.
- Separation dates consistently affect Attendance, Leave, Payroll, system access decisions, assets, and settlement.
- Sensitive warning/termination access is separately authorized and audited.

### Delivered implementation

- Added company KPI libraries and Appraisal Cycles with explicit configured score bounds; no business scoring scale or KPI was seeded.
- Appraisals retain Employee/Reviewer Employments, encrypted goals/scores/comments/outcome, exact KPI-weight evidence, a deterministic submission checksum, and Draft → Submitted → Reviewed → Approved → Acknowledged / Rejected workflow.
- Submission requires an active cycle and weights totaling exactly 100. Review and approval enforce independent actors, configured score bounds, immutable submitted evidence, actor/time fields, row locks, transactions, policies, and Activitylog events.
- Added configurable Warning Letter Templates and sensitive Employee Warnings with encrypted body/response/closure notes, independent issuance, response, acknowledgement, closure, private Document attachments, immutable issued evidence, and dedicated sensitive-view permission.
- Added effective-dated Promotion/Transfer requests covering Department, Designation, reporting Employment, Work Location, Employment Category, and an optional separately approved same-date Compensation record.
- Movement submission snapshots current and target values. Approval/application updates the live Employment once while recording an immutable effective-dated Employment Change with before/after evidence instead of rewriting historical records.
- Added Resignation and Termination workflows with request/proposed/approved dates, notice metadata, encrypted reason/authority/protected/handover notes, resignation acceptance/withdrawal, rejection, independent approval, and private attachments.
- Approved separation updates the shared Employment ending date and Resigned/Terminated status. Existing Attendance finalization and Payroll generation consume this lifecycle boundary; Leave requests outside it are rejected and separation approval blocks unresolved Leave extending beyond it.
- A linked application User is not silently disabled. Separation creates an explicit pending access-review state with a controlled completion action; Employment ending date is the source boundary later HR-9 asset clearance and HR-10 Final Settlement consume.
- Added tenant-scoped Filament resources/forms/tables, appraisal-item and Document relation managers, operational workflow actions, eight policies, factories, Company/Employment relations, 69 permissions, and sensitive-data boundaries.

### Data and migration result

- Migrations:
  - `2026_07_28_195513_create_performance_kpis_table.php`
  - `2026_07_28_195514_create_appraisal_cycles_table.php`
  - `2026_07_28_195515_create_performance_appraisals_table.php`
  - `2026_07_28_195516_create_performance_appraisal_items_table.php`
  - `2026_07_28_195517_create_warning_letter_templates_table.php`
  - `2026_07_28_195518_create_employee_warnings_table.php`
  - `2026_07_28_195519_create_employment_movement_requests_table.php`
  - `2026_07_28_195520_create_employment_separations_table.php`
- Fresh migration and production-data seeding passed. Permission registry now contains 823 unique permissions.
- No KPI, cycle, appraisal, warning, movement, or separation operational rows were fabricated; company configuration and live workflows remain business-driven.

### Verification and limitations

- `vendor/bin/pint --dirty --format agent`, `php artisan route:list --except-vendor`, and `git diff --check` passed.
- HR-8 focused suite: **5 tests, 35 assertions passed** across appraisal maker-checker/scoring, warning evidence, transfer history, resignation/access review, tenant isolation, and termination authority.
- Broader HR-1 through HR-8/Payroll regression: **50 tests, 268 assertions passed**. The final affected Attendance/Leave/Payroll/HR-8 rerun passed **20 tests, 128 assertions**.
- Boost verified live appraisal schema foreign keys/indexes plus 6 companies, 823 permissions, and zero HR-8 operational rows after fresh seed.
- HR-8 does not invent appraisal scales, warning levels, resignation rules, notice policy, or termination authority. Companies must configure/provide them.
- Actual User suspension remains a reviewed administrative decision. Asset return/clearance and Final Settlement calculations remain HR-9 and HR-10; HR-8 exposes the approved lifecycle boundary they must consume.
- Finance plan: not changed because HR-8 creates no Journal, Treasury, Asset, Payroll-posting, or consolidated-report behavior.

## HR-9 — Employee asset custody and clearance

Status: **Implemented and Verified**

Started: 2026-07-29

Completed: 2026-07-29

Depends on: HR-8 and implemented Fixed Asset foundation.

### Scope

- Fixed Asset issuance to Employment, employee acknowledgement, condition, accessories, location, and issued-at evidence.
- Transfer, return, damage/loss, recovery recommendation, and closure.
- Non-capital item boundary decision.
- Company-configurable clearance checklist for HR, manager, IT, Administration, Store, Finance, Loans/Advances, and Assets.
- Department-level clearance approvals and blocking exceptions.

### Acceptance criteria

- One asset cannot be concurrently issued to multiple Employments.
- Transfer/return history is immutable and company-scoped.
- Clearance exposes outstanding assets, Loans, Advances, leave/handover, and other configured obligations.
- Recovery recommendations do not post accounting until approved in Final Settlement or another authorized financial workflow.

### Delivered implementation

- Added company-scoped Fixed Asset custody drafts with Employment, issue/due dates, condition, encrypted accessories/notes, issued location, and independent issue/employee acknowledgement evidence.
- Asset issue locks the Fixed Asset and rejects any other live custody. Transfer atomically closes the prior custody, creates a new issued custody, updates the existing Fixed Asset custodian, and records immutable `AssetTransfer` plus custody-event evidence.
- Added controlled return request/independent acceptance, physical condition evidence, damage/loss reporting, exception resolution, and encrypted recovery recommendations. These workflows do not create Journals, Treasury transactions, Payroll deductions, or Asset accounting changes.
- Added append-only encrypted custody-event snapshots; issued terms, transfer records, and event rows become immutable while drafts remain editable.
- Added approved-separation-linked Employee Clearance with one checklist per separation, deterministic source keys/checksum, and refreshable obligations for live assets, outstanding Loans, outstanding Advances, Leave review, handover review, and company-configured checklist templates.
- Added configurable HR, Manager, IT, Administration, Store, Finance, Loans, and Assets areas with independent permissions. Mandatory pending/blocked items prevent completion; waiver and recovery recommendation require additional explicit permissions.
- Recovery recommendations resolve the operational checklist only as non-posting evidence for HR-10 or another authorized financial workflow. Independent submission/completion, encrypted decisions, Activitylog audit, and company isolation are enforced.
- Added private Document tabs for asset custody and clearance, tenant Filament resources for custody/checklist/clearance, read-only event evidence, departmental item decisions, policies, factories, relations, and 42 permissions.
- Preserved the Finance Phase 11 capitalization, depreciation, disposal, reconciliation, and GL behavior. Capital Fixed Assets are supported; consumable/non-capital issuance remains explicitly deferred under HR-D014.

### Data and migration result

- Migrations:
  - `2026_07_28_201803_create_employee_asset_custodies_table.php`
  - `2026_07_28_201804_create_employee_asset_custody_events_table.php`
  - `2026_07_28_201805_create_clearance_checklist_templates_table.php`
  - `2026_07_28_201806_create_employee_clearances_table.php`
  - `2026_07_28_201807_create_employee_clearance_items_table.php`
- Fresh migration and production-data seeding passed. Repeated permission seeding remained idempotent with 865 unique permissions.
- No custody, checklist, clearance, Journal, or Treasury operational rows were fabricated.

### Verification and limitations

- `vendor/bin/pint --dirty --format agent`, route compilation, fresh migrate/seed, repeated permission seed, and Boost schema/index/foreign-key checks passed.
- HR-9 focused suite passed **4 tests with 25 assertions** covering single-custodian enforcement, draft/issued immutability, employee acknowledgement, transfer/return history, damage/loss recovery recommendation, clearance source coverage, departmental permission denial, tenant UI, and zero financial posting.
- Broader affected HR-8, Fixed Asset, Loan/Advance, and Document regression passed **31 tests with 170 assertions**.
- The existing Fixed Asset register is the source only for capital items. Consumable issuance and live biometric-device integration are outside HR-9 and remain controlled by HR-D014 and HR-5 respectively.
- Final monetary recovery, leave encashment, notice recovery, and accounting remain HR-10 decisions; HR-9 stores recommendations and evidence only.

## HR-10 — Final Settlement

Status: **Implemented and Verified**

Started: 2026-07-29

Completed: 2026-07-29

Depends on: HR-6–HR-9.

### Scope

- Separation-linked Final Settlement preparation.
- Salary through last working date.
- Approved benefits, leave encashment, notice pay/recovery, bonus/incentive, gratuity or other configured benefits.
- Loan, Advance, asset, and other approved recoveries.
- Net payable or receivable.
- Review, approval, posting, payment/receipt, reversal, documents, and settlement letter.
- Dedicated accounting mappings where the existing Payroll mappings are insufficient.

### Acceptance criteria

- Settlement inputs reconcile to approved source records and cutoff dates.
- No unreturned asset or open mandatory clearance item is silently ignored.
- GL posting is balanced, idempotent, period-aware, and reversible.
- Treasury payment/receipt cannot exceed the posted open amount.
- Final Settlement Report reconciles operational totals, GL, and Treasury.

### Actual implementation

- Added one company/Employment-scoped Final Settlement per approved separation and completed clearance, with the approved last-working date as the immutable cutoff. Draft refresh synchronizes exact active Loan/Advance balances and approved clearance recovery recommendations; submission refuses changed or missing sources.
- Added source-backed Salary, Leave Encashment, Notice Pay/Recovery, Bonus, Incentive, Gratuity, other benefit/recovery, Loan/Advance, and Asset recovery components. Each line retains encrypted quantity/rate/amount/evidence, an approved source reference, checksum, posting account, and idempotency key. No formula or live amount is seeded.
- Added separate preparer, reviewer, approver, and Finance poster boundaries; rejection/resubmission, post, Treasury settle, Treasury reverse, and Final Settlement reverse retain actor/time/reason evidence and Activitylog history.
- Added dedicated company component/account mappings where existing controls are insufficient. Loan/Advance recovery and net employee receivables reuse the controlled Employee Advances mapping; net employee payables reuse Salary Payable.
- Posting creates one balanced, open-period, idempotent Payroll voucher. Earnings debit mapped accounts, recoveries credit mapped accounts, and the net payable/receivable balances Salary Payable or Employee Advances. Final Settlement Loan/Advance recoveries update the immutable financing subledger only after posting and are restored by linked reversal.
- Treasury Payments and Receipts allocate polymorphically to posted Final Settlements, enforce direction and company/Employment scope, reject over-settlement, change fully settled records to Settled, and restore Posted status when Treasury is reversed.
- Added tenant Final Settlement and account-mapping resources, source-evidence lines, related private Documents, actions from approved separations, amount-sensitive permissions, printable approved settlement-letter snapshots, and a tenant Final Settlement/GL/Treasury reconciliation report.
- Added three migrations, three factories, three policies, 22 permissions, enums, relations, reporting, and Treasury/Journal/Document integration.

### Migrations

- `2026_07_28_203826_create_final_settlements_table.php`
- `2026_07_28_203827_create_final_settlement_lines_table.php`
- `2026_07_28_203828_create_final_settlement_account_mappings_table.php`

### Verification and limitations

- `php artisan migrate:fresh --seed --no-interaction`, two additional idempotent seed runs, `vendor/bin/pint --dirty --format agent`, route compilation with 359 application routes, syntax checks, and `git diff --check` passed.
- HR-10 focused coverage passed **4 tests with 40 assertions** for clearance/source refresh, independent actors, posting, financing recovery, pending-Treasury reversal blocking, over-allocation denial, Treasury settlement/reversal, zero-balance closure/reversal, Final Settlement reversal, letter/report reconciliation, and tenant isolation.
- Affected HR-6/HR-9, Payroll, Treasury, and Document regression passed **35 tests with 228 assertions**; together with HR-10 this is **39 tests with 268 assertions**.
- Boost verified all three Final Settlement tables, foreign keys, unique/idempotency constraints, and indexes. Repeated seeding leaves 6 companies, 36 optional HR Document Types, 887 unique permissions, and zero fabricated Final Settlement, Journal, or Treasury transactions.
- The settlement letter is an authorized printable HTML snapshot; formal PDF/e-sign output remains outside HR-10. HR-11 owns consolidated/export reporting.
- Live Salary, Leave Encashment, Notice, Gratuity, Bonus/Incentive, benefit, and recovery rules/amounts remain configuration/business inputs under HR-D013. The system requires explicit approved evidence and mappings instead of inventing formulas.

## HR-11 — HR reports, dashboard, exports, and group consolidation

Status: **Implemented and Verified**

Started: 2026-07-29

Completed: 2026-07-29

Depends on: Implemented source phases for each report.

### Required reports

- Employee List.
- Department-wise Employees.
- Designation-wise Employees.
- Salary Register.
- Payroll Summary.
- Project-wise Payroll.
- Employee Loan Report.
- Employee Advance Report.
- Increment History.
- Attendance Summary.
- Leave Summary.
- Final Settlement Report.
- HR Dashboard.

### Group reporting

- Parent/descendant headcount and status.
- Company-comparative attendance, leave, payroll cost, Loans/Advances, joiners, and exits.
- Explicit duplicate-person versus Employment-count semantics.
- Authorized group scope only; no synthetic transaction company.

### Acceptance criteria

- Every report is company-scoped by default.
- Group reports verify hierarchy and user access for included companies.
- Sensitive salary, bank, medical, identity, warning, and termination data remains separately protected.
- Exports are authorized, private, auditable, and reconcile to screen totals.
- Dashboard aggregates use indexed queries and realistic-volume tests.

### Implementation completion record

Implementation completed:
- Date: 2026-07-29
- Implemented by: Codex
- Status changed from: In Progress
- Status changed to: Implemented and Verified

Actual implementation:
- Migrations/tables: Added one reporting-index migration covering company/date, Department, Designation, approved Compensation, and Payroll Entry lookup paths. No reporting snapshot or synthetic consolidation table was introduced.
- Data migration/backfill: None. Reports derive from live company-owned source records, and no HR transaction was fabricated.
- Models/enums: No new persistent model or enum was required.
- Actions/services/connectors: Added company and group HR report services with server-side permission and tenant-access checks, bounded eager loading, encrypted-value handling, and explicit unique-person versus Employment semantics. Added one reusable streamed HR exporter.
- Filament resources/pages/relation managers: Added tenant HR Reports & Dashboard and Group HR pages. Enhanced the existing Payroll and Final Settlement report pages with protected exports.
- Policies/permissions: Added `View:HrReports`, `Export:HrReports`, `View:GroupHrReports`, `Export:GroupHrReports`, `Export:PayrollReports`, and `Export:FinalSettlementReport`. Existing Payroll, Compensation, Financing, Attendance, Leave, and Final Settlement amount/view permissions remain additional gates for sensitive sections and exports.
- Seeders/provisioning: Extended the idempotent permission catalog. Two consecutive seed runs retained six companies and 893 unique permissions.
- Documents/audit: Every CSV/XLSX export records actor, company/root scope, report, format, row count, and company/group scope in Activitylog. Responses are private/no-store; XLSX uses a request-local temporary file that is deleted after streaming.
- Reports/exports: Delivered Employee List, Department-wise Employees, Designation-wise Employees, Employee Loan, Employee Advance, Increment History, Attendance Summary, Leave Summary, and HR Dashboard in the new company page. Existing Salary Register, Payroll Summary, Project-wise Payroll, and Final Settlement reports were retained and enhanced. Every listed report supports authorized CSV and XLSX export.
- Accounting/Treasury/Asset integration: Existing Payroll/GL/Treasury, Financing, Project allocation, Final Settlement, and Fixed Asset sources remain authoritative; HR-11 is read-only and creates no accounting or operational transaction.

Group reporting:
- Starts from the real current parent/root company and recursively includes only that active hierarchy.
- Requires access to every included company and rejects partial group scope.
- Reports group-unique people separately from per-company Employment counts.
- Compares company headcount/status, joiners/exits, Attendance, approved Leave, Payroll cost, and Loan/Advance outstanding balances.
- Payroll and Financing amounts remain null/hidden unless their existing sensitive permissions are also present.
- No fake “All Companies” tenant, merged Employment, or synthetic group transaction record is created.

Verification:
- Focused tests: `php artisan test --compact tests/Feature/HrReportingTest.php` — 4 passed, 33 assertions.
- Broader tests: 72 affected HR, Attendance, Leave, Financing, Payroll, Final Settlement, Filament, consolidation, and accounting tests passed with 434 assertions using the documented 512 MB PHPUnit invocation.
- Formatting/static checks: `vendor/bin/pint --dirty --format agent`, PHP syntax, 361-route discovery, and `git diff --check` passed.
- Schema/database verification: The HR-11 migration is applied. Boost confirmed the new indexes and indexed query plans for joiners, Attendance, Leave, and Compensation. Two seed runs produced 893 unique permissions including all six HR-11 permissions; operational HR/Payroll/Final Settlement rows remain zero.
- Manual/device/UAT verification: Livewire page rendering and private CSV/XLSX response generation were verified in feature tests. Actual device UAT remains correctly blocked in HR-5 and is unrelated to HR-11.

Decisions and deviations:
- Decisions resolved: HR-D015 is implemented as authorized real-hierarchy reporting with both unique-person and Employment counts.
- Differences from planned design: Report export uses the already installed OpenSpout dependency for XLSX and Laravel streamed responses for CSV instead of adding a new export package or persisted export table.
- Reason: This meets the approved CSV/XLSX direction while keeping files request-private, memory-bounded, and auditable.
- Known limitations: The default group comparison period is year-to-date in the initial page; saved/custom report filters and scheduled delivery remain future enhancements. Formal PDF remains reserved for formal document workflows.
- Follow-up: HR-12 owns final UAT, end-to-end hardening, and production rollout. HR-5 remains blocked until real fingerprint-machine evidence arrives.

Project-state update:
- Sections changed in `docs/PROJECT_STATE.md`: product direction, implemented HR reporting, tests, remaining scope, HR status index, and next-work record.
- Newly implemented behavior: Company HR dashboard/report catalog, protected CSV/XLSX exports, and authorized group HR comparison.
- Still planned/blocked at HR-11 completion: HR-12 had not yet started; HR-5 remains Blocked on actual device evidence. HR-12 is now Implemented and Verified as recorded below.
- Cross-plan update: Not applicable. HR-11 reads existing Finance/Payroll sources without changing the Finance plan’s ledger, consolidation, or posting behavior.

## HR-12 — Migration, UAT, security, performance, and rollout hardening

Status: **Implemented and Verified**

Started: 2026-07-29

Completed: 2026-07-29

Depends on: HR-1–HR-11.

### Scope

- Controlled imports for Employees/Employments, Department hierarchy, document metadata, Leave balances, Loans/Advances, asset custody, and historical Attendance where approved.
- Attendance-machine historical backfill after HR-5.
- Dry-run, row-level validation, checksums, reconciliation, and rollback/reversal strategy.
- End-to-end company isolation, authorization, maker-checker, audit, concurrency, and sensitive-data testing.
- Payroll/GL/Treasury/Loan/Advance/Final Settlement reconciliation.
- Performance and index review using realistic workforce and punch volumes.
- UAT scenarios for one pilot company.
- Production rollout and monitoring checklist; operational runbook only if explicitly requested.

### Acceptance criteria

- Imported totals and record counts reconcile to approved sources.
- No cross-company mapping or duplicate Employee code/punch is accepted.
- Pilot Payroll reconciles Attendance through GL and Treasury.
- Backup/recovery and device-offline procedures are tested at the deployment level where available.
- All earlier phases are **Implemented and Verified** with no planned scope represented as implemented.

### Completion record

Implementation completed:
- Date: 2026-07-29.
- Implemented by: Codex.
- Status changed from: In Progress.
- Status changed to: Implemented and Verified.

Actual implementation:
- Migrations/tables: Added `hr_data_migrations` and immutable `hr_data_migration_rows` with company/type/source idempotency, checksums, status/count/totals reconciliation, independent actor evidence, imported-record references, rollback evidence, foreign keys, and lookup indexes.
- Data migration/backfill: Implemented exact-header controlled CSV adapters for Department hierarchy, Employees/Employments, HR document metadata, Leave opening balances, approved Loan/Advance schedules, issued Fixed Asset custody, and finalized historical monthly Attendance summaries. No operational data was fabricated or seeded.
- Models/enums: Added typed migration/status enums plus audited `HrDataMigration` and immutable row-evidence models, relationships, factories, and company ownership.
- Actions/services/connectors: Added independent prepare/dry-run, validate, import, and controlled rollback actions with private 10 MB/10,000-row sources, SHA-256 evidence, tenant-safe reference resolution, row-level errors, same-source idempotency, transaction locks, deterministic imported checksums, downstream-use guards, and source/count/amount reconciliation.
- Filament resources/pages/relation managers: Added tenant-scoped HR Data Migrations list/view workflow with private upload, validation/import/rollback actions, row evidence and totals, plus an HR Operational Readiness page for configuration, reconciliation, rollout, and device-continuity gates.
- Policies/permissions: Added company-scoped migration policy and eight explicit view/create/validate/import/rollback/readiness/recovery capabilities while preserving independent actors and existing sensitive-data permissions.
- Seeders/provisioning: Extended the idempotent Foundation permission seeder only; no migration batches, operational policies, balances, devices, or transactions are provisioned.
- Documents/audit: Source CSV remains on private local storage with checksum verification. Actor, decision, import, rollback reason, row evidence, and model changes remain auditable without exposing sensitive source rows.
- Reports/exports: Added an authorized HR recovery manifest across 24 HR tables with per-row and aggregate hashes and a company operational-readiness report covering configuration, integrity, migration, reconciliation, performance, device fallback, and rollout gates.
- Accounting/Treasury/Asset integration: Historical financing and custody adapters preserve approved source state without inventing disbursement/GL activity. Pilot UAT verified Attendance-derived Payroll through balanced GL and Treasury settlement. Payroll reconciliation now compares the Payroll expense basis to net expense-account movement, correctly accounting for attendance-deduction credits.

Verification:
- Focused tests: `php -d memory_limit=512M artisan test --compact tests/Feature/HrMigrationAndHardeningTest.php tests/Feature/PayrollAccountingWorkflowTest.php tests/Feature/PayrollCalculationIntegrationTest.php` — 11 passed, 100 assertions.
- Broader tests: `php -d memory_limit=512M vendor/bin/phpunit --colors=never` — full suite passed, 263 tests and 1,429 assertions.
- Formatting/static checks: `vendor/bin/pint --dirty --format agent`, route discovery for the two migration routes and readiness page, PHP loading through the full suite, and `git diff --check` passed.
- Schema/database verification: Both HR-12 migrations apply cleanly. Repeated permission seeding remains idempotent with 901 unique permissions and 36 optional HR document-type configurations; the development baseline retains zero HR migration, Employment, Payroll, Attendance-summary, financing, Final Settlement, Journal, or Treasury operational rows.
- Manual/device/UAT verification: Feature tests render the migration and readiness UI, exercise every adapter, reject duplicates/cross-company references, verify recovery mismatch detection after mutation, hold a 250-Employment readiness query budget, validate normalized CSV device-offline continuity, and complete the pilot Attendance → Payroll → GL → Treasury reconciliation.

Decisions and deviations:
- Decisions resolved: HR-D016 is implemented for the current pre-production lifecycle through controlled migration/recovery tooling and an explicit rollout gate.
- Differences from planned design: Historical Attendance imports finalized monthly summaries only; historical raw fingerprint-machine events are not accepted. Document imports create metadata without pretending a source file/version exists. Approved historical financing schedules do not invent disbursement or opening GL entries.
- Reason: These boundaries preserve truthful source evidence and accounting integrity while the actual device and approved live financial opening sources remain unavailable.
- Known limitations: HR-5 remains externally blocked on the actual fingerprint machine, protocol/SDK/export, topology, safe fixtures, clock behavior, and credentials. Consequently, actual-device synchronization/backfill and infrastructure-level backup/restore execution cannot be represented as tested. The implemented normalized CSV/manual path is the operational offline fallback.
- Follow-up: Supply HR-D006 evidence to reopen HR-5. Before first deployment, onboard approved production sources/configuration and execute the environment-specific backup/restore and monitoring runbook; create a separate operational runbook document only if explicitly requested.

Project-state update:
- Sections changed in `docs/PROJECT_STATE.md`: product direction, implemented HR migration/hardening, tests, remaining external/operational scope, and HR status index.
- Newly implemented behavior: Controlled HR imports and rollback, migration evidence/reconciliation, recovery manifest, readiness/security/performance gates, and pilot Payroll end-to-end reconciliation.
- Remaining planned behavior: No remaining application-source HR-12 behavior. HR-5 actual-device integration remains Blocked, and live source onboarding plus deployment-environment recovery execution remain operational inputs.
- Test baseline/state changes: Full verified suite is 263 tests with 1,429 assertions; fresh deterministic baseline has 6 companies, 901 permissions, 36 optional HR document types, and zero fabricated HR/Payroll/accounting transactions.

Cross-plan update:
- Updated Finance Phase 10’s Progress Ledger with the corrected net-expense Payroll reconciliation basis and the HR-12 Attendance → Payroll → GL → Treasury pilot evidence. Posting behavior did not change.

## Decision Register

Update decisions in place. Do not delete history; mark superseded entries and reference replacements.

| ID | Decision | Status | Blocks | Current direction |
| --- | --- | --- | --- | --- |
| HR-D001 | Employee code format and reset rules | Confirmed default 2026-07-28 | None | Company-specific atomic `EMP-00001`; configurable prefix/padding, no year/reset, preserve/skip existing codes |
| HR-D002 | Probation status versus derived confirmation state | Confirmed default 2026-07-28 | None | Retain Probation; confirmation normally moves to Active; Ended becomes legacy read-only and is not guessed into Resigned/Terminated |
| HR-D003 | Work Location model | Confirmed default 2026-07-28 | None | Controlled company Work Location with optional same-company Project Site |
| HR-D004 | Department hierarchy | Confirmed 2026-07-28 | None | Add optional same-company Parent Department and prevent cycles |
| HR-D005 | HR document types | Confirmed 2026-07-28 | None | Reuse private Documents with controlled HR type metadata |
| HR-D006 | Attendance-machine make/model/protocol | Partially confirmed 2026-07-30; device identity and protocol established, operational evidence pending | HR-5 | ZKTeco K50 confirmed; TCP Pull on port 4370 (no ADMS/push); PHP library approval, physical connectivity test, sample punch capture, timezone/password confirmation, and production topology decision remain before moving HR-5 to In Progress |
| HR-D007 | Biometric template storage | Confirmed default 2026-07-28 | Production exception only | Do not store templates; store device user mapping and punch evidence. Any exception requires explicit privacy/security approval |
| HR-D008 | Attendance, late, half-day, and overtime rules | Architecture confirmed; live configuration deferred 2026-07-28 | Attendance finalization and HR-7 live calculation | Effective-dated company configuration; no numeric production defaults |
| HR-D009 | Leave types and accrual rules | Architecture confirmed; live configuration deferred 2026-07-28 | Live Leave and affected Payroll/settlement | Effective-dated company policies; no statutory/business values invented |
| HR-D010 | Loan/Advance terms and mappings | Architecture confirmed; live terms deferred 2026-07-28 | Live HR-6 approval/disbursement | Separate origination, schedules, balances, control mappings, and explicit financial terms |
| HR-D011 | Bonus and Incentive rules | Architecture confirmed; live configuration deferred 2026-07-28 | Live HR-7 processing | Separate approved Payroll components and account mappings |
| HR-D012 | Appraisal and warning workflows | Architecture confirmed; live configuration deferred 2026-07-28 | Live HR-8 cycles | Shared configurable workflows; scoring scales and levels require setup |
| HR-D013 | Separation and Final Settlement formulas | Architecture confirmed; live configuration deferred 2026-07-28 | Live HR-8/HR-10 processing | Source-backed configurable components, clearance, maker-checker, GL and Treasury reconciliation |
| HR-D014 | Non-capital employee-issued items | Boundary confirmed; detailed workflow deferred 2026-07-28 | Non-capital part of HR-9 | Fixed Assets reused for capital items; consumable issuance requires a later approved inventory boundary |
| HR-D015 | Group HR reporting access and semantics | Updated 2026-07-30 | None | Authorized explicit active-company scope; report both unique-person and Employment counts; no fake all-company tenant |
| HR-D016 | Pre-production migration and seeding policy | Confirmed 2026-07-28 | None | Active development with no production deployment/data: reset/reseed and unreleased migration revision are allowed; establish a production migration baseline before first deployment |

## Phase Completion Record Template

Copy this section into the completed phase and fill every applicable field:

```text
Implementation completed:
- Date:
- Implemented by:
- Status changed from:
- Status changed to:

Actual implementation:
- Migrations/tables:
- Data migration/backfill:
- Models/enums:
- Actions/services/connectors:
- Filament resources/pages/relation managers:
- Policies/permissions:
- Seeders/provisioning:
- Documents/audit:
- Reports/exports:
- Accounting/Treasury/Asset integration:

Verification:
- Focused tests:
- Broader tests:
- Formatting/static checks:
- Schema/database verification:
- Manual/device/UAT verification:

Decisions and deviations:
- Decisions resolved:
- Differences from planned design:
- Reason:
- Known limitations:
- Follow-up:

Project-state update:
- Sections changed in docs/PROJECT_STATE.md:
- Newly implemented behavior:
- Remaining planned behavior:
- Test baseline/state changes:

Cross-plan update:
- Finance plan sections changed, or Not applicable:
```

## Progress Ledger

Append entries; do not rewrite history except to correct a factual error with an explicit note.

| Date | Phase | Status change | Summary | Verification / blocker |
| --- | --- | --- | --- | --- |
| 2026-07-28 | Plan | Created | Recorded verified HR/Payroll baseline, business feedback, phased workforce roadmap, biometric-machine-ready ingestion architecture, completion protocol, and project-state synchronization requirements | Documentation review; focused existing HR/Payroll suite passed 18 tests with 73 assertions |
| 2026-07-28 | HR-0 | Planned → In Progress | Began the HR decision/evidence gate after re-reading the controlling plans and verifying the empty local HR/Payroll transaction baseline | Recording configuration-first defaults, synthetic scenarios, approval matrix, and deferred live-data gates |
| 2026-07-28 | HR-0 | In Progress → Implemented and Verified | Approved configuration-first HR defaults, normalized source layouts, synthetic Attendance/Leave/Loan/Payroll/separation scenarios, biometric privacy and machine gates, approval matrix, and cross-module boundaries | Existing 18-test/73-assertion focused baseline; all migrations applied; 6 companies, 585 unique permissions, zero local HR/Payroll/Journal transactions; documentation validation passed |
| 2026-07-28 | HR-1 | Planned → In Progress | Began Department hierarchy, company Employee-code sequencing, Employment lifecycle fields, Work Location, and immutable Employment-history implementation after the user's explicit request | HR-0 is Implemented and Verified; focused implementation and verification underway |
| 2026-07-28 | HR-1 | In Progress → Implemented and Verified | Delivered tenant-safe Department hierarchy, atomic configurable Employee codes, Employment types/statuses/lifecycle dates, Work Locations, and immutable effective-dated Employment snapshots with UI, authorization, permissions, factories, migrations, and tests | Migrations applied; Pint and diff check passed; focused HR/Payroll regression passed 58 tests with 294 assertions; full-suite attempt documented under the 128 MB runner limit |
| 2026-07-28 | Plan | Development lifecycle clarified | Recorded that the application has no production deployment/data, so reset/reseed and unreleased migration revision are allowed until a production baseline is established | Production-grade domain security and integrity controls remain mandatory; production migration/rollout hardening remains in HR-12 |
| 2026-07-28 | HR-2 | Planned → In Progress | Began controlled HR document types and compliance metadata by extending the existing private/versioned Document platform | HR-1 is Implemented and Verified; existing Document architecture and tests inspected |
| 2026-07-28 | HR-2 | In Progress → Implemented and Verified | Delivered six configurable company HR document types, legacy-safe nullable mapping, type-aware private uploads and filters, separate identity/medical access, compliance gaps, provisioning, UI, policies, and tests | Migrations applied; 36 optional type configurations and 607 unique permissions; Pint/diff passed; broader focused regression passed 78 tests with 401 assertions |
| 2026-07-28 | HR-3 | Planned → In Progress | Began company calendars/holidays, shifts/schedules, effective attendance rules, daily attendance evidence/corrections, monthly summaries, and Leave policies/balances/approvals | HR-0 through HR-2 are Implemented and Verified; no live attendance or leave values will be invented |
| 2026-07-28 | HR-3 | In Progress → Implemented and Verified | Delivered effective Attendance configuration, maker-checker manual evidence/corrections, daily calculation/finalization, immutable monthly summaries, Leave policy/ledger/approvals/cancellation, attachments, tenant UI, permissions, factories, migrations, and audit | Fresh migrate/seed and Pint passed; HR-3 11 tests/62 assertions; broader affected regression 65 tests/327 assertions; Boost schema/data checks passed; full suite remains constrained by the existing 128 MB child-process limit |
| 2026-07-28 | HR-4 | Planned → In Progress | Began vendor-neutral Attendance Device registry, device-user mappings, import/sync batches, immutable raw events, deterministic deduplication, quarantine/reprocessing, normalized CSV, and replay-safe HR-3 normalization | HR-3 is Implemented and Verified; actual machine adapter and credentials remain deferred to HR-5 |
| 2026-07-28 | HR-4 | In Progress → Implemented and Verified | Delivered tenant-safe Device registry, effective user mappings, immutable batches/raw events/errors, deterministic deduplication, quarantine/replay, private normalized CSV import, adapter contract, HR-3 machine-punch normalization, UI, permissions, factories, and tests | Fresh migrate/seed, Pint, route compilation, diff check, and Boost schema checks passed; HR-4 8 tests/38 assertions and broader affected regression 37 tests/189 assertions; live machine adapter remains HR-5 |
| 2026-07-28 | HR-5 | Planned → Blocked | User requested the device-specific connector; repository, configuration, and database were inspected, but no actual machine identity, protocol/SDK/export documentation, topology, safe fixtures, clock behavior, or credential model exists | Zero configured Attendance Devices and no connector configuration/fixtures; speculative vendor code was rejected. Resume when HR-D006 evidence is supplied |
| 2026-07-28 | HR-6 | Planned → In Progress | Began formal company/Employment Loans and Advances with approved terms, immutable schedules/subledger, maker-checker, Treasury disbursement/recovery, rescheduling, waiver, early payoff, and Payroll-due boundaries | HR-0–HR-1 and Finance Phases 4, 8, and 10 are Implemented and Verified; local Employment, Payroll, Treasury, and Journal transaction counts are zero |
| 2026-07-28 | HR-6 | In Progress → Implemented and Verified | Delivered tenant-safe Loan/Advance origination, maker-checker, versioned schedules, immutable subledger, Treasury disbursement/recovery/reversal, rescheduling, early payoff, principal waiver accounting, documents, and due-as-of/outstanding boundaries | Fresh migrate/seed; 5 focused tests/32 assertions and 44 broader tests/243 assertions; Pint, routes, schema/FK/index, diff, permission idempotency, 735 unique permissions, and zero fabricated operational data verified. Finance-bearing disbursement remains gated by HR-D010 |
| 2026-07-29 | HR-7 | Planned → In Progress | Began traceable Payroll components, effective calculation rules, finalized Attendance/Leave consumption, Loan/Advance due recovery, approved Bonus/Incentive sources, deterministic regeneration, accounting extensions, and report foundations | HR-3, HR-4, HR-6 and Finance Phase 10 are Implemented and Verified; clean local baseline has 6 companies, 735 unique permissions, and zero Employment, Payroll, Attendance-summary, financing, or Journal transactions |
| 2026-07-29 | HR-7 | In Progress → Implemented and Verified | Delivered effective calculation rules, approved Bonus/Incentive sources, immutable source components, finalized Attendance/Leave deductions, due Loan/Advance recovery, deterministic regeneration, extended Payroll GL/reversal behavior, tenant evidence UI, and report foundations | Fresh migrate/seed and repeated seed passed; 11 focused Payroll tests/64 assertions and 47 broader HR/Filament tests/249 assertions; Pint, routes, Boost schema/data checks, 754 unique permissions, and zero fabricated HR-7 transactions verified |
| 2026-07-29 | HR-8 | Planned → In Progress | Began configurable KPI/appraisal and warning workflows plus effective-dated promotion, transfer, resignation, and termination workflows with sensitive evidence and maker-checker controls | HR-1 through HR-3 are Implemented and Verified; clean local baseline has 6 companies, 754 permissions, and zero Employment, compensation, Employment-change, or Document transactions |
| 2026-07-29 | HR-8 | In Progress → Implemented and Verified | Delivered configurable KPI/appraisal, Warning Letter, effective Promotion/Transfer, Resignation/Termination, private evidence, explicit access review, lifecycle integration, tenant UI, authorization, audit, and immutable snapshots/history | Fresh migrate/seed, Pint, routes, diff, and Boost checks passed; HR-8 5 tests/35 assertions and broader HR/Payroll regression 50 tests/268 assertions; 823 permissions and zero fabricated HR-8 operational rows |
| 2026-07-29 | HR-9 | Planned → In Progress | Began Fixed Asset custody issuance/acknowledgement/transfer/return/loss-damage evidence and separation-linked configurable departmental clearance | HR-8 and Finance Phase 11 are Implemented and Verified; baseline has 6 companies, 823 permissions, and zero Employment, Fixed Asset, Asset Transfer, Separation, or Financing operational rows |
| 2026-07-29 | HR-9 | In Progress → Implemented and Verified | Delivered one-live-custodian Fixed Asset issuance, employee acknowledgement, immutable transfer/return/exception evidence, and approved-separation clearance aggregating assets, Loans, Advances, Leave, handover, and configured departmental obligations | Fresh migrate/seed, Pint, routes, repeated permission seed, Boost schema/data checks, focused 4-test/25-assertion workflow coverage, and 31-test/170-assertion affected regression passed; 865 unique permissions and zero fabricated HR-9/Journal/Treasury rows |
| 2026-07-29 | HR-10 | Planned → In Progress | Began approved-separation Final Settlement with configurable/source-backed earnings and recoveries, completed-clearance enforcement, maker-checker review/approval, balanced reversible GL posting, and bounded Treasury payment/receipt settlement | HR-6 through HR-9 and Finance ledger/Treasury foundations are Implemented and Verified; baseline has 6 companies, 865 permissions, and zero Employment, Separation, Clearance, Journal, or Treasury transactions |
| 2026-07-29 | HR-10 | In Progress → Implemented and Verified | Delivered approved-separation Final Settlement, exact source refresh, independent review/approval/posting, balanced reversible GL, bounded Treasury payment/receipt, financing recovery reversal, documents, printable letter, tenant UI, and reconciliation report | Fresh migration/seed and repeated seed passed; focused 4 tests/40 assertions and affected 35 tests/228 assertions passed; Pint, routes, diff, Boost schema/data checks, 887 permissions, and zero fabricated settlement/Journal/Treasury rows verified |
| 2026-07-29 | HR-11 | Planned → In Progress | Began company HR reports/dashboard, authorized streamed exports, and parent/descendant comparative HR reporting with explicit unique-person and Employment counts | HR-1 through HR-10 source phases are Implemented and Verified; baseline has 6 companies, 887 permissions, and zero Employment, Payroll, Attendance-summary, Leave, Financing, or Final Settlement operational rows |
| 2026-07-29 | HR-11 | In Progress → Implemented and Verified | Delivered the company HR report catalog/dashboard, existing Payroll and Final Settlement report exports, private audited CSV/XLSX, indexed aggregates, and authorized hierarchy-only group comparisons with protected monetary sections | Focused 4 tests/33 assertions and broader affected 72 tests/434 assertions passed; Pint, 361 routes, migration/index/query-plan checks, repeated seed, 893 unique permissions, private export headers/audit, and zero fabricated operational data verified |
| 2026-07-29 | HR-12 | Planned → In Progress | Began controlled HR source migration, independent validation/import/rollback, recovery/readiness evidence, security and reconciliation hardening, realistic-volume performance checks, and pilot UAT verification | HR-1–HR-4 and HR-6–HR-11 are Implemented and Verified; HR-5 remains an explicitly external device-evidence blocker and its historical machine backfill will not be represented as available |
| 2026-07-29 | HR-12 | In Progress → Implemented and Verified | Delivered seven controlled HR source adapters, independent dry-run/validation/import/rollback, private immutable evidence, recovery manifest, operational-readiness/security/performance gates, and pilot Attendance-to-Treasury reconciliation | Full suite passed 263 tests/1,429 assertions; routes, Pint, diff, fresh migration/repeated seed, 901 permissions, empty operational baseline, recovery mismatch detection, and 250-Employment query budget passed. HR-5 remains explicitly external-blocked |
| 2026-07-30 | HR-5 | Blocked (partial unblock) | User confirmed device identity: ZKTeco K50 fingerprint terminal; physical device menu inspection confirmed no ADMS/Cloud Server capability; integration mode is TCP Pull on port 4370 only; PHP Packagist libraries available; ATTLOG data format and field mapping to HR-4 AttendanceEventData documented; biometric privacy boundary satisfied (device-side matching, no template storage) | HR-D006 partially confirmed; remaining blockers: PHP library approval as new Composer dependency, physical LAN connectivity test, sample raw punch capture, timezone/password confirmation, and production network topology decision |

## Whole-plan completion rule

Overall status may become **Implemented and Verified** only when:

- all HR-0 through HR-12 phases have that status;
- each phase contains actual completion and verification evidence;
- no Decision Register item blocks delivered behavior;
- `docs/PROJECT_STATE.md`, this plan, the Finance plan, repository, database, and test state agree;
- attendance-machine integration has either passed actual-device UAT or is explicitly recorded as deferred external scope without representing it as implemented;
- final cross-company, Payroll/GL/Treasury, authorization, audit, privacy, performance, and regression verification passes.
