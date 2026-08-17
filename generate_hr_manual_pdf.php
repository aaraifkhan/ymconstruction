<?php

/**
 * PDF Generator for YM Construction Management System - HR & Workforce Module User Manual
 */

require __DIR__.'/vendor/autoload.php';
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Dompdf\Dompdf;
use Dompdf\Options;

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>YM Construction Management System — HR & Workforce Module User Manual</title>
<style>
    @page {
        margin: 22mm 14mm 20mm 14mm;
        size: A4 portrait;
    }

    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        font-size: 8.5pt;
        line-height: 1.45;
        color: #1e293b;
        background-color: #ffffff;
        margin: 0;
        padding: 0;
    }

    /* Running Header & Footer via fixed positions */
    .running-header {
        position: fixed;
        top: -16mm;
        left: 0;
        right: 0;
        height: 10mm;
        border-bottom: 1.5px solid #0284c7;
        font-size: 7.5pt;
        color: #64748b;
        padding-bottom: 3px;
    }
    .running-header table {
        width: 100%;
        border-collapse: collapse;
    }
    .running-header td {
        padding: 0;
        vertical-align: bottom;
    }

    .page-break {
        page-break-before: always;
    }

    .no-break {
        page-break-inside: avoid;
    }

    /* Headings */
    h1, h2, h3, h4, h5 {
        color: #0f172a;
        font-weight: 700;
        margin-top: 14pt;
        margin-bottom: 6pt;
        page-break-after: avoid;
    }

    h1 {
        font-size: 15pt;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 4pt;
        color: #0f172a;
        margin-top: 18pt;
    }

    .section-title {
        font-size: 13pt;
        background-color: #0f172a;
        color: #ffffff;
        padding: 6pt 10pt;
        border-radius: 3px;
        margin-top: 16pt;
        margin-bottom: 10pt;
    }

    h2 {
        font-size: 11pt;
        color: #0369a1;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 3pt;
        margin-top: 12pt;
    }

    h3 {
        font-size: 9.5pt;
        color: #1e293b;
        margin-top: 9pt;
    }

    h4 {
        font-size: 8.5pt;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 7pt;
    }

    p {
        margin-top: 0;
        margin-bottom: 5pt;
        text-align: justify;
    }

    ul, ol {
        margin-top: 0;
        margin-bottom: 5pt;
        padding-left: 18px;
    }

    li {
        margin-bottom: 2.5pt;
    }

    /* Tables */
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 5pt;
        margin-bottom: 8pt;
        font-size: 7.8pt;
    }

    table.data-table th, table.data-table td {
        border: 1px solid #cbd5e1;
        padding: 4pt 5.5pt;
        text-align: left;
        vertical-align: top;
    }

    table.data-table th {
        background-color: #0f172a;
        color: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 7.2pt;
        letter-spacing: 0.3px;
    }

    table.data-table tr:nth-child(even) td {
        background-color: #f8fafc;
    }

    table.data-table td.numeric {
        text-align: right;
        font-family: 'Courier New', Courier, monospace;
    }

    table.data-table th.numeric {
        text-align: right;
    }

    table.compact-table th, table.compact-table td {
        padding: 2.5pt 4pt;
        font-size: 7.2pt;
    }

    /* Callout boxes */
    .callout {
        padding: 5pt 8pt;
        border-left: 3.5px solid #0284c7;
        background-color: #f0f9ff;
        border-radius: 0 4px 4px 0;
        margin-top: 5pt;
        margin-bottom: 7pt;
        page-break-inside: avoid;
    }

    .callout-title {
        font-weight: 700;
        font-size: 8.2pt;
        color: #0369a1;
        margin-bottom: 2pt;
    }

    .callout.danger {
        border-left-color: #e11d48;
        background-color: #fff1f2;
    }
    .callout.danger .callout-title { color: #be123c; }

    .callout.warning {
        border-left-color: #d97706;
        background-color: #fffbeb;
    }
    .callout.warning .callout-title { color: #b45309; }

    .callout.success {
        border-left-color: #059669;
        background-color: #ecfdf5;
    }
    .callout.success .callout-title { color: #047857; }

    .callout.dark {
        border-left-color: #475569;
        background-color: #f8fafc;
    }
    .callout.dark .callout-title { color: #1e293b; }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 1.5pt 4pt;
        font-size: 6.8pt;
        font-weight: 700;
        border-radius: 3px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-primary { background-color: #0284c7; color: #ffffff; }
    .badge-success { background-color: #059669; color: #ffffff; }
    .badge-warning { background-color: #d97706; color: #ffffff; }
    .badge-danger { background-color: #e11d48; color: #ffffff; }
    .badge-gray { background-color: #64748b; color: #ffffff; }
    .badge-dark { background-color: #0f172a; color: #ffffff; }
    .badge-purple { background-color: #7c3aed; color: #ffffff; }

    /* Diagram / Code Block */
    .diagram-box {
        font-family: 'Courier New', Courier, monospace;
        font-size: 7.2pt;
        line-height: 1.35;
        background-color: #0f172a;
        color: #38bdf8;
        padding: 8pt 10pt;
        border-radius: 4px;
        margin-top: 5pt;
        margin-bottom: 7pt;
        white-space: pre-wrap;
        page-break-inside: avoid;
    }

    .code-inline {
        font-family: 'Courier New', Courier, monospace;
        background-color: #f1f5f9;
        padding: 1pt 3pt;
        border-radius: 2px;
        font-size: 7.8pt;
        color: #0f172a;
        border: 1px solid #e2e8f0;
    }

    /* Procedure step box */
    .procedure-box {
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        background-color: #ffffff;
        margin-bottom: 7pt;
        page-break-inside: avoid;
    }

    .procedure-header {
        background-color: #f1f5f9;
        padding: 4pt 7pt;
        font-weight: 700;
        font-size: 8.2pt;
        color: #0f172a;
        border-bottom: 1px solid #e2e8f0;
    }

    .procedure-body {
        padding: 5pt 7pt;
    }

    .step-list {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

    .step-item {
        position: relative;
        padding-left: 20px;
        margin-bottom: 4pt;
    }

    .step-number {
        position: absolute;
        left: 0;
        top: 0;
        background-color: #0284c7;
        color: #ffffff;
        font-size: 6.8pt;
        font-weight: 700;
        width: 14px;
        height: 14px;
        line-height: 14px;
        text-align: center;
        border-radius: 50%;
    }

    /* Cover Page */
    .cover-container {
        padding-top: 30mm;
        text-align: center;
    }

    .cover-badge {
        display: inline-block;
        background-color: #0284c7;
        color: #ffffff;
        font-size: 8.5pt;
        font-weight: 700;
        letter-spacing: 1.5px;
        padding: 3.5pt 12pt;
        border-radius: 20px;
        text-transform: uppercase;
        margin-bottom: 12pt;
    }

    .cover-title {
        font-size: 24pt;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
        margin-bottom: 6pt;
    }

    .cover-subtitle {
        font-size: 12pt;
        font-weight: 500;
        color: #0369a1;
        margin-bottom: 20pt;
        line-height: 1.3;
    }

    .cover-divider {
        width: 70px;
        height: 3.5px;
        background-color: #0284c7;
        margin: 0 auto 20pt auto;
        border-radius: 2px;
    }

    .cover-meta {
        margin: 0 auto;
        width: 85%;
        border-top: 1px solid #cbd5e1;
        border-bottom: 1px solid #cbd5e1;
        padding: 12pt 0;
        margin-bottom: 25pt;
    }

    .cover-meta table {
        width: 100%;
        border-collapse: collapse;
    }

    .cover-meta td {
        padding: 3pt 6pt;
        font-size: 8pt;
        color: #475569;
        text-align: left;
    }

    .cover-meta td strong {
        color: #0f172a;
    }

    .company-pills {
        margin-top: 12pt;
    }

    .company-pill {
        display: inline-block;
        background-color: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-weight: 600;
        padding: 3pt 7pt;
        border-radius: 3px;
        margin: 2pt;
        font-size: 7.5pt;
    }

    .toc-title {
        font-size: 14pt;
        font-weight: 700;
        color: #0f172a;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 4pt;
        margin-top: 15pt;
        margin-bottom: 12pt;
    }

    .toc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8pt;
    }

    .toc-table td {
        padding: 3.5pt 0;
        vertical-align: bottom;
    }

    .toc-section {
        font-weight: 700;
        color: #0f172a;
        padding-top: 6pt !important;
    }

    .toc-dots {
        border-bottom: 1px dotted #94a3b8;
        height: 1px;
    }

    .toc-page {
        text-align: right;
        font-weight: 600;
        color: #0369a1;
        width: 30px;
    }
</style>
</head>
<body>

<!-- RUNNING HEADER -->
<div class="running-header">
    <table>
        <tr>
            <td style="text-align: left; font-weight: 600; color: #0f172a;">YM Construction Management System (YM-CMS)</td>
            <td style="text-align: center; color: #0284c7; font-weight: 600;">HR & Workforce Module User Manual</td>
            <td style="text-align: right; color: #64748b;">Enterprise Standard Operating Procedure</td>
        </tr>
    </table>
</div>

<!-- ========================================== -->
<!-- COVER PAGE                                 -->
<!-- ========================================== -->
<div class="cover-container">
    <div class="cover-badge">Enterprise Workforce Management Edition</div>
    <div class="cover-title">Human Resources & Workforce Management</div>
    <div class="cover-subtitle">Complete Operational Architecture, Workflows, Biometrics, Payroll & Lifecycle Guide</div>
    <div class="cover-divider"></div>

    <p style="text-align: center; font-size: 8.5pt; color: #475569; max-width: 80%; margin: 0 auto 16pt auto;">
        An authoritative, end-to-end standard operating manual covering the complete HR ecosystem across all four operating companies: Multi-Company Tenancy, Organization & Department Hierarchy, Employee Master Profiles, Versioned Document Vault, Attendance & Shift Scheduling, ZKTeco Biometric Push/Pull Ingestion, Double-Entry Leave Ledger, 2-Tier Department Operations & Daily Work Reporting, Employee Financing Subledger, Attendance-Driven Payroll Processing, Fixed Asset Custody, Performance Appraisals, Separation & Clearance, and Source-Reconciled Final Settlements.
    </p>

    <div class="cover-meta">
        <table>
            <tr>
                <td style="width: 25%;"><strong>Document Version:</strong> 3.0 (Enterprise)</td>
                <td style="width: 35%;"><strong>Target Framework:</strong> Laravel 13 / Filament v5</td>
                <td style="width: 40%;"><strong>Database Engine:</strong> Multi-Company Tenancy (SQLite/MySQL)</td>
            </tr>
            <tr>
                <td><strong>Security Model:</strong> Spatie / Filament Shield</td>
                <td><strong>Biometric Protocol:</strong> ZKTeco ADMS Push & TCP Pull</td>
                <td><strong>Accounting Engine:</strong> Double-Entry General Ledger</td>
            </tr>
            <tr>
                <td><strong>Author:</strong> Lead Systems Architect</td>
                <td><strong>Classification:</strong> Confidential & Proprietary</td>
                <td><strong>Published:</strong> August 2026</td>
            </tr>
        </table>
    </div>

    <div style="font-size: 7.5pt; color: #64748b; margin-top: 10pt; text-align: center;">
        <strong>Multi-Company Operating Group Scope:</strong>
        <div class="company-pills">
            <span class="company-pill">BMC Construction</span>
            <span class="company-pill">YMC Construction</span>
            <span class="company-pill">7 Orbit</span>
            <span class="company-pill">7 Orbit Medical Billing</span>
        </div>
    </div>
</div>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- TABLE OF CONTENTS                          -->
<!-- ========================================== -->
<div class="toc-title">Table of Contents</div>

<table class="toc-table">
    <tr>
        <td class="toc-section" colspan="3">1. Complete HR Architecture & Organizational Hierarchy</td>
    </tr>
    <tr>
        <td style="width: 60%; padding-left: 10px;">1.1 Multi-Tenant Corporate Structure & Isolation Model</td>
        <td class="toc-dots"></td>
        <td class="toc-page">5</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">1.2 Separation of Global Employee Identity vs Company Employment</td>
        <td class="toc-dots"></td>
        <td class="toc-page">5</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">1.3 Department Hierarchy & Nested Parent-Child Architecture</td>
        <td class="toc-dots"></td>
        <td class="toc-page">5</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">1.4 Designations & Departmental Linkages</td>
        <td class="toc-dots"></td>
        <td class="toc-page">6</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">1.5 Work Locations & Project Site Linkages</td>
        <td class="toc-dots"></td>
        <td class="toc-page">6</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">1.6 Department Teams & Specialized Pods</td>
        <td class="toc-dots"></td>
        <td class="toc-page">6</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">1.7 Employee Categorization & Employment Types</td>
        <td class="toc-dots"></td>
        <td class="toc-page">6</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">1.8 Atomic Sequential Employee Code Allocation Engine</td>
        <td class="toc-dots"></td>
        <td class="toc-page">7</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">1.9 Comprehensive HR Entity-Relationship Architecture Diagram</td>
        <td class="toc-dots"></td>
        <td class="toc-page">7</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">2. Employee Master & Complete Profile Management</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">2.1 Employee Master Architecture & Data Flow</td>
        <td class="toc-dots"></td>
        <td class="toc-page">9</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">2.2 Personal Identity, PII Encryption & Keyed Hashes</td>
        <td class="toc-dots"></td>
        <td class="toc-page">9</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">2.3 Company Employment Attributes & Lifecycle Dates</td>
        <td class="toc-dots"></td>
        <td class="toc-page">9</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">2.4 Profile Infolists & Tabbed Sub-Masters (Contacts, Qualifications, Experience, Banks)</td>
        <td class="toc-dots"></td>
        <td class="toc-page">10</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">2.5 Security, Masking & Sensitive Field Governance</td>
        <td class="toc-dots"></td>
        <td class="toc-page">10</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">3. Typed HR Documents & Compliance Management (HR-2)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">3.1 Controlled Document Types & Applicability Scopes</td>
        <td class="toc-dots"></td>
        <td class="toc-page">11</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">3.2 Private Storage, SHA-256 Checksums & Immutable Versions</td>
        <td class="toc-dots"></td>
        <td class="toc-page">11</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">3.3 Verification & Approval Workflow</td>
        <td class="toc-dots"></td>
        <td class="toc-page">11</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">3.4 Compliance Audit Summary & Missing Document Tracking</td>
        <td class="toc-dots"></td>
        <td class="toc-page">12</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">4. Work Calendars, Shifts & Biometric Attendance Management (HR-3, HR-4, HR-5)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">4.1 Work Calendars, Shift Schedules & Overnight Cross-Midnight Shifts</td>
        <td class="toc-dots"></td>
        <td class="toc-page">13</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">4.2 Effective-Dated Attendance Calculation Rules & Thresholds</td>
        <td class="toc-dots"></td>
        <td class="toc-page">13</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">4.3 Biometric Ingestion: ZKTeco ADMS Push Protocol & Direct TCP Pull</td>
        <td class="toc-dots"></td>
        <td class="toc-page">13</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">4.4 Device User Mappings, Deduplication & Quarantine Replay</td>
        <td class="toc-dots"></td>
        <td class="toc-page">14</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">4.5 Normalized CSV Import Contract & Manual Ingestion</td>
        <td class="toc-dots"></td>
        <td class="toc-page">14</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">4.6 Daily Attendance Processing & Maker-Checker Corrections</td>
        <td class="toc-dots"></td>
        <td class="toc-page">14</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">4.7 Monthly Summaries & Finalization (Immutable Payroll Input)</td>
        <td class="toc-dots"></td>
        <td class="toc-page">15</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">5. Leave Management & Double-Entry Ledger Engine (HR-3)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">5.1 Leave Types & Effective-Dated Policies</td>
        <td class="toc-dots"></td>
        <td class="toc-page">16</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">5.2 Double-Entry Leave Ledger Mechanics (Opening, Accrual, Consumption, Reversal)</td>
        <td class="toc-dots"></td>
        <td class="toc-page">16</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">5.3 3-Tier Leave Workflow: Request $\rightarrow$ Manager Approval $\rightarrow$ HR Final Sign-off</td>
        <td class="toc-dots"></td>
        <td class="toc-page">16</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">5.4 Negative Balance Controls, Encashment & Downstream Payroll Impact</td>
        <td class="toc-dots"></td>
        <td class="toc-page">17</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">6. Department Operations, Tasks & 6:00 PM Daily Reporting</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">6.1 Department Operations Architecture & Team Delegation</td>
        <td class="toc-dots"></td>
        <td class="toc-page">18</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">6.2 Specialized Workflow Sub-Modules (Social Media, Design, Video, Web Dev, Sales)</td>
        <td class="toc-dots"></td>
        <td class="toc-page">18</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">6.3 2-Tier Quality Approval Chain (Lead Review $\rightarrow$ Head Final Sign-off)</td>
        <td class="toc-dots"></td>
        <td class="toc-page">19</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">6.4 Mandatory 6:00 PM Daily Reporting Cutoff & Automated Notifications</td>
        <td class="toc-dots"></td>
        <td class="toc-page">19</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">6.5 Head Operations Dashboard & Real-Time Daily Attendance & Work Matrix</td>
        <td class="toc-dots"></td>
        <td class="toc-page">19</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">6.6 Composite Employee Productivity Scorecard (0–100 Mathematical Formula)</td>
        <td class="toc-dots"></td>
        <td class="toc-page">19</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">7. Employee Loans, Advances & Subledger Engine (HR-6)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">7.1 Loans vs Advances: Architectural Distinction & Control Accounts</td>
        <td class="toc-dots"></td>
        <td class="toc-page">21</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">7.2 Amortization Schedules & Treasury Disbursement</td>
        <td class="toc-dots"></td>
        <td class="toc-page">21</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">7.3 Multi-Channel Recovery: Payroll, Direct Receipt, Early Payoff & Settlement</td>
        <td class="toc-dots"></td>
        <td class="toc-page">21</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">7.4 Rescheduling, Principal Waivers & Reversals</td>
        <td class="toc-dots"></td>
        <td class="toc-page">22</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">8. Payroll Processing, Calculation Rules & Double-Entry Accounting (HR-7)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">8.1 Compensation History & Salary Allowances Structure</td>
        <td class="toc-dots"></td>
        <td class="toc-page">23</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">8.2 Effective-Dated Calculation Rules & Attendance Deductions</td>
        <td class="toc-dots"></td>
        <td class="toc-page">23</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">8.3 Variable Earning Sources: Bonus & Incentives</td>
        <td class="toc-dots"></td>
        <td class="toc-page">23</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">8.4 Monthly Payroll Run Execution & Component Traceability</td>
        <td class="toc-dots"></td>
        <td class="toc-page">23</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">8.5 Double-Entry General Ledger Posting Engine & Reversal Mechanics</td>
        <td class="toc-dots"></td>
        <td class="toc-page">24</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">8.6 Construction Project Cost Allocations & Treasury Salary Settlement</td>
        <td class="toc-dots"></td>
        <td class="toc-page">24</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">9. Joining Letters, Recruitment & Onboarding Foundation</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">9.1 Joining Letter Templates & Placeholder Substitution Engine</td>
        <td class="toc-dots"></td>
        <td class="toc-page">25</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">9.2 Draft Generation, Approval & Cryptographic Issuance</td>
        <td class="toc-dots"></td>
        <td class="toc-page">25</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">10. Performance Appraisals & Disciplinary Warnings (HR-8)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">10.1 KPI Master Library & 100-Point Appraisal Cycles</td>
        <td class="toc-dots"></td>
        <td class="toc-page">26</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">10.2 Warning Letter Templates, Issuance, Response & Closure</td>
        <td class="toc-dots"></td>
        <td class="toc-page">26</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">11. Employment Movements, Transfers & Promotions (HR-8)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">11.1 Movement Requests (Promotion vs Transfer) & Workflow</td>
        <td class="toc-dots"></td>
        <td class="toc-page">27</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">11.2 Historical Integrity & Audit Logs</td>
        <td class="toc-dots"></td>
        <td class="toc-page">27</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">12. Fixed Asset Custody & Issuance (HR-9)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">12.1 Custody Issuance, Single Custodian Locking & Acknowledgement</td>
        <td class="toc-dots"></td>
        <td class="toc-page">28</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">12.2 Transfers, Return Inspections, Damage/Loss Recommendations</td>
        <td class="toc-dots"></td>
        <td class="toc-page">28</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">13. Separation, Clearance & Final Settlement (HR-8, HR-9, HR-10)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">13.1 Resignation & Termination Lifecycle & Access Review</td>
        <td class="toc-dots"></td>
        <td class="toc-page">29</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">13.2 Multi-Departmental Employee Clearance Protocol</td>
        <td class="toc-dots"></td>
        <td class="toc-page">29</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">13.3 Source-Reconciled Final Settlement Calculation</td>
        <td class="toc-dots"></td>
        <td class="toc-page">29</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">13.4 Final Settlement GL Posting, Reversal & Treasury Settlement</td>
        <td class="toc-dots"></td>
        <td class="toc-page">30</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">14. HR Reports, Analytics & Group Consolidation (HR-11)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">14.1 Tenant HR Reports & Dashboard Catalog</td>
        <td class="toc-dots"></td>
        <td class="toc-page">31</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">14.2 Group HR Multi-Company Consolidated Reporting</td>
        <td class="toc-dots"></td>
        <td class="toc-page">31</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">15. HR Data Migration, Rollback & Operational Readiness (HR-12)</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">15.1 Controlled CSV Migration Pipeline & Rollback Engine</td>
        <td class="toc-dots"></td>
        <td class="toc-page">32</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">15.2 HR Operational Readiness Auditing & Recovery Manifest</td>
        <td class="toc-dots"></td>
        <td class="toc-page">32</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">16. Roles, Authorization & Maker-Checker Security Matrix</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">16.1 Pre-Configured HR Roles & Scope Separation</td>
        <td class="toc-dots"></td>
        <td class="toc-page">33</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">16.2 Sensitive Permissions & PII Protection Boundaries</td>
        <td class="toc-dots"></td>
        <td class="toc-page">33</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">16.3 Segregation of Duties Matrix Across All HR Workflows</td>
        <td class="toc-dots"></td>
        <td class="toc-page">33</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">17. Practical Operational Scenarios & End-to-End Walkthroughs</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">17.1 Hiring & Onboarding Walkthrough</td>
        <td class="toc-dots"></td>
        <td class="toc-page">35</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">17.2 Monthly Payroll Execution Walkthrough</td>
        <td class="toc-dots"></td>
        <td class="toc-page">35</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">17.3 Resignation, Clearance & Settlement Walkthrough</td>
        <td class="toc-dots"></td>
        <td class="toc-page">36</td>
    </tr>

    <tr>
        <td class="toc-section" colspan="3">18. Troubleshooting & Operational Quick Reference</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">18.1 Comprehensive Troubleshooting Matrix</td>
        <td class="toc-dots"></td>
        <td class="toc-page">37</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">18.2 System Navigation Index & URL Reference Table</td>
        <td class="toc-dots"></td>
        <td class="toc-page">37</td>
    </tr>
    <tr>
        <td style="padding-left: 10px;">18.3 Key HR & Technical Terms Glossary</td>
        <td class="toc-dots"></td>
        <td class="toc-page">39</td>
    </tr>
</table>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 1: HR HIERARCHY & ARCHITECTURE     -->
<!-- ========================================== -->
<div class="section-title">1. Complete HR Architecture & Organizational Hierarchy</div>

<p>
    The <strong>Human Resources & Workforce Management Module</strong> of the YM Construction Management System (YM-CMS) provides a multi-company, lifecycle-driven human capital architecture. It is specifically designed to meet the rigorous compliance, organizational governance, cost-allocation, and biometric tracking requirements of construction contracting, IT technology ventures, medical billing operations, and group corporate administration.
</p>

<h2>1.1 Multi-Tenant Corporate Structure & Tenancy Isolation</h2>
<p>
    YM-CMS operates four distinct, legally independent operating companies within a single unified Laravel and Filament framework. None of these entities is configured as a synthetic tenant or database-level subsidiary; each maintains completely isolated memberships, organizational structures, chart of accounts, employee master records, attendance logs, and financial ledgers:
</p>

<ul>
    <li><strong>BMC Construction:</strong> Civil engineering, infrastructure development, heavy construction contracting, and site-based project operations.</li>
    <li><strong>YMC Construction:</strong> General contracting, architectural development, project subcontracting, and construction procurement.</li>
    <li><strong>7 Orbit:</strong> Digital marketing, software engineering, media production, creative branding, and technology solutions.</li>
    <li><strong>7 Orbit Medical Billing:</strong> Revenue cycle management (RCM), medical coding, healthcare billing claims, and professional services.</li>
</ul>

<div class="callout">
    <div class="callout-title">Tenancy & Security Invariant</div>
    All HR resources enforce strict tenant scoping via <span class="code-inline">company_id</span>. Cross-company visibility, document sharing, or reporting lines are prohibited at both the database foreign-key layer and Eloquent policy boundaries. Group-level reporting is permitted solely through authorized multi-company aggregation interfaces for users possessing explicit group-wide authorization.
</div>

<h2>1.2 Separation of Global Employee Identity vs Company-Specific Employment</h2>
<p>
    A foundational architectural innovation in YM-CMS is the strict separation between an individual's <strong>Global Personal Identity</strong> (<span class="code-inline">Employee</span>) and their <strong>Company-Specific Operational Contract</strong> (<span class="code-inline">Employment</span>):
</p>

<div class="diagram-box">
+---------------------------------------------------------------------------------------+
| GLOBAL EMPLOYEE IDENTITY (app/Models/Employee.php)                                   |
| - Full Legal Name, Date of Birth, Gender, Marital Status, Blood Group                 |
| - Normalized CNIC (Encrypted at Rest with SHA-256 Keyed Lookup Hash)                  |
| - Profile Picture, Global Contact Info, Emergency Contacts, Qualifications, Past Exp |
+---------------------------------------------------------------------------------------+
                                           |
                   +-----------------------+-----------------------+
                   | (1-to-Many Multi-Company Employments)         |
                   v                                               v
+------------------------------------+   +------------------------------------+
| BMC EMPLOYMENT (Employment.php)    |   | 7 ORBIT EMPLOYMENT (Employment.php)|
| - Company: BMC Construction        |   | - Company: 7 Orbit                 |
| - Code: EMP-00001                  |   | - Code: EMP-00001 (Isolated)       |
| - Dept: Civil Engineering          |   | - Dept: Web Development            |
| - Designation: Project Manager     |   | - Designation: Senior Tech Lead    |
| - Category: Project Staff          |   | - Category: Administrative Staff   |
| - Type: Permanent                  |   | - Type: Contract                   |
| - Status: Active                   |   | - Status: Active                   |
| - Compensation: PKR 150,000 / mo   |   | - Compensation: PKR 180,000 / mo   |
| - Shift: Construction Site Shift   |   | - Shift: Head Office Tech Shift    |
+------------------------------------+   +------------------------------------+
</div>

<p>
    This dual-entity design enables a single person (e.g., an executive engineer or IT director) to hold simultaneous or sequential employments across different sister companies without duplicating their personal profile, while guaranteeing that payroll, attendance, loans, taxes, and departmental reporting remain 100% isolated within each respective legal company.
</p>

<h2>1.3 Department Hierarchy & Nested Parent-Child Architecture</h2>
<p>
    Departments in YM-CMS (<span class="code-inline">Department</span>) are company-scoped organizational units supporting unlimited recursive parent-child hierarchy. A department can be a root-level division (e.g., <em>Operations</em>, <em>Finance & Accounts</em>) or a sub-department (e.g., <em>Site Logistics</em> under <em>Operations</em>).
</p>

<ul>
    <li><strong>Self-Parenting Prevention:</strong> A department cannot be assigned as its own parent.</li>
    <li><strong>Cycle Prevention:</strong> Recursive ancestor scanning blocks circular dependencies (e.g., A $\rightarrow$ B $\rightarrow$ C $\rightarrow$ A).</li>
    <li><strong>Same-Company Enforcement:</strong> Parent departments must strictly belong to the same active company tenant.</li>
    <li><strong>Child-Aware Deletion Constraint:</strong> A department cannot be deleted if it has child departments or active employments assigned to it.</li>
</ul>

<h2>1.4 Designations & Departmental Linkages</h2>
<p>
    Designations (<span class="code-inline">Designation</span>) represent formal corporate job titles (e.g., <em>Chief Operating Officer</em>, <em>Site Engineer</em>, <em>Full Stack Developer</em>, <em>Medical Billing Specialist</em>). Designations are company-scoped and can optionally be linked to a specific department. In the Employment creation interface, selecting a Department dynamically filters the available Designations to ensure organizational consistency.
</p>

<h2>1.5 Work Locations & Project Site Linkages</h2>
<p>
    Work Locations (<span class="code-inline">WorkLocation</span>) establish controlled physical or virtual job sites for staff assignment. Each location captures a location code, location name, physical address, and an optional link to an active same-company <strong>Project Site</strong> (<span class="code-inline">ProjectSite</span>). This allows attendance devices, site allowances, and project labor cost distributions to automatically bind to specific physical work locations.
</p>

<h2>1.6 Department Teams & Specialized Pods</h2>
<p>
    Under Phase D additions, Departments can be further structured into operational <strong>Department Teams</strong> (<span class="code-inline">DepartmentTeam</span>). Teams allow fine-grained task delegation, daily check-in supervision, and specialized creative/technical workflows. Supported team types include:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 20%;">Team Type</th>
            <th style="width: 25%;">Domain Focus</th>
            <th style="width: 55%;">Specialized Tracking Schema & Features</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Social Media</strong></td>
            <td>Digital Marketing & Growth</td>
            <td>8-stage content pipeline, platform targeting (Meta, LinkedIn, X, TikTok), caption bank, scheduling.</td>
        </tr>
        <tr>
            <td><strong>Graphic Design</strong></td>
            <td>Visual Creative & Branding</td>
            <td>Design types (Post, Banner, Brochure, UI), dimensions/aspect ratios, brand briefs, Figma/PSD deliverables.</td>
        </tr>
        <tr>
            <td><strong>Video Production</strong></td>
            <td>Reels, Promos & Walkthroughs</td>
            <td>Video formats, target durations, cloud footage URLs, script notes, Draft V1/V2 & Final Cut links.</td>
        </tr>
        <tr>
            <td><strong>Web Development</strong></td>
            <td>Software Engineering</td>
            <td>Task categories, GitHub PR links, staging/live URLs, QA testing statuses, bug tracking.</td>
        </tr>
        <tr>
            <td><strong>Sales & CRM</strong></td>
            <td>Outreach & Client Acquisition</td>
            <td>Daily outreach KPIs (leads received, calls made, meetings booked, proposals sent, closed revenue).</td>
        </tr>
        <tr>
            <td><strong>General / Operations</strong></td>
            <td>Standard Department Work</td>
            <td>Standard task assignment, deliverable attachments, deadline tracking, and blocker escalations.</td>
        </tr>
    </tbody>
</table>

<h2>1.7 Employee Categorization & Employment Types</h2>
<p>
    To satisfy accounting, payroll distribution, and statutory reporting rules, every Employment is categorized into two distinct orthogonal classifications:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Classification Axis</th>
            <th style="width: 25%;">Allowed Enum Values</th>
            <th style="width: 50%;">Operational & Accounting Significance</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Employment Category</strong><br><span class="code-inline">EmploymentCategory</span></td>
            <td>
                • <span class="badge badge-dark">Director</span><br>
                • <span class="badge badge-primary">Administrative Staff</span><br>
                • <span class="badge badge-success">Project Staff</span>
            </td>
            <td>
                <strong>Critical Accounting Split:</strong> Administrative Staff and Directors post salary expenses directly to Head Office overhead accounts (<span class="code-inline">5010 Salaries Expense</span>). Project Staff require mandatory project and cost-center allocations, posting directly to <span class="code-inline">7000 Construction Direct Labor Cost</span>.
            </td>
        </tr>
        <tr>
            <td><strong>Employment Type</strong><br><span class="code-inline">EmploymentType</span></td>
            <td>
                • <span class="badge badge-primary">Permanent</span><br>
                • <span class="badge badge-warning">Contract</span><br>
                • <span class="badge badge-gray">Daily Wages</span><br>
                • <span class="badge badge-purple">Internship</span>
            </td>
            <td>
                Governs leave accrual eligibility, probation requirements, notice period mandates, and benefits entitlement in final settlement calculations.
            </td>
        </tr>
        <tr>
            <td><strong>Employment Status</strong><br><span class="code-inline">EmploymentStatus</span></td>
            <td>
                • <span class="badge badge-warning">Probation</span><br>
                • <span class="badge badge-success">Active</span><br>
                • <span class="badge badge-primary">On Leave</span><br>
                • <span class="badge badge-danger">Resigned</span><br>
                • <span class="badge badge-danger">Terminated</span><br>
                • <span class="badge badge-gray">Ended</span> (Legacy Read-Only)
            </td>
            <td>
                Determines active presence in biometric sync, daily attendance processing, monthly payroll generation, task delegation eligibility, and clearance readiness.
            </td>
        </tr>
    </tbody>
</table>

<h2>1.8 Atomic Sequential Employee Code Allocation Engine</h2>
<p>
    Employee Codes (e.g., <span class="code-inline">EMP-00001</span>) are generated dynamically and atomically per company using the <span class="code-inline">EmployeeCodeSequence</span> model and <span class="code-inline">AllocateEmployeeCodeAction</span>:
</p>

<ul>
    <li><strong>Row Locking:</strong> The company's sequence record is locked using database-level pessimistic locking (<span class="code-inline">lockForUpdate()</span>) within an active transaction.</li>
    <li><strong>Collision Avoidance:</strong> If a manually entered or legacy code collides with the sequence, the engine scans forward and skips to the next available unique code.</li>
    <li><strong>Configurable Formatting:</strong> The prefix (default <span class="code-inline">EMP</span>) and zero-padding width (default 5 digits) can be customized per company tenant via the <em>Employee Code Sequences</em> configuration resource.</li>
    <li><strong>Tenancy Isolation:</strong> Sequence counters are strictly isolated; Company A and Company B can both maintain an independent <span class="code-inline">EMP-00001</span> without conflict.</li>
</ul>

<h2>1.9 Comprehensive HR Entity-Relationship Architecture Diagram</h2>
<div class="diagram-box">
+----------------------------------------------------------------------------------------------------+
|                                    COMPANY TENANT (Company.php)                                   |
+----------------------------------------------------------------------------------------------------+
   |               |                   |                  |                   |                 |
   v               v                   v                  v                   v                 v
+------------+  +--------------+  +---------------+  +--------------+  +--------------+  +--------------+
| Department |  | Designation  |  | Work Location |  | Work Calendar|  |  Leave Type  |  | Attend Device|
+------------+  +--------------+  +---------------+  +--------------+  +--------------+  +--------------+
   | (Tree)        |                   |                  |                   |                 | (Push/Pull)
   v               v                   v                  v                   v                 v
+-------------------------------------------------------------------------------------+  +--------------+
|                         COMPANY EMPLOYMENT (Employment.php)                          |<---| Device Mapping|
| - Links Global Employee Master (CNIC, Bio, Contacts, Education, Experience, Banks)  |  +--------------+
| - Manages Lifecycle Dates (Joining, Probation, Confirmation, Separation)            |         |
+-------------------------------------------------------------------------------------+         v
   |             |              |             |              |             |             +--------------+
   v             v              v             v              v             v             | Raw Punches  |
+-------------+ +------------+ +-----------+ +------------+ +-----------+ +------------+ +--------------+
| Compensation| | Daily Tasks| | Att Record| |Leave Ledger| | Financing | | Performance|        |
|  (Approved) | | (2-Tier)   | | (Summary) | | (Balances) | |(Loan/Adv) | | Appraisals |        v
+-------------+ +------------+ +-----------+ +------------+ +-----------+ +------------+ +--------------+
   |             |              |             |              |             |             | Daily Attend |
   +-------------+--------------+-------------+--------------+-------------+             +--------------+
                                       |
                                       v
                     +-----------------------------------+
                     |    MONTHLY PAYROLL RUN (HR-7)     |
                     | - Payable Days & Prorated Basic   |
                     | - Allowances + Bonus/Incentives   |
                     | - Attendance & Unpaid Deductions  |
                     | - Loan Installment Recoveries     |
                     | - GL Posting & Treasury Pay Slip  |
                     +-----------------------------------+
                                       |
                                       v (Upon Separation)
                     +-----------------------------------+
                     |  FINAL SETTLEMENT ENGINE (HR-10)  |
                     | - Multi-Dept Clearance (HR-9)     |
                     | - Asset Custody Return (HR-9)     |
                     | - Leave Encashment + Loan Payoff  |
                     | - Balanced GL Posting & Payout    |
                     +-----------------------------------+
</div>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 2: EMPLOYEE MASTER PROFILE         -->
<!-- ========================================== -->
<div class="section-title">2. Employee Master & Complete Profile Management</div>

<p>
    The Employee Master is the central repository of personnel data within YM-CMS. It provides HR administrators, department heads, and compliance officers with complete 360-degree visibility over an individual's personal identity, emergency contacts, academic qualifications, employment history, bank accounts, approved salary structure, asset custodies, performance records, and legal document compliance.
</p>

<h2>2.1 Employee Master Architecture & Data Flow</h2>
<p>
    When creating a new worker, the system automatically performs an atomic transaction that creates the global <span class="code-inline">Employee</span> record and instantiates their primary <span class="code-inline">Employment</span> within the active company tenant.
</p>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Creating a New Employee Record</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Sidebar Navigation:</strong> Expand <code>HR Management</code> and select <code>Employees</code> (<span class="code-inline">/admin/company/{tenant}/employees</span>).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Action:</strong> Click the primary <code>+ New Employee</code> button in the header toolbar.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Tab 1: Personal Information:</strong> Enter First Name, Last Name, Father/Husband Name, CNIC, Date of Birth, Gender, Marital Status, Blood Group, Personal Email, Mobile Number, and Residential Address. Upload a profile photograph (JPEG/PNG, max 2MB).
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Tab 2: Employment Details:</strong> Select Department, Designation, Employment Category (Director, Administrative Staff, Project Staff), Employment Type (Permanent, Contract, Daily Wages, Internship), Joining Date, Reporting Manager, Work Location, and Work Schedule. Set Employee Code to <em>Auto-generated</em> or input a manual override.
            </li>
            <li class="step-item">
                <div class="step-number">5</div>
                <strong>Save & Commit:</strong> Click <code>Create</code>. The system transactionally commits the Employee, generates the atomic Employee Code, links the initial Employment, logs the creation audit event, and redirects to the full Profile View.
            </li>
        </ul>
    </div>
</div>

<h2>2.2 Personal Identity, PII Encryption & Keyed Hashes</h2>
<p>
    To comply with strict enterprise data privacy standards, all Personally Identifiable Information (PII) is protected using envelope encryption at rest:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Field Identifier</th>
            <th style="width: 25%;">Database Column & Cast</th>
            <th style="width: 50%;">Encryption, Normalization & Security Controls</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>National ID (CNIC)</strong></td>
            <td><span class="code-inline">cnic</span> (encrypted:string)<br><span class="code-inline">cnic_hash</span> (indexed string)</td>
            <td>
                • Automatically stripped of dashes (normalized to 13 digits: <span class="code-inline">3520112345671</span>).<br>
                • Encrypted at rest via Laravel AES-256-CBC cipher.<br>
                • Supported by a keyed HMAC-SHA256 hash (<span class="code-inline">cnic_hash</span>) for instant duplicate detection and search lookups without plaintext exposure.<br>
                • Masked on screens (<span class="code-inline">35201-*****71-1</span>) unless the user possesses the <span class="code-inline">ViewSensitiveData:Employee</span> permission.
            </td>
        </tr>
        <tr>
            <td><strong>Bank Account & IBAN</strong></td>
            <td><span class="code-inline">account_number</span> (encrypted)<br><span class="code-inline">iban</span> (encrypted)</td>
            <td>
                • Fully encrypted at rest in <span class="code-inline">employee_bank_accounts</span>.<br>
                • Masked in general UI views (<span class="code-inline">PK** **** **** 1234</span>).<br>
                • Excluded from standard Spatie activity log payload snapshots to prevent log leakage.
            </td>
        </tr>
        <tr>
            <td><strong>Private Health & Medical</strong></td>
            <td><span class="code-inline">medical_notes</span> (encrypted)</td>
            <td>
                • Accessible only to users holding the specialized <span class="code-inline">ViewMedicalInfo:Employee</span> permission.<br>
                • Hidden from department heads and team leads.
            </td>
        </tr>
    </tbody>
</table>

<h2>2.3 Company Employment Attributes & Lifecycle Dates</h2>
<p>
    An individual's active Employment record governs their operational lifecycle. Important fields include:
</p>

<ul>
    <li><strong>Joining Date (<span class="code-inline">joining_date</span>):</strong> The official start date. Used to prorate basic salary in the first payroll month and determine leave accrual eligibility.</li>
    <li><strong>Probation Start & End (<span class="code-inline">probation_start_date</span>, <span class="code-inline">probation_end_date</span>):</strong> Explicit duration of trial employment. When probation is successfully completed, HR records the <strong>Confirmation Date</strong> (<span class="code-inline">confirmation_date</span>), moving status from <span class="code-inline">Probation</span> to <span class="code-inline">Active</span>.</li>
    <li><strong>Notice Period Days (<span class="code-inline">notice_period_days</span>):</strong> Calendar days of advance notice required upon resignation. Used during separation and final settlement notice-pay shortfall calculations.</li>
    <li><strong>Reporting Manager (<span class="code-inline">reporting_to_employment_id</span>):</strong> Points to another active Employment within the same company. The system automatically prevents self-reporting and circular management loops.</li>
    <li><strong>Employment Change History (<span class="code-inline">employment_changes</span>):</strong> Any modification to an employee's department, designation, manager, work location, or status automatically creates an immutable, append-only history record capturing the old value, new value, effective date, and acting user ID.</li>
</ul>

<h2>2.4 Profile Infolists & Tabbed Sub-Masters</h2>
<p>
    The Employee Profile infolist provides dedicated relation managers for managing detailed personnel records:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 20%;">Sub-Master Tab</th>
            <th style="width: 30%;">Managed Fields</th>
            <th style="width: 50%;">Business Rules & Validation Constraints</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Emergency Contacts</strong><br>(<span class="code-inline">EmployeeEmergencyContact</span>)</td>
            <td>Contact Name, Relationship, Primary Phone, Secondary Phone, Work Phone, Address, Is Primary Flag.</td>
            <td>Exactly one primary emergency contact is enforced per employee. Secondary phone and full address are masked without sensitive data authorization.</td>
        </tr>
        <tr>
            <td><strong>Qualifications</strong><br>(<span class="code-inline">EmployeeQualification</span>)</td>
            <td>Degree/Diploma Title, Major/Field of Study, Institution Name, Passing Year, Total Marks/CGPA, Obtained Marks, Grade.</td>
            <td>Passing year cannot be in the future. Multiple degrees are ordered chronologically. Used for HR verification and compliance audits.</td>
        </tr>
        <tr>
            <td><strong>Previous Experience</strong><br>(<span class="code-inline">EmployeeExperience</span>)</td>
            <td>Organization Name, Designation Held, Starting Date, Ending Date, Reason for Leaving, Contact Reference.</td>
            <td>Ending date cannot precede starting date. Total prior service years are computed automatically for seniority calculations.</td>
        </tr>
        <tr>
            <td><strong>Bank Accounts</strong><br>(<span class="code-inline">EmployeeBankAccount</span>)</td>
            <td>Bank Name, Branch Name/Code, Account Title, Account Number, IBAN, Is Primary Payroll Flag.</td>
            <td>Enforces exactly one active primary payroll bank account per employment. Account number and IBAN are encrypted at rest. Used by Payroll Bank Advice exports.</td>
        </tr>
        <tr>
            <td><strong>Compensation History</strong><br>(<span class="code-inline">EmploymentCompensation</span>)</td>
            <td>Monthly Basic Salary, House & Travel, Food, Fuel, Mobile, Internet, Site, Project, Other Allowances, Gross Salary.</td>
            <td>Effective-dated salary records. Approved salaries are 100% immutable. Approving a new compensation automatically closes the previous period.</td>
        </tr>
        <tr>
            <td><strong>Document Vault</strong><br>(<span class="code-inline">Document</span>)</td>
            <td>Controlled Document Type, Title, Issue Date, Expiry Date, Verification Status, Version History, File Attachment.</td>
            <td>Private local storage. Uploading replacement files increments version counter (<span class="code-inline">v1 &rarr; v2</span>) without overwriting historical files.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 3: TYPED HR DOCUMENTS & COMPLIANCE -->
<!-- ========================================== -->
<div class="section-title">3. Typed HR Documents & Compliance Management (HR-2)</div>

<p>
    Phase HR-2 establishes an enterprise-grade document compliance subsystem built directly on top of the YM-CMS private document platform. It standardizes corporate onboarding documentation, enforces cryptographic integrity, supports multi-tier verifications, and highlights missing mandatory compliance records.
</p>

<h2>3.1 Controlled Document Types & Applicability Scopes</h2>
<p>
    The system standardizes personnel documentation into six controlled, company-configurable document types (<span class="code-inline">HrDocumentType</span>):
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Document Type Code</th>
            <th style="width: 20%;">Applicability Scope</th>
            <th style="width: 20%;">Default Sensitivity</th>
            <th style="width: 35%;">Required Attributes & Compliance Rules</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>CNIC</strong></td>
            <td><span class="badge badge-primary">Employee (Global)</span></td>
            <td><span class="badge badge-danger">Restricted</span></td>
            <td>Requires Issue Date and Expiry Date. Requires Identity Document permission to view/download.</td>
        </tr>
        <tr>
            <td><strong>Educational Document</strong></td>
            <td><span class="badge badge-primary">Employee (Global)</span></td>
            <td><span class="badge badge-warning">Confidential</span></td>
            <td>Degree certificate or official transcript. Verified by HR against submitted qualifications.</td>
        </tr>
        <tr>
            <td><strong>Experience Certificate</strong></td>
            <td><span class="badge badge-primary">Employee (Global)</span></td>
            <td><span class="badge badge-warning">Confidential</span></td>
            <td>Relieving letters and service certificates from previous employers.</td>
        </tr>
        <tr>
            <td><strong>Appointment Letter</strong></td>
            <td><span class="badge badge-success">Employment (Company)</span></td>
            <td><span class="badge badge-danger">Restricted</span></td>
            <td>Company-specific signed employment contract or formal offer letter.</td>
        </tr>
        <tr>
            <td><strong>Medical Certificate</strong></td>
            <td><span class="badge badge-primary">Employee (Global)</span></td>
            <td><span class="badge badge-danger">Restricted</span></td>
            <td>Pre-employment medical fitness certificate. Requires dedicated Medical Data permission.</td>
        </tr>
        <tr>
            <td><strong>Police Verification</strong></td>
            <td><span class="badge badge-primary">Employee (Global)</span></td>
            <td><span class="badge badge-danger">Restricted</span></td>
            <td>Character certificate or formal police clearance. Mandatory for site security access.</td>
        </tr>
    </tbody>
</table>

<h2>3.2 Private Storage, SHA-256 Checksums & Immutable Versions</h2>
<p>
    All HR documents are stored outside the public web root on the private Laravel <span class="code-inline">local</span> disk (<span class="code-inline">storage/app/documents</span>). Direct HTTP file access is blocked. Files can only be accessed through short-lived, authenticated preview URLs generated dynamically by the document controller.
</p>

<ul>
    <li><strong>Cryptographic Fingerprinting:</strong> Every uploaded file is hashed using SHA-256 upon ingestion. The resulting 64-character hexadecimal digest is permanently stored in <span class="code-inline">document_versions.checksum</span>.</li>
    <li><strong>Immutable Versioning:</strong> If an employee submits an updated document (e.g., a renewed CNIC or updated degree), uploading the file creates a new child version record (<span class="code-inline">version_number = 2</span>). The historical version (<span class="code-inline">version_number = 1</span>) remains permanently preserved in storage and auditable in the system ledger.</li>
    <li><strong>Automatic State Reset:</strong> Creating a new document version automatically resets prior Verification and Approval states back to <span class="code-inline">Draft</span>, requiring HR to re-verify the new file.</li>
</ul>

<h2>3.3 Verification & Approval Workflow</h2>
<p>
    HR document governance enforces an independent two-stage review lifecycle:
</p>

<div class="workflow-flow">
    Document Upload (Draft) <span class="flow-arrow">&rarr;</span> 
    HR Officer Verification (<span class="badge badge-primary">Verified</span>) <span class="flow-arrow">&rarr;</span> 
    HR Manager Final Sign-Off (<span class="badge badge-success">Approved</span>)
</div>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Verifying and Approving an Employee Document</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Navigate to Document:</strong> Open the Employee Profile &rarr; switch to the <code>Documents</code> tab (or navigate directly to <code>Documents &rarr; Documents</code>).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Inspect Attachment:</strong> Click the <code>Preview</code> action to inspect the document in the secure private viewer.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Step 1 (Verification):</strong> Click <code>Verify Document</code>. The system records the acting user ID (<span class="code-inline">verified_by</span>) and current timestamp (<span class="code-inline">verified_at</span>).
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Step 2 (Approval):</strong> A separate authorized HR Approver reviews the verified document and clicks <code>Approve Document</code>. The status transitions to <span class="badge badge-success">Approved</span>.
            </li>
            <li class="step-item">
                <div class="step-number">5</div>
                <strong>Rejection Handling:</strong> If the document is illegible or invalid, the reviewer clicks <code>Reject Document</code> and types a mandatory rejection reason. The status becomes <span class="badge badge-danger">Rejected</span> and an alert is flagged on the employee's profile.
            </li>
        </ul>
    </div>
</div>

<h2>3.4 Compliance Audit Summary & Missing Document Tracking</h2>
<p>
    The system includes an automated compliance scanner that evaluates each employee's document vault against the company's active <span class="code-inline">HrDocumentType</span> configuration where <span class="code-inline">is_required = true</span>. Any missing mandatory document is highlighted in amber/red on the Employee View infolist and compiled into the <strong>HR Operational Readiness</strong> audit report.
</p>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 4: ATTENDANCE & BIOMETRICS         -->
<!-- ========================================== -->
<div class="section-title">4. Work Calendars, Shifts & Biometric Attendance Management (HR-3, HR-4, HR-5)</div>

<p>
    The Attendance Subsystem provides a multi-layer ingestion and calculation engine capable of processing real-time biometric raw punches from on-premise fingerprint terminals, handling complex day and overnight cross-midnight shifts, applying company-specific late/grace policies, supporting maker-checker manual corrections, and compiling immutable monthly attendance summaries for payroll calculation.
</p>

<h2>4.1 Work Calendars, Shift Schedules & Overnight Shifts</h2>
<p>
    Attendance calculation is governed by three foundational configuration entities:
</p>

<ul>
    <li><strong>Work Calendars (<span class="code-inline">WorkCalendar</span>):</strong> Define the standard working week per company (e.g., Monday through Saturday, Sunday Rest Day). Links to <strong>Company Holidays</strong> (<span class="code-inline">CompanyHoliday</span>) for public, religious, and gazetted non-working days.</li>
    <li><strong>Work Shifts (<span class="code-inline">WorkShift</span>):</strong> Define shift timing boundaries:
        <ul>
            <li><strong>Standard Day Shift:</strong> e.g., 09:00:00 to 18:00:00. Break: 13:00 to 14:00.</li>
            <li><strong>Overnight Shift (<span class="code-inline">is_overnight = true</span>):</strong> e.g., 20:00:00 to 05:00:00 (next day). The system automatically binds punches occurring early the following morning (00:00 to 06:00) to the previous day's attendance ledger date.</li>
        </ul>
    </li>
    <li><strong>Shift Assignments (<span class="code-inline">ShiftAssignment</span>):</strong> Map individual Employments to a specific Work Shift with effective date ranges (<span class="code-inline">effective_from</span>, <span class="code-inline">effective_to</span>), allowing seamless shift rotations without rewriting attendance history.</li>
</ul>

<h2>4.2 Effective-Dated Attendance Calculation Rules & Thresholds</h2>
<p>
    Each company configures one or more active <strong>Attendance Rules</strong> (<span class="code-inline">AttendanceRule</span>). The rule defines mathematical criteria for evaluating raw punches into daily attendance states:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30%;">Rule Parameter</th>
            <th style="width: 20%;">Default / Unit</th>
            <th style="width: 50%;">Evaluation Logic & Payroll Consequence</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Grace Period Minutes</strong><br>(<span class="code-inline">grace_minutes</span>)</td>
            <td>15 Minutes</td>
            <td>Permitted arrival window after shift start. Arrival within shift start + grace minutes is marked <span class="badge badge-success">Present</span> without penalty.</td>
        </tr>
        <tr>
            <td><strong>Late Arrival Threshold</strong><br>(<span class="code-inline">late_threshold_minutes</span>)</td>
            <td>16 to 120 Minutes</td>
            <td>Arrival after grace period but before half-day threshold. Flags attendance state as <span class="badge badge-warning">Late</span> and records total late minutes.</td>
        </tr>
        <tr>
            <td><strong>Half-Day Minimum Minutes</strong><br>(<span class="code-inline">half_day_minimum_minutes</span>)</td>
            <td>240 Minutes (4 Hours)</td>
            <td>Total work duration required to earn a half-day credit. Working less than this threshold automatically marks the day as <span class="badge badge-danger">Absent</span>.</td>
        </tr>
        <tr>
            <td><strong>Full-Day Minimum Minutes</strong><br>(<span class="code-inline">full_day_minimum_minutes</span>)</td>
            <td>480 Minutes (8 Hours)</td>
            <td>Total work duration required for a full day credit. Working between half-day and full-day minimums marks the day as <span class="badge badge-warning">Half Day</span>.</td>
        </tr>
        <tr>
            <td><strong>Missing Punch Treatment</strong><br>(<span class="code-inline">missing_punch_treatment</span>)</td>
            <td><span class="badge badge-warning">Flag</span> / <span class="badge badge-danger">Absent</span> / <span class="badge badge-purple">Half Day</span></td>
            <td>Determines the system default if an employee records a check-in but forgets to check-out (or vice versa). Defaults to flagging for administrative review.</td>
        </tr>
        <tr>
            <td><strong>Overtime Minimum Minutes</strong><br>(<span class="code-inline">overtime_minimum_minutes</span>)</td>
            <td>60 Minutes</td>
            <td>Extra work duration beyond shift end required before overtime minutes start accumulating.</td>
        </tr>
    </tbody>
</table>

<h2>4.3 Biometric Hardware Ingestion: ZKTeco ADMS Push & TCP Pull</h2>
<p>
    YM-CMS features a hardware-agnostic attendance ingestion architecture supporting the industry-standard ZKTeco biometric communication protocols:
</p>

<div class="diagram-box">
+-------------------------------------------------------------------------------------------------------+
| BIOMETRIC INGESTION CHANNELS                                                                         |
|                                                                                                       |
| 1. HTTP PUSH (ZKTeco ADMS Protocol):                                                                  |
|    Terminal (K50/MB460+) POSTs to /iclock/cdata -> ZkTecoAdmsController -> ProcessZkTecoAdmsPushAction |
|                                                                                                       |
| 2. TCP PULL (Port 4370 Binary Socket):                                                                |
|    Artisan / Cron -> SyncAttendanceDeviceAction -> Direct TCP Pull Socket -> ATTLOG Extraction       |
|                                                                                                       |
| 3. NORMALIZED CSV IMPORT (Offline Flash Drive):                                                       |
|    Admin UI -> Upload CSV -> ImportAttendanceCsvAction -> Exact Header Validation & Ingestion        |
+-------------------------------------------------------------------------------------------------------+
                                                   |
                                                   v
+-------------------------------------------------------------------------------------------------------+
| INGESTION FOUNDATION (app/Actions/HR/IngestAttendanceEventAction.php)                                 |
| - Deterministic Deduplication: Hash(device_id + external_user_id + punched_at_local + direction)      |
| - Resolves Device User Mapping: Maps external_user_id to company Employment (Effective-Dated)         |
| - Status Routing:                                                                                     |
|   * Mapped & Valid -> Creates AttendanceRawEvent (Processed) -> Creates AttendancePunch (Machine)    |
|   * Unmapped User -> Creates AttendanceRawEvent (Quarantined) -> Holds in Error Queue for Mapping    |
+-------------------------------------------------------------------------------------------------------+
</div>

<h2>4.4 Device User Mappings, Deduplication & Quarantine Replay</h2>
<p>
    To protect employee biometric privacy, biometric templates (fingerprint minutiae points) are enrolled and stored exclusively on the local physical terminal. YM-CMS stores only the machine's assigned numeric user ID (<span class="code-inline">external_user_id</span>).
</p>

<ul>
    <li><strong>Effective-Dated Mappings (<span class="code-inline">AttendanceDeviceUserMapping</span>):</strong> Explicitly maps an <span class="code-inline">external_user_id</span> (e.g., Device ID <span class="code-inline">1042</span>) on a specific terminal to an active company <span class="code-inline">Employment</span>. Date ranges prevent historical punch misattribution if a device ID is re-assigned to another employee in the future.</li>
    <li><strong>Quarantine Queue:</strong> If punches arrive from an unmapped device ID, the system does not drop the data. It stores the events in <span class="code-inline">attendance_raw_events</span> with status <span class="badge badge-warning">Quarantined</span>. Once HR registers the mapping in the UI, clicking <code>Reprocess Raw Event</code> instantly normalizes all historical quarantined punches into the attendance records without data loss.</li>
    <li><strong>Deterministic Idempotency:</strong> Re-uploading the same CSV file, retrying network packets, or re-running device sync produces zero duplicate punch records. Duplicate events are silently matched to existing checksum fingerprints.</li>
</ul>

<h2>4.5 Normalized CSV Import Contract & Manual Ingestion</h2>
<p>
    For remote construction job sites lacking network connectivity, attendance logs exported via USB flash drive can be uploaded via the standardized CSV import interface:
</p>

<div class="callout dark">
    <div class="callout-title">Standardized Attendance CSV Specification (v1)</div>
    <strong>Header:</strong> <span class="code-inline">device_code,external_user_id,punched_at_local,timezone,direction,source_event_id</span><br>
    <strong>Sample Row:</strong> <span class="code-inline">DEV-K50-01,1042,2026-08-10 08:55:00,Asia/Karachi,in,EVT-99201</span><br>
    <strong>Allowed Directions:</strong> <span class="code-inline">in</span>, <span class="code-inline">out</span>, <span class="code-inline">break_out</span>, <span class="code-inline">break_in</span> (or blank).
</div>

<h2>4.6 Daily Attendance Processing & Maker-Checker Corrections</h2>
<p>
    Each calendar day, the system evaluates all accepted punches against the assigned shift schedule to calculate the daily attendance state (<span class="code-inline">AttendanceRecord</span>):
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 20%;">Attendance State</th>
            <th style="width: 25%;">Triggering Condition</th>
            <th style="width: 55%;">Calculation & Derived Values</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><span class="badge badge-success">Present</span></td>
            <td>Check-in $\le$ Shift Start + Grace Minutes. Work Duration $\ge$ Full-Day Minimum.</td>
            <td>Payable Day = 1.0. Late Minutes = 0. Overtime calculated if check-out exceeds shift end.</td>
        </tr>
        <tr>
            <td><span class="badge badge-warning">Late</span></td>
            <td>Check-in > Shift Start + Grace Minutes. Work Duration $\ge$ Full-Day Minimum.</td>
            <td>Payable Day = 1.0. Records exact <span class="code-inline">late_minutes</span> for monthly late penalty deductions.</td>
        </tr>
        <tr>
            <td><span class="badge badge-warning">Half Day</span></td>
            <td>Work Duration between Half-Day Min and Full-Day Min (or approved Half-Day leave).</td>
            <td>Payable Day = 0.5. Half-day deduction factor applied during monthly payroll calculation.</td>
        </tr>
        <tr>
            <td><span class="badge badge-danger">Absent</span></td>
            <td>No punches recorded on working day (and no approved leave), or Work Duration < Half-Day Min.</td>
            <td>Payable Day = 0.0. Marked as Unpaid Absence; deducted from gross salary during payroll.</td>
        </tr>
        <tr>
            <td><span class="badge badge-primary">Paid Leave</span></td>
            <td>Approved paid leave request covering the calendar date.</td>
            <td>Payable Day = 1.0. Consumes leave balance from Leave Ledger; zero payroll deduction.</td>
        </tr>
        <tr>
            <td><span class="badge badge-danger">Unpaid Leave</span></td>
            <td>Approved unpaid leave request covering the calendar date.</td>
            <td>Payable Day = 0.0. Creates a dedicated Unpaid Leave deduction component in payroll.</td>
        </tr>
        <tr>
            <td><span class="badge badge-gray">Holiday / Rest Day</span></td>
            <td>Date matches Company Holiday or weekly rest day on Work Calendar.</td>
            <td>Payable Day = 1.0 (for monthly salaried staff). No punch required.</td>
        </tr>
    </tbody>
</table>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Submitting & Approving an Attendance Correction</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Navigate:</strong> Open <code>Attendance & Leave &rarr; Attendance Corrections</code> &rarr; Click <code>+ New Correction</code>.
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Select Record & Propose:</strong> Select the affected Employment and Date. Choose the proposed status (e.g., change <span class="code-inline">Absent</span> to <span class="code-inline">Present</span>) and input corrected check-in/out times and a mandatory reason (e.g., "Official site visit with client").
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Submit:</strong> Click <code>Submit for Approval</code>. Status becomes <span class="badge badge-warning">Pending</span>.
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Maker-Checker Approval:</strong> An independent HR Manager reviews the correction evidence and clicks <code>Approve Correction</code>. The underlying <span class="code-inline">AttendanceRecord</span> recalculates immediately while preserving the original raw biometric punches in the audit trail.
            </li>
        </ul>
    </div>
</div>

<h2>4.7 Monthly Summaries & Finalization (Immutable Payroll Input)</h2>
<p>
    At the conclusion of each calendar month, HR generates an <strong>Attendance Monthly Summary</strong> (<span class="code-inline">AttendanceMonthlySummary</span>) via <span class="code-inline">BuildAttendanceMonthlySummaryAction</span>. This aggregates total present days, late arrivals, late minutes, half days, paid leaves, unpaid leaves, absences, and overtime hours per employee. Once reviewed, clicking <code>Finalize Monthly Summary</code> locks the record into an <strong>immutable cryptographic snapshot</strong> (<span class="code-inline">evidence_checksum</span>). The finalized summary serves as the authoritative, tamper-proof input for the Monthly Payroll calculation engine.
</p>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 5: LEAVE MANAGEMENT & LEDGER       -->
<!-- ========================================== -->
<div class="section-title">5. Leave Management & Double-Entry Ledger Engine (HR-3)</div>

<p>
    The Leave Management Subsystem provides a policy-driven, double-entry leave accounting framework. Instead of maintaining simple mutable integer counters on employee records, YM-CMS tracks all leave accruals, adjustments, consumptions, and cancellations through an <strong>append-only Leave Ledger</strong> (<span class="code-inline">LeaveLedgerEntry</span>).
</p>

<h2>5.1 Leave Types & Effective-Dated Policies</h2>
<p>
    Leave governance is structured into two configurable tiers:
</p>

<ul>
    <li><strong>Leave Types (<span class="code-inline">LeaveType</span>):</strong> Represent formal categories of time off (e.g., <em>Casual Leave</em>, <em>Annual Leave</em>, <em>Sick / Medical Leave</em>, <em>Maternity Leave</em>, <em>Unpaid Leave</em>). Attributes include leave unit (<span class="code-inline">day</span> or <span class="code-inline">hour</span>), paid status (<span class="code-inline">is_paid</span>), and encashment eligibility (<span class="code-inline">is_encashable</span>).</li>
    <li><strong>Leave Policies (<span class="code-inline">LeavePolicy</span>):</strong> Define the mathematical rules governing a Leave Type for a specific company and effective date range:
        <ul>
            <li><strong>Annual Entitlement (<span class="code-inline">annual_quota_units</span>):</strong> Total units allocated per year (e.g., 14 days Casual, 14 days Annual, 8 days Sick).</li>
            <li><strong>Accrual Frequency (<span class="code-inline">accrual_cadence</span>):</strong> Upfront annual allocation, monthly pro-rata accrual, or tenure-based milestone accrual.</li>
            <li><strong>Carry-Forward Limit (<span class="code-inline">max_carry_forward_units</span>):</strong> Maximum unused balance permitted to roll over into the next calendar year.</li>
            <li><strong>Negative Balance Control (<span class="code-inline">allow_negative_balance</span>):</strong> Strict boolean gate. When <span class="code-inline">false</span>, the system strictly blocks any leave request that exceeds the employee's current ledger balance.</li>
            <li><strong>Attachment Mandate (<span class="code-inline">requires_attachment</span>, <span class="code-inline">attachment_threshold_days</span>):</strong> Mandates supporting documentation (e.g., a certified medical certificate for medical leave exceeding 2 consecutive days).</li>
        </ul>
    </li>
</ul>

<h2>5.2 Double-Entry Leave Ledger Mechanics</h2>
<p>
    Every balance movement is posted as an immutable entry in <span class="code-inline">leave_ledger_entries</span>. The current available leave balance is derived by summing all posted debits and credits:
</p>

<div class="diagram-box">
+----------------------------------------------------------------------------------------------------+
| LEAVE LEDGER ENTRY TYPES (app/Enums/LeaveLedgerEntryType.php)                                     |
+----------------------------------------------------------------------------------------------------+
| 1. OPENING     (+) : Initial migration or beginning-of-year entitlement balance.                  |
| 2. ACCRUAL     (+) : Monthly or periodic earned leave credit.                                      |
| 3. ADJUSTMENT  (+/-): Authorized manual correction by HR with recorded business justification.    |
| 4. CONSUMPTION (-) : Approved leave request deduction. Linked to leave_request_id.                 |
| 5. REVERSAL    (+) : Restores balance upon approved leave cancellation or early recall.            |
+----------------------------------------------------------------------------------------------------+
  CURRENT AVAILABLE BALANCE = SUM(Opening + Accrual + Adjustments - Consumptions + Reversals)
</div>

<h2>5.3 3-Tier Leave Application Workflow</h2>
<p>
    Leave processing follows a rigorous multi-tier authorization chain ensuring operational coverage and HR policy enforcement:
</p>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: End-to-End Leave Application & Approval Flow</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Step 1: Submission (Employee / HR Proxy):</strong> Navigate to <code>Attendance & Leave &rarr; Leave Requests</code> &rarr; Click <code>+ New Request</code>. Select Employment, Leave Type, Starting Date, Ending Date, and input reason. The system computes total days, checks weekend/holiday overlaps, validates available ledger balance, and attaches required medical certificates. Click <code>Submit Request</code> (Status: <span class="badge badge-warning">Requested</span>).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Step 2: Department Manager Endorsement:</strong> The reporting manager opens the request from their approval queue, evaluates team coverage, and clicks <code>Manager Approve</code>. The status transitions to <span class="badge badge-primary">Manager Approved</span>.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Step 3: HR Final Sign-Off:</strong> HR Officer performs the final compliance check and clicks <code>HR Approve</code>.
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>System Execution:</strong> The system automatically: (a) Posts a <span class="code-inline">consumption</span> row to <span class="code-inline">leave_ledger_entries</span>, reducing the balance; (b) Generates or updates daily <span class="code-inline">AttendanceRecord</span> rows for the leave dates, setting state to <span class="code-inline">Paid Leave</span> or <span class="code-inline">Unpaid Leave</span>; (c) Marks status as <span class="badge badge-success">Approved</span>.
            </li>
        </ul>
    </div>
</div>

<h2>5.4 Leave Cancellation, Reversal & Downstream Payroll Impact</h2>
<ul>
    <li><strong>Leave Cancellation:</strong> If an approved leave is cancelled prior to execution, HR triggers <code>Cancel Leave Request</code>. The system transactionally posts a <span class="code-inline">reversal</span> row in the Leave Ledger to restore the exact balance units and resets the daily attendance records.</li>
    <li><strong>Payroll Integration:</strong> Approved <span class="code-inline">Paid Leave</span> maintains 100% payable salary. Approved <span class="code-inline">Unpaid Leave</span> automatically registers an unpaid day count, which is pulled into the Monthly Payroll Run as a dedicated salary deduction component.</li>
</ul>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 6: DEPARTMENT OPERATIONS & TASKS   -->
<!-- ========================================== -->
<div class="section-title">6. Department Operations, Tasks & 6:00 PM Daily Reporting</div>

<p>
    The Department Operations Subsystem links high-level project milestones and corporate objectives to everyday staff task execution, creative/technical deliverables, multi-tier supervisory reviews, mandatory daily work check-ins, and automated employee productivity scoring.
</p>

<h2>6.1 Department Operations Architecture & Hierarchy</h2>
<p>
    Department Operations is structured into an 8-level accountability hierarchy designed to eliminate communication bottlenecks and ensure daily operational transparency:
</p>

<div class="workflow-flow">
    Company Tenant <span class="flow-arrow">&rarr;</span> 
    Department <span class="flow-arrow">&rarr;</span> 
    Department Teams <span class="flow-arrow">&rarr;</span> 
    Team Leads (L1 Review) <span class="flow-arrow">&rarr;</span> 
    Team Members (Assignees) <span class="flow-arrow">&rarr;</span> 
    Specialized Tasks <span class="flow-arrow">&rarr;</span> 
    Daily Reports (6 PM Cutoff) <span class="flow-arrow">&rarr;</span> 
    Productivity Analytics (0–100)
</div>

<h2>6.2 Specialized Workflow Sub-Modules</h2>
<p>
    Every task in YM-CMS (<span class="code-inline">Task</span>, sequence <span class="code-inline">TSK-YYYY-XXXXX</span>) dynamically renders tailored tracking schemas based on the selected Department Team:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 22%;">Specialized Module</th>
            <th style="width: 38%;">Tailored Fields & Metadata Schema</th>
            <th style="width: 40%;">Deliverable Artifacts & Output Formats</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Social Media Marketing</strong><br>(<span class="code-inline">social_media_task_details</span>)</td>
            <td>
                • Platforms: Meta, Instagram, LinkedIn, X, TikTok, YouTube.<br>
                • Content Formats: Reel/Short, Static Post, Carousel, Story.<br>
                • 8-Step Pipeline: Idea &rarr; Brief &rarr; Copy &rarr; Design &rarr; Edit &rarr; Client Review &rarr; Scheduled &rarr; Published.<br>
                • Post Copy, Hashtag Banks, Target Scheduled Date/Time.
            </td>
            <td>Live post URLs, Meta Business Suite scheduling links, copy documents. Direct cross-delegation to assigned Graphic Designers and Video Editors.</td>
        </tr>
        <tr>
            <td><strong>Graphic Design</strong><br>(<span class="code-inline">design_task_details</span>)</td>
            <td>
                • Design Type: Social Graphic, Billboard, Brochure, UI Screen, Logo.<br>
                • Dimensions & Aspect Ratios (1:1, 9:16, 16:9, Print A4/300DPI).<br>
                • Brand Guidelines, Color Palettes, Typography Requirements.<br>
                • Design Brief, Required Headlines & Body Copy.
            </td>
            <td>Figma canvas links, Adobe Illustrator (.ai) / Photoshop (.psd) cloud assets, high-resolution PNG/PDF preview exports.</td>
        </tr>
        <tr>
            <td><strong>Video Production</strong><br>(<span class="code-inline">video_task_details</span>)</td>
            <td>
                • Video Type: Promo Commercial, Site Walkthrough, Reel, Podcast.<br>
                • Target Duration (seconds/minutes), Aspect Ratio (9:16 / 16:9).<br>
                • Raw Footage Cloud URLs (Google Drive / Dropbox / Frame.io).<br>
                • Script Timings, Voiceover Notes, B-Roll Pace, Sound FX Tone.
            </td>
            <td>Cloud raw footage directories, Draft V1 / V2 review links (Frame.io/Loom), 4K/1080p Final Master Video URLs.</td>
        </tr>
        <tr>
            <td><strong>Web Development</strong><br>(<span class="code-inline">web_dev_task_details</span>)</td>
            <td>
                • Task Type: Feature, Bug Fix, UI/UX, API, Performance, Database.<br>
                • Technical Specifications & Architecture Acceptance Criteria.<br>
                • QA Testing Status: Untested &rarr; In QA &rarr; Passed &rarr; Failed.<br>
                • Bug Count Tracking & Regression Notes.
            </td>
            <td>GitHub Pull Request URLs, staging test environments, production release endpoints, automated CI/CD build logs.</td>
        </tr>
        <tr>
            <td><strong>Sales & Business Dev</strong><br>(<span class="code-inline">daily_work_reports</span>)</td>
            <td>
                • Daily Outreach Metrics: Leads Received, First Contacts Made.<br>
                • Outbound Call Volumes & WhatsApp Business Engagements.<br>
                • Client Meetings & Construction Site Inspection Visits Booked.<br>
                • Proposals Dispatched, Final Deals Closed, Total Revenue Booked.
            </td>
            <td>CRM deal links, formal quotation references, signed client contract PDFs, categorized lost-lead root cause analyses.</td>
        </tr>
    </tbody>
</table>

<h2>6.3 2-Tier Quality Approval Chain</h2>
<p>
    Task deliverables undergo a rigorous two-tier quality control process before being marked complete:
</p>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Executing the 2-Tier Task Approval Lifecycle</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Stage 1: Member Submission:</strong> The assignee attaches all deliverable URLs (Figma, GitHub, Drive, Loom) in the <code>Task Attachments</code> relation manager and clicks <code>Submit Deliverable</code>. Task status becomes <span class="badge badge-warning">Submitted</span> (100% progress).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Stage 2: Level-1 Team Lead Review:</strong> The Team Lead inspects the files. If quality meets team standards, the Lead clicks <code>Review & Pass to Head</code> (recording Lead ID and timestamp). If changes are required, the Lead clicks <code>Request Revision</code>, entering specific revision feedback (Status becomes <span class="badge badge-danger">Revision Required</span> and revision counter increments).
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Stage 3: Level-2 Head Final Approval:</strong> The Department Head reviews the lead-endorsed task on the <em>Head Operations Dashboard</em> and clicks <code>Approve & Complete</code>. Status transitions to <span class="badge badge-success">Completed</span> and the turnaround clock stops.
            </li>
        </ul>
    </div>
</div>

<h2>6.4 Mandatory 6:00 PM Daily Reporting Cutoff & Notifications</h2>
<p>
    Every active employee is required to submit a <strong>Daily Work Report</strong> (<span class="code-inline">DailyWorkReport</span>) summarizing tasks worked, hours spent, deliverable links, blockers, and sales metrics before the daily cutoff:
</p>

<div class="diagram-box">
DAILY REPORTING TIME-WINDOW:
08:00 AM ----------------------------- 06:00:00 PM (CUTOFF) ---------------------------- 11:59 PM
  |                                           |                                              |
  +--- SUBMITTED ON OR BEFORE 18:00:00 -------+--- SUBMITTED AFTER 18:00:00 -----------------+
  |    Status: [ON TIME] (Green)              |    Status: [LATE] (Orange)                   |
  |    Full Productivity Reliability Credit   |    Late Tagged; Minor Score Penalty          |
  +-------------------------------------------+----------------------------------------------+
                                              |
                                              +---> NO SUBMISSION AT CUTOFF:
                                                    Automated Cron: php artisan work-reports:check-deadline
                                                    Status: [MISSING] (Red)
                                                    Broadcasts real-time in-app alerts to Department Head
</div>

<h2>6.5 Head Operations Dashboard & Real-Time Daily Attendance & Work Matrix</h2>
<ul>
    <li><strong>Head Operations Dashboard (<span class="code-inline">DepartmentHeadDashboard</span>):</strong> Real-time command center displaying total departmental staff, today's report submission rate, active tasks in progress, overdue tasks, and the 1-click Level-2 approval queue.</li>
    <li><strong>Daily Attendance & Work Matrix (<span class="code-inline">DailyReportingMatrixPage</span>):</strong> An interactive grid rendering every team member alongside their real-time daily status: 🟢 <span class="badge badge-success">On-Time</span>, 🟠 <span class="badge badge-warning">Late</span>, or 🔴 <span class="badge badge-danger">Missing</span>. Department heads can filter by date and team, clicking <em>View Report</em> to inspect deliverables.</li>
</ul>

<h2>6.6 Composite Employee Productivity Scorecard (0–100 Mathematical Formula)</h2>
<p>
    YM-CMS features an automated, objective productivity scoring algorithm implemented in <span class="code-inline">CalculateEmployeeProductivityService</span>. The score is evaluated dynamically across four performance pillars:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Productivity Pillar</th>
            <th style="width: 15%;">Max Points</th>
            <th style="width: 30%;">Mathematical Evaluation Formula</th>
            <th style="width: 30%;">Operational Performance Objective</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Task Completion Rate</strong></td>
            <td><strong>40 Pts</strong></td>
            <td><span class="code-inline">(Completed Tasks / Total Assigned Tasks) &times; 40</span></td>
            <td>Rewards high output and timely delivery of assigned responsibilities.</td>
        </tr>
        <tr>
            <td><strong>Daily Reporting Reliability</strong></td>
            <td><strong>30 Pts</strong></td>
            <td><span class="code-inline">(On-Time 6 PM Reports / Working Days) &times; 30</span></td>
            <td>Enforces daily reporting discipline and operational transparency.</td>
        </tr>
        <tr>
            <td><strong>Turnaround & Timeliness</strong></td>
            <td><strong>20 Pts</strong></td>
            <td><span class="code-inline">20 - (Overdue Tasks &times; 5)</span> [Minimum 0]</td>
            <td>Penalizes task delays that exceed established client deadlines.</td>
        </tr>
        <tr>
            <td><strong>First-Time Quality Index</strong></td>
            <td><strong>10 Pts</strong></td>
            <td><span class="code-inline">10 - (Average Revisions per Task &times; 3)</span> [Min 0]</td>
            <td>Rewards high accuracy and "First-Time Right" deliverable quality.</td>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td>TOTAL SCORE</td>
            <td>100 Pts</td>
            <td colspan="2">
                <span class="badge badge-success">85–100: Top Performer 🌟</span> &nbsp;|&nbsp;
                <span class="badge badge-primary">70–84: Solid Performer ⚡</span> &nbsp;|&nbsp;
                <span class="badge badge-danger">&lt; 70: Needs Improvement ⚠️</span>
            </td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 7: LOANS & ADVANCES SUBLEDGER      -->
<!-- ========================================== -->
<div class="section-title">7. Employee Loans, Advances & Subledger Engine (HR-6)</div>

<p>
    Phase HR-6 establishes a formal financing subledger managing corporate lending, salary advances, amortization schedules, Treasury disbursements, and automated payroll recoveries. It completely replaces informal, untracked cash advances with strict maker-checker controls and balanced double-entry accounting.
</p>

<h2>7.1 Loans vs Advances: Architectural Distinction & Control Accounts</h2>
<p>
    YM-CMS maintains a strict distinction between <strong>Employee Loans</strong> and <strong>Employee Advances</strong>:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Dimension</th>
            <th style="width: 35%;">Employee Loan (<span class="code-inline">loan</span>)</th>
            <th style="width: 40%;">Employee Advance (<span class="code-inline">advance</span>)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Business Purpose</strong></td>
            <td>Long-term corporate financing (e.g., vehicle loan, home assistance, medical emergency).</td>
            <td>Short-term emergency cash advance against upcoming earned monthly salary.</td>
        </tr>
        <tr>
            <td><strong>Recovery Horizon</strong></td>
            <td>Multi-month amortization schedule (e.g., 6 to 24 monthly installments).</td>
            <td>Immediate recovery in 1 to 3 upcoming consecutive payroll cycles.</td>
        </tr>
        <tr>
            <td><strong>GL Control Mapping</strong></td>
            <td><span class="code-inline">1113 Employee Advances / Loans Receivable</span> (Current Asset)</td>
            <td><span class="code-inline">1113 Employee Advances Receivable</span> (Current Asset)</td>
        </tr>
        <tr>
            <td><strong>Finance Charges</strong></td>
            <td>Supports explicit zero-charge or approved finance fee terms.</td>
            <td>Strictly zero interest / fee. Principal recovery only.</td>
        </tr>
    </tbody>
</table>

<h2>7.2 Amortization Schedules & Treasury Disbursement</h2>
<p>
    When a financing request is approved, the system generates a versioned amortization schedule (<span class="code-inline">EmployeeFinancingInstallment</span>) defining installment numbers, due dates, principal amounts, finance charges, and total due amounts.
</p>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Originating and Disbursing an Employee Loan / Advance</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Request Origination:</strong> Navigate to <code>Loans & Advances &rarr; Employee Loans & Advances</code> &rarr; Click <code>+ New Request</code>. Select Employment, Financing Type (Loan vs Advance), Request Date, Principal Amount (PKR), Number of Installments, and First Due Date. Click <code>Submit for Approval</code> (Status: <span class="badge badge-warning">Requested</span>).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Finance Approval (Maker-Checker):</strong> An authorized Finance Approver inspects the employee's tenure, existing active debt, and debt-to-income ratio, then clicks <code>Approve Request</code> (Status becomes <span class="badge badge-primary">Approved</span>).
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Treasury Disbursement:</strong> Treasury opens the approved financing and clicks <code>Disburse Funds</code>. Select Payment Method (Company Bank Account or Cash Book), Payment Date, and Instrument Reference (Cheque / Pay Order / Online Transfer).
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Automated Double-Entry Posting:</strong> The system automatically posts a balanced Treasury Payment Voucher:
                <div class="diagram-box" style="margin-top: 3pt;">
Dr 1113 Employee Advances Receivable (Subledger: EMP-00001) ..... PKR 60,000
    Cr 1111/1112 Company Bank Account / Cash Book ..................... PKR 60,000
                </div>
                Financing status transitions to <span class="badge badge-success">Active</span> and the installment schedule is activated.
            </li>
        </ul>
    </div>
</div>

<h2>7.3 Multi-Channel Recovery Engine</h2>
<p>
    YM-CMS supports four distinct recovery channels for liquidating active loan and advance balances:
</p>

<ul>
    <li><strong>Channel 1: Automated Payroll Deduction (Primary):</strong> During monthly payroll generation, the payroll engine queries all active installments where <span class="code-inline">due_date &le; payroll_period_end</span>. The exact installment amount is deducted from Net Salary, posting a credit against <span class="code-inline">1113 Employee Advances Receivable</span>.</li>
    <li><strong>Channel 2: Direct Treasury Cash/Bank Receipt:</strong> If an employee makes an out-of-cycle direct cash repayment, Treasury records a <span class="code-inline">Treasury Receipt</span> allocated against the financing. The subledger applies the payment to open installments in strict chronological due-date order.</li>
    <li><strong>Channel 3: Early Lump-Sum Payoff:</strong> An employee can settle their total remaining balance early. Treasury processes a single payoff receipt, marking all future installments as <span class="badge badge-success">Paid</span> and closing the financing as <span class="badge badge-dark">Settled</span>.</li>
    <li><strong>Channel 4: Final Settlement Deduction:</strong> Upon employee exit, any outstanding loan or advance principal is automatically swept into the Final Settlement statement as a mandatory recovery component.</li>
</ul>

<h2>7.4 Rescheduling, Principal Waivers & Reversals</h2>
<ul>
    <li><strong>Rescheduling (<span class="code-inline">RescheduleEmployeeFinancingAction</span>):</strong> If an employee experiences financial hardship, HR can restructure open installments over an extended duration. Prior schedule rows are marked <span class="badge badge-gray">Superseded</span> and new schedule rows are instantiated without altering historical paid records.</li>
    <li><strong>Principal Waiver (<span class="code-inline">WaiveEmployeeFinancingAction</span>):</strong> Under executive board approval, unpaid principal can be formally written off. The system automatically posts a balanced General Ledger journal debiting <span class="code-inline">5090 Miscellaneous Expense (Staff Welfare)</span> and crediting <span class="code-inline">1113 Employee Advances Receivable</span>, marking the installments as <span class="badge badge-purple">Waived</span>.</li>
    <li><strong>Reversals:</strong> If a disbursement or repayment voucher is reversed in Treasury, the financing subledger automatically rolls back installment statuses and restores exact outstanding balance snapshots.</li>
</ul>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 8: PAYROLL & ACCOUNTING            -->
<!-- ========================================== -->
<div class="section-title">8. Payroll Processing, Calculation Rules & Double-Entry Accounting (HR-7)</div>

<p>
    The Payroll Subsystem combines approved employee compensation structures, finalized biometric attendance summaries, double-entry leave ledger records, active loan installment schedules, and variable performance bonuses into a deterministic monthly payroll execution and General Ledger posting engine.
</p>

<h2>8.1 Compensation History & Salary Allowance Structure</h2>
<p>
    Worker remuneration is defined in <span class="code-inline">EmploymentCompensation</span> records. Approved compensation is effective-dated and 100% immutable. The salary structure separates earnings into specific, transparent components:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Salary Component</th>
            <th style="width: 25%;">Classification</th>
            <th style="width: 50%;">Calculation Basis & Accounting Expense Mapping</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Basic Salary</strong></td>
            <td>Fixed Basic Earning</td>
            <td>Contracted monthly base pay. Prorated based on payable days within the payroll month. Maps to <span class="code-inline">5010 Salaries Expense</span> (Admin) or <span class="code-inline">7000 Direct Labor</span> (Project).</td>
        </tr>
        <tr>
            <td><strong>House & Travel Allowance</strong></td>
            <td>Fixed Monthly Allowance</td>
            <td>Standard residential and transportation allowance. Maps to <span class="code-inline">5011 House & Travel Allowance</span>.</td>
        </tr>
        <tr>
            <td><strong>Food Allowance</strong></td>
            <td>Fixed Monthly Allowance</td>
            <td>Staff meal and dining subsidy. Maps to <span class="code-inline">5012 Food Allowance</span>.</td>
        </tr>
        <tr>
            <td><strong>Fuel & Vehicle Allowance</strong></td>
            <td>Operational Allowance</td>
            <td>Executive vehicle fuel reimbursement. Maps to <span class="code-inline">5013 Fuel Allowance</span>.</td>
        </tr>
        <tr>
            <td><strong>Mobile & Internet Allowance</strong></td>
            <td>Communication Allowance</td>
            <td>Phone and connectivity stipend for remote/site staff. Maps to <span class="code-inline">5014 Mobile & Internet Allowance</span>.</td>
        </tr>
        <tr>
            <td><strong>Site & Project Allowance</strong></td>
            <td>Construction Site Allowance</td>
            <td>Special hardship/relocation allowance for active site engineers. Maps to <span class="code-inline">7001 Site Allowances</span>.</td>
        </tr>
        <tr>
            <td><strong>Other Allowances</strong></td>
            <td>Discretionary Allowance</td>
            <td>Special customized stipends or utility subsidies. Maps to <span class="code-inline">5019 Other Staff Allowances</span>.</td>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td>GROSS SALARY</td>
            <td>Derived Total</td>
            <td colspan="2">Sum of Basic Salary + All Configured Monthly Allowances.</td>
        </tr>
    </tbody>
</table>

<h2>8.2 Effective-Dated Calculation Rules & Attendance Deductions</h2>
<p>
    The monthly calculation engine evaluates deductions according to active company <span class="code-inline">PayrollCalculationRule</span> settings:
</p>

<ul>
    <li><strong>Payable Basic Calculation:</strong> Basic pay is prorated according to actual payable days:
        $$\text{Payable Basic} = \left(\frac{\text{Basic Salary}}{\text{Calendar Days in Month}}\right) \times \left(\text{Payable Days} - \text{Unpaid Absences} - \text{Unpaid Leaves} - (0.5 \times \text{Half Days})\right)$$
    </li>
    <li><strong>Late Arrival Penalty:</strong> Companies can enforce minute-based or count-based late arrival deductions (e.g., 1 day salary deduction for every 3 late arrivals exceeding the grace threshold).</li>
    <li><strong>Unpaid Leave & Absence Deductions:</strong> Each unexcused absence or approved unpaid leave day deducts exactly 1 full day's basic wage rate from the employee's gross entitlement.</li>
</ul>

<h2>8.3 Variable Earning Sources: Bonus & Incentives</h2>
<p>
    Variable compensation (<span class="code-inline">PayrollVariableComponent</span>) allows HR to award performance bonuses, eid incentives, or site completion rewards. Each variable earning requires independent submission, supporting project reference, and maker-checker approval. Approved components are pulled into the upcoming payroll run as immutable earning line items.
</p>

<h2>8.4 Monthly Payroll Run Execution & Component Traceability</h2>
<p>
    Monthly payroll runs (<span class="code-inline">PayrollRun</span>, sequence <span class="code-inline">PR-YYYY-MM</span>) progress through an audited 6-stage lifecycle:
</p>

<div class="workflow-flow">
    Draft (Generate Entries) <span class="flow-arrow">&rarr;</span> 
    Under Review (HR Review) <span class="flow-arrow">&rarr;</span> 
    Approved (Finance Approval) <span class="flow-arrow">&rarr;</span> 
    Posted (GL Journal Created) <span class="flow-arrow">&rarr;</span> 
    Paid (Treasury Settle) <span class="flow-arrow">&rarr;</span> 
    Locked (Immutable Archive)
</div>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Generating and Finalizing a Monthly Payroll Run</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Initiate Payroll Run:</strong> Navigate to <code>HR Management &rarr; Payroll Runs</code> &rarr; Click <code>+ New Payroll Run</code>. Select Payroll Period (Year & Month, e.g., August 2026).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Generate Entries:</strong> Click <code>Generate / Refresh Entries</code>. The engine scans all active employments, pulls approved compensation records, checks finalized attendance summaries, pulls due loan installments, attaches approved bonuses, and compiles detailed <span class="code-inline">PayrollEntry</span> records with cryptographic checksums.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Review Exceptions:</strong> HR inspects gross-to-net calculations, attendance deductions, and bank/cash allocation splits. Any manual adjustment is recorded with mandatory justification notes.
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Submit for Review:</strong> Click <code>Submit for Review</code>. Status transitions to <span class="badge badge-warning">Under Review</span>. All entry fields become read-only.
            </li>
            <li class="step-item">
                <div class="step-number">5</div>
                <strong>Finance Approval (Maker-Checker):</strong> An independent Finance Director reviews totals and clicks <code>Approve Payroll Run</code>.
            </li>
            <li class="step-item">
                <div class="step-number">6</div>
                <strong>Post to General Ledger:</strong> Finance clicks <code>Post to Accounts</code>. The engine atomically compiles and posts the official balanced Payroll Journal Voucher into the open financial period.
            </li>
        </ul>
    </div>
</div>

<h2>8.5 Double-Entry General Ledger Posting Engine & Reversal Mechanics</h2>
<p>
    Posting an approved Payroll Run creates an official, balanced double-entry General Ledger journal:
</p>

<div class="diagram-box">
========================================================================================================
BALANCED PAYROLL GENERAL LEDGER VOUCHER (VOUCHER TYPE: PAYROLL)
========================================================================================================
Dr 5010 Salaries Expense (Administrative & Management Staff) ............ PKR 450,000
Dr 5011 House & Travel Allowance Expense ................................ PKR 120,000
Dr 5012 Food Allowance Expense .......................................... PKR  45,000
Dr 5013 Fuel & Utility Allowance Expense ................................. PKR  35,000
Dr 5020 Performance Bonus & Staff Incentive Expense ..................... PKR  50,000
Dr 7000 Construction Direct Labor Cost (Allocated to Project Sites) ..... PKR 650,000
    Cr 2110 Salary Payable (Subledger Breakdown per Employee) .................... PKR 1,210,000
    Cr 1113 Employee Advances / Loans Receivable (Recovered Installments) ........ PKR   90,000
    Cr 2150 Income Tax Withholding Payable (Statutory Employee Tax) .............. PKR   50,000
========================================================================================================
TOTAL DEBITS: PKR 1,350,000  |  TOTAL CREDITS: PKR 1,350,000  |  STATUS: BALANCED & POSTED
========================================================================================================
</div>

<h2>8.6 Construction Project Cost Allocations & Treasury Settlement</h2>
<ul>
    <li><strong>Project Cost Allocation:</strong> For Project Staff (<span class="code-inline">EmploymentCategory::ProjectStaff</span>), payroll entry creation mandates project allocation lines. Direct labor costs are charged directly to specific construction projects and cost centers, updating project budget-vs-actual cost ledgers in real time.</li>
    <li><strong>Treasury Settlement (<span class="code-inline">MarkPayrollRunPaidAction</span>):</strong> When salaries are disbursed, Treasury processes payments allocated against <span class="code-inline">2110 Salary Payable</span>. Disbursing funds via bank transfer debits <span class="code-inline">Salary Payable</span> and credits <span class="code-inline">1111 Company Bank Account</span>, generating the official <strong>Bank Transfer Advice Schedule</strong>.</li>
    <li><strong>Payroll Reversal:</strong> If an error is discovered post-posting, clicking <code>Reverse Payroll Run</code> automatically creates a linked reversal journal in an open period, restores loan installment schedules, and releases the run for correction and regeneration.</li>
</ul>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 9: RECRUITMENT & ONBOARDING        -->
<!-- ========================================== -->
<div class="section-title">9. Joining Letters, Recruitment & Onboarding Foundation</div>

<p>
    The Joining Letter & Onboarding Subsystem provides a templated document generation and candidate conversion workflow. It snapshots contractual terms, validates placeholders, generates immutable offer letters, and transitions candidates into formal employees upon signed acceptance.
</p>

<h2>9.1 Joining Letter Templates & Placeholder Substitution Engine</h2>
<p>
    Companies define standardized offer letter templates (<span class="code-inline">JoiningLetterTemplate</span>). The system features an intelligent placeholder engine that injects dynamic employee, compensation, and organizational values into the rendered document body:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30%;">Placeholder Tag</th>
            <th style="width: 30%;">Data Source & Entity Reference</th>
            <th style="width: 40%;">Sample Rendered Output</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><span class="code-inline">{{employee_name}}</span></td>
            <td><span class="code-inline">Employee.first_name + ' ' + last_name</span></td>
            <td>Engr. Muhammad Hamza Khan</td>
        </tr>
        <tr>
            <td><span class="code-inline">{{designation}}</span></td>
            <td><span class="code-inline">Employment.designation.name</span></td>
            <td>Senior Site Structural Engineer</td>
        </tr>
        <tr>
            <td><span class="code-inline">{{department}}</span></td>
            <td><span class="code-inline">Employment.department.name</span></td>
            <td>Civil Engineering & Construction</td>
        </tr>
        <tr>
            <td><span class="code-inline">{{joining_date}}</span></td>
            <td><span class="code-inline">Employment.joining_date</span> (Formatted)</td>
            <td>1st September 2026</td>
        </tr>
        <tr>
            <td><span class="code-inline">{{basic_salary}}</span></td>
            <td><span class="code-inline">EmploymentCompensation.basic_salary</span></td>
            <td>PKR 120,000 / month</td>
        </tr>
        <tr>
            <td><span class="code-inline">{{gross_salary}}</span></td>
            <td><span class="code-inline">EmploymentCompensation.gross_salary</span></td>
            <td>PKR 185,000 / month</td>
        </tr>
        <tr>
            <td><span class="code-inline">{{work_location}}</span></td>
            <td><span class="code-inline">Employment.workLocation.name</span></td>
            <td>Head Office / Bahria Town Site Store</td>
        </tr>
        <tr>
            <td><span class="code-inline">{{probation_period}}</span></td>
            <td>Calculated difference: start to end date</td>
            <td>3 Months (90 Calendar Days)</td>
        </tr>
    </tbody>
</table>

<div class="callout warning">
    <div class="callout-title">Placeholder Security Validation</div>
    The rendering engine enforces strict template sanitization. Any unrecognized or malicious placeholder tag (e.g., <span class="code-inline">{{raw_eval}}</span>) is automatically rejected with an execution exception, preventing template injection vulnerabilities.
</div>

<h2>9.2 Draft Generation, Approval & Cryptographic Issuance</h2>
<p>
    Joining Letter management enforces an audited 6-stage lifecycle:
</p>

<div class="workflow-flow">
    Draft (Generate Snapshot) <span class="flow-arrow">&rarr;</span> 
    Pending Approval <span class="flow-arrow">&rarr;</span> 
    Approved (HR Director Sign-Off) <span class="flow-arrow">&rarr;</span> 
    Issued (SHA-256 Checksum Fixed) <span class="flow-arrow">&rarr;</span> 
    Accepted (Worker Onboarded)
</div>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Generating, Approving & Issuing a Joining Letter</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Generate Draft:</strong> Navigate to <code>HR Management &rarr; Joining Letters</code> &rarr; Click <code>+ New Joining Letter</code>. Select Company Employment and Template. The system generates the encrypted body snapshot.
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Submit:</strong> Click <code>Submit for Approval</code> (Status: <span class="badge badge-warning">Pending Approval</span>).
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Approve (Maker-Checker):</strong> HR Director reviews contractual clauses and clicks <code>Approve Letter</code>.
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Issue:</strong> Click <code>Issue Letter</code>. The system generates a permanent SHA-256 hash of the letter body and locks the text against further edits (Status: <span class="badge badge-primary">Issued</span>).
            </li>
            <li class="step-item">
                <div class="step-number">5</div>
                <strong>Record Acceptance:</strong> When the candidate signs and returns the contract, HR clicks <code>Record Acceptance</code>, inputting the accepting signatory's name and acceptance timestamp. The record transitions to <span class="badge badge-success">Accepted</span> (100% Immutable).
            </li>
        </ul>
    </div>
</div>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 10: PERFORMANCE & DISCIPLINE       -->
<!-- ========================================== -->
<div class="section-title">10. Performance Appraisals & Disciplinary Warnings (HR-8)</div>

<p>
    Phase HR-8 delivers objective performance evaluation frameworks and structured disciplinary grievance workflows. It allows companies to track annual/quarterly KPI reviews, self-evaluations, manager ratings, and formal warning letter escalations.
</p>

<h2>10.1 KPI Master Library & 100-Point Appraisal Cycles</h2>
<p>
    Performance management is structured into three integrated entities:
</p>

<ul>
    <li><strong>Performance KPIs (<span class="code-inline">PerformanceKpi</span>):</strong> Central library of standard measurable performance indicators (e.g., <em>Project Delivery Timeliness</em>, <em>Code Quality & Review Rigor</em>, <em>Site Safety Compliance</em>, <em>Sales Revenue Target Achievement</em>).</li>
    <li><strong>Appraisal Cycles (<span class="code-inline">AppraisalCycle</span>):</strong> Define formal review timeframes (e.g., <em>FY 2025-26 Annual Review</em>, <em>Q2 2026 Mid-Year Review</em>) with configured minimum and maximum rating scales (e.g., 1.00 to 5.00).</li>
    <li><strong>Performance Appraisals (<span class="code-inline">PerformanceAppraisal</span>):</strong> Individual review documents. Appraisals require itemized KPI weightages totaling <strong>exactly 100%</strong>.</li>
</ul>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Conducting an Employee Performance Appraisal</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Initiate Appraisal:</strong> Open <code>HR Management &rarr; Performance Appraisals</code> &rarr; Click <code>+ New Appraisal</code>. Select Active Cycle, Employee, and Lead Reviewer.
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Allocate KPI Weights:</strong> Add appraisal items from the KPI library. Assign individual weight percentages (e.g., Technical Delivery: 40%, Safety Compliance: 30%, Team Leadership: 30%). The sum must equal 100.00%. Click <code>Submit Appraisal</code>.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Manager Evaluation & Scoring:</strong> The Reviewer grades each KPI item against the approved rating scale (1 to 5), entering detailed qualitative remarks and improvement goals. The system calculates the weighted composite score:
                $$\text{Overall Score} = \sum \left( \text{Item Rating} \times \frac{\text{Item Weight}}{100} \right)$$
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Review & Final Approval:</strong> The Reviewer clicks <code>Submit Review</code>. The Department Head performs the final review and clicks <code>Approve Appraisal</code>.
            </li>
            <li class="step-item">
                <div class="step-number">5</div>
                <strong>Employee Acknowledgement:</strong> The employee inspects their scorecard and signs off via <code>Acknowledge Appraisal</code> (Status: <span class="badge badge-success">Acknowledged</span>).
            </li>
        </ul>
    </div>
</div>

<h2>10.2 Warning Letter Templates, Issuance, Response & Closure</h2>
<p>
    Disciplinary actions (<span class="code-inline">EmployeeWarning</span>) provide a legally defensible framework for addressing workplace misconduct, chronic absenteeism, or performance failures:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Workflow Step</th>
            <th style="width: 25%;">Authorized Actor</th>
            <th style="width: 50%;">Action & System Behavior</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>1. Issuance (Draft &rarr; Issued)</strong></td>
            <td>HR Officer / Dept Head</td>
            <td>Selects template (<span class="code-inline">warning_letter_templates</span>), inputs violation severity level (First Warning, Second Warning, Final Warning), incident date, detailed violation description, and remediation timeline. Clicks <code>Issue Warning</code>. Body is hashed and locked.</td>
        </tr>
        <tr>
            <td><strong>2. Employee Response</strong></td>
            <td>Employee / HR Proxy</td>
            <td>Employee submits formal written explanation and defense. Attached documents are stored securely in the private document vault. Clicks <code>Record Response</code>.</td>
        </tr>
        <tr>
            <td><strong>3. Acknowledgement</strong></td>
            <td>Employee</td>
            <td>Employee formally signs and acknowledges receipt of the disciplinary notice.</td>
        </tr>
        <tr>
            <td><strong>4. Closure / Remediation</strong></td>
            <td>HR Director</td>
            <td>Evaluates employee conduct following the remediation period. Clicks <code>Close Warning</code> (marking satisfactory remediation or recommending further escalation). Status becomes <span class="badge badge-dark">Closed</span>.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 11: MOVEMENTS & TRANSFERS          -->
<!-- ========================================== -->
<div class="section-title">11. Employment Movements, Transfers & Promotions (HR-8)</div>

<p>
    Phase HR-8 provides a formal mechanism for executing internal employee transfers, departmental reorganizations, promotions, manager reassignments, and linked salary revisions without overwriting historical employment records.
</p>

<h2>11.1 Movement Requests (Promotion vs Transfer) & Workflow</h2>
<p>
    Staff reassignments are managed through <strong>Employment Movement Requests</strong> (<span class="code-inline">EmploymentMovementRequest</span>):
</p>

<ul>
    <li><strong>Movement Type (<span class="code-inline">EmploymentMovementType</span>):</strong>
        <ul>
            <li><span class="badge badge-success">Promotion</span>: Elevation in seniority, designation level, and responsibility, typically linked to an approved salary increase.</li>
            <li><span class="badge badge-primary">Transfer</span>: Lateral move across departments, project sites, work locations, or reporting managers.</li>
        </ul>
    </li>
    <li><strong>Reassignment Attributes:</strong> A movement request can modify any combination of: (1) Department, (2) Designation, (3) Reporting Manager, (4) Work Location, and (5) Employment Category.</li>
</ul>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Executing an Employee Promotion / Transfer Request</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Initiate Movement Request:</strong> Open <code>HR Management &rarr; Employment Movement Requests</code> &rarr; Click <code>+ New Request</code>. Select Employment, Movement Type, Effective Date, and Target Reassignment attributes (New Department, New Designation, New Manager, New Work Location).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Link Compensation Revision:</strong> If the promotion includes a salary increment, check <code>Revise Compensation</code> and input the new Basic Salary and Allowances.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Submit:</strong> Click <code>Submit for Approval</code> (Status: <span class="badge badge-warning">Pending Approval</span>).
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Executive Approval (Maker-Checker):</strong> Managing Director / HR Director reviews the request and clicks <code>Approve & Apply Movement</code>.
            </li>
            <li class="step-item">
                <div class="step-number">5</div>
                <strong>System Execution:</strong> The system automatically:
                <ul>
                    <li>Creates an immutable before-and-after snapshot in <span class="code-inline">employment_changes</span>.</li>
                    <li>Updates the live <span class="code-inline">Employment</span> record with the new department, designation, manager, and location.</li>
                    <li>If compensation was included, creates and auto-approves a new <span class="code-inline">EmploymentCompensation</span> record with matching effective date, automatically closing the previous salary period.</li>
                    <li>Transitions movement status to <span class="badge badge-success">Applied</span>.</li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<h2>11.2 Historical Integrity & Audit Logs</h2>
<p>
    YM-CMS enforces a strict non-destructive update policy. Live employment updates never overwrite historical payroll or attendance lookups. Prior payroll runs permanently retain the exact designation, department, and salary snapshots active during that historical month.
</p>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 12: FIXED ASSET CUSTODY            -->
<!-- ========================================== -->
<div class="section-title">12. Fixed Asset Custody & Issuance (HR-9)</div>

<p>
    Phase HR-9 bridges corporate capital asset management (<span class="code-inline">FixedAsset</span>) with human resources operations. It manages the issuance, physical custody, employee acknowledgement, transfer, return inspection, damage recovery, and clearance of enterprise equipment (e.g., laptops, surveying equipment, company vehicles, mobile devices).
</p>

<h2>12.1 Custody Issuance, Single Custodian Locking & Acknowledgement</h2>
<p>
    Asset issuance is tracked via <strong>Employee Asset Custodies</strong> (<span class="code-inline">EmployeeAssetCustody</span>):
</p>

<ul>
    <li><strong>Single Live Custodian Invariant:</strong> An individual fixed asset can only be issued to <strong>exactly one</strong> active Employment at any given time. The system enforces database row locks; attempting to issue an asset currently in another worker's custody is strictly blocked.</li>
    <li><strong>Custody Lifecycle:</strong>
        <div class="workflow-flow" style="margin-top: 3pt;">
            Draft <span class="flow-arrow">&rarr;</span> 
            Issued (Asset Locked) <span class="flow-arrow">&rarr;</span> 
            Acknowledged (Employee Signs) <span class="flow-arrow">&rarr;</span> 
            Return Pending <span class="flow-arrow">&rarr;</span> 
            Returned (Released)
        </div>
    </li>
</ul>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Issuing a Fixed Asset to an Employee</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Create Custody Draft:</strong> Navigate to <code>HR Management &rarr; Asset Issuance</code> &rarr; Click <code>+ New Asset Custody</code>. Select Employment, Fixed Asset (e.g., <em>Dell Precision 7680 Laptop - AST-0012</em>), Issue Date, Expected Return Date, Physical Condition (Brand New, Excellent, Good, Fair), and list accessories (Charger, Bag, Mouse).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Issue Asset:</strong> Click <code>Issue Asset</code>. The asset's master record updates its active custodian, locks the item against other issuances, and records status as <span class="badge badge-primary">Issued</span>.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Employee Sign-Off:</strong> Employee inspects the physical hardware and signs the custody form via <code>Acknowledge Custody</code> (Status: <span class="badge badge-success">Acknowledged</span>).
            </li>
        </ul>
    </div>
</div>

<h2>12.2 Transfers, Return Inspections & Damage Recovery</h2>
<p>
    When hardware is transferred, returned, or reported damaged, the system executes structured recovery actions:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Custody Event</th>
            <th style="width: 35%;">Software Action & Trigger</th>
            <th style="width: 40%;">System Behavior & Ledger Update</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Asset Transfer</strong></td>
            <td>Click <code>Transfer Asset</code> &rarr; Select New Recipient Employment.</td>
            <td>Atomically closes current custody record (<span class="code-inline">Returned</span>), instantiates a new custody record for the recipient (<span class="code-inline">Issued</span>), and updates the Fixed Asset master custodian.</td>
        </tr>
        <tr>
            <td><strong>Normal Return</strong></td>
            <td>Click <code>Accept Return</code> &rarr; Verify condition & accessories.</td>
            <td>Marks custody as <span class="badge badge-dark">Returned</span>, unlocks the Fixed Asset master record, and returns equipment to the available store inventory.</td>
        </tr>
        <tr>
            <td><strong>Damage / Loss Reporting</strong></td>
            <td>Click <code>Report Exception</code> &rarr; Select Type (Damage vs Loss) & input Recovery Amount.</td>
            <td>Marks custody as <span class="badge badge-danger">Exception</span>, logs physical condition inspection report, and forwards the <strong>Recommended Monetary Recovery</strong> to the employee's pending clearance and Final Settlement queue.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 13: SEPARATION & FINAL SETTLEMENT  -->
<!-- ========================================== -->
<div class="section-title">13. Separation, Clearance & Final Settlement (HR-8, HR-9, HR-10)</div>

<p>
    Phases HR-8, HR-9, and HR-10 deliver an end-to-end employee exit, departmental clearance, source-reconciled financial settlement, and General Ledger posting engine. It guarantees that no departing worker is liquidated without returning corporate assets, clearing loan obligations, settling unserved notice periods, and reconciling terminal entitlements.
</p>

<h2>13.1 Resignation vs Termination Workflows & Access Review</h2>
<p>
    Employee departures are initiated via <strong>Employment Separations</strong> (<span class="code-inline">EmploymentSeparation</span>):
</p>

<ul>
    <li><strong>Resignation (<span class="code-inline">resignation</span>):</strong> Employee-initiated exit. Captures Resignation Date, Proposed Last Working Date, Notice Days Required vs Served, and Handover Plan. Can be withdrawn prior to executive approval.</li>
    <li><strong>Termination (<span class="code-inline">termination</span>):</strong> Company-initiated exit. Captures Termination Authority, Official Reason (Misconduct, Redundancy, Performance, Probation Non-Confirmation), Effective Date, and Severance Terms.</li>
    <li><strong>System Access Review:</strong> Approving a separation automatically triggers a <strong>Pending System Access Review</strong> (<span class="code-inline">EmploymentAccessReviewStatus::Pending</span>), alerting IT administrators to revoke ERP credentials, VPN access, and corporate email accounts on the approved last working date.</li>
</ul>

<h2>13.2 Multi-Departmental Employee Clearance Protocol (HR-9)</h2>
<p>
    Approving a separation automatically creates an <strong>Employee Clearance</strong> record (<span class="code-inline">EmployeeClearance</span>) with mandatory clearance checklist items across eight departmental areas:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 20%;">Clearance Area</th>
            <th style="width: 30%;">Automatic System Obligation Checks</th>
            <th style="width: 50%;">Sign-Off Action & Blocking Criteria</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>1. Fixed Assets (IT / Store)</strong></td>
            <td>Scans <span class="code-inline">employee_asset_custodies</span> for active hardware.</td>
            <td><strong>Hard Blocker:</strong> Cannot be cleared while unreturned assets exist. Requires asset return or approved damage recovery recommendation.</td>
        </tr>
        <tr>
            <td><strong>2. Loans & Advances (Finance)</strong></td>
            <td>Queries <span class="code-inline">employee_financings</span> for unpaid loan principal.</td>
            <td>Identifies exact outstanding balance. Approves deduction from final settlement payout or records cash settlement.</td>
        </tr>
        <tr>
            <td><strong>3. Department Handover</strong></td>
            <td>Verifies project documentation and knowledge transfer.</td>
            <td>Department Head signs off on physical files, client handover, and repository access.</td>
        </tr>
        <tr>
            <td><strong>4. IT & Systems</strong></td>
            <td>Confirms deactivation of software licenses and accounts.</td>
            <td>IT Officer confirms revocation of email, ERP, GitHub, and cloud credentials.</td>
        </tr>
        <tr>
            <td><strong>5. Administration & Security</strong></td>
            <td>Collects company ID cards, access badges, office keys.</td>
            <td>Admin Officer confirms return of office keys, biometric unmapping, vehicle pass.</td>
        </tr>
        <tr>
            <td><strong>6. Leave Review (HR)</strong></td>
            <td>Reconciles Leave Ledger for encashable leave balance.</td>
            <td>HR Officer confirms final leave balance units available for encashment.</td>
        </tr>
    </tbody>
</table>

<h2>13.3 Source-Reconciled Final Settlement Calculation (HR-10)</h2>
<p>
    Once departmental clearance reaches <span class="badge badge-success">Completed</span>, HR prepares the formal <strong>Final Settlement</strong> (<span class="code-inline">FinalSettlement</span>). The engine pulls exact, source-reconciled components:
</p>

<div class="diagram-box">
========================================================================================================
SOURCE-RECONCILED FINAL SETTLEMENT STATEMENT (FS-YYYY-XXXXX)
========================================================================================================
EARNING COMPONENTS (+):
1. Final Earned Salary (Prorated through approved last working date) ...... PKR  65,000.00
2. Leave Encashment (12.0 Unused Annual Leave Days @ Daily Rate) ......... PKR  48,000.00
3. Notice Pay Credit (Company-waived notice period entitlement) ........... PKR       0.00
4. Gratuity / Terminal Severance Benefit (Configured statutory formula) ... PKR 150,000.00
5. Approved Unpaid Bonus / Site Completion Incentive ..................... PKR  25,000.00
--------------------------------------------------------------------------------------------------------
TOTAL GROSS EARNINGS ENTITLEMENT (A) ..................................... PKR 288,000.00

RECOVERY & DEDUCTION COMPONENTS (-):
1. Outstanding Employee Loan Recovery (HR-6 Financing Subledger) ......... PKR  60,000.00
2. Outstanding Employee Advance Recovery (HR-6 Advance Balance) .......... PKR  15,000.00
3. Notice Shortfall Deduction (10 Unserved Notice Days @ Daily Rate) ..... PKR  40,000.00
4. Asset Damage / Loss Recovery (HR-9 Clearance Recommendation) .......... PKR  12,000.00
5. Income Tax Withholding on Terminal Benefits ........................... PKR   8,000.00
--------------------------------------------------------------------------------------------------------
TOTAL TERMINAL DEDUCTIONS & RECOVERIES (B) ................................ PKR 135,000.00

========================================================================================================
NET SETTLEMENT PAYABLE TO EMPLOYEE (A - B) ............................... PKR 153,000.00
========================================================================================================
</div>

<h2>13.4 Final Settlement GL Posting, Reversal & Treasury Settlement</h2>
<p>
    Final Settlement processing enforces maker-checker governance (Preparer $\rightarrow$ Reviewer $\rightarrow$ Finance Approver). Posting creates a balanced General Ledger voucher:
</p>

<div class="diagram-box">
Dr 5010 Salaries Expense (Final Earned Salary) .......................... PKR  65,000.00
Dr 5030 Leave Encashment Expense ........................................ PKR  48,000.00
Dr 5040 Gratuity / Terminal Benefits Expense ............................ PKR 150,000.00
Dr 5020 Performance Bonus Expense ....................................... PKR  25,000.00
    Cr 1113 Employee Advances / Loans Receivable (Loan Payoff) ................... PKR  75,000.00
    Cr 5010 Salaries Expense (Notice Shortfall Recovery Credit) .................. PKR  40,000.00
    Cr 1210 Fixed Asset Clearing (Asset Damage Recovery) ......................... PKR  12,000.00
    Cr 2150 Withholding Tax Payable .............................................. PKR   8,000.00
    Cr 2110 Salary Payable / Terminal Settlement Payable ......................... PKR 153,000.00
</div>

<p>
    Treasury liquidates the net payable via bank transfer or cheque, automatically updating status to <span class="badge badge-dark">Settled</span> and generating the official <strong>Full & Final Discharge Certificate</strong>.
</p>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 14: HR REPORTS & DASHBOARD         -->
<!-- ========================================== -->
<div class="section-title">14. HR Reports, Analytics & Group Consolidation (HR-11)</div>

<p>
    Phase HR-11 establishes an enterprise reporting catalog, interactive HR dashboards, authorized multi-company group consolidation, and private streamed CSV/XLSX export engines.
</p>

<h2>14.1 Tenant HR Reports & Dashboard Catalog</h2>
<p>
    The <strong>HR Reports & Dashboard</strong> page (<span class="code-inline">/admin/company/{tenant}/hr-reports</span>) provides instant access to comprehensive personnel analytics:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Report Title</th>
            <th style="width: 25%;">Target Audience</th>
            <th style="width: 50%;">Data Scope, Filters & Operational Utility</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Employee Master List</strong></td>
            <td>HR Admin / Auditors</td>
            <td>Complete personnel roster. Filters by Department, Designation, Type, Status, and Joining Date. Exports full profile metadata.</td>
        </tr>
        <tr>
            <td><strong>Department-Wise Roster</strong></td>
            <td>Executive Management</td>
            <td>Staff breakdown and headcount distribution across organizational divisions and sub-departments.</td>
        </tr>
        <tr>
            <td><strong>Salary Register</strong></td>
            <td>Payroll / Finance</td>
            <td>Detailed breakdown of Basic Salary, Allowances, Gross, Deductions, and Net Pay across departments and categories.</td>
        </tr>
        <tr>
            <td><strong>Payroll Summary</strong></td>
            <td>CFO / Finance Director</td>
            <td>Executive roll-up of total monthly salary burden, cash vs bank disbursements, tax withholdings, and loan recoveries.</td>
        </tr>
        <tr>
            <td><strong>Project-Wise Payroll</strong></td>
            <td>Project Managers</td>
            <td>Direct construction labor cost allocations broken down by active project contracts and cost centers.</td>
        </tr>
        <tr>
            <td><strong>Employee Loan / Advance</strong></td>
            <td>Finance / Treasury</td>
            <td>Subledger statement showing original principal, disbursed dates, monthly recoveries, paid totals, and remaining balances.</td>
        </tr>
        <tr>
            <td><strong>Increment History</strong></td>
            <td>HR Director / CEO</td>
            <td>Audit trail of salary increments, promotion dates, historical salary growth percentages, and compensation revisions.</td>
        </tr>
        <tr>
            <td><strong>Attendance Summary</strong></td>
            <td>HR / Operations</td>
            <td>Monthly attendance matrix showing present days, late arrivals, half days, absences, paid leaves, and overtime hours.</td>
        </tr>
        <tr>
            <td><strong>Leave Summary</strong></td>
            <td>HR / Dept Heads</td>
            <td>Leave ledger balances, annual entitlements, used days, carried-forward balances, and pending requests.</td>
        </tr>
        <tr>
            <td><strong>Final Settlement Report</strong></td>
            <td>Finance / Audit</td>
            <td>Terminal settlement statements, clearance statuses, leave encashment, loan recoveries, and GL voucher links.</td>
        </tr>
    </tbody>
</table>

<h2>14.2 Group HR Multi-Company Consolidated Reporting</h2>
<p>
    The <strong>Group HR Reports</strong> interface (<span class="code-inline">/admin/company/{tenant}/group-hr-reports</span>) allows executive directors and group HR auditors to inspect cross-company workforce metrics across all four legal companies:
</p>

<ul>
    <li><strong>Unique Person vs Employment Count:</strong> Explicitly reports unique individual headcount (de-duplicated by CNIC) alongside total active employments across the group.</li>
    <li><strong>Comparative Headcount Matrix:</strong> Side-by-side headcount, joining trends, and turnover rates across BMC Construction, YMC Construction, 7 Orbit, and 7 Orbit Medical Billing.</li>
    <li><strong>Consolidated Salary Burden:</strong> Group-wide payroll expenditure roll-up with dedicated sensitive-data permission gates.</li>
    <li><strong>Private Streamed Exports:</strong> All reports support instant export to CSV and Excel (XLSX) via streamed, memory-bounded download handlers with mandatory activity logging.</li>
</ul>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 15: MIGRATION & READINESS          -->
<!-- ========================================== -->
<div class="section-title">15. HR Data Migration, Rollback & Operational Readiness (HR-12)</div>

<p>
    Phase HR-12 delivers industrial-strength data migration pipelines, automated pre-flight integrity validation, controlled batch rollbacks, and operational readiness auditing to ensure safe production onboarding without data corruption.
</p>

<h2>15.1 Controlled CSV Migration Pipeline & Rollback Engine</h2>
<p>
    Legacy HR records are imported through seven specialized migration adapters (<span class="code-inline">HrDataMigration</span>):
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Migration Adapter Type</th>
            <th style="width: 35%;">Imported Data Entities</th>
            <th style="width: 40%;">Validation & Rollback Capabilities</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><span class="code-inline">departments</span></td>
            <td>Department Master & Parent-Child Hierarchy</td>
            <td>Validates parent codes; prevents circular references. Rollback verifies no employments assigned.</td>
        </tr>
        <tr>
            <td><span class="code-inline">employees</span></td>
            <td>Employee Profiles, Employments & Compensation</td>
            <td>Checks CNIC uniqueness, validates salary sums. Rollback deletes imported batch atomically.</td>
        </tr>
        <tr>
            <td><span class="code-inline">document_metadata</span></td>
            <td>Legacy Document Vault Records</td>
            <td>Maps document types and sensitivity tiers. Preserves metadata without fabricating dummy files.</td>
        </tr>
        <tr>
            <td><span class="code-inline">leave_balances</span></td>
            <td>Opening Leave Ledger Balances</td>
            <td>Posts <span class="code-inline">opening</span> ledger entries. Rollback deletes ledger rows if unused.</td>
        </tr>
        <tr>
            <td><span class="code-inline">financings</span></td>
            <td>Active Loan & Advance Opening Schedules</td>
            <td>Establishes open installment schedules without fabricating historical GL journals.</td>
        </tr>
        <tr>
            <td><span class="code-inline">asset_custody</span></td>
            <td>Existing Fixed Asset Custodies</td>
            <td>Binds assets to employments, enforcing single custodian constraints.</td>
        </tr>
        <tr>
            <td><span class="code-inline">historical_attendance</span></td>
            <td>Past Monthly Attendance Summaries</td>
            <td>Imports finalized monthly summaries for historical payroll audit.</td>
        </tr>
    </tbody>
</table>

<div class="procedure-box">
    <div class="procedure-header">Software Navigation: Executing a 4-Stage HR Data Migration Batch</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Upload Batch (Stage 1):</strong> Navigate to <code>HR &rarr; HR Data Migrations</code> &rarr; Click <code>+ New Migration</code>. Select Migration Type and upload source CSV (Max 10MB / 10,000 rows).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Dry-Run Validation (Stage 2):</strong> Click <code>Validate Batch</code>. The engine scans every row, validates foreign keys, checks unique constraints, and outputs a detailed line-by-line error log without writing to production tables.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Execute Import (Stage 3):</strong> If validation passes with 0 errors, click <code>Import Batch</code>. Records are committed in a single atomic transaction and an immutable batch checksum is generated.
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Controlled Rollback (Stage 4):</strong> If an issue is identified post-import, an authorized admin clicks <code>Rollback Migration</code>, entering a mandatory reason. The system verifies no downstream transactions depend on the data and cleanly deletes all imported rows.
            </li>
        </ul>
    </div>
</div>

<h2>15.2 HR Operational Readiness Auditing & Recovery Manifest</h2>
<ul>
    <li><strong>HR Operational Readiness Page (<span class="code-inline">HrOperationalReadiness</span>):</strong> Live pre-flight dashboard evaluating corporate readiness across 6 key pillars: (1) Organization Setup, (2) Biometric Hardware Continuity, (3) Policy & Rule Configuration, (4) Document Compliance, (5) Migration Integrity, and (6) Payroll/GL Mapping Validation.</li>
    <li><strong>Cryptographic Recovery Manifest:</strong> Scans 24 core HR database tables, computing per-row and aggregate SHA-256 hashes to instantly detect any out-of-band tampering or database corruption.</li>
</ul>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 16: ROLES, PERMISSIONS & SECURITY  -->
<!-- ========================================== -->
<div class="section-title">16. Roles, Authorization & Maker-Checker Security Matrix</div>

<p>
    YM-CMS enforces a comprehensive Role-Based Access Control (RBAC) architecture managed through Filament Shield and Spatie Laravel Permission. It guarantees strict segregation of duties across all workforce operations.
</p>

<h2>16.1 Pre-Configured HR Roles & Scope Separation</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 20%;">Role Title</th>
            <th style="width: 30%;">Operational Domain</th>
            <th style="width: 50%;">Granted Capabilities & System Boundaries</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Super Admin</strong></td>
            <td>Global System Administration</td>
            <td>Full unrestricted access across all companies, modules, configurations, and administrative overrides.</td>
        </tr>
        <tr>
            <td><strong>HR Administrator</strong></td>
            <td>Corporate HR Management</td>
            <td>Full management of Employee Masters, Employments, Shifts, Policies, Document Types, and HR Setup.</td>
        </tr>
        <tr>
            <td><strong>HR Officer</strong></td>
            <td>Day-to-Day HR Operations</td>
            <td>Creates employees, uploads documents, verifies qualifications, submits leave requests, originates loans.</td>
        </tr>
        <tr>
            <td><strong>Attendance Administrator</strong></td>
            <td>Biometrics & Scheduling</td>
            <td>Manages attendance devices, device-user mappings, raw punch imports, attendance corrections.</td>
        </tr>
        <tr>
            <td><strong>Payroll Officer</strong></td>
            <td>Compensation & Payroll Runs</td>
            <td>Manages compensation structures, generates monthly payroll runs, exports bank advice sheets.</td>
        </tr>
        <tr>
            <td><strong>Department Head</strong></td>
            <td>Departmental Leadership</td>
            <td>Full visibility over department staff, creates teams, delegates tasks, performs Level-2 task approvals, audits daily work matrix, conducts appraisals.</td>
        </tr>
        <tr>
            <td><strong>Team Lead</strong></td>
            <td>Supervisory Pod Lead</td>
            <td>Monitors assigned team members, delegates tasks, performs Level-1 deliverable quality reviews, acknowledges daily reports.</td>
        </tr>
        <tr>
            <td><strong>Employee / Team Member</strong></td>
            <td>Self-Service Execution</td>
            <td>Views assigned tasks, updates progress %, submits deliverable URLs, files mandatory 6:00 PM daily reports, submits leave applications.</td>
        </tr>
    </tbody>
</table>

<h2>16.2 Sensitive Permissions & PII Protection Boundaries</h2>
<p>
    Access to confidential personnel data requires explicit, fine-grained permission capabilities:
</p>

<ul>
    <li><span class="code-inline">ViewSensitiveData:Employee</span>: Controls unmasking of national CNIC numbers and personal residential details.</li>
    <li><span class="code-inline">ViewMedicalInfo:Employee</span>: Controls access to private health records and medical certificates.</li>
    <li><span class="code-inline">ViewSensitiveBankDetails:Employee</span>: Controls unmasking of bank account numbers and IBAN strings.</li>
    <li><span class="code-inline">ViewCompensation:Employment</span>: Controls visibility of monthly basic salaries, allowances, and gross earnings.</li>
    <li><span class="code-inline">ManageCompensation:Employment</span>: Authorizes creation, editing, and submission of salary structures.</li>
    <li><span class="code-inline">ViewSensitiveDisciplinaryData:EmployeeWarning</span>: Authorizes inspection of confidential warning letters.</li>
</ul>

<h2>16.3 Segregation of Duties Matrix Across All HR Workflows</h2>
<p>
    To eliminate internal fraud and administrative errors, YM-CMS strictly prohibits a single user from acting as both the initiator (Maker) and authorizer (Checker) of a transaction:
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Workflow Entity</th>
            <th style="width: 25%;">Initiator (Maker)</th>
            <th style="width: 25%;">Authorizer (Checker)</th>
            <th style="width: 25%;">Segregation Rule Enforced</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Attendance Correction</strong></td>
            <td>Attendance Officer</td>
            <td>HR Manager / Dept Head</td>
            <td>Initiator cannot approve their own correction.</td>
        </tr>
        <tr>
            <td><strong>Leave Request</strong></td>
            <td>Employee / HR Proxy</td>
            <td>Manager + HR Approver</td>
            <td>Applicant cannot approve their own leave.</td>
        </tr>
        <tr>
            <td><strong>Loan / Advance</strong></td>
            <td>HR Officer / Preparer</td>
            <td>Finance Approver</td>
            <td>Preparer cannot approve or disburse loan funds.</td>
        </tr>
        <tr>
            <td><strong>Salary Compensation</strong></td>
            <td>HR Officer / Preparer</td>
            <td>HR Director / CEO</td>
            <td>Preparer cannot approve salary structures.</td>
        </tr>
        <tr>
            <td><strong>Monthly Payroll Run</strong></td>
            <td>Payroll Preparer</td>
            <td>Finance Approver</td>
            <td>Preparer cannot approve or post the payroll run.</td>
        </tr>
        <tr>
            <td><strong>Performance Appraisal</strong></td>
            <td>Lead Reviewer</td>
            <td>Department Head</td>
            <td>Reviewer cannot provide final executive sign-off.</td>
        </tr>
        <tr>
            <td><strong>Employment Movement</strong></td>
            <td>HR Officer</td>
            <td>Managing Director</td>
            <td>Requester cannot execute promotions/transfers.</td>
        </tr>
        <tr>
            <td><strong>Final Settlement</strong></td>
            <td>HR Settlement Officer</td>
            <td>Finance Approver</td>
            <td>Settlement preparer cannot approve settlement GL post.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 17: PRACTICAL SCENARIOS            -->
<!-- ========================================== -->
<div class="section-title">17. Practical Operational Scenarios & End-to-End Walkthroughs</div>

<p>
    This section provides realistic, step-by-step walkthroughs for common day-to-day HR scenarios in the YM-CMS software.
</p>

<h2>Scenario 17.1: Hiring and Onboarding a New Construction Site Engineer</h2>
<p>
    <strong>Business Context:</strong> BMC Construction hires Engr. Tariq Mehmood as a <em>Site Structural Engineer</em> on a 3-month probation at a monthly gross salary of PKR 130,000, assigned to the Bahria Town Project Site.
</p>

<div class="procedure-box">
    <div class="procedure-header">Step-by-Step Software Execution</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Create Employee Master:</strong> Navigate to <code>HR Management &rarr; Employees</code> &rarr; Click <code>+ New Employee</code>.
                <ul>
                    <li>Personal Info: First Name = "Tariq", Last Name = "Mehmood", CNIC = "35201-9876543-1", Mobile = "0300-1234567".</li>
                    <li>Employment: Company = BMC Construction, Department = Civil Engineering, Designation = Site Structural Engineer, Category = Project Staff, Type = Permanent, Joining Date = 2026-08-01, Work Location = Bahria Town Site Store, Reporting To = Project Director.</li>
                    <li>Probation: Probation Start = 2026-08-01, Probation End = 2026-10-31, Notice Period = 30 Days.</li>
                    <li>Click <code>Create</code>. System generates code <span class="code-inline">EMP-00045</span>.</li>
                </ul>
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Set Compensation Structure:</strong> In the Employee Profile, scroll to <code>Compensation History</code> &rarr; Click <code>+ New Compensation</code>.
                <ul>
                    <li>Effective From = 2026-08-01. Basic Salary = PKR 80,000. House & Travel = PKR 25,000. Food Allowance = PKR 10,000. Site Allowance = PKR 15,000. Gross Salary = PKR 130,000.</li>
                    <li>Click <code>Create</code> &rarr; Click <code>Submit for Approval</code>.</li>
                    <li>HR Director logs in and clicks <code>Approve Compensation</code> (Status: <span class="badge badge-success">Approved</span>).</li>
                </ul>
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Assign Shift & Biometrics:</strong>
                <ul>
                    <li>Navigate to <code>Attendance & Leave &rarr; Shift Assignments</code> &rarr; Assign Tariq to "Construction Site Shift" (08:00–17:00).</li>
                    <li>Navigate to <code>Attendance Devices &rarr; Device User Mappings</code> &rarr; Map Device User ID "1045" on Site Terminal "DEV-SITE-01" to Tariq Mehmood.</li>
                </ul>
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Upload Onboarding Documents:</strong> Open Tariq's Profile &rarr; <code>Documents</code> tab &rarr; Upload scanned CNIC, Engineering Degree, and Signed Appointment Letter. HR verifies and approves each file.
            </li>
        </ul>
    </div>
</div>

<h2>Scenario 17.2: End-to-End Monthly Payroll Execution & General Ledger Posting</h2>
<p>
    <strong>Business Context:</strong> It is August 31, 2026. HR processes the monthly payroll run for BMC Construction covering all 45 employees, incorporating attendance summaries, bonus incentives, and loan installment recoveries.
</p>

<div class="procedure-box">
    <div class="procedure-header">Step-by-Step Software Execution</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Finalize Monthly Attendance:</strong> Navigate to <code>Attendance & Leave &rarr; Attendance Monthly Summaries</code> &rarr; Click <code>+ Generate Monthly Summary</code> for Period August 2026. Review present days, late minutes, and unpaid absences. Click <code>Finalize Monthly Summary</code> (Checksum locked).
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Approve Variable Bonuses:</strong> Open <code>Payroll &rarr; Bonus & Incentives</code>. Review submitted project completion bonuses (PKR 50,000 total) and click <code>Approve</code>.
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Generate Payroll Run:</strong> Open <code>HR Management &rarr; Payroll Runs</code> &rarr; Click <code>+ New Payroll Run</code>.
                <ul>
                    <li>Period = 2026-08. Click <code>Generate Entries</code>.</li>
                    <li>The engine automatically compiles 45 employee entries, deducting unpaid leaves, late penalties, and active loan installments (PKR 90,000 total).</li>
                    <li>Review Gross Total (PKR 4,500,000) and Net Payable Total (PKR 4,250,000).</li>
                </ul>
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Submit & Approve:</strong> Click <code>Submit for Review</code>. Finance Director logs in, verifies the salary register, and clicks <code>Approve Payroll Run</code>.
            </li>
            <li class="step-item">
                <div class="step-number">5</div>
                <strong>Post to General Ledger:</strong> Finance clicks <code>Post to Accounts</code>. The engine atomically generates and posts the balanced Payroll Journal Voucher, crediting <span class="code-inline">2110 Salary Payable</span> and <span class="code-inline">1113 Employee Advances Receivable</span>.
            </li>
            <li class="step-item">
                <div class="step-number">6</div>
                <strong>Treasury Payout & Payslips:</strong> Treasury disburses funds via online bank transfer, attaches the bank reference, and marks the run as <span class="badge badge-success">Paid</span>. Employees receive automated PDF Salary Slips.
            </li>
        </ul>
    </div>
</div>

<h2>Scenario 17.3: Employee Resignation, Clearance, and Final Settlement</h2>
<p>
    <strong>Business Context:</strong> Senior Architect Asim Riaz resigns from YMC Construction with an approved last working date of August 20, 2026. He holds an outstanding laptop custody and an active employee loan balance of PKR 40,000.
</p>

<div class="procedure-box">
    <div class="procedure-header">Step-by-Step Software Execution</div>
    <div class="procedure-body">
        <ul class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <strong>Record Resignation:</strong> Navigate to <code>HR Management &rarr; Employment Separations</code> &rarr; Click <code>+ New Separation</code>. Type = Resignation, Request Date = 2026-07-20, Last Working Date = 2026-08-20, Notice Required = 30 Days, Notice Served = 30 Days. Managing Director approves the resignation.
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <strong>Execute Departmental Clearance:</strong> Open <code>HR Management &rarr; Employee Clearances</code> &rarr; Open Asim's Clearance Checklist:
                <ul>
                    <li>IT / Store: Asim returns the laptop in good condition. IT accepts the return, clearing the Asset obligation.</li>
                    <li>Finance: Identifies PKR 40,000 outstanding loan balance. Notes loan deduction for Final Settlement.</li>
                    <li>HR / Admin: Reconciles 8 days unused annual leave for encashment; collects building access card.</li>
                    <li>All 8 checklist items sign off &rarr; Clearance status becomes <span class="badge badge-success">Completed</span>.</li>
                </ul>
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <strong>Prepare Final Settlement:</strong> Open <code>HR Management &rarr; Final Settlements</code> &rarr; Click <code>+ New Settlement</code> &rarr; Select Asim Riaz.
                <ul>
                    <li>Click <code>Refresh Source Components</code>. The engine pulls:
                        <ul>
                            <li>Final Earned Salary (20 Days in August) = PKR 80,000.</li>
                            <li>Leave Encashment (8 Days Unused Leave) = PKR 32,000.</li>
                            <li>Gratuity Entitlement = PKR 75,000.</li>
                            <li>Outstanding Loan Payoff Deduction = -PKR 40,000.</li>
                        </ul>
                    </li>
                    <li>Total Net Settlement Payable = PKR 147,000.</li>
                </ul>
            </li>
            <li class="step-item">
                <div class="step-number">4</div>
                <strong>Approve, Post & Settle:</strong> Finance Director reviews and clicks <code>Approve Settlement</code> &rarr; Click <code>Post to Accounts</code> (Posts balanced GL voucher and updates financing subledger to Settled). Treasury disburses PKR 147,000 via cheque, marking the settlement <span class="badge badge-dark">Settled</span>.
            </li>
        </ul>
    </div>
</div>

<div class="page-break"></div>

<!-- ========================================== -->
<!-- SECTION 18: TROUBLESHOOTING & REFERENCE    -->
<!-- ========================================== -->
<div class="section-title">18. Troubleshooting & Operational Quick Reference</div>

<p>
    This section provides diagnostic solutions for common operational issues and an executive quick-reference guide.
</p>

<h2>18.1 Comprehensive Troubleshooting Matrix</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 22%;">Operational Problem</th>
            <th style="width: 25%;">Probable Root Cause</th>
            <th style="width: 28%;">Diagnostic Procedure</th>
            <th style="width: 25%;">Resolution & Verification</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Biometric punches not appearing on Attendance Records.</strong></td>
            <td>1. Device User ID is not mapped to Employment.<br>2. Shift Assignment is missing or expired.</td>
            <td>1. Check <code>Attendance Raw Events</code> for <span class="badge badge-warning">Quarantined</span> status.<br>2. Check <code>Shift Assignments</code> for active record.</td>
            <td>Map user ID in <code>Device User Mappings</code> & click <code>Reprocess Event</code>. Assign active shift schedule.</td>
        </tr>
        <tr>
            <td><strong>Employee Code Sequence collision error.</strong></td>
            <td>A manual employee code was previously entered matching the next sequence counter.</td>
            <td>Inspect <code>Employee Code Sequences</code> and compare against highest code in <code>Employments</code>.</td>
            <td>System automatically skips collisions. If sequence is stuck, edit <span class="code-inline">next_number</span> to exceed highest existing code.</td>
        </tr>
        <tr>
            <td><strong>Leave Request blocked with "Insufficient Balance" error.</strong></td>
            <td>Policy has <span class="code-inline">allow_negative_balance = false</span> and ledger balance is 0.</td>
            <td>Inspect <code>Leave Ledger Entries</code> filtered by employee and leave type.</td>
            <td>Post an approved <span class="code-inline">adjustment</span> or <span class="code-inline">accrual</span> entry, or select Unpaid Leave.</td>
        </tr>
        <tr>
            <td><strong>Payroll Run fails to generate entries.</strong></td>
            <td>One or more active employees lack an active, approved <span class="code-inline">EmploymentCompensation</span>.</td>
            <td>Check system error notification for the specific unconfigured employee code.</td>
            <td>Create and approve a compensation record with an effective date $\le$ payroll period start.</td>
        </tr>
        <tr>
            <td><strong>Asset cannot be issued to employee.</strong></td>
            <td>The asset is currently marked <span class="code-inline">Issued</span> to another custodian.</td>
            <td>Inspect <code>Asset Issuance</code> records filtered by the target Fixed Asset ID.</td>
            <td>Process a formal return or transfer from the previous custodian before issuing to the new worker.</td>
        </tr>
        <tr>
            <td><strong>Final Settlement cannot be prepared.</strong></td>
            <td>1. Separation is not approved.<br>2. Clearance checklist has pending mandatory items.</td>
            <td>Check <code>Employment Separations</code> and <code>Employee Clearances</code> status.</td>
            <td>Approve the separation and ensure all 8 departmental clearance checklist items are signed off.</td>
        </tr>
    </tbody>
</table>

<h2>18.2 System Navigation Index & URL Reference Table</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30%;">Functional Domain</th>
            <th style="width: 35%;">Filament Navigation Menu Path</th>
            <th style="width: 35%;">Direct System URL Endpoint</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><strong>Employee Profiles</strong></td><td>HR Management &rarr; Employees</td><td><span class="code-inline">/admin/company/{tenant}/employees</span></td></tr>
        <tr><td><strong>Company Employments</strong></td><td>HR Management &rarr; Employments</td><td><span class="code-inline">/admin/company/{tenant}/employments</span></td></tr>
        <tr><td><strong>Departments & Hierarchy</strong></td><td>HR Management &rarr; Departments</td><td><span class="code-inline">/admin/company/{tenant}/departments</span></td></tr>
        <tr><td><strong>Designations</strong></td><td>HR Management &rarr; Designations</td><td><span class="code-inline">/admin/company/{tenant}/designations</span></td></tr>
        <tr><td><strong>Work Locations</strong></td><td>HR Management &rarr; Work Locations</td><td><span class="code-inline">/admin/company/{tenant}/work-locations</span></td></tr>
        <tr><td><strong>Compensation History</strong></td><td>HR Management &rarr; Compensation History</td><td><span class="code-inline">/admin/company/{tenant}/employment-compensation</span></td></tr>
        <tr><td><strong>Document Types & Compliance</strong></td><td>HR Management &rarr; Hr Document Types</td><td><span class="code-inline">/admin/company/{tenant}/hr-document-types</span></td></tr>
        <tr><td><strong>Biometric Devices Registry</strong></td><td>Attendance & Leave &rarr; Attendance Devices</td><td><span class="code-inline">/admin/company/{tenant}/attendance-devices</span></td></tr>
        <tr><td><strong>Device User Mappings</strong></td><td>Attendance & Leave &rarr; Device User Mappings</td><td><span class="code-inline">/admin/company/{tenant}/attendance-device-user-mappings</span></td></tr>
        <tr><td><strong>Raw Attendance Events</strong></td><td>Attendance & Leave &rarr; Attendance Raw Events</td><td><span class="code-inline">/admin/company/{tenant}/attendance-raw-events</span></td></tr>
        <tr><td><strong>Daily Attendance Records</strong></td><td>Attendance & Leave &rarr; Attendance Records</td><td><span class="code-inline">/admin/company/{tenant}/attendance-records</span></td></tr>
        <tr><td><strong>Attendance Corrections</strong></td><td>Attendance & Leave &rarr; Attendance Corrections</td><td><span class="code-inline">/admin/company/{tenant}/attendance-corrections</span></td></tr>
        <tr><td><strong>Monthly Attendance Summaries</strong></td><td>Attendance & Leave &rarr; Attendance Monthly Summaries</td><td><span class="code-inline">/admin/company/{tenant}/attendance-monthly-summaries</span></td></tr>
        <tr><td><strong>Leave Requests & Approvals</strong></td><td>Attendance & Leave &rarr; Leave Requests</td><td><span class="code-inline">/admin/company/{tenant}/leave-requests</span></td></tr>
        <tr><td><strong>Leave Ledger Balances</strong></td><td>Attendance & Leave &rarr; Leave Ledger Entries</td><td><span class="code-inline">/admin/company/{tenant}/leave-ledger-entries</span></td></tr>
        <tr><td><strong>Head Operations Dashboard</strong></td><td>Department Operations &rarr; Head Dashboard</td><td><span class="code-inline">/admin/company/{tenant}/department-head-dashboard</span></td></tr>
        <tr><td><strong>Tasks & Delegations</strong></td><td>Department Operations &rarr; Tasks</td><td><span class="code-inline">/admin/company/{tenant}/tasks</span></td></tr>
        <tr><td><strong>Daily Work Reports (6 PM)</strong></td><td>Department Operations &rarr; Daily Work Reports</td><td><span class="code-inline">/admin/company/{tenant}/daily-work-reports</span></td></tr>
        <tr><td><strong>Daily Attendance & Work Matrix</strong></td><td>Department Operations &rarr; Daily Work Matrix</td><td><span class="code-inline">/admin/company/{tenant}/daily-reporting-matrix</span></td></tr>
        <tr><td><strong>Productivity Analytics</strong></td><td>Department Operations &rarr; Performance</td><td><span class="code-inline">/admin/company/{tenant}/employee-performance</span></td></tr>
        <tr><td><strong>Employee Loans & Advances</strong></td><td>Loans & Advances &rarr; Loans & Advances</td><td><span class="code-inline">/admin/company/{tenant}/employee-financings</span></td></tr>
        <tr><td><strong>Monthly Payroll Runs</strong></td><td>HR Management &rarr; Payroll Runs</td><td><span class="code-inline">/admin/company/{tenant}/payroll-runs</span></td></tr>
        <tr><td><strong>Bonus & Incentives</strong></td><td>Payroll &rarr; Bonus & Incentives</td><td><span class="code-inline">/admin/company/{tenant}/payroll-variable-components</span></td></tr>
        <tr><td><strong>Performance Appraisals</strong></td><td>HR Management &rarr; Performance Appraisals</td><td><span class="code-inline">/admin/company/{tenant}/performance-appraisals</span></td></tr>
        <tr><td><strong>Disciplinary Warnings</strong></td><td>HR Management &rarr; Employee Warnings</td><td><span class="code-inline">/admin/company/{tenant}/employee-warnings</span></td></tr>
        <tr><td><strong>Promotions & Transfers</strong></td><td>HR Management &rarr; Movement Requests</td><td><span class="code-inline">/admin/company/{tenant}/employment-movement-requests</span></td></tr>
        <tr><td><strong>Fixed Asset Custody</strong></td><td>HR Management &rarr; Asset Issuance</td><td><span class="code-inline">/admin/company/{tenant}/employee-asset-custodies</span></td></tr>
        <tr><td><strong>Separations & Exits</strong></td><td>HR Management &rarr; Employment Separations</td><td><span class="code-inline">/admin/company/{tenant}/employment-separations</span></td></tr>
        <tr><td><strong>Employee Clearances</strong></td><td>HR Management &rarr; Employee Clearances</td><td><span class="code-inline">/admin/company/{tenant}/employee-clearances</span></td></tr>
        <tr><td><strong>Final Settlements</strong></td><td>HR Management &rarr; Final Settlements</td><td><span class="code-inline">/admin/company/{tenant}/final-settlements</span></td></tr>
        <tr><td><strong>HR Reports & Analytics</strong></td><td>Reports &rarr; HR Reports & Dashboard</td><td><span class="code-inline">/admin/company/{tenant}/hr-reports</span></td></tr>
        <tr><td><strong>Payroll Reports & Registers</strong></td><td>Reports &rarr; Payroll & Advances</td><td><span class="code-inline">/admin/company/{tenant}/payroll-reports</span></td></tr>
        <tr><td><strong>Group HR Consolidation</strong></td><td>Reports &rarr; Group HR</td><td><span class="code-inline">/admin/company/{tenant}/group-hr-reports</span></td></tr>
        <tr><td><strong>HR Data Migrations</strong></td><td>HR &rarr; HR Data Migrations</td><td><span class="code-inline">/admin/company/{tenant}/hr-data-migrations</span></td></tr>
        <tr><td><strong>HR Operational Readiness</strong></td><td>HR &rarr; HR Readiness</td><td><span class="code-inline">/admin/company/{tenant}/hr-operational-readiness</span></td></tr>
    </tbody>
</table>

<h2>18.3 Key HR & Technical Terms Glossary</h2>
<ul>
    <li><strong>Global Employee vs Company Employment:</strong> Global identity (<span class="code-inline">Employee</span>) encapsulates persistent personal PII; Company Employment (<span class="code-inline">Employment</span>) encapsulates company-scoped contracts, designation, payroll, attendance, and separation history.</li>
    <li><strong>ADMS Push Protocol:</strong> Automated Data Master Server protocol used by ZKTeco biometric terminals to transmit real-time punch events via HTTP POST to the central ERP without requiring port forwarding.</li>
    <li><strong>TCP Pull Mode:</strong> Binary socket communication on Port 4370 where the ERP connects directly to an on-premise terminal IP to download transaction logs (<span class="code-inline">ATTLOG</span>).</li>
    <li><strong>Double-Entry Leave Ledger:</strong> Accounting model for time off where leave is tracked via immutable debit/credit entries (<span class="code-inline">opening</span>, <span class="code-inline">accrual</span>, <span class="code-inline">consumption</span>, <span class="code-inline">reversal</span>).</li>
    <li><strong>Maker-Checker Governance:</strong> Security protocol requiring transactional changes (compensations, loans, corrections, payroll, settlements) to be initiated by one user and independently authorized by a separate user.</li>
    <li><strong>Single Custodian Invariant:</strong> Rule stating that a capital Fixed Asset can only be issued to exactly one active worker at a time, locked against concurrent issuance.</li>
    <li><strong>Payable Basic Proration:</strong> Mathematical calculation of base salary earned during a partial month based on joining date, exit date, and attendance deductions.</li>
    <li><strong>Source-Reconciled Settlement:</strong> Final exit statement where all earning and recovery lines strictly reconcile to approved system source ledgers (Attendance, Leave, Loans, Clearance).</li>
</ul>

<div style="margin-top: 25pt; padding-top: 8pt; border-top: 1px solid #cbd5e1; text-align: center; font-size: 7.5pt; color: #64748b;">
    <strong>YM Construction Management System (YM-CMS)</strong> &bull; HR & Workforce Module User Manual &bull; Confidential & Proprietary
</div>

</body>
</html>
HTML;

echo "Assembling PDF document with Dompdf...\n";

$options = new Options;
$options->set('isPhpEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Dynamic running page numbers script via Dompdf canvas
$canvas = $dompdf->getCanvas();
$fontMetrics = $dompdf->getFontMetrics();
$font = $fontMetrics->getFont('Helvetica', 'normal');
$size = 7.5;

// Render footer page number: "Page X of Y" on pages after cover
$canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($font, $size) {
    if ($pageNumber > 1) {
        $text = "Page {$pageNumber} of {$pageCount}";
        $width = $fontMetrics->getTextWidth($text, $font, $size);
        $x = 595.28 - 39.68 - $width; // A4 width (595.28 pt) - margin (14mm ~ 39.68pt) - text width
        $y = 841.89 - 30.0;          // A4 height (841.89 pt) - 30pt
        $canvas->text($x, $y, $text, $font, $size, [0.4, 0.45, 0.55]);

        $footerLeft = 'YM Construction Management System — HR & Workforce Module User Manual';
        $canvas->text(39.68, $y, $footerLeft, $font, $size, [0.4, 0.45, 0.55]);
    }
});

$pdfOutput = $dompdf->output();

$destWorkspace = __DIR__.'/docs/YM_Construction_HR_Module_User_Manual.pdf';
file_put_contents($destWorkspace, $pdfOutput);
echo "Successfully generated manual in workspace: {$destWorkspace} (".strlen($pdfOutput)." bytes)\n";

$artifactDir = '/Users/aaraifhanif/.gemini/antigravity/brain/d9023706-91a7-49cd-9e6d-0ce4171dae67';
if (is_dir($artifactDir)) {
    $destArtifact = $artifactDir.'/YM_Construction_HR_Module_User_Manual.pdf';
    file_put_contents($destArtifact, $pdfOutput);
    echo "Successfully copied manual to artifact directory: {$destArtifact}\n";
}

echo "HR Module PDF User Manual Generation Complete!\n";
