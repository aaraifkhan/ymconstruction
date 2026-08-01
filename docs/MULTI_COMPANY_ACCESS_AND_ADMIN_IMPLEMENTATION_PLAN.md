# Multi-Company Access, Company-Scoped Authorization, and Group Admin Implementation Plan

Last updated: 2026-07-30

Overall status: **Planned**

## Purpose

This is the controlling implementation and handoff plan for:

- the post-login company selection experience;
- separate company operations and group administration surfaces;
- direct user access to one, several, or all companies;
- company-specific roles and role assignments;
- company-specific module enablement, variants, rules, and settings;
- super-admin consolidated records, statistics, and reporting;
- migration from the current global-role authorization model.

This document is a plan only. It does not authorize implementation. Work starts only when the user explicitly requests the relevant phase.

## Confirmed client experience

### Normal user

After successful login:

1. Show a company-selection page containing only active companies the user may access.
2. If the user has access to one company, show exactly one large company card. Do not expose any other company.
3. If the user has access to two companies, show exactly two large company cards.
4. Apply the same rule for any higher number of companies.
5. Clicking a company card opens that company's operational panel in an explicit company context.
6. The user may return to the company-selection page and switch to another authorized company.

### Super admin

After successful login:

1. Show large cards for every active company.
2. Show a separate fifth **Super Admin** card.
3. Clicking a company card opens that company's operational panel with the selected company as the active tenant.
4. Clicking **Super Admin** opens a non-transactional group-administration panel.
5. The group-administration panel shows consolidated records and statistics across authorized companies while preserving each legal company's separate books and operational records.

### No-company user

A non-super-admin user with no active company access must not enter an arbitrary company or see company names. Show a safe access-pending page with logout/profile options and an instruction to contact an administrator.

## Non-negotiable boundaries

- One application and one shared database remain the approved architecture.
- The only initial legal companies are BMC Construction, YMC Construction, 7 Orbit, and 7 Orbit Medical Billing.
- The four companies are independent; this plan does not use parent/child relationships, descendant access, or inherited company configuration.
- Every operational record remains owned by one legal company.
- Company membership determines data scope.
- A role assignment determines capability within a specific company.
- A role in Company A must grant no permission in Company B unless that role is separately assigned in Company B.
- The global `super_admin` role is a platform role and remains the only initial bypass across all companies and the group Admin Panel.
- Consolidation is a reporting scope, not a fake “All Companies” tenant.
- The Admin Panel must not merge, re-own, or silently edit company transactions.
- Shared modules use shared code. Company variation belongs in explicit module state, typed settings, mappings, templates, feature flags, or named workflow strategies.
- A disabled module must be denied at navigation, route/resource, policy/action, report, export, and background-processing boundaries.
- UI hiding is never the only authorization control.
- Existing maker-checker, posting, reversal, privacy, document, and audit rules remain effective inside every company.

## Verified current project baseline

The following already exists and should be extended rather than replaced:

- Laravel 13, Filament 5, Livewire 4, PHPUnit 12, and PHP 8.4.
- One Filament panel with ID `admin`, path `/admin`, and `Company` tenancy.
- Tenant URLs using the `company/{company-slug}` prefix.
- Searchable Filament tenant switcher.
- Four provisioned independent company records with their approved card logos.
- Active/inactive direct company membership through `company_user`.
- Direct active company membership through `company_user`.
- Super-admin access to every active company through `User::getAccessibleCompanies()`.
- A shared module catalog and per-company `company_modules` state, variant, and JSON settings.
- Tenant-scoped operational resources, policies, reports, and tests across Finance, Projects, HR, Payroll, Documents, and Operations.
- Existing authorized consolidated Finance and Group HR report services.
- Filament Shield and Spatie Permission with a global permission catalog.
- Current global reusable roles, including `super_admin` and `Manager`.

The main gaps are:

- there is no dedicated post-login large-card company chooser;
- the new Access Portal and restricted Super Admin landing are implemented on the existing authenticated panel session; full panel separation remains planned;
- Spatie Permission teams are disabled;
- roles and direct user permissions are currently global rather than company-scoped;
- Filament Shield is configured with `scopeToTenant(false)` and no tenant model;
- module records exist, but module state is not yet a complete runtime authorization boundary across every resource, page, action, report, export, and background process;
- consolidated management entry is mixed into tenant navigation rather than presented as a separate Admin Panel;
- the current local `Manager` user has no company membership, which must be handled explicitly during migration rather than guessed.

## Target application surfaces

Use three clearly separated surfaces with one shared authenticated session.

### 1. Access Portal

Purpose:

- login;
- post-login destination;
- large branded company cards;
- Super Admin fifth card for super admins;
- access-pending state;
- profile and logout.

Recommended target route:

```text
/portal
```

The Access Portal is non-tenant. It must query only the authenticated user's authorized company list.

### 2. Company Operations Panel

Purpose:

- all company-owned operational workflows;
- current-company dashboard;
- company-specific navigation;
- company-specific roles and permissions;
- company-specific module settings and workflow behavior.

Recommended target route:

```text
/app/company/{company-slug}
```

The current tenant-aware resources should move to or be registered in this panel without duplicating their domain implementation.

### 3. Group Admin Panel

Purpose:

- company directory and topology;
- users and company memberships;
- global module catalog;
- platform configuration;
- consolidated Finance, HR, Payroll, Projects, Sales, Procurement, Inventory, Treasury, Assets, Documents, and system-health summaries;
- links into a selected company context for authorized operational detail.

Recommended target route:

```text
/admin
```

Initial access is limited to the global `super_admin` role. Later delegation requires a separate approved platform-role design and must not reuse a company role implicitly.

### Route transition

The current `/admin/company/{company}` URLs are already used by tests and possibly bookmarks. During implementation:

- introduce named routes for all cross-panel links;
- add temporary compatibility redirects from old tenant URLs to the equivalent Company Operations route;
- preserve query filters where safe;
- verify redirects do not accept an unauthorized company slug;
- remove compatibility redirects only after explicit rollout approval.

## Post-login routing rules

Use one deterministic redirect service or authentication response instead of scattered panel-specific checks.

| User state | Post-login destination |
| --- | --- |
| Global super admin | Access Portal with every active company card and Admin Panel card |
| One active accessible company | Access Portal with one company card |
| Multiple active accessible companies | Access Portal with exactly those company cards |
| No active accessible company | Access-pending page |
| Inactive/soft-deleted user | Authentication denied |

Do not automatically enter the only company in the first release because the client explicitly requested that the available company appear and be clicked. A later preference such as “remember my last company” may be added only if it does not bypass the chooser requirement or authorization revalidation.

Every company-card click must:

1. resolve the company by slug;
2. verify that it is active;
3. call the authoritative `canAccessTenant()` rule;
4. establish the current company context;
5. establish the company permission-team context;
6. redirect through a named route;
7. record a safe company-entry audit event where approved.

## Authorization architecture

### Platform scope versus company scope

Maintain two explicit authorization scopes.

#### Platform scope

- no company context;
- global `super_admin` role and fifth Super Admin card;
- Admin Panel access;
- global module catalog and platform settings;
- company topology and consolidated system views;
- no accidental reuse of an arbitrary last-selected company role.

#### Company scope

- one current `company_id`;
- active membership or super-admin access;
- company-specific role assignments;
- shared permission keys evaluated in the selected company context;
- company module state and settings;
- company-owned records only.

### Recommended Spatie Permission teams design

Enable Spatie Permission teams using `company_id` as the team foreign key.

Target schema direction:

```text
roles
- id
- company_id nullable
- name
- guard_name
- timestamps

model_has_roles
- role_id
- model_type
- model_id
- company_id nullable

model_has_permissions
- permission_id
- model_type
- model_id
- company_id nullable

permissions
- id
- name
- guard_name
- timestamps
```

Rules:

- `roles.company_id = null` is reserved for approved platform roles such as `super_admin`.
- Company operational roles require `roles.company_id`.
- Role names are unique within `(company_id, name, guard_name)`, not globally.
- Permission definitions remain a shared global capability catalog.
- Company roles select from only the permissions allowed by the company's enabled modules.
- Direct user permissions should be disallowed in normal UI after migration. If retained for exceptional cases, they must also be company-scoped, auditable, and clearly distinguished from role-derived permissions.
- The current company permission-team ID must be set by persistent tenant middleware on the initial request and every Livewire request.
- The platform Admin Panel must clear the permission-team context.
- queued jobs, scheduled commands, exports, notifications, and console workflows must establish company context explicitly; they must never inherit an unrelated web request's context.
- permission cache invalidation must occur after role, assignment, module, or membership changes.

### Global super-admin behavior

The global `super_admin` role remains a deliberate platform bypass through the existing Gate-before behavior.

It must:

- access every active company;
- access the Admin Panel;
- bypass company-role checks only as explicitly designed;
- still operate inside a selected company context for company-owned transactions;
- still comply with maker-checker and “different actor” business rules where those rules intentionally include super admins;
- never cause a fake company context in consolidated reporting.

### Membership and roles

Membership and role assignment are separate records with separate effects.

- Active membership without a company role: company card may be visible, but operational access is limited to a safe “no company role assigned” state unless an approved baseline role exists.
- Company role without active membership: no company access.
- Inactive membership: no company card and no tenant access.
- Access is based on a direct active membership only. A membership in one company has no effect in any other company.

## Company-specific role management

Each company should have its own role-management page inside the Company Operations Panel.

Company administrators may:

- list roles belonging to the current company;
- create, rename, clone, archive, and update current-company roles;
- select permissions available to the company's enabled modules;
- assign current-company roles to active current-company members;
- see permission labels in business language;
- view the role's member count and last change evidence.

They may not:

- view or edit another company's roles;
- edit the global `super_admin` role;
- assign platform roles;
- grant permissions belonging only to disabled modules;
- bypass membership;
- grant their own role-management capability unless the acting user is authorized to do so;
- delete a role while protected assignments or workflow references require it.

Recommended safe role templates:

- keep group-standard role templates as optional blueprints;
- cloning a template creates an independent company-owned role;
- later template changes do not silently modify company roles;
- company roles may differ even when their names are the same.

## Module architecture

### Shared module catalog

Keep one shared module catalog. Expand it from broad labels into an authoritative module registry that can map:

- stable module key;
- business name and description;
- navigation group;
- resources;
- pages;
- widgets;
- reports and exports;
- permission prefixes/keys;
- dependencies;
- supported workflow variants;
- settings schema/version;
- default safe state;
- whether it participates in consolidated reporting.

Do not duplicate resources or domain actions per company.

### Company module assignment

Continue using `company_modules` as the company assignment and configuration record:

```text
company_modules
- company_id
- module_id
- state: enabled | disabled
- variant: nullable named workflow strategy
- settings: typed/versioned module configuration
```

Add or verify:

- unique `(company_id, module_id)`;
- indexes supporting current-company module resolution;
- setting-schema version where module settings may evolve;
- validation against the selected module and variant;
- audit evidence for state, variant, and setting changes.

### Effective module resolution

Use one resolver with deterministic precedence:

1. explicit company state/settings;
2. safe module default when no company setting exists.

The resolver must:

- cache results per company with explicit invalidation;
- return typed configuration, not unvalidated arbitrary arrays;
- resolve named variants through a strategy registry;
- reject unsupported variants and invalid setting combinations.

### Runtime enforcement

Every module must be enforced in all relevant paths:

- navigation registration;
- resource/page/widget access;
- direct route access;
- policies and custom actions;
- relationship selectors;
- reports and exports;
- imports;
- queued jobs and scheduled tasks;
- consolidated Admin Panel cards and metrics;
- API/MCP endpoints if added later.

A disabled module must return a controlled authorization denial or unavailable state. Existing historical data remains intact and is not deleted when a module is disabled.

### Module-specific permissions

The permission catalog remains global, but each permission must map to a module or platform scope.

When a module is disabled:

- its permissions cannot be newly granted to a company role;
- existing grants remain stored for possible re-enable but are ineffective;
- its navigation and direct access are denied;
- consolidated metrics clearly show “module disabled” rather than zero where zero would be misleading.

### Company-specific rules and settings

Use typed settings or dedicated company tables according to complexity.

Use `company_modules.settings` for small, module-level configuration such as:

- feature toggles;
- allowed workflow variant;
- display preferences;
- simple threshold references;
- navigation options.

Use dedicated company-owned tables for:

- accounting mappings;
- approval rules;
- statutory/effective-dated rates;
- payroll calculation rules;
- attendance/leave policies;
- sequences;
- bank and treasury configuration;
- any settings requiring relationships, history, approvals, or effective dates.

Never place core structured business data into a generic JSON settings field.

## Group Admin Panel

### Landing dashboard

The first Admin Panel page should contain:

- an **All Companies** summary header;
- one large card per active company;
- group-wide KPI cards;
- module-health and configuration warnings;
- recent audited administrative events;
- links to consolidated report areas;
- a link from each company card into that company's Company Operations Panel.

### Company cards

Each card should show only approved high-level information:

- logo or generated initials;
- company name and legal name where configured;
- independent-company status;
- active/inactive state;
- enabled module count;
- user/member count;
- configuration/readiness warnings;
- selected summary metrics that are safe and meaningful.

Sensitive payroll, bank, identity, medical, warning, or document data must not appear merely because a company card exists.

### Consolidated reporting rules

Reuse and extend existing report services rather than querying in Blade views.

Initial consolidated areas:

- Finance: consolidated Trial Balance, Balance Sheet, P&L, inter-company reconciliation, closing/integrity status.
- HR: unique people, Employment count, active/on-leave/joiners/exits, Attendance/Leave summaries.
- Payroll: company payroll cost and settlement status.
- Projects: project count, contract value, budget versus actual, profitability.
- Procurement/AP: requisitions, orders, unmatched receipts, Vendor Bills, aging.
- Sales/AR: invoices/running bills, collections, aging, project revenue.
- Inventory: stock value, exceptions, inactive/disabled-module state.
- Treasury: cash/bank position and unreconciled items.
- Assets: cost, carrying value, depreciation, custody and reconciliation exceptions.
- Documents/System: expiring/review-pending documents, module/configuration readiness, audit and recovery status.

Every consolidated query must:

- use an explicit authorized company-ID set;
- preserve company columns and drill-down traceability;
- use posted/reversed accounting sources where applicable;
- use the explicit active-company set for collective views;
- distinguish “not authorized,” “module disabled,” “not configured,” and true zero;
- support bounded date/period filters;
- avoid N+1 queries and select only required columns;
- use supporting indexes and query-plan verification;
- retain PKR-only assumptions until foreign currency is separately approved;
- remain read-only unless an explicit platform administrative workflow is designed.

## Audit requirements

At minimum, log:

- login success/failure through existing authentication facilities;
- company selection/entry where approved;
- membership activation and deactivation;
- company role creation, update, archive, and permission changes;
- role assignment and removal;
- module enable, disable, variant, and settings changes;
- Admin Panel consolidated export/download events;
- company-context and platform-context changes for sensitive administrative actions.

Audit metadata should include actor, company or platform scope, affected user/role/module, safe before/after values, timestamp, and request correlation data. Do not log passwords, tokens, private document contents, encrypted plaintext values, or secrets.

## Phase execution protocol

1. Read `AGENTS.md`, `docs/PROJECT_STATE.md`, this entire plan, and both domain implementation plans before each phase.
2. Inspect the current diff, schema, migration state, roles, assignments, company memberships, modules, routes, policies, and tests.
3. Preserve unrelated worktree changes.
4. Work on only one numbered MCA phase at a time.
5. At most one MCA phase may be **In Progress**.
6. Do not begin a later phase until every dependency is **Implemented and Verified**.
7. Change phase status and append a Progress Ledger entry when starting, blocking, reopening, or completing a phase.
8. Update `docs/PROJECT_STATE.md` in the same change whenever architecture or implementation status changes.
9. Update the Finance plan when consolidated Finance behavior or company accounting access changes.
10. Update the HR plan when group HR reporting or company HR authorization changes.
11. A phase is complete only after its full acceptance criteria and relevant tests pass.
12. Stop after one phase unless the user explicitly requests the next phase.

## Phase Status Index

| Phase | Name | Status | Depends on |
| --- | --- | --- | --- |
| MCA-0 | Decisions, inventory, and migration evidence | In Progress | Current verified project |
| MCA-1 | Company-scoped role and permission data model | Planned | MCA-0 |
| MCA-2 | Company authorization context and policy hardening | Planned | MCA-1 |
| MCA-3 | Access Portal and panel separation | Planned | MCA-2 |
| MCA-4 | Runtime module governance and typed settings | Planned | MCA-2 |
| MCA-5 | Company membership, role, and module management UI | Planned | MCA-3–MCA-4 |
| MCA-6 | Group Admin Panel and consolidated dashboards | Planned | MCA-3–MCA-5 |
| MCA-7 | Migration, UAT, security, performance, and rollout | Planned | MCA-1–MCA-6 |

## MCA-0 — Decisions, inventory, and migration evidence

Status: **Planned**

### Objective

Freeze the exact access, role, module, route, and dashboard rules before changing authorization schema.

### Scope

- inventory every panel, resource, page, widget, report, export, custom action, job, and scheduled command;
- map every permission to platform scope or one module;
- inventory global roles, direct permissions, role assignments, direct company memberships, and inactive access;
- identify all `hasRole()`, `can()`, `Gate`, policy, and custom authorization call sites that must become company-context aware;
- define the initial module taxonomy and dependencies;
- approve the initial consolidated KPI catalog;
- approve route names and compatibility period;
- decide whether tenant self-registration remains available;
- decide how current users with roles but no company membership will be migrated;
- record rollback and reconciliation queries.

### Required decisions

- Keep the recommended three surfaces and target paths.
- Confirm direct company membership and role assignment for every operating user; never infer cross-company access.
- Confirm that company creation/registration is super-admin-only. Recommended: remove tenant self-registration from normal users.
- Confirm whether inactive companies appear in the Admin Panel. Recommended: visible to super admins with an inactive badge, never selectable for operations.
- Confirm whether company role templates are global blueprints. Recommended: yes, clone-only.
- Confirm the first Admin Panel KPI list and default date period.

### Acceptance criteria

- Every existing permission is classified.
- Every current role/assignment has a migration outcome.
- No user is assigned to a company by assumption.
- No module or dashboard metric has an unidentified source.
- Route and rollback decisions are recorded.
- Documentation-only verification passes.

## MCA-1 — Company-scoped role and permission data model

Status: **Planned**

### Objective

Introduce company-owned roles and assignments without granting or losing access silently.

### Scope

- enable Spatie Permission teams with `company_id`;
- add nullable `company_id` to roles and applicable pivots;
- configure Filament Shield tenant model and tenant-scoped role behavior;
- preserve global `super_admin`;
- create company role factories and provisioning actions;
- define optional global role-template storage without making templates active roles;
- migrate existing role assignments only from approved mapping evidence;
- clear and rebuild permission caches safely;
- add database constraints and indexes;
- add audit events for role and assignment changes.

### Migration rules

- back up/export current role, permission, assignment, and membership mappings before transformation;
- migrate `super_admin` as global;
- do not copy a global role into every company automatically;
- require an approved mapping for `Manager` and any later roles;
- quarantine unresolved assignments in a reconciliation report;
- verify before/after counts and sample capabilities;
- retain a forward-fix/rollback plan appropriate to the pre-production migration state.

### Acceptance criteria

- identical role names can exist independently in two companies;
- changing Company A's role does not change Company B's role;
- the same user can hold different roles in different companies;
- a role without active membership grants no tenant access;
- super admin remains global and functional;
- role/assignment constraints and cache invalidation are verified;
- migration reconciliation has no silent unresolved assignments.

## MCA-2 — Company authorization context and policy hardening

Status: **Planned**

### Objective

Make the selected company the authoritative permission context for every company operation.

### Scope

- add persistent company permission-context middleware;
- clear context in the platform/Admin Panel;
- centralize company access checks;
- update policies, reports, custom actions, exports, jobs, and commands to evaluate permissions in the intended company;
- remove unsafe reliance on ambient or last-selected context;
- ensure Livewire requests restore the correct context;
- add strict Filament authorization mode after policy coverage is complete;
- add tenant-aware middleware/scopes where models without tenant resources require it;
- review scoped unique/exists validation and relationship options;
- define company context propagation for queues and scheduled work.

### Acceptance criteria

- Company A permission cannot authorize Company B.
- A crafted URL cannot switch the permission team without tenant access.
- Livewire requests retain the correct company context.
- Admin Panel requests have no stale company permission context.
- queued/scheduled operations require explicit company IDs.
- direct actions and exports enforce the same scope as their UI.
- all missing policy methods fail closed under strict authorization.

## MCA-3 — Access Portal and panel separation

Status: **Planned**

### Objective

Deliver the exact post-login company-card and Admin Panel entry experience.

### Scope

- create the Access Portal;
- create large responsive company cards;
- add safe logo/initial rendering;
- add the super-admin-only Admin Panel card;
- add no-company and no-company-role states;
- create/rename the Company Operations panel;
- create the Group Admin panel shell;
- register resources/pages/widgets only in their intended panel;
- implement deterministic post-login routing;
- add named cross-panel URLs and old-route compatibility redirects;
- keep one authenticated session and profile/logout behavior;
- revalidate access on every company-card click.

### Acceptance criteria

- one-company user sees one card;
- two-company user sees two cards;
- inaccessible and inactive companies are absent;
- super admin sees all active company cards plus Admin Panel;
- normal users never see or open Admin Panel;
- no-company user sees the safe pending state;
- card clicks enter only the selected authorized company;
- old URLs redirect safely during compatibility;
- panel resources do not leak into the wrong navigation.

## MCA-4 — Runtime module governance and typed settings

Status: **Planned**

### Objective

Make company modules control actual system capability, not only configuration records or navigation.

### Scope

- define the module registry and permission mapping;
- expand module catalog granularity only where approved;
- implement direct per-company module resolution;
- validate named workflow variants;
- introduce typed/versioned settings schemas;
- register module-aware navigation/resources/pages/widgets;
- enforce module access in policies, actions, reports, exports, jobs, and APIs;
- implement module dependency validation;
- add cache invalidation and readiness warnings;
- preserve historical records when disabling a module.

### Acceptance criteria

- enabled/disabled state resolves deterministically;
- direct URLs cannot bypass a disabled module;
- company variants change only approved workflow behavior;
- invalid settings and dependency conflicts are rejected;
- disabling in one company does not affect another;
- historical data remains intact;
- disabled-module permissions are ineffective and unavailable for new role grants;
- module cache changes are visible immediately after authorized updates.

## MCA-5 — Company membership, role, and module management UI

Status: **Planned**

### Objective

Allow authorized company administrators to manage their own members, roles, permissions, and modules safely.

### Scope

- current-company membership list and invitations/account linking as approved;
- activate/deactivate membership;
- direct membership management by platform/suitably authorized administrators;
- current-company role CRUD and clone-from-template;
- assign/remove current-company roles;
- filter permission choices by enabled modules;
- current-company module state, variant, settings, and effective-source display;
- protected-role and last-administrator safeguards;
- audit/history displays;
- business-readable permission labels.

### Acceptance criteria

- company admin sees only current-company members and roles;
- another company's assignments cannot be viewed or modified;
- platform roles cannot be edited in company UI;
- disabled-module permissions cannot be assigned;
- deactivation removes the company card and access immediately;
- role changes take effect without stale cache;
- the last required company administrator cannot be removed accidentally;
- every change is audited.

## MCA-6 — Group Admin Panel and consolidated dashboards

Status: **Planned**

### Objective

Deliver the super-admin entry point for all companies, consolidated records, and statistics.

### Scope

- Admin Panel landing page with group KPIs and company cards;
- company topology and readiness status;
- global user, membership, role-template, module-catalog, and platform settings management;
- consolidated Finance and inter-company views;
- consolidated HR and Payroll summaries;
- consolidated Projects, Procurement, Sales, Inventory, Treasury, Assets, Documents, and system-health summaries;
- module-aware “disabled/not configured/zero” states;
- date/period/company/module filters;
- authorized private exports with audit;
- drill-down links into a selected company.

### Acceptance criteria

- only global super admins enter the Admin Panel in the initial release;
- every metric reconciles to an authoritative existing report/source;
- company totals and group totals reconcile;
- no fake tenant or merged transaction record is created;
- module-disabled companies are represented accurately;
- sensitive sections use explicit authorization and masking;
- exports are private, no-store, and audited;
- company drill-down rechecks tenant access;
- dashboard query counts and plans meet approved budgets.

## MCA-7 — Migration, UAT, security, performance, and rollout

Status: **Planned**

### Objective

Prove the new access model end to end and roll it out without silent authorization changes.

### Scope

- migrate approved roles and assignments;
- reconcile unresolved global roles/direct permissions;
- verify all users' visible company-card sets;
- test role differences for the same user across companies;
- test module differences and settings variants across companies;
- test super-admin portal and Admin Panel;
- test inactive users, companies, memberships, modules, and roles;
- test that a membership or role in one company grants no access to another;
- test every sensitive report/export and direct URL;
- test Livewire, queues, scheduled jobs, caching, and concurrent role/module changes;
- run realistic consolidated dashboard volumes and query-plan review;
- perform pilot UAT with one normal user, one multi-company user, one company admin, and one super admin;
- document rollout, monitoring, reconciliation, and rollback evidence in this plan;
- update all controlling project documents.

### Minimum UAT scenarios

1. User A has only YMC Construction: sees one card and only YMC data.
2. User B has YMC Construction and BMC Construction: sees two cards and different roles/navigation in each.
3. User C has BMC Construction membership only: cannot see or enter 7 Orbit Medical Billing.
4. Company Admin A edits a YM role: the same-named BMC role remains unchanged.
5. A module enabled in YM and disabled in BMC is available only in YM, including direct URLs and actions.
6. Two companies use the same module with different validated variants/settings and each workflow follows its own configuration.
7. Super admin sees every active company and Admin Panel, then drills safely into one company.
8. Normal users cannot discover or open Admin Panel routes.
9. Consolidated figures reconcile to company-level figures and preserve company attribution.
10. Deactivating membership immediately removes the company card, tenant route access, and company permissions.

### Acceptance criteria

- no cross-company access is found in automated or manual tests;
- before/after role and assignment counts reconcile;
- every user has an approved access outcome;
- portal cards match authoritative access queries;
- module, policy, action, report, export, queue, and route checks fail closed;
- consolidated figures reconcile;
- performance budgets pass;
- focused and full PHPUnit suites pass;
- Pint, route discovery, schema/index inspection, permission-cache checks, and diff validation pass;
- `docs/PROJECT_STATE.md` and affected Finance/HR plan sections match actual implementation.

## Test matrix

### Access Portal and panels

- login redirect for super admin, one-company, multi-company, and no-company users;
- active/inactive user, company, and membership behavior;
- company card content and counts;
- Admin Panel card visibility;
- card click and forged-slug denial;
- old-route compatibility redirects;
- session, profile, logout, and MFA behavior across panels.

### Company-scoped roles

- same role name in two companies;
- different permissions for the same user in two companies;
- role edit isolation;
- assignment with and without membership;
- cache invalidation;
- protected platform role;
- direct permission exception if retained;
- concurrent role updates and assignments.

### Modules

- enabled and disabled states;
- dependency failure;
- invalid variant/settings;
- navigation and direct route denial;
- policy/action/export/job denial;
- historical-data preservation;
- same module with different company settings.

### Consolidation

- authorized company set;
- explicit all-active-company inclusion;
- Finance and HR reconciliation;
- module-disabled versus zero semantics;
- sensitive amount masking;
- export authorization and audit;
- query count, index, and realistic-volume performance.

### Regression

- existing tenant-isolation suites;
- Finance posting/reversal and consolidation;
- HR/Payroll/Final Settlement reporting;
- Documents and sensitive downloads;
- company creation/provisioning;
- Filament resources under the new panel IDs;
- all custom workflow authorization tests.

## Implementation completion record template

Copy under the completed phase and fill every applicable field:

```text
Implementation completed:
- Date:
- Implemented by:
- Status changed from:
- Status changed to:

Actual implementation:
- Migrations/tables:
- Data migration/backfill:
- Models/services/middleware:
- Panels/pages/resources/widgets:
- Policies/permissions/roles:
- Module registry/settings:
- Reports/exports:
- Audit:

Verification:
- Focused tests:
- Broader/full tests:
- Formatting/static checks:
- Schema/data reconciliation:
- Manual UAT:
- Performance/query plans:

Decisions and deviations:
- Decisions resolved:
- Differences from planned design:
- Reason:
- Known limitations:
- Follow-up:

Documentation synchronization:
- PROJECT_STATE changes:
- Finance plan changes or Not applicable:
- HR plan changes or Not applicable:
```

## Decision Register

| ID | Decision | Status | Blocks | Current direction |
| --- | --- | --- | --- | --- |
| MCA-D001 | Post-login company cards | Confirmed by client 2026-07-29 | None | Always show only accessible company cards; one access means one visible card |
| MCA-D002 | Super-admin landing | Confirmed by client 2026-07-30 | None | Show four active company cards plus a fifth Super Admin card |
| MCA-D003 | Company-specific roles | Confirmed by client 2026-07-29 | MCA-1 | Roles and assignments must be scoped by company |
| MCA-D004 | Company-specific modules | Confirmed by client 2026-07-29 | MCA-4 | Same shared code; modules may differ by company |
| MCA-D005 | Same module, different rules/settings | Confirmed by client 2026-07-29 | MCA-4 | Typed company settings and named variants |
| MCA-D006 | Consolidated Admin Panel | Confirmed by client 2026-07-29 | MCA-6 | Read-only group reporting/administration scope; no fake tenant |
| MCA-D007 | Panel route structure | Recommended; approval required in MCA-0 | MCA-3 | `/portal`, `/app/company/{slug}`, and `/admin` |
| MCA-D008 | Cross-company role behavior | Confirmed by client 2026-07-30 | MCA-1–MCA-2 | Companies are independent; direct membership and role assignment are required per company |
| MCA-D009 | Tenant self-registration | Recommended; approval required in MCA-0 | MCA-3 | Super-admin/company-provisioning only; remove normal self-registration |
| MCA-D010 | Global role templates | Recommended; approval required in MCA-0 | MCA-1/MCA-5 | Optional clone-only blueprints; company copies do not auto-sync |
| MCA-D011 | Users with roles but no memberships | Evidence required in MCA-0 | MCA-1/MCA-7 | Never infer company; map explicitly or leave access pending |
| MCA-D012 | First consolidated KPI catalog | Business approval required in MCA-0 | MCA-6 | Start from existing verified Finance and Group HR reports, then add source-backed module summaries |

## Progress Ledger

| Date | Phase | Status change | Summary | Verification / blocker |
| --- | --- | --- | --- | --- |
| 2026-07-29 | Plan | Created | Recorded the client-confirmed company-card login flow, separate super-admin Admin Panel, company-scoped roles, per-company module variants/settings, consolidated reporting boundaries, and phased migration/verification plan | Documentation-only; no feature implementation, schema change, role migration, or data mutation performed |
| 2026-07-30 | MCA-0 | Planned → In Progress | Rebased the organization to four independent companies, added approved logo assets, direct-membership access, the branded Access Portal, and the Super Admin entry route | Focused provisioning, portal, production-seeding, and collective-report tests in progress; legacy hierarchy schema is retained temporarily but no longer used by the baseline or access logic |

## Whole-plan completion rule

Overall status may become **Implemented and Verified** only when:

- MCA-0 through MCA-7 are all **Implemented and Verified**;
- each phase contains actual implementation and verification evidence;
- every current user, role, permission, membership, and assignment has a reconciled outcome;
- company-role isolation and module enforcement pass automated and manual tests;
- the Access Portal matches the client-confirmed card behavior;
- the Admin Panel is restricted and its consolidated figures reconcile;
- no Decision Register item blocks delivered behavior;
- this plan, `docs/PROJECT_STATE.md`, affected Finance/HR plans, repository, database, and test state agree.
