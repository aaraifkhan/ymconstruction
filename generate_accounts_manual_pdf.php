<?php

/**
 * PDF Generator for YM Construction Management System - Accounts Module User Manual
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
<title>YM Construction Management System — Accounts Module User Manual</title>
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
        font-size: 16pt;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 4pt;
        color: #0f172a;
        margin-top: 20pt;
    }

    .section-title {
        font-size: 14pt;
        background-color: #0f172a;
        color: #ffffff;
        padding: 6pt 10pt;
        border-radius: 3px;
        margin-top: 18pt;
        margin-bottom: 10pt;
    }

    h2 {
        font-size: 12pt;
        color: #0369a1;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 3pt;
        margin-top: 14pt;
    }

    h3 {
        font-size: 10pt;
        color: #1e293b;
        margin-top: 10pt;
    }

    h4 {
        font-size: 9pt;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 8pt;
    }

    p {
        margin-top: 0;
        margin-bottom: 6pt;
        text-align: justify;
    }

    ul, ol {
        margin-top: 0;
        margin-bottom: 6pt;
        padding-left: 18px;
    }

    li {
        margin-bottom: 3pt;
    }

    /* Tables */
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6pt;
        margin-bottom: 10pt;
        font-size: 7.8pt;
    }

    table.data-table th, table.data-table td {
        border: 1px solid #cbd5e1;
        padding: 4.5pt 6pt;
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
        padding: 3pt 4.5pt;
        font-size: 7.3pt;
    }

    /* Callout boxes */
    .callout {
        padding: 6pt 9pt;
        border-left: 3.5px solid #0284c7;
        background-color: #f0f9ff;
        border-radius: 0 4px 4px 0;
        margin-top: 6pt;
        margin-bottom: 8pt;
        page-break-inside: avoid;
    }

    .callout-title {
        font-weight: 700;
        font-size: 8.5pt;
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

    /* Diagram / Code Block */
    .diagram-box {
        font-family: 'Courier New', Courier, monospace;
        font-size: 7.2pt;
        line-height: 1.35;
        background-color: #0f172a;
        color: #38bdf8;
        padding: 8pt 10pt;
        border-radius: 4px;
        margin-top: 6pt;
        margin-bottom: 8pt;
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
        margin-bottom: 8pt;
        page-break-inside: avoid;
    }

    .procedure-header {
        background-color: #f1f5f9;
        padding: 5pt 8pt;
        font-weight: 700;
        font-size: 8.5pt;
        color: #0f172a;
        border-bottom: 1px solid #e2e8f0;
    }

    .procedure-body {
        padding: 6pt 8pt;
    }

    .step-list {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

    .step-item {
        position: relative;
        padding-left: 22px;
        margin-bottom: 5pt;
    }

    .step-number {
        position: absolute;
        left: 0;
        top: 0;
        background-color: #0284c7;
        color: #ffffff;
        font-size: 7pt;
        font-weight: 700;
        width: 15px;
        height: 15px;
        line-height: 15px;
        text-align: center;
        border-radius: 50%;
    }

    /* Cover Page */
    .cover-container {
        padding-top: 35mm;
        text-align: center;
    }

    .cover-badge {
        display: inline-block;
        background-color: #0284c7;
        color: #ffffff;
        font-size: 9pt;
        font-weight: 700;
        letter-spacing: 1.5px;
        padding: 4pt 14pt;
        border-radius: 20px;
        text-transform: uppercase;
        margin-bottom: 15pt;
    }

    .cover-title {
        font-size: 26pt;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
        margin-bottom: 8pt;
    }

    .cover-subtitle {
        font-size: 13pt;
        font-weight: 500;
        color: #0369a1;
        margin-bottom: 25pt;
        line-height: 1.3;
    }

    .cover-divider {
        width: 80px;
        height: 4px;
        background-color: #0284c7;
        margin: 0 auto 25pt auto;
        border-radius: 2px;
    }

    .cover-desc {
        font-size: 9.5pt;
        color: #475569;
        max-width: 480px;
        margin: 0 auto 35pt auto;
        line-height: 1.6;
    }

    .cover-metadata-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 14pt 18pt;
        width: 85%;
        margin: 0 auto;
        text-align: left;
    }

    .cover-metadata-card table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
    }

    .cover-metadata-card td {
        padding: 3pt 6pt;
    }

    .cover-metadata-card td.label {
        font-weight: 700;
        color: #0f172a;
        width: 32%;
    }

    .cover-metadata-card td.value {
        color: #334155;
    }

    /* TOC */
    .toc-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10pt;
    }

    .toc-table tr td {
        padding: 4pt 2pt;
        border-bottom: 1px dotted #cbd5e1;
        font-size: 8.5pt;
    }

    .toc-table td.toc-title {
        font-weight: 600;
        color: #0f172a;
    }

    .toc-table td.toc-title.sub {
        padding-left: 15pt;
        font-weight: normal;
        color: #334155;
        font-size: 8pt;
    }

    .toc-table td.toc-page {
        text-align: right;
        font-weight: 600;
        color: #0284c7;
        width: 30px;
    }

    .grid-2 {
        width: 100%;
        border-collapse: collapse;
    }
    .grid-2 td {
        width: 50%;
        vertical-align: top;
        padding: 0 4pt;
    }
</style>
</head>
<body>

<!-- Dynamic Footer & Header Script for Dompdf -->
<script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->get_font("Helvetica", "normal");
    $boldFont = $fontMetrics->get_font("Helvetica", "bold");
    $size = 7.5;
    
    // Total pages calculation is deferred by Dompdf
    $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($font, $boldFont, $size) {
        if ($pageNumber > 1) {
            // Header
            $canvas->text(40, 20, "YM CONSTRUCTION MANAGEMENT SYSTEM", $boldFont, 7.5, array(0.06, 0.09, 0.16));
            $canvas->text(220, 20, "•   ACCOUNTS & OPERATIONS MANUAL", $font, 7.5, array(0.2, 0.4, 0.6));
            $canvas->text(440, 20, "CONFIDENTIAL & OPERATIONAL", $font, 7.5, array(0.5, 0.5, 0.5));
            $canvas->line(40, 30, 555, 30, array(0.8, 0.85, 0.9), 1);
            
            // Footer
            $canvas->line(40, 795, 555, 795, array(0.8, 0.85, 0.9), 1);
            $canvas->text(40, 805, "YM-CMS v1.0 Enterprise Edition  |  Financial & Accounting System", $font, $size, array(0.4, 0.4, 0.4));
            $pageText = "Page " . $pageNumber . " of " . $pageCount;
            $canvas->text(500, 805, $pageText, $boldFont, $size, array(0.1, 0.3, 0.6));
        }
    });
}
</script>

<!-- ========================================================================= -->
<!-- COVER PAGE                                                                -->
<!-- ========================================================================= -->
<div class="cover-container">
    <div class="cover-badge">Enterprise Operations Manual</div>
    <div class="cover-title">ACCOUNTS & FINANCIAL<br>OPERATIONS MODULE</div>
    <div class="cover-subtitle">Complete Architecture, Chart of Accounts, Double-Entry Workflows, Standard Operating Procedures & Financial Reporting</div>
    <div class="cover-divider"></div>
    
    <div class="cover-desc">
        A comprehensive, authoritative user guide and operational standard for the Accounts Module of the YM Construction Management System. Designed for Financial Controllers, Accountants, Project Accountants, Treasury Officers, and Site Managers.
    </div>

    <div class="cover-metadata-card">
        <table>
            <tr>
                <td class="label">System Name:</td>
                <td class="value">YM Construction Management System (YM-CMS)</td>
            </tr>
            <tr>
                <td class="label">Module Scope:</td>
                <td class="value">General Ledger, Treasury, AP, AR, Inventory, Fixed Assets, Payroll & Reporting</td>
            </tr>
            <tr>
                <td class="label">Governing Entities:</td>
                <td class="value">BMC Construction, YMC Construction, 7-Orbit IT, 7-Orbit Medical Billing, BMC Trading</td>
            </tr>
            <tr>
                <td class="label">Accounting Standard:</td>
                <td class="value">Accrual-based Double-Entry (PKR Base Currency, Moving Weighted Average)</td>
            </tr>
            <tr>
                <td class="label">Security & Control:</td>
                <td class="value">Strict Multi-Company Tenancy & Maker-Checker Segregation of Duties</td>
            </tr>
            <tr>
                <td class="label">Document Version:</td>
                <td class="value">1.0 (Production Verified Baseline)</td>
            </tr>
            <tr>
                <td class="label">Release Date:</td>
                <td class="value">August 2026</td>
            </tr>
        </table>
    </div>
</div>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- TABLE OF CONTENTS                                                         -->
<!-- ========================================================================= -->
<div class="section-title">TABLE OF CONTENTS</div>

<table class="toc-table">
    <tr><td class="toc-title">1. Executive Overview & System Architecture</td><td class="toc-page">3</td></tr>
    <tr><td class="toc-title sub">1.1 Multi-Company Foundation & Data Isolation</td><td class="toc-page">3</td></tr>
    <tr><td class="toc-title sub">1.2 Double-Entry Ledger Single Source of Truth</td><td class="toc-page">3</td></tr>
    <tr><td class="toc-title sub">1.3 Maker-Checker Segregation of Duties Architecture</td><td class="toc-page">4</td></tr>
    <tr><td class="toc-title sub">1.4 High-Level Accounting Workflow Map</td><td class="toc-page">4</td></tr>

    <tr><td class="toc-title">2. Complete Accounts Hierarchy & Classification Structure</td><td class="toc-page">5</td></tr>
    <tr><td class="toc-title sub">2.1 The Standard 101-Account Structure & Categories</td><td class="toc-page">5</td></tr>
    <tr><td class="toc-title sub">2.2 Account Types & Normal Balances</td><td class="toc-page">6</td></tr>
    <tr><td class="toc-title sub">2.3 Control Accounts vs. Posting Leaf Accounts</td><td class="toc-page">6</td></tr>
    <tr><td class="toc-title sub">2.4 Multi-Dimensional Subledgers & Dimension Tagging</td><td class="toc-page">7</td></tr>
    <tr><td class="toc-title sub">2.5 Core System Accounting Mappings (The 26 Mapping Keys)</td><td class="toc-page">8</td></tr>
    <tr><td class="toc-title sub">2.6 Visual Master Accounting Hierarchy Tree</td><td class="toc-page">9</td></tr>

    <tr><td class="toc-title">3. Comprehensive Screen & Component Directory</td><td class="toc-page">10</td></tr>
    <tr><td class="toc-title sub">3.1 Master Configuration: COA, Settings, Periods & Voucher Sequences</td><td class="toc-page">10</td></tr>
    <tr><td class="toc-title sub">3.2 Core Vouchers & Manual Journal Entries</td><td class="toc-page">12</td></tr>
    <tr><td class="toc-title sub">3.3 Treasury: Payments, Receipts, Contra & Bank Reconciliation</td><td class="toc-page">14</td></tr>
    <tr><td class="toc-title sub">3.4 Accounts Payable: 3-Way Matching, Vendor Bills & Credit Notes</td><td class="toc-page">16</td></tr>
    <tr><td class="toc-title sub">3.5 Accounts Receivable: Running Bills, Invoices & Sales</td><td class="toc-page">18</td></tr>
    <tr><td class="toc-title sub">3.6 Site Inventory Accounting & GRNI Handover</td><td class="toc-page">20</td></tr>
    <tr><td class="toc-title sub">3.7 Fixed Assets, Asset Register & Monthly Depreciation</td><td class="toc-page">21</td></tr>
    <tr><td class="toc-title sub">3.8 Operational Expenses, Petty Cash, Bidding & Director Ledgers</td><td class="toc-page">23</td></tr>
    <tr><td class="toc-title sub">3.9 Opening Balances & Year-End Closings</td><td class="toc-page">25</td></tr>

    <tr><td class="toc-title">4. Step-by-Step Operational Instructions</td><td class="toc-page">27</td></tr>
    <tr><td class="toc-title sub">4.1 Creating Custom Accounts & Managing Financial Periods</td><td class="toc-page">27</td></tr>
    <tr><td class="toc-title sub">4.2 Creating, Approving & Posting Manual Journal Vouchers (JV)</td><td class="toc-page">28</td></tr>
    <tr><td class="toc-title sub">4.3 Processing Vendor Bills with 3-Way GRN Matching</td><td class="toc-page">29</td></tr>
    <tr><td class="toc-title sub">4.4 Processing Construction Running Bills & Progress Certificates</td><td class="toc-page">30</td></tr>
    <tr><td class="toc-title sub">4.5 Executing Treasury Payments & Customer Receipts</td><td class="toc-page">31</td></tr>
    <tr><td class="toc-title sub">4.6 Performing Bank Statement Import & Reconciliation</td><td class="toc-page">32</td></tr>
    <tr><td class="toc-title sub">4.7 Managing Petty Cash Register & Physical Cash Count</td><td class="toc-page">33</td></tr>
    <tr><td class="toc-title sub">4.8 Multi-Company Shared Cost Allocation</td><td class="toc-page">34</td></tr>
    <tr><td class="toc-title sub">4.9 Generating & Posting Monthly Fixed Asset Depreciation</td><td class="toc-page">35</td></tr>
    <tr><td class="toc-title sub">4.10 Posting Payroll Runs to the General Ledger</td><td class="toc-page">36</td></tr>
    <tr><td class="toc-title sub">4.11 Performing Financial Reversals</td><td class="toc-page">37</td></tr>
    <tr><td class="toc-title sub">4.12 Executing Fiscal Year-End Closing</td><td class="toc-page">38</td></tr>

    <tr><td class="toc-title">5. End-to-End Accounting Workflows & Double-Entry Impacts</td><td class="toc-page">39</td></tr>
    <tr><td class="toc-title sub">5.1 Procurement to Payment (P2P) Accounting Trail</td><td class="toc-page">39</td></tr>
    <tr><td class="toc-title sub">5.2 Construction Project Costing & Material Issues</td><td class="toc-page">40</td></tr>
    <tr><td class="toc-title sub">5.3 Order to Cash (O2C) & Progress Billing Trail</td><td class="toc-page">41</td></tr>
    <tr><td class="toc-title sub">5.4 Trading Sales & Automatic COGS Recognition</td><td class="toc-page">42</td></tr>
    <tr><td class="toc-title sub">5.5 Petty Cash, Director Loans & Reimbursement Flow</td><td class="toc-page">43</td></tr>
    <tr><td class="toc-title sub">5.6 Inter-Company Cost Allocation Flow</td><td class="toc-page">44</td></tr>
    <tr><td class="toc-title sub">5.7 Fixed Asset Depreciation & Disposal Accounting</td><td class="toc-page">45</td></tr>
    <tr><td class="toc-title sub">5.8 Payroll Accrual & Disbursal Trail</td><td class="toc-page">46</td></tr>

    <tr><td class="toc-title">6. Financial Reports & Verification Catalog</td><td class="toc-page">47</td></tr>
    <tr><td class="toc-title sub">6.1 Core Financial Statements (TB, GL, P&L, Balance Sheet)</td><td class="toc-page">47</td></tr>
    <tr><td class="toc-title sub">6.2 Subledger Aging & Reconciliation Reports (AP, AR)</td><td class="toc-page">48</td></tr>
    <tr><td class="toc-title sub">6.3 Treasury, Bank Book & Cash Position Statements</td><td class="toc-page">49</td></tr>
    <tr><td class="toc-title sub">6.4 Project Costing, Bidding & Expense Summary Books</td><td class="toc-page">50</td></tr>
    <tr><td class="toc-title sub">6.5 Group Consolidated Financial Reports</td><td class="toc-page">51</td></tr>

    <tr><td class="toc-title">7. Practical Business Scenarios & Step-by-Step Walkthroughs</td><td class="toc-page">52</td></tr>
    <tr><td class="toc-title">8. Business Rules, Accounting Policies & Control Matrix</td><td class="toc-page">56</td></tr>
    <tr><td class="toc-title">9. Comprehensive Diagnostic & Troubleshooting Guide</td><td class="toc-page">59</td></tr>
    <tr><td class="toc-title">10. Quick Reference Guide & Technical Glossary</td><td class="toc-page">62</td></tr>
</table>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 1: EXECUTIVE OVERVIEW & ARCHITECTURE                              -->
<!-- ========================================================================= -->
<div class="section-title">1. EXECUTIVE OVERVIEW & SYSTEM ARCHITECTURE</div>

<p>
    The <strong>YM Construction Management System (YM-CMS)</strong> is an enterprise-grade ERP designed specifically for multi-entity construction, engineering, medical billing, and IT service conglomerates. The Accounts module serves as the central financial nervous system of the organization, enforcing strict double-entry rigor, immutable auditability, and complete multi-tenant corporate segregation.
</p>

<h2>1.1 Multi-Company Foundation & Data Isolation</h2>
<p>
    The software manages multiple distinct legal entities within a single unified database instance without allowing synthetic data leakage across corporate boundaries:
</p>
<ul>
    <li><strong>BMC Construction:</strong> Commercial and infrastructure civil works, heavy plant operations, and project contracting.</li>
    <li><strong>YMC Construction:</strong> Residential and commercial construction, architectural project delivery, and site development.</li>
    <li><strong>7-Orbit:</strong> Technology management, software development, IT services, and corporate holding operations.</li>
    <li><strong>7-Orbit Medical Billing:</strong> Offshore healthcare revenue cycle management, medical claims processing, and healthcare BPO.</li>
    <li><strong>BMC Trading:</strong> Material distribution, wholesale construction inputs, equipment supply, and commercial trading.</li>
</ul>

<div class="callout danger">
    <div class="callout-title">CRITICAL ARCHITECTURAL RULE: STRICT CORPORATE TENANCY</div>
    Every operational transaction, journal line, bank balance, customer balance, and inventory record belongs strictly to <strong>one active company</strong>. Users switch active companies via the top navigation switcher. Cross-company postings are strictly prohibited in standard vouchers; inter-company transactions must execute via dedicated paired bilateral vouchers (Due-To / Due-From).
</div>

<h2>1.2 Double-Entry Ledger: The Single Source of Truth</h2>
<p>
    The accounting engine enforces classical double-entry bookkeeping across all modules. Direct modification of account balances or ledger balances in the database is strictly impossible:
</p>
<ul>
    <li><strong>Every financial event</strong> originates from a balanced Journal Entry composed of at least two balanced lines.</li>
    <li><strong>Precision:</strong> All monetary values are computed and stored at <strong>four decimal places (<span class="code-inline">decimal(19,4)</span>)</strong> to eliminate fractional rounding discrepancies, and formatted to two decimal places (<span class="code-inline">PKR 0.00</span>) in UI presentations and statements.</li>
    <li><strong>Immutability:</strong> Once a voucher is moved to the <span class="badge badge-primary">Posted</span> state, it is permanently locked. No user, administrator, or developer can edit or delete a posted journal line.</li>
    <li><strong>Reversals:</strong> Any error correction requires a formal, authorized <span class="badge badge-danger">Reverse</span> transaction, which automatically generates a linked opposite journal entry (<span class="code-inline">REV-YYYY-XXXXXX</span>) in an active open financial period.</li>
</ul>

<h2>1.3 Maker-Checker Segregation of Duties Architecture</h2>
<p>
    To ensure absolute internal control and prevent financial fraud, the software strictly enforces the <strong>Maker-Checker principle</strong> across all financial transactions:
</p>
<table class="data-table">
    <thead>
        <tr>
            <th>Workflow Stage</th>
            <th>Required Role</th>
            <th>Permitted Actions</th>
            <th>Enforced Constraints</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>1. Preparation (Maker)</strong></td>
            <td>Accounts / Site Preparer</td>
            <td>Create, Edit draft, Add lines, Attach documents, Submit for review</td>
            <td>Cannot Approve or Post own prepared record.</td>
        </tr>
        <tr>
            <td><strong>2. Verification / Review</strong></td>
            <td>Accounts Reviewer / Auditor</td>
            <td>Verify supporting documents, review 3-way match, inspect variances</td>
            <td>Must be a different user from Preparer.</td>
        </tr>
        <tr>
            <td><strong>3. Approval (Checker)</strong></td>
            <td>Finance Approver / Manager</td>
            <td>Approve transaction, Reject with mandatory reason</td>
            <td>Cannot approve own transaction. Checks period & budget limits.</td>
        </tr>
        <tr>
            <td><strong>4. Posting (Final Signoff)</strong></td>
            <td>Finance Poster / Controller</td>
            <td>Post to General Ledger, Assign atomic voucher number</td>
            <td>Locks record permanently; triggers GL updates & subledger impact.</td>
        </tr>
    </tbody>
</table>

<h2>1.4 High-Level Accounting Workflow Map</h2>
<p>
    The diagram below illustrates how external operational events flow through verification gates and culminate in the immutable General Ledger and executive financial statements:
</p>

<div class="diagram-box">
+----------------------------------------------------------------------------------------------------+
|                                OPERATIONAL SOURCES (DECENTRALIZED)                                 |
|  +--------------------+   +---------------------+   +---------------------+   +-----------------+  |
|  |  Purchase Orders   |   |   Goods Receipts    |   | Customer IPC Bills  |   |  Payroll Runs   |  |
|  | (Site Procurement) |   | (Store Inspection)  |   | (Project Running)   |   | (HR Department) |  |
|  +---------+----------+   +----------+----------+   +----------+----------+   +--------+--------+  |
+------------|-------------------------|-------------------------|-----------------------|-----------+
             |                         |                         |                       |            
             v                         v                         v                       v            
+----------------------------------------------------------------------------------------------------+
|                                 ACCOUNTS TRANSACTION CONTROL GATES                                 |
|  +--------------------+   +---------------------+   +---------------------+   +-----------------+  |
|  |  3-Way AP Matching |   |   GRNI Inventory    |   |  Certified Revenue  |   | Net Pay & Loan  |  |
|  |   (Vendor Bills)   |   |      Accrual        |   |   & Retention Calc  |   |   Recovery Calc |  |
|  +---------+----------+   +----------+----------+   +----------+----------+   +--------+--------+  |
+------------|-------------------------|-------------------------|-----------------------|-----------+
             |                         |                         |                       |            
             +-------------------------+------------+------------+-----------------------+            
                                                    |                                                 
                                                    v                                                 
+----------------------------------------------------------------------------------------------------+
|                                    DOUBLE-ENTRY POSTING ENGINE                                     |
|    - Validates Open Financial Period             - Validates Debit Total == Credit Total           |
|    - Checks Active Leaf Account Status           - Enforces Mandatory Dimension Tagging            |
|    - Reserves Atomic Sequential Voucher Number   - Creates Immutable Audit Log & Hash              |
+---------------------------------------------------+------------------------------------------------+
                                                    |                                                 
                                                    v                                                 
+----------------------------------------------------------------------------------------------------+
|                            IMMUTABLE GENERAL LEDGER (SINGLE TRUTH)                                 |
|  +-----------------------------------------------------------------------------------------------+ |
|  | Journal Entries (JV) | Payment (PV) | Receipt (RV) | Purchase (PUR) | Sales (SAL) | Contra (CV)| |
|  +-----------------------------------------------------------------------------------------------+ |
+---------------------------------------------------+------------------------------------------------+
                                                    |                                                 
         +------------------------------------------+----------------------------------------+        
         v                                          v                                        v        
+------------------+                      +-------------------+                    +------------------+
| FINANCIAL STMT   |                      | SUBLEDGER AGING   |                    | EXECUTIVE BOOKS  |
| - Trial Balance  |                      | - Vendor Ledger   |                    | - Daily 5-Funds  |
| - Profit & Loss  |                      | - Customer Ledger |                    | - Petty Cash Reg |
| - Balance Sheet  |                      | - AP / AR Aging   |                    | - Project Ledger |
+------------------+                      +-------------------+                    +------------------+
</div>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 2: COMPLETE ACCOUNTS HIERARCHY & STRUCTURE                        -->
<!-- ========================================================================= -->
<div class="section-title">2. COMPLETE ACCOUNTS HIERARCHY & CLASSIFICATION STRUCTURE</div>

<p>
    The Accounts module uses a structured 4-tier chart of accounts based on international construction and manufacturing accounting standards. The system maintains an account catalog of 101 standard accounts configured across 5 major account classes.
</p>

<h2>2.1 The Standard 101-Account Structure & Major Classes</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 12%;">Code Range</th>
            <th style="width: 25%;">Account Class / Group</th>
            <th style="width: 15%;">Normal Balance</th>
            <th style="width: 48%;">Operational Purpose & Typical Accounts</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>1000 – 1999</strong></td>
            <td><strong>ASSETS</strong></td>
            <td><span class="badge badge-primary">Debit</span></td>
            <td>Economic resources owned by the company:
                <br>• <strong>1100 Current Assets:</strong> Cash (1111), Site Petty Cash (1112), Bank Accounts (1120), Accounts Receivable (1130), Employee Advances (1140), Vendor Advances (1150), Security Deposits (1160), Retention Receivable (1180), WHT Receivable (1185), Input GST (1190), Site Inventory (1196), Work in Progress (1197), Due from Related Companies (1198).
                <br>• <strong>1200 Fixed Assets:</strong> Land (1210), Buildings (1220), Plant & Machinery (1260), Vehicles (1270), Office Equipment (1280), Computers (1240), Less: Accumulated Depreciation (1290 Contra-Asset).
            </td>
        </tr>
        <tr>
            <td><strong>2000 – 2999</strong></td>
            <td><strong>LIABILITIES</strong></td>
            <td><span class="badge badge-warning">Credit</span></td>
            <td>Financial obligations owed to third parties, staff, directors, and state:
                <br>• <strong>2100 Current Liabilities:</strong> Accounts Payable (2110), Contractor Payable (2120), Supplier Payable (2130), Salary Payable (2140), Staff Reimbursement Payable (2145), WHT Payable (2150), Output GST Payable (2160), Accrued Rent (2180), Retention Payable (2191), Customer Mobilization Advance (2192), GRNI Clearing (2193), Due to Related Companies (2195).
                <br>• <strong>2200 Long-Term Liabilities:</strong> Bank Loans (2210), Director Loans / Financing (2220), Partner Loans (2230), Vehicle Lease Obligations (2240).
            </td>
        </tr>
        <tr>
            <td><strong>3000 – 3999</strong></td>
            <td><strong>EQUITY / CAPITAL</strong></td>
            <td><span class="badge badge-warning">Credit</span></td>
            <td>Shareholders' and owners' residual interest in company assets:
                <br>• <strong>3100 Paid-up Capital:</strong> Founder and partner equity contributions.
                <br>• <strong>3200 Retained Earnings:</strong> Cumulative historical net profits/losses.
                <br>• <strong>3300 Current Year Profit / Loss:</strong> System-calculated real-time net result.
            </td>
        </tr>
        <tr>
            <td><strong>4000 – 4999</strong></td>
            <td><strong>REVENUE / INCOME</strong></td>
            <td><span class="badge badge-warning">Credit</span></td>
            <td>Gross earnings generated from operational trading and contracting:
                <br>• <strong>4100 Construction Revenue:</strong> Certified running bill progress earnings.
                <br>• <strong>4200 IT Services Revenue:</strong> Software development, hosting & technical consulting.
                <br>• <strong>4300 Medical Billing Revenue:</strong> Medical RCM and claims commission earnings.
                <br>• <strong>4400 Trading Sales:</strong> Commercial building material and item sales.
                <br>• <strong>4500 Consultancy Income:</strong> Professional engineering and advisory fees.
                <br>• <strong>4700 Other Income:</strong> Scrap sales, interest, and gain on asset disposals.
            </td>
        </tr>
        <tr>
            <td><strong>5000 – 6999</strong></td>
            <td><strong>OPERATING & ADMIN EXPENSES</strong></td>
            <td><span class="badge badge-primary">Debit</span></td>
            <td>Head office overheads, administrative, and corporate costs:
                <br>• <strong>5050–5058 Bidding Expenses:</strong> Pre-award tender fees, bid bonds, drawings.
                <br>• <strong>5100 Head Office Salaries:</strong> Administrative, management & director remuneration.
                <br>• <strong>5200–5500 Utilities & Rent:</strong> Fuel (5200), Office Rent (5300), Power/Gas (5400), Internet (5500).
                <br>• <strong>5600–6000 Maintenance & Transport:</strong> Vehicle Rent (5600), Printing (5700), Stationery (5800), Repairs (5900), Vehicle Maintenance (6000).
                <br>• <strong>6100 Depreciation:</strong> Monthly asset depreciation write-offs.
                <br>• <strong>6200–6800 Marketing & Legal:</strong> Marketing (6200), Legal (6300), Audit (6400), Travel (6800).
            </td>
        </tr>
        <tr>
            <td><strong>7000 – 7999</strong></td>
            <td><strong>DIRECT PROJECT COSTS</strong></td>
            <td><span class="badge badge-primary">Debit</span></td>
            <td>Job-site direct construction costs (Requires mandatory Project tagging):
                <br>• <strong>7100–7180 Structural Materials:</strong> Cement (7100), Steel (7110), Sand (7120), Crush (7130), Bricks (7140), Electrical (7150), Plumbing (7160), Paint (7170), Tiles (7180).
                <br>• <strong>7190 Direct Site Labour:</strong> Daily wages, masons, carpenters, site staff.
                <br>• <strong>7200–7230 Equipment & Heavy Machinery:</strong> Machinery Rental (7200), Excavation (7210), Concrete Pump (7220), Shuttering & Scaffolding (7230).
                <br>• <strong>7240–7280 Site Overheads:</strong> Safety Gear (7240), Site Office (7250), Site Utilities (7260), Site Security (7270), Project Transportation (7280).
            </td>
        </tr>
    </tbody>
</table>

<h2>2.2 Account Types & Normal Balances</h2>
<p>
    The software enforces exact debit/credit mechanics based on the fundamental accounting equation:
</p>
<div style="text-align: center; font-size: 11pt; font-weight: bold; margin: 8pt 0; color: #0369a1;">
    ASSETS + EXPENSES + DIRECT COSTS = LIABILITIES + EQUITY + REVENUE
</div>
<table class="data-table compact-table">
    <thead>
        <tr>
            <th>Account Type</th>
            <th>Normal Balance</th>
            <th>Debit Effect (+)</th>
            <th>Credit Effect (-)</th>
            <th>Financial Statement Destination</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><strong>Asset</strong></td><td>Debit</td><td>Increases Asset Balance</td><td>Decreases Asset Balance</td><td>Balance Sheet (Assets)</td></tr>
        <tr><td><strong>Liability</strong></td><td>Credit</td><td>Decreases Liability Balance</td><td>Increases Liability Balance</td><td>Balance Sheet (Liabilities)</td></tr>
        <tr><td><strong>Equity</strong></td><td>Credit</td><td>Decreases Equity (Drawings)</td><td>Increases Equity (Capital/Profit)</td><td>Balance Sheet (Equity)</td></tr>
        <tr><td><strong>Revenue</strong></td><td>Credit</td><td>Decreases Revenue (Sales Returns)</td><td>Increases Revenue (Earned Sales)</td><td>Profit & Loss (Income)</td></tr>
        <tr><td><strong>Expense</strong></td><td>Debit</td><td>Increases Expense (Incurred Cost)</td><td>Decreases Expense (Rebate/Discount)</td><td>Profit & Loss (Overheads)</td></tr>
    </tbody>
</table>

<h2>2.3 Control Accounts vs. Posting Leaf Accounts</h2>
<p>
    To preserve ledger purity and prevent accidental manual postings to complex automated balances, the system distinguishes between two operational account categories:
</p>
<ul>
    <li><strong>Control Accounts (<span class="code-inline">is_control_account = true</span>):</strong> These accounts represent aggregated master balances backed by dedicated subsidiary ledgers (such as Accounts Receivable backed by Customer Subledgers, Accounts Payable backed by Vendor Subledgers, or Site Inventory backed by Stock Ledgers).
        <div class="callout warning">
            <div class="callout-title">CONTROL ACCOUNT PROTECTION RULE</div>
            Manual journal entry posting directly to Control Accounts is <strong>strictly blocked</strong> (<span class="code-inline">allows_manual_posting = false</span>). Control accounts can only receive entries generated by automated operational workflows (e.g. Vendor Bills, Customer Invoices, Goods Receipts, Treasury Allocations) that carry mandatory subledger dimensions.
        </div>
    </li>
    <li><strong>Posting Leaf Accounts (<span class="code-inline">allows_manual_posting = true</span>):</strong> Active leaf accounts at the bottom of the hierarchy tree with no child accounts that permit direct manual voucher entry (e.g. 5200 Fuel, 5300 Office Rent, 1111 Head Office Cash).</li>
    <li><strong>Parent / Group Accounts:</strong> Summary nodes in the tree (e.g. 1000 Assets, 1100 Current Assets, 5000 Expenses). Postings to parent accounts are strictly rejected by the database schema; they exist solely for hierarchy aggregation in financial reporting.</li>
</ul>

<h2>2.4 Multi-Dimensional Subledgers & Dimension Tagging</h2>
<p>
    Rather than creating separate GL accounts for every customer, vendor, site, or project (which leads to Chart of Accounts explosion), YM-CMS uses a <strong>Multi-Dimensional Tagging Architecture</strong>. Every Journal Line captures:
</p>
<table class="data-table">
    <thead>
        <tr>
            <th>Dimension</th>
            <th>Associated Model</th>
            <th>When Mandatory</th>
            <th>Downstream Reporting Impact</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Party ID</strong></td>
            <td><span class="code-inline">App\Models\Party</span></td>
            <td>Mandatory on AR (1130), AP (2110), Vendor Advances (1150), Customer Advances (2192).</td>
            <td>Customer Ledger, Vendor Ledger, AP/AR Aging Schedules, Party Statements.</td>
        </tr>
        <tr>
            <td><strong>Project ID</strong></td>
            <td><span class="code-inline">App\Models\Project</span></td>
            <td>Mandatory on all Direct Project Costs (7000–7290) and Construction Running Bills (4100).</td>
            <td>Project Profitability Report, Project Budget-vs-Actual, Project Expense Ledger.</td>
        </tr>
        <tr>
            <td><strong>Project Site ID</strong></td>
            <td><span class="code-inline">App\Models\ProjectSite</span></td>
            <td>Mandatory on Site Inventory (1196), GRNI Clearing (2193), and Store Issues.</td>
            <td>Site Inventory Balances, Material Movement Register, Store Stock Ledger.</td>
        </tr>
        <tr>
            <td><strong>Cost Center ID</strong></td>
            <td><span class="code-inline">App\Models\CostCenter</span></td>
            <td>Optional/Required by policy on Head Office Overheads (5000s).</td>
            <td>Departmental Cost Analysis, Administrative Overhead Breakdown.</td>
        </tr>
        <tr>
            <td><strong>Employment ID</strong></td>
            <td><span class="code-inline">App\Models\Employment</span></td>
            <td>Mandatory on Salary Payable (2140), Employee Advances (1140), Staff Payable (2145).</td>
            <td>Employee Advance Ledger, Individual Salary Settlement, Clearance Audit.</td>
        </tr>
        <tr>
            <td><strong>Company Bank ID</strong></td>
            <td><span class="code-inline">CompanyBankAccount</span></td>
            <td>Mandatory on all Bank GL Accounts (1120 series).</td>
            <td>Bank Book, Treasury Position Statement, Bank Reconciliation Register.</td>
        </tr>
        <tr>
            <td><strong>Fixed Asset ID</strong></td>
            <td><span class="code-inline">App\Models\FixedAsset</span></td>
            <td>Mandatory on Asset Additions (1200s), Depreciation (6100), and Disposals.</td>
            <td>Fixed Asset Register, Asset Depreciation Schedule, Asset Custody Ledger.</td>
        </tr>
        <tr>
            <td><strong>Related Company ID</strong></td>
            <td><span class="code-inline">App\Models\Company</span></td>
            <td>Mandatory on Due from Related (1198) and Due to Related (2195).</td>
            <td>Inter-Company Bilateral Reconciliation, Elimination for Group Consolidation.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<h2>2.5 Core System Accounting Mappings (The 26 Mapping Keys)</h2>
<p>
    The system maps operational events to General Ledger accounts via 26 standardized system keys defined in <span class="code-inline">App\Enums\AccountingMappingKey</span>. Every active company is provisioned with these mappings to guarantee automated posting integrity:
</p>

<table class="data-table compact-table">
    <thead>
        <tr>
            <th>System Mapping Key</th>
            <th>Default GL Code & Name</th>
            <th>Account Type</th>
            <th>Triggering Operational Workflow</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><span class="code-inline">default_cash</span></td><td>1111 Head Office Cash</td><td>Asset</td><td>Cash payments, cash receipts, cash top-ups.</td></tr>
        <tr><td><span class="code-inline">bank_accounts</span></td><td>1120 Company Bank Accounts</td><td>Asset</td><td>Cheque / Electronic bank transactions, statement imports.</td></tr>
        <tr><td><span class="code-inline">accounts_receivable</span></td><td>1130 Accounts Receivable (Control)</td><td>Asset</td><td>Customer Invoices, Running Bills, Credit Notes, AR Receipts.</td></tr>
        <tr><td><span class="code-inline">accounts_payable</span></td><td>2110 Accounts Payable (Control)</td><td>Liability</td><td>Vendor Bills, Subcontractor Bills, AP Payments.</td></tr>
        <tr><td><span class="code-inline">employee_advances</span></td><td>1140 Employee Advances (Control)</td><td>Asset</td><td>HR Loans, Emergency Salary Advances, Payroll Deductions.</td></tr>
        <tr><td><span class="code-inline">vendor_advances</span></td><td>1150 Vendor Advances (Control)</td><td>Asset</td><td>Advance mobilizations paid to suppliers/subcontractors.</td></tr>
        <tr><td><span class="code-inline">input_tax</span></td><td>1190 Input GST / Sales Tax</td><td>Asset</td><td>Recoverable sales tax paid on vendor purchases and materials.</td></tr>
        <tr><td><span class="code-inline">output_tax</span></td><td>2160 Output GST / Sales Tax Payable</td><td>Liability</td><td>Sales tax charged to clients on construction/service bills.</td></tr>
        <tr><td><span class="code-inline">wht_receivable</span></td><td>1185 WHT Receivable</td><td>Asset</td><td>Income tax withheld at source by clients upon paying our bills.</td></tr>
        <tr><td><span class="code-inline">wht_payable</span></td><td>2150 WHT Payable</td><td>Liability</td><td>Income tax withheld by us from vendor bills & staff payroll.</td></tr>
        <tr><td><span class="code-inline">salary_payable</span></td><td>2140 Salary Payable (Control)</td><td>Liability</td><td>Monthly payroll postings prior to net bank/cash disbursement.</td></tr>
        <tr><td><span class="code-inline">retention_receivable</span></td><td>1180 Retention Receivable</td><td>Asset</td><td>5-10% contract security withheld by clients from progress bills.</td></tr>
        <tr><td><span class="code-inline">retention_payable</span></td><td>2191 Retention Payable</td><td>Liability</td><td>Security withheld by us from subcontractor work certificates.</td></tr>
        <tr><td><span class="code-inline">customer_advances</span></td><td>2192 Customer Mobilization Advances</td><td>Liability</td><td>Upfront unearned advance funds received from clients.</td></tr>
        <tr><td><span class="code-inline">grni</span></td><td>2193 Goods Received Not Invoiced</td><td>Liability</td><td>Temporary accrual clearing account at GRN handover stage.</td></tr>
        <tr><td><span class="code-inline">site_inventory</span></td><td>1196 Project / Site Inventory</td><td>Asset</td><td>Valuation of materials held at stores at moving average cost.</td></tr>
        <tr><td><span class="code-inline">work_in_progress</span></td><td>1197 Construction Work In Progress</td><td>Asset</td><td>Uncertified direct construction costs on active job sites.</td></tr>
        <tr><td><span class="code-inline">due_from_related_companies</span></td><td>1198 Due from Related Companies</td><td>Asset</td><td>Receivable due from sister company for shared expenses paid.</td></tr>
        <tr><td><span class="code-inline">due_to_related_companies</span></td><td>2195 Due to Related Companies</td><td>Liability</td><td>Payable due to sister company for expenses absorbed on our behalf.</td></tr>
        <tr><td><span class="code-inline">retained_earnings</span></td><td>3200 Retained Earnings</td><td>Equity</td><td>Cumulative historical profits/losses transferred at Year-End.</td></tr>
        <tr><td><span class="code-inline">current_year_result</span></td><td>3300 Current Year Profit / Loss</td><td>Equity</td><td>System-calculated dynamic closing result of current fiscal year.</td></tr>
        <tr><td><span class="code-inline">site_petty_cash</span></td><td>1112 Site Petty Cash</td><td>Asset</td><td>Job-site cash floats for emergency and daily expenses.</td></tr>
        <tr><td><span class="code-inline">director_cash_advance</span></td><td>1113 Director Cash Advance</td><td>Asset</td><td>Company funds temporarily advanced to director for company use.</td></tr>
        <tr><td><span class="code-inline">director_loan</span></td><td>2220 Director Loans / Financing</td><td>Liability</td><td>Personal funds injected by Director to pay company expenses.</td></tr>
        <tr><td><span class="code-inline">staff_reimbursement_payable</span></td><td>2145 Staff Reimbursement Payable</td><td>Liability</td><td>Out-of-pocket claims owed to employees for company expenses.</td></tr>
        <tr><td><span class="code-inline">rental_payable</span></td><td>2180 Rental Payable / Accrued Rent</td><td>Liability</td><td>Accrued machinery and office rental obligations.</td></tr>
    </tbody>
</table>

<h2>2.6 Visual Master Accounting Hierarchy Tree</h2>
<div class="diagram-box">
1000 ASSETS [Dr]
├── 1100 CURRENT ASSETS [Dr]
│   ├── 1110 Cash in Hand
│   │   ├── 1111 Head Office Cash (Posting Leaf)
│   │   ├── 1112 Site Petty Cash (Posting Leaf)
│   │   └── 1113 Director Cash Advance (Posting Leaf)
│   ├── 1120 Bank Accounts (Dynamic Child Account per Company Bank Account)
│   ├── 1130 Accounts Receivable (Control Account -> Party Dimension Required)
│   ├── 1140 Employee Advances (Control Account -> Employment Dimension Required)
│   ├── 1150 Vendor Advances (Control Account -> Party Dimension Required)
│   ├── 1180 Retention Receivable (Control Account -> Project Dimension Required)
│   ├── 1185 WHT Receivable (Tax Asset)
│   ├── 1190 Input GST / Sales Tax (Tax Asset)
│   ├── 1196 Project / Site Inventory (Control Account -> Site & Item Dimensions)
│   ├── 1197 Construction Work in Progress (WIP Asset)
│   └── 1198 Due from Related Companies (Inter-Company Asset -> Sister Company Dimension)
└── 1200 FIXED ASSETS [Dr]
    ├── 1210 Land | 1220 Building | 1260 Machinery | 1270 Vehicles | 1240 Computers
    └── 1290 Accumulated Depreciation (Contra-Asset [Cr] -> Class Children)

2000 LIABILITIES [Cr]
├── 2100 CURRENT LIABILITIES [Cr]
│   ├── 2110 Accounts Payable (Control Account -> Party Dimension Required)
│   ├── 2140 Salary Payable (Control Account -> Employment Dimension Required)
│   ├── 2145 Staff Reimbursement Payable (Staff Claims)
│   ├── 2150 WHT Payable (Tax Liability)
│   ├── 2160 Output GST / Sales Tax Payable (Tax Liability)
│   ├── 2180 Accrued Rent / Rental Payable (Machinery / Office Rent)
│   ├── 2191 Retention Payable (Contract Security Owed)
│   ├── 2192 Customer / Mobilization Advances (Unearned Client Inflows)
│   ├── 2193 Goods Received Not Invoiced (GRNI Accrual Clearing)
│   └── 2195 Due to Related Companies (Inter-Company Liability -> Sister Company Dimension)
└── 2200 LONG-TERM LIABILITIES [Cr]
    └── 2220 Director Loans / Financing (Director Funding Liability)

3000 EQUITY [Cr]
├── 3100 Paid-up Capital
├── 3200 Retained Earnings (Prior Years Accumulated Profit/Loss)
└── 3300 Current Year Profit / Loss (System-Calculated Real-Time Result)

4000 REVENUE [Cr]
├── 4100 Construction Revenue (Running Bill / Certified IPC Earnings)
├── 4200 IT Services Revenue | 4300 Medical Billing Revenue | 4400 Trading Sales
└── 4700 Other Income (Scrap, Gains)

5000 OPERATING & ADMIN EXPENSES [Dr]
├── 5050–5058 Bidding & Tender Expenses (Pre-award Bidding, Quotations, Drawings)
├── 5100 Head Office Salaries | 5200 Fuel | 5300 Office Rent | 5400 Utilities
├── 5500 Internet | 5700 Printing | 5800 Stationery | 5900 Repairs & Maintenance
└── 6100 Depreciation Expense (Monthly Depreciation Write-off)

7000 DIRECT PROJECT COSTS / COST OF SALES [Dr] (Mandatory Project Tagging)
├── 7100 Cement | 7110 Steel | 7120 Sand | 7130 Crush | 7140 Bricks | 7150 Electrical
├── 7190 Direct Site Labour (Wages, Masonry, Site Crews)
├── 7200 Machinery Rental | 7210 Excavation | 7220 Concrete Pump | 7230 Shuttering
└── 7240 Safety Equipment | 7250 Site Office | 7260 Site Utilities | 7270 Site Security
</div>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 3: DETAILED COMPONENT & SCREEN DIRECTORY                          -->
<!-- ========================================================================= -->
<div class="section-title">3. COMPREHENSIVE SCREEN & COMPONENT DIRECTORY</div>

<p>
    This section documents every screen, form, table, button, workflow transition, and configuration interface implemented in the software.
</p>

<h2>3.1 Master Configuration: COA, Settings, Periods & Voucher Sequences</h2>

<h3>Screen 3.1.1: Chart of Accounts (<span class="code-inline">AccountResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Chart of Accounts</span> (<span class="code-inline">/admin/accounts</span>)</li>
    <li><strong>Purpose:</strong> View, create, and maintain the company's active chart of accounts. Enables activation of leaf accounts and controls manual posting permissions.</li>
    <li><strong>Who Uses It:</strong> Chief Financial Officer, Head of Accounts, System Administrator.</li>
    <li><strong>Form Fields & Meaning:</strong>
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th style="width: 22%;">Field Name</th>
                    <th style="width: 12%;">Type / Rules</th>
                    <th style="width: 66%;">Functional Definition & Accounting Meaning</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><span class="code-inline">code</span></td><td>Text (Req, Max 50)</td><td>Unique alphanumeric GL code (e.g. <span class="code-inline">5200</span>, <span class="code-inline">7100</span>). Must be unique within the active company.</td></tr>
                <tr><td><span class="code-inline">name</span></td><td>Text (Req, Max 255)</td><td>Descriptive legal account title (e.g. <span class="code-inline">Site Fuel & Generator Expense</span>).</td></tr>
                <tr><td><span class="code-inline">parent_id</span></td><td>Select (Searchable)</td><td>Parent group under which this account nests (e.g. <span class="code-inline">5000 Operating Expenses</span>).</td></tr>
                <tr><td><span class="code-inline">account_type</span></td><td>Select (Req)</td><td>Asset, Liability, Equity, Revenue, Expense. Controls balance behavior & statement destination.</td></tr>
                <tr><td><span class="code-inline">normal_balance</span></td><td>Select (Req)</td><td>Debit or Credit. Dictates whether positive balance represents Dr or Cr.</td></tr>
                <tr><td><span class="code-inline">reporting_group</span></td><td>Text (Req)</td><td>Reporting tag used for multi-company consolidation (e.g. <span class="code-inline">Direct Material</span>).</td></tr>
                <tr><td><span class="code-inline">is_control_account</span></td><td>Toggle (Live)</td><td>If enabled, marks account as a subledger master. Automatically disables manual posting.</td></tr>
                <tr><td><span class="code-inline">allows_manual_posting</span></td><td>Toggle</td><td>If false, blocks manual voucher preparation. Required true for manual JV leaf accounts.</td></tr>
                <tr><td><span class="code-inline">is_active</span></td><td>Toggle (Default: true)</td><td>If false, hides account from entry dropdowns while preserving historical postings.</td></tr>
            </tbody>
        </table>
    </li>
    <li><strong>Immutability & Safety:</strong> Accounts that possess posted journal lines cannot be deleted or assigned a parent from a conflicting account class.</li>
</ul>

<h3>Screen 3.1.2: Accounting Settings (<span class="code-inline">AccountingSettingResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Accounting Settings</span></li>
    <li><strong>Purpose:</strong> Governs company-wide fiscal year rules, base currency, timezone, and inventory policies.</li>
    <li><strong>Standard Values:</strong> Base Currency = <span class="code-inline">PKR</span>, Timezone = <span class="code-inline">Asia/Karachi</span>, Fiscal Year Start = <span class="code-inline">July 1</span>, Fiscal Year End = <span class="code-inline">June 30</span>, Inventory Valuation = <span class="code-inline">Moving Weighted Average</span>, Allow Negative Inventory = <span class="code-inline">false</span>.</li>
</ul>

<h3>Screen 3.1.3: Financial Years & Periods (<span class="code-inline">FinancialYearResource</span>, <span class="code-inline">FinancialPeriodResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Financial Years</span> & <span class="badge badge-primary">Financial Periods</span></li>
    <li><strong>Purpose:</strong> Defines the 12 monthly accounting periods (e.g. July 2026 to June 2027) per fiscal year.</li>
    <li><strong>Period Lifecycle States:</strong>
        <ul>
            <li><span class="badge badge-success">Open</span>: Transactions can be posted with transaction dates within the period date boundaries.</li>
            <li><span class="badge badge-gray">Closed</span>: Regular posting is halted at month-end. Can be reopened with authorized reason.</li>
            <li><span class="badge badge-danger">Locked</span>: Permanently sealed (typically after audit / year-end). Reopening requires high-severity audit logging.</li>
        </ul>
    </li>
    <li><strong>Header Actions:</strong> <span class="badge badge-danger">Close Period</span>, <span class="badge badge-warning">Reopen Period</span> (Requires explicit reason text and audit tracking).</li>
</ul>

<h3>Screen 3.1.4: Voucher Sequences (<span class="code-inline">VoucherSequenceResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Voucher Sequences</span></li>
    <li><strong>Purpose:</strong> Manages gapless, atomic sequential numbering across 14 voucher types per fiscal year.</li>
    <li><strong>Fields:</strong> <span class="code-inline">voucher_type</span>, <span class="code-inline">prefix</span> (e.g. <span class="code-inline">JV</span>, <span class="code-inline">PV</span>, <span class="code-inline">RV</span>, <span class="code-inline">PUR</span>, <span class="code-inline">SAL</span>), <span class="code-inline">financial_year_id</span>, <span class="code-inline">next_number</span> (Auto-incremented), <span class="code-inline">padding</span> (Default 6 digits: <span class="code-inline">JV-2026-000001</span>).</li>
</ul>

<div class="page-break"></div>

<h2>3.2 Core Vouchers & Manual Journal Entries (<span class="code-inline">JournalEntryResource</span>)</h2>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Vouchers / Journals</span> (<span class="code-inline">/admin/journal-entries</span>)</li>
    <li><strong>Purpose:</strong> The primary interface for preparing, reviewing, approving, posting, and reversing general journal entries, payment vouchers, receipt vouchers, contra vouchers, debit notes, and credit notes.</li>
    <li><strong>Who Uses It:</strong> Accounts Officers (Preparers), Finance Managers (Approvers), Controllers (Posters).</li>
</ul>

<h3>Voucher Header Form Schema</h3>
<table class="data-table compact-table">
    <thead>
        <tr>
            <th style="width: 22%;">Field</th>
            <th style="width: 15%;">Rules / Options</th>
            <th style="width: 63%;">Description & Accounting Validation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><span class="code-inline">voucher_type</span></td><td>Select (Required)</td><td><span class="code-inline">journal</span> (JV), <span class="code-inline">payment</span> (PV), <span class="code-inline">receipt</span> (RV), <span class="code-inline">contra</span> (CV), <span class="code-inline">debit_note</span> (DN), <span class="code-inline">credit_note</span> (CN).</td></tr>
        <tr><td><span class="code-inline">financial_period_id</span></td><td>Select (Searchable, Req)</td><td>Must select an active <span class="badge badge-success">Open</span> period that contains the <span class="code-inline">transaction_date</span>.</td></tr>
        <tr><td><span class="code-inline">transaction_date</span></td><td>DatePicker (Required)</td><td>Effective accounting date. Dictates report period placement.</td></tr>
        <tr><td><span class="code-inline">reference</span></td><td>Text (Max 120)</td><td>External reference (e.g. Cheque No., Supplier Invoice No., Bank Slip No.).</td></tr>
        <tr><td><span class="code-inline">currency_code</span></td><td>Text (Default: 'PKR')</td><td>Base operating currency.</td></tr>
        <tr><td><span class="code-inline">description</span></td><td>Textarea (Required)</td><td>Comprehensive transaction narration explaining business rationale.</td></tr>
    </tbody>
</table>

<h3>Repeater: Double-Entry Lines (<span class="code-inline">lines</span>)</h3>
<p>Requires a minimum of 2 lines. Automatically attaches the active tenant <span class="code-inline">company_id</span>:</p>
<table class="data-table compact-table">
    <thead>
        <tr>
            <th style="width: 22%;">Column Name</th>
            <th style="width: 15%;">Type / Rules</th>
            <th style="width: 63%;">Description & Rules Enforced</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><span class="code-inline">account_id</span></td><td>Select (Searchable, Req)</td><td>Filtered to active leaf accounts with no children (<span class="code-inline">is_active = true</span>). Displays code + name.</td></tr>
        <tr><td><span class="code-inline">debit</span></td><td>Numeric (Min 0)</td><td>Debit amount in PKR. Exactly one of Debit or Credit must be greater than zero.</td></tr>
        <tr><td><span class="code-inline">credit</span></td><td>Numeric (Min 0)</td><td>Credit amount in PKR. Cannot enter both Debit and Credit on the same line.</td></tr>
        <tr><td><span class="code-inline">party_id</span></td><td>Select (Searchable)</td><td>Customer / Vendor party dimension. Mandatory if account is AR or AP.</td></tr>
        <tr><td><span class="code-inline">project_id</span></td><td>Select (Searchable)</td><td>Project dimension. Mandatory on Direct Project Cost accounts (7000s).</td></tr>
        <tr><td><span class="code-inline">project_site_id</span></td><td>Select (Searchable)</td><td>Job-site / Store dimension for material allocations.</td></tr>
        <tr><td><span class="code-inline">cost_center_id</span></td><td>Select (Searchable)</td><td>Department / Cost Center overhead tag.</td></tr>
        <tr><td><span class="code-inline">employment_id</span></td><td>Select (Searchable)</td><td>Employee dimension. Mandatory on Salary/Advance accounts.</td></tr>
        <tr><td><span class="code-inline">company_bank_account_id</span></td><td>Select (Searchable)</td><td>Bank account dimension. Mandatory on Bank GL accounts (1120).</td></tr>
        <tr><td><span class="code-inline">description</span></td><td>Text (Optional)</td><td>Line-level narration / memo for itemized statement reporting.</td></tr>
    </tbody>
</table>

<h3>Voucher Lifecycle & Action Buttons</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>Workflow Action</th>
            <th>Button Appearance</th>
            <th>Visible When Status Is</th>
            <th>Required Permission</th>
            <th>Execution Logic & State Change</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Submit</strong></td>
            <td><span class="badge badge-warning">Submit</span></td>
            <td><span class="badge badge-gray">Draft</span></td>
            <td><span class="code-inline">submit</span></td>
            <td>Validates balanced debits/credits ($\sum Dr = \sum Cr > 0$), checks open period. Moves status to <span class="badge badge-warning">Submitted</span>.</td>
        </tr>
        <tr>
            <td><strong>Approve</strong></td>
            <td><span class="badge badge-success">Approve</span></td>
            <td><span class="badge badge-warning">Submitted</span></td>
            <td><span class="code-inline">approve</span></td>
            <td>Checker signoff. Validates Maker $\neq$ Checker. Snapshots lines. Moves status to <span class="badge badge-success">Approved</span>.</td>
        </tr>
        <tr>
            <td><strong>Reject</strong></td>
            <td><span class="badge badge-danger">Reject</span></td>
            <td><span class="badge badge-warning">Submitted</span> / <span class="badge badge-success">Approved</span></td>
            <td><span class="code-inline">reject</span></td>
            <td>Opens modal requiring <span class="code-inline">reason</span> text. Returns voucher to <span class="badge badge-danger">Rejected</span> (editable for correction).</td>
        </tr>
        <tr>
            <td><strong>Post</strong></td>
            <td><span class="badge badge-primary">Post</span></td>
            <td><span class="badge badge-success">Approved</span></td>
            <td><span class="code-inline">post</span></td>
            <td>Assigns atomic voucher number (<span class="code-inline">JV-2026-000001</span>), updates GL balances, locks lines permanently. Status $\rightarrow$ <span class="badge badge-primary">Posted</span>.</td>
        </tr>
        <tr>
            <td><strong>Reverse</strong></td>
            <td><span class="badge badge-danger">Reverse</span></td>
            <td><span class="badge badge-primary">Posted</span></td>
            <td><span class="code-inline">reverse</span></td>
            <td>Requires <span class="code-inline">reversal_date</span> & <span class="code-inline">reason</span>. Generates linked opposite journal entry. Status $\rightarrow$ <span class="badge badge-danger">Reversed</span>.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 3.3: TREASURY & BANKING                                           -->
<!-- ========================================================================= -->
<h2>3.3 Treasury: Payments, Receipts, Transfers & Bank Reconciliation</h2>

<h3>Screen 3.3.1: Treasury Transactions (<span class="code-inline">TreasuryTransactionResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Payments, Receipts & Transfers</span> (<span class="code-inline">/admin/treasury-transactions</span>)</li>
    <li><strong>Purpose:</strong> Comprehensive liquid cash and bank disbursement/inflow management, supporting open-item bill settlement, employee advances, and inter-account transfers.</li>
    <li><strong>Transaction Types & Accounting Routing:</strong>
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Type (<span class="code-inline">type</span>)</th>
                    <th>Purpose (<span class="code-inline">purpose</span>)</th>
                    <th>Source (Credit Side)</th>
                    <th>Destination (Debit Side)</th>
                    <th>Supported Open-Item Allocations</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge badge-danger">Payment</span></td>
                    <td><span class="code-inline">settlement</span></td>
                    <td>Company Bank (1120) or Cash (1111)</td>
                    <td>Accounts Payable (2110) or Salary Payable (2140)</td>
                    <td>Allocates against posted Vendor Bills, Payroll Entries, Final Settlements.</td>
                </tr>
                <tr>
                    <td><span class="badge badge-danger">Payment</span></td>
                    <td><span class="code-inline">advance</span></td>
                    <td>Company Bank (1120) or Cash (1111)</td>
                    <td>Vendor Advances (1150) or Employee Advances (1140)</td>
                    <td>Increases counterparty advance subledger balance.</td>
                </tr>
                <tr>
                    <td><span class="badge badge-success">Receipt</span></td>
                    <td><span class="code-inline">settlement</span></td>
                    <td>Accounts Receivable (1130)</td>
                    <td>Company Bank (1120) or Cash (1111)</td>
                    <td>Allocates against posted Customer Invoices & Running Bills.</td>
                </tr>
                <tr>
                    <td><span class="badge badge-success">Receipt</span></td>
                    <td><span class="code-inline">advance</span></td>
                    <td>Customer Advances (2192)</td>
                    <td>Company Bank (1120) or Cash (1111)</td>
                    <td>Increases customer mobilization liability.</td>
                </tr>
                <tr>
                    <td><span class="badge badge-primary">Transfer</span></td>
                    <td><span class="code-inline">other</span></td>
                    <td>Source Bank / Cash Account</td>
                    <td>Destination Bank / Cash Account</td>
                    <td>Same-company Contra Transfer (CV). Both accounts must be same entity.</td>
                </tr>
            </tbody>
        </table>
    </li>
    <li><strong>Repeater: Open-Item Allocations (<span class="code-inline">allocations</span>):</strong>
        <ul>
            <li>Allows partial or full settlement of posted open items.</li>
            <li>Enforces that total allocated amount does not exceed the payment/receipt amount or the remaining open balance of the document.</li>
            <li>Dynamically filters posted unpaid bills for the selected Vendor, or unpaid invoices for the selected Customer, or unpaid payroll runs for the selected Employee.</li>
        </ul>
    </li>
</ul>

<h3>Screen 3.3.2: Bank Statements (<span class="code-inline">BankStatementResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Bank Statements</span></li>
    <li><strong>Purpose:</strong> Secure, normalized import of official bank statement CSV files for electronic bank reconciliation.</li>
    <li><strong>Validation & Security:</strong> Validates strict CSV headers (<span class="code-inline">transaction_date</span>, <span class="code-inline">value_date</span>, <span class="code-inline">description</span>, <span class="code-inline">bank_reference</span>, <span class="code-inline">debit</span>, <span class="code-inline">credit</span>, <span class="code-inline">balance</span>), verifies running balance continuity against opening and closing balances, computes SHA-256 fingerprint per row to prevent duplicate uploads, and performs atomic database rollback on any parsing failure.</li>
</ul>

<h3>Screen 3.3.3: Bank Reconciliation (<span class="code-inline">BankReconciliationResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Bank Reconciliation</span></li>
    <li><strong>Purpose:</strong> Match imported bank statement lines against posted General Ledger bank activity, record adjusting entries (bank charges, withholding tax, interest), and lock the reconciled period.</li>
    <li><strong>Reconciliation Screen Actions (<span class="code-inline">ViewBankReconciliation</span>):</strong>
        <ul>
            <li><span class="badge badge-primary">Match Activity</span>: Selects an imported statement line and a posted GL journal line of identical amount. Creates an immutable <span class="code-inline">BankReconciliationMatch</span> link. Supports 1-to-many and partial matching.</li>
            <li><span class="badge badge-warning">Remove Match</span>: Unlinks a matched pair while reconciliation is <span class="badge badge-success">Open</span>.</li>
            <li><span class="badge badge-warning">Post Adjustment</span>: Generates a direct balancing adjustment journal (e.g. Dr 5400 Bank Charges / Cr 1120 Bank) for unmatched items appearing on the bank statement.</li>
            <li><span class="badge badge-success">Close Reconciliation</span>: Validates that <strong>Bank Statement Closing Balance == Adjusted General Ledger Balance</strong> (Variance = 0.00). Locks the reconciliation period.</li>
            <li><span class="badge badge-danger">Reopen</span>: Allows reopening closed reconciliation with mandatory reason text and audit trail.</li>
        </ul>
    </li>
</ul>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 3.4: ACCOUNTS PAYABLE & 3-WAY MATCHING                            -->
<!-- ========================================================================= -->
<h2>3.4 Accounts Payable: 3-Way Matching, Vendor Bills & Credit Notes</h2>

<h3>Screen 3.4.1: Vendor Bills & Credit Notes (<span class="code-inline">VendorBillResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Vendor Bills & Credit Notes</span> (<span class="code-inline">/admin/vendor-bills</span>)</li>
    <li><strong>Purpose:</strong> Recording vendor invoices, subcontractor progress claims, direct service bills, and vendor credit notes with rigorous 3-Way Matching against issued Purchase Orders and accepted Goods Receipts.</li>
    <li><strong>Who Uses It:</strong> Procurement Officers, Accounts Payable Accountants, Finance Approvers.</li>
</ul>

<h3>Form Architecture & Matching Engine</h3>
<table class="data-table compact-table">
    <thead>
        <tr>
            <th>Section</th>
            <th>Key Fields</th>
            <th>Operational & Accounting Logic</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Vendor Document Header</strong></td>
            <td><span class="code-inline">type</span> (Invoice/Credit Note), <span class="code-inline">purchase_order_id</span>, <span class="code-inline">vendor_id</span>, <span class="code-inline">vendor_invoice_number</span>, <span class="code-inline">invoice_date</span>, <span class="code-inline">due_date</span>, <span class="code-inline">project_id</span>, <span class="code-inline">project_site_id</span>.</td>
            <td>Links the bill to an issued PO. <span class="code-inline">vendor_invoice_number</span> is uniquely checked per vendor to eliminate duplicate invoice entry.</td>
        </tr>
        <tr>
            <td><strong>Invoice Lines (Repeater)</strong></td>
            <td><span class="code-inline">purchase_order_line_id</span>, <span class="code-inline">item_id</span>, <span class="code-inline">unit_of_measure_id</span>, <span class="code-inline">quantity</span>, <span class="code-inline">unit_rate</span>, <span class="code-inline">tax_code_id</span>, <span class="code-inline">clearing_account_id</span>, <span class="code-inline">variance_account_id</span>.</td>
            <td><strong>FIFO GRN Consumption:</strong> At submission, stock lines automatically allocate FIFO against accepted, inspected, and handed-over GRN quantities. Quantity cannot exceed remaining handed-over stock.</td>
        </tr>
        <tr>
            <td><strong>Deductions & Withholding</strong></td>
            <td><span class="code-inline">type</span> (<span class="code-inline">wht</span>, <span class="code-inline">retention</span>, <span class="code-inline">vendor_advance</span>, <span class="code-inline">other</span>), <span class="code-inline">tax_code_id</span>, <span class="code-inline">amount</span>, <span class="code-inline">account_id</span>.</td>
            <td>Calculates income tax withholding (Cr 2150 WHT Payable), contract retention (Cr 2191 Retention Payable), or advance recovery (Cr 1150 Vendor Advances). Deductions reduce net Accounts Payable.</td>
        </tr>
    </tbody>
</table>

<h3>3-Way Matching Workflow States & Actions</h3>
<p>
    The workflow enforces a dedicated review step (<span class="code-inline">ReviewVendorBillMatchAction</span>) between submission and approval:
</p>
<div class="diagram-box">
[ Draft ] ──(Submit)──> [ Submitted ] ──(Review Match)──> [ Reviewed ] ──(Approve)──> [ Approved ] ──(Post)──> [ Posted ]
                               │                                                          │
                               └──(Mismatch Detected / Override Required)                └──(Reject)──> [ Rejected ]
</div>
<ul>
    <li><strong>Review Match Action:</strong> Evaluates PO Rate vs Invoice Rate, and PO GRN Accepted Quantity vs Invoiced Quantity.
        <ul>
            <li>If parameters match within tolerance (<span class="code-inline">ApMatchingSetting</span> = 0.00% default), status advances to <span class="badge badge-success">Reviewed</span>.</li>
            <li>If a rate or quantity mismatch occurs, approval is blocked unless a user with dedicated <span class="code-inline">Override AP Match</span> permission explicitly checks <span class="code-inline">override_mismatch</span> and supplies a detailed <span class="code-inline">mismatch_reason</span>.</li>
        </ul>
    </li>
    <li><strong>Posting Accounting Effect (<span class="code-inline">PostVendorBillAction</span>):</strong>
        <ul>
            <li><strong>Stock Line:</strong> <span class="badge badge-primary">Dr</span> <span class="code-inline">2193 GRNI Clearing</span> (Clears GRN accrual) + <span class="badge badge-primary">Dr</span> <span class="code-inline">1190 Input Tax</span> (if recoverable) + <span class="badge badge-primary">Dr/Cr</span> <span class="code-inline">Price Variance</span> $\rightarrow$ <span class="badge badge-warning">Cr</span> <span class="code-inline">2110 Accounts Payable</span> + <span class="badge badge-warning">Cr</span> <span class="code-inline">2150 WHT Payable</span> + <span class="badge badge-warning">Cr</span> <span class="code-inline">2191 Retention Payable</span>.</li>
            <li><strong>Service Line:</strong> <span class="badge badge-primary">Dr</span> <span class="code-inline">7000s Direct Project Cost</span> or <span class="code-inline">5000s Expense</span> $\rightarrow$ <span class="badge badge-warning">Cr</span> <span class="code-inline">2110 Accounts Payable</span>.</li>
        </ul>
    </li>
</ul>

<h3>Screen 3.4.2: AP Match Tolerances (<span class="code-inline">ApMatchingSettingResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Administration</span> $\rightarrow$ <span class="badge badge-primary">AP Match Tolerances</span></li>
    <li><strong>Purpose:</strong> Configures acceptable percentage and monetary variance thresholds for 3-way matching. Defaults to zero tolerance ($0.00\%$).</li>
</ul>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 3.5: ACCOUNTS RECEIVABLE & PROGRESS BILLING                       -->
<!-- ========================================================================= -->
<h2>3.5 Accounts Receivable: Running Bills, Invoices & Sales (<span class="code-inline">CustomerInvoiceResource</span>)</h2>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Customer Invoices & Credit Notes</span> (<span class="code-inline">/admin/customer-invoices</span>)</li>
    <li><strong>Purpose:</strong> Complete billing engine for Construction Progress Claims (Running Bills / IPC), IT & Medical Service Invoices, and Commercial Trading Sales.</li>
    <li><strong>Invoice Categories:</strong>
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Category (<span class="code-inline">category</span>)</th>
                    <th>Required Operational Dimensions</th>
                    <th>Line & Deduction Architecture</th>
                    <th>Accounting & Inventory Impact</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge badge-primary">Running Bill</span></td>
                    <td>Mandatory <span class="code-inline">project_id</span>, <span class="code-inline">certificate_number</span>, <span class="code-inline">certificate_date</span>, <span class="code-inline">work_value</span>, <span class="code-inline">variation_amount</span>.</td>
                    <td>Revenue lines mapped to <span class="code-inline">4100 Construction Revenue</span>. Deductions capture Contract Retention (<span class="code-inline">1180</span>), Advance Mobilization Recovery (<span class="code-inline">2192</span>), and WHT (<span class="code-inline">1185</span>).</td>
                    <td><span class="badge badge-primary">Dr</span> <span class="code-inline">1130 Accounts Receivable</span> (Net)<br><span class="badge badge-primary">Dr</span> <span class="code-inline">1180 Retention Receivable</span><br><span class="badge badge-primary">Dr</span> <span class="code-inline">1185 WHT Receivable</span><br><span class="badge badge-primary">Dr</span> <span class="code-inline">2192 Customer Advance</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">4100 Construction Revenue</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">2160 Output Tax Payable</span></td>
                </tr>
                <tr>
                    <td><span class="badge badge-success">Service Invoice</span></td>
                    <td>Customer Party, optional Project / Cost Center.</td>
                    <td>Service item lines mapped to <span class="code-inline">4200 IT Revenue</span> or <span class="code-inline">4300 Medical Billing Revenue</span> with Sales Tax codes.</td>
                    <td><span class="badge badge-primary">Dr</span> <span class="code-inline">1130 Accounts Receivable</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">4200/4300 Service Revenue</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">2160 Output Tax Payable</span></td>
                </tr>
                <tr>
                    <td><span class="badge badge-dark">Trading Sale</span></td>
                    <td>Customer Party, <span class="code-inline">inventory_site_id</span>, Stock-tracked Items.</td>
                    <td>Material lines with quantity, rate, <span class="code-inline">revenue_account_id</span> (4400) and <span class="code-inline">cogs_account_id</span> (5000/7000).</td>
                    <td><strong>Balanced Revenue Entry:</strong><br><span class="badge badge-primary">Dr</span> <span class="code-inline">1130 Accounts Receivable</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">4400 Trading Sales</span><br><strong>Automatic COGS Stock Entry:</strong><br><span class="badge badge-primary">Dr</span> <span class="code-inline">Cost of Goods Sold</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">1196 Site/Store Inventory</span> (at Moving Average Cost)</td>
                </tr>
            </tbody>
        </table>
    </li>
</ul>

<!-- ========================================================================= -->
<!-- SECTION 3.6: SITE INVENTORY & MATERIAL ACCOUNTING                         -->
<!-- ========================================================================= -->
<h2>3.6 Site Inventory Accounting & GRNI Handover</h2>

<h3>Screen 3.6.1: Goods Receipts & Inspection (<span class="code-inline">GoodsReceiptResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Goods Receipts & Inspection</span></li>
    <li><strong>3-Stage Material Control Process:</strong>
        <ol>
            <li><strong>Receive Goods (<span class="code-inline">ReceiveGoodsAction</span>):</strong> Storekeeper logs physical delivery against issued PO lines. Locks PO line quantities; assigns <span class="code-inline">GRN-YYYY-XXXXXX</span>.</li>
            <li><strong>QA Inspection (<span class="code-inline">InspectGoodsReceiptAction</span>):</strong> Site Engineer inspects quality. Must enter <span class="code-inline">accepted_quantity</span> and <span class="code-inline">rejected_quantity</span> (<span class="code-inline">Accepted + Rejected = Received</span>). Rejected goods are returned immediately and never enter stock.</li>
            <li><strong>Accounts Handover (<span class="code-inline">HandoverGoodsReceiptToAccountsAction</span>):</strong> Store Supervisor executes financial handover. Automatically updates site inventory quantity and moving average unit cost, creates immutable stock movements, and generates balanced double-entry accrual journal:
                <br>$\rightarrow$ <span class="badge badge-primary">Dr</span> <span class="code-inline">1196 Project/Site Inventory</span> | <span class="badge badge-warning">Cr</span> <span class="code-inline">2193 Goods Received Not Invoiced (GRNI)</span>.
            </li>
        </ol>
    </li>
</ul>

<h3>Screen 3.6.2: Inventory Transfers & Issues (<span class="code-inline">InventoryTransactionResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Inventory Transfers & Issues</span></li>
    <li><strong>Transaction Types & Impact:</strong>
        <ul>
            <li><strong>Material Issue to Project:</strong> Consumes inventory from store for direct job installation.
                <br>$\rightarrow$ <span class="badge badge-primary">Dr</span> <span class="code-inline">7100s Direct Project Cost (Cement/Steel/etc.)</span> [With Project ID] | <span class="badge badge-warning">Cr</span> <span class="code-inline">1196 Site Inventory</span>.
            </li>
            <li><strong>Inter-Site Transfer:</strong> Moves material from Central Store to Site Store. Transfers at current moving weighted-average cost. No GL entry is created because both sites belong to the same company inventory control account.</li>
            <li><strong>Inventory Adjustment:</strong> Physical count surplus/shrinkage adjustment:
                <br>$\rightarrow$ Shrinkage: <span class="badge badge-primary">Dr</span> <span class="code-inline">5900 Inventory Loss / Adjustment</span> | <span class="badge badge-warning">Cr</span> <span class="code-inline">1196 Site Inventory</span>.
            </li>
        </ul>
    </li>
</ul>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 3.7: FIXED ASSETS & DEPRECIATION                                  -->
<!-- ========================================================================= -->
<h2>3.7 Fixed Assets, Asset Register & Monthly Depreciation</h2>

<h3>Screen 3.7.1: Fixed Assets Register (<span class="code-inline">FixedAssetResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Assets</span> $\rightarrow$ <span class="badge badge-primary">Fixed Assets</span> (<span class="code-inline">/admin/fixed-assets</span>)</li>
    <li><strong>Purpose:</strong> Master register of capitalized tangible capital assets (vehicles, concrete batching plants, excavators, server hardware, office premises).</li>
    <li><strong>Form Sections & Core Fields:</strong>
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Field</th>
                    <th>Type / Rules</th>
                    <th>Accounting & Asset Tracking Purpose</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td rowspan="3"><strong>Asset Identity</strong></td>
                    <td><span class="code-inline">asset_number</span></td>
                    <td>Text (Req, Unique)</td>
                    <td>Internal tag / barcode number (e.g. <span class="code-inline">FA-VEH-001</span>, <span class="code-inline">FA-PLT-024</span>).</td>
                </tr>
                <tr><td><span class="code-inline">name</span></td><td>Text (Req)</td><td>Full asset description (e.g. <span class="code-inline">Hino Dump Truck 10-Wheeler</span>).</td></tr>
                <tr><td><span class="code-inline">asset_category_id</span></td><td>Select (Req)</td><td>Asset Category (Vehicles, Machinery, Computers, Buildings).</td></tr>
                <tr>
                    <td rowspan="5"><strong>Acquisition & Depreciation</strong></td>
                    <td><span class="code-inline">acquisition_source</span></td><td>Select (Req)</td><td><span class="code-inline">manual</span>, <span class="code-inline">vendor_bill</span>, or <span class="code-inline">opening_balance</span>.</td></tr>
                    <tr><td><span class="code-inline">acquired_on</span></td><td>DatePicker (Req)</td><td>Purchase date.</td></tr>
                    <tr><td><span class="code-inline">available_for_use_on</span></td><td>DatePicker (Req)</td><td>In-service date. Depreciation calculation begins from this month.</td></tr>
                    <tr><td><span class="code-inline">acquisition_cost</span></td><td>Numeric (Req)</td><td>Gross capitalized cost in PKR (<span class="code-inline">decimal(19,4)</span>).</td></tr>
                    <tr><td><span class="code-inline">residual_value</span></td><td>Numeric (Default 0)</td><td>Estimated salvage / scrap value at end of useful life.</td></tr>
                    <tr><td><span class="code-inline">useful_life_months</span></td><td>Numeric (Req, Min 1)</td><td>Total depreciable lifespan in months (e.g. 60 months = 5 years).</td></tr>
                <tr>
                    <td rowspan="2"><strong>Assignment</strong></td>
                    <td><span class="code-inline">custodian_employment_id</span></td><td>Select</td><td>Staff member or driver holding custody of the asset.</td></tr>
                    <tr><td><span class="code-inline">project_id</span> / <span class="code-inline">site_id</span></td><td>Select</td><td>Job-site or store location where the asset is deployed.</td></tr>
            </tbody>
        </table>
    </li>
    <li><strong>Capitalization Accounting Entry (<span class="code-inline">CapitalizeFixedAssetAction</span>):</strong>
        <br>$\rightarrow$ <span class="badge badge-primary">Dr</span> <span class="code-inline">1200s Fixed Asset Class Account</span> | <span class="badge badge-warning">Cr</span> <span class="code-inline">2110 Accounts Payable</span> (or <span class="code-inline">1120 Bank</span> / <span class="code-inline">Capitalization Credit</span>).
    </li>
</ul>

<h3>Screen 3.7.2: Depreciation Runs (<span class="code-inline">DepreciationRunResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Assets</span> $\rightarrow$ <span class="badge badge-primary">Depreciation Runs</span></li>
    <li><strong>Purpose:</strong> Monthly automated batch depreciation calculation across all active, capitalized fixed assets using the <strong>Straight-Line Depreciation Method</strong>.</li>
    <li><strong>Calculation Formula:</strong>
        <div style="text-align: center; font-size: 10pt; font-weight: bold; margin: 6pt 0; color: #0369a1;">
            Monthly Depreciation = ( Acquisition Cost - Residual Value ) / Useful Life in Months
        </div>
    </li>
    <li><strong>Depreciation Posting Accounting Effect (<span class="code-inline">PostDepreciationRunAction</span>):</strong>
        <br>$\rightarrow$ <span class="badge badge-primary">Dr</span> <span class="code-inline">6100 Depreciation Expense</span> (Operating Overhead)
        <br>$\rightarrow$ <span class="badge badge-warning">Cr</span> <span class="code-inline">1290 Accumulated Depreciation</span> (Contra-Asset Account for Asset Class)
    </li>
</ul>

<h3>Screen 3.7.3: Asset Disposals (<span class="code-inline">AssetDisposalResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Assets</span> $\rightarrow$ <span class="badge badge-primary">Asset Disposals</span></li>
    <li><strong>Purpose:</strong> Retires assets sold, scrapped, or lost. Computes Book Value ($\text{Cost} - \text{Accumulated Depreciation}$) and recognizes Gain/Loss on Disposal.</li>
    <li><strong>Disposal Accounting Entry:</strong>
        <br>$\rightarrow$ <span class="badge badge-primary">Dr</span> <span class="code-inline">1120 Bank / Cash</span> (Sale Proceeds Received)
        <br>$\rightarrow$ <span class="badge badge-primary">Dr</span> <span class="code-inline">1290 Accumulated Depreciation</span> (Clears historical depreciation)
        <br>$\rightarrow$ <span class="badge badge-primary">Dr</span> <span class="code-inline">5900 Loss on Asset Disposal</span> (if Proceeds < Book Value)
        <br>$\rightarrow$ <span class="badge badge-warning">Cr</span> <span class="code-inline">1200s Fixed Asset Cost Account</span> (Clears gross asset cost)
        <br>$\rightarrow$ <span class="badge badge-warning">Cr</span> <span class="code-inline">4700 Gain on Asset Disposal</span> (if Proceeds > Book Value)
    </li>
</ul>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 3.8: OPERATIONAL EXPENSES, PETTY CASH & SPECIAL LEDGERS           -->
<!-- ========================================================================= -->
<h2>3.8 Operational Expenses, Petty Cash, Bidding & Director Ledgers</h2>

<h3>Screen 3.8.1: Quick Expense Entry (<span class="code-inline">QuickExpenseEntryPage</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounts</span> $\rightarrow$ <span class="badge badge-primary">Quick Expense Entry</span> (<span class="code-inline">/admin/quick-expense-entry</span>)</li>
    <li><strong>Purpose:</strong> Fast operational voucher creation with smart category defaults. Users enter date, expense head, amount, and payment method without writing manual debit/credit lines. The system automatically constructs, balances, and submits the journal voucher.</li>
    <li><strong>Supported Payment Methods & Automatic Liability Routing:</strong>
        <ul>
            <li><strong>Cash:</strong> Credits <span class="code-inline">1111 Head Office Cash</span>.</li>
            <li><strong>Bank:</strong> Credits selected <span class="code-inline">Company Bank Account</span> (1120).</li>
            <li><strong>Petty Cash:</strong> Credits <span class="code-inline">1112 Site Petty Cash</span>.</li>
            <li><strong>Director Funded:</strong> Automatically routes to <span class="code-inline">2220 Director Loans</span> (Credit), recording the company's liability to repay the director.</li>
            <li><strong>Staff Out-of-Pocket:</strong> Automatically routes to <span class="code-inline">2145 Staff Reimbursement Payable</span> (Credit).</li>
        </ul>
    </li>
    <li><strong>Mandatory Project Enforcement:</strong> If the selected category is a Direct Project Cost (Cement, Steel, Sand, Crush, Bricks, Site Labour, Machinery Rental, Excavation, Shuttering), the <span class="code-inline">project_id</span> field is strictly mandatory.</li>
</ul>

<h3>Screen 3.8.2: Petty Cash Register & Reconciliation (<span class="code-inline">PettyCashRegisterPage</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounts</span> $\rightarrow$ <span class="badge badge-primary">Petty Cash Register</span> (<span class="code-inline">/admin/petty-cash-register</span>)</li>
    <li><strong>Purpose:</strong> Replicates the classical imprest petty cash book showing chronological receipts, disbursements, and real-time running cash float balances.</li>
    <li><strong>Interactive Modals & Operations:</strong>
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Button</th>
                    <th>Input Fields</th>
                    <th>Accounting & Balance Impact</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Record Petty Expense</strong></td>
                    <td><span class="badge badge-danger">Record Petty Expense</span></td>
                    <td>Date, Expense Head, Amount, Project (Optional), Narration.</td>
                    <td><span class="badge badge-primary">Dr</span> <span class="code-inline">Expense Account</span> | <span class="badge badge-warning">Cr</span> <span class="code-inline">1112 Site Petty Cash</span>. Immediately reduces float.</td>
                </tr>
                <tr>
                    <td><strong>Top-up Float</strong></td>
                    <td><span class="badge badge-success">Top-up Float</span></td>
                    <td>Date, Source (<span class="code-inline">director</span>, <span class="code-inline">bank</span>, <span class="code-inline">head_office_cash</span>), Amount, Memo.</td>
                    <td><span class="badge badge-primary">Dr</span> <span class="code-inline">1112 Site Petty Cash</span> | <span class="badge badge-warning">Cr</span> <span class="code-inline">2220 Director Loan / 1120 Bank / 1111 Cash</span>. Replenishes float.</td>
                </tr>
                <tr>
                    <td><strong>Physical Reconciliation</strong></td>
                    <td><span class="badge badge-warning">Physical Reconciliation</span></td>
                    <td>Date, Physical Counted Cash (PKR), On-Account Held Cash (PKR), Notes.</td>
                    <td>Computes: $\text{System Balance} - ( \text{Physical Cash} + \text{On-Account Held} ) = \text{Variance}$. Records physical count audit log.</td>
                </tr>
            </tbody>
        </table>
    </li>
</ul>

<h3>Screen 3.8.3: Director Expense Ledger (<span class="code-inline">DirectorExpenseLedgerPage</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounts</span> $\rightarrow$ <span class="badge badge-primary">Director Expense Ledger</span></li>
    <li><strong>Purpose:</strong> Dedicated current account ledger tracking personal funds injected by directors to support company operations versus company repayments to directors. Tracks running net balance owed to/from directors.</li>
</ul>

<h3>Screen 3.8.4: Bidding & Tender Expenses (<span class="code-inline">BiddingExpenseLedgerPage</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounts</span> $\rightarrow$ <span class="badge badge-primary">Bidding & Tender Expenses</span></li>
    <li><strong>Purpose:</strong> Itemized tracking of pre-award tender acquisition expenses (accounts <span class="code-inline">5050–5058</span>: Tender Documents, Bid Security, Estimation, Architectural Drawings, Client Meetings).</li>
    <li><strong>Capitalization to Awarded Project:</strong> Features an action to transfer and capitalize accumulated pre-award bidding costs directly into an awarded project's direct cost accounts (<span class="code-inline">7000s</span>) upon winning a contract award.</li>
</ul>

<h3>Screen 3.8.5: Project / Phase Expense Ledger (<span class="code-inline">ProjectExpenseLedgerPage</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Projects</span> $\rightarrow$ <span class="badge badge-primary">Project Expense Ledger</span></li>
    <li><strong>Purpose:</strong> Deep-dive construction cost reporting grouped into 3 operational buckets:
        <br>1. <strong>Materials:</strong> Cement, Steel, Sand, Crush, Bricks, Electrical, Plumbing, Paint, Tiles.
        <br>2. <strong>Labour & Equipment:</strong> Daily Site Wages, Machinery Rental, Excavation, Concrete Pump, Shuttering.
        <br>3. <strong>Site Overheads:</strong> Safety Gear, Site Office, Site Utilities, Site Security, Project Transportation.
    </li>
</ul>

<h3>Screen 3.8.6: Monthly / FY Expense Summary Book (<span class="code-inline">MonthlyExpenseSummaryPage</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Monthly Expense Summary</span></li>
    <li><strong>Purpose:</strong> Executive summary replicating the 10-sheet structure of corporate expense books. Summarizes Head Office Overheads, Bidding Expenses, Project Direct Costs, Shared Costs, and Funding Sources breakdown (Cash, Bank, Petty Cash, Director Loans, Staff Payables).</li>
</ul>

<h3>Screen 3.8.7: Shared Cost Allocation (<span class="code-inline">SharedCostAllocationPage</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounts</span> $\rightarrow$ <span class="badge badge-primary">Shared Cost Allocation</span></li>
    <li><strong>Purpose:</strong> Automates the distribution of shared utility bills, corporate rent, and shared staff costs paid by one entity (e.g. YM Construction) on behalf of sister companies (7-Orbit, BMC Trading).
        <br>$\rightarrow$ <strong>Paying Company Entry:</strong> <span class="badge badge-primary">Dr</span> <span class="code-inline">Own Expense (Share)</span> + <span class="badge badge-primary">Dr</span> <span class="code-inline">1198 Due from Sister Co</span> $\rightarrow$ <span class="badge badge-warning">Cr</span> <span class="code-inline">1120 Bank / 1111 Cash</span>.
        <br>$\rightarrow$ <strong>Recipient Company Entry:</strong> <span class="badge badge-primary">Dr</span> <span class="code-inline">Expense (Share)</span> $\rightarrow$ <span class="badge badge-warning">Cr</span> <span class="code-inline">2195 Due to Paying Co</span>.
    </li>
</ul>

<h3>Screen 3.8.8: Daily Cash & Bank Position Statement (<span class="code-inline">DailyCashBankPositionPage</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Daily Cash & Bank Statement</span></li>
    <li><strong>Purpose:</strong> Executive 5-Fund Daily Position statement showing Opening Balance, Itemized Receipts, Itemized Payments, and Closing Balances across: (1) Head Office Cash, (2) Site Petty Cash, (3) Company Bank Accounts, (4) Director Funded Activities, and (5) Staff Payables. Features dedicated print stylesheet.</li>
</ul>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 3.9: OPENING BALANCES & YEAR-END CLOSING                          -->
<!-- ========================================================================= -->
<h2>3.9 Opening Balances & Year-End Closings</h2>

<h3>Screen 3.9.1: Opening Balances (<span class="code-inline">OpeningBalanceBatchResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Opening Balances</span></li>
    <li><strong>Purpose:</strong> Controlled migration and setup of historical opening Trial Balances when onboarding a company into the system.</li>
    <li><strong>Validation & Posting:</strong> Requires selecting the first open financial period. Enforces that total opening debits equal total opening credits ($\sum Dr = \sum Cr$). Posts as a single formal Opening Balance voucher (<span class="code-inline">OB-2026-000001</span>).</li>
</ul>

<h3>Screen 3.9.2: Year-End Closings (<span class="code-inline">YearEndClosingResource</span>)</h3>
<ul>
    <li><strong>Navigation:</strong> <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Year-end Closings</span></li>
    <li><strong>Purpose:</strong> Executes formal annual financial year closing and balance transfer.</li>
    <li><strong>Workflow & Calculation:</strong>
        <ol>
            <li><strong>Calculation:</strong> Sums all Revenue accounts (4000s) and all Expense & Direct Cost accounts (5000s/7000s) for the 12 periods of the fiscal year. Computes Net Profit or Net Loss.</li>
            <li><strong>Closing Journal Creation:</strong> Generates a closing voucher that clears all Revenue accounts to zero (Debit Revenue) and all Expense accounts to zero (Credit Expense), transferring the exact net difference to <span class="code-inline">3200 Retained Earnings</span>.</li>
            <li><strong>Period Sealing:</strong> Automatically transitions all 12 financial periods of the closed fiscal year to <span class="badge badge-danger">Locked</span> status, permanently preventing any subsequent postings.</li>
        </ol>
    </li>
</ul>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 4: STEP-BY-STEP SOFTWARE PROCEDURES                               -->
<!-- ========================================================================= -->
<div class="section-title">4. STEP-BY-STEP OPERATIONAL INSTRUCTIONS</div>

<p>
    This section provides step-by-step instructions for executing standard accounting workflows in the software.
</p>

<!-- PROCEDURE 1 -->
<div class="procedure-box">
    <div class="procedure-header">Procedure 4.1: Creating a New Chart of Accounts Leaf Account</div>
    <div class="procedure-body">
        <ol class="step-list">
            <li class="step-item"><span class="step-number">1</span>Ensure you are in the correct active company using the top navigation tenant switcher.</li>
            <li class="step-item"><span class="step-number">2</span>Navigate to <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Chart of Accounts</span>.</li>
            <li class="step-item"><span class="step-number">3</span>Click the <span class="badge badge-primary">+ New Account</span> button in the top right corner.</li>
            <li class="step-item"><span class="step-number">4</span>Enter the unique <strong>Account Code</strong> (e.g. <span class="code-inline">5210</span>) within the appropriate code range.</li>
            <li class="step-item"><span class="step-number">5</span>Enter the descriptive <strong>Account Name</strong> (e.g. <span class="code-inline">Site Generator Maintenance</span>).</li>
            <li class="step-item"><span class="step-number">6</span>Select the <strong>Parent Account</strong> (e.g. <span class="code-inline">5200 Fuel & Power</span> or <span class="code-inline">5000 Expenses</span>).</li>
            <li class="step-item"><span class="step-number">7</span>Select the <strong>Account Type</strong> (<span class="code-inline">Expense</span>) and <strong>Normal Balance</strong> (<span class="code-inline">Debit</span>).</li>
            <li class="step-item"><span class="step-number">8</span>Enter the <strong>Reporting Group</strong> (e.g. <span class="code-inline">Operating Overheads</span>) for consolidation mapping.</li>
            <li class="step-item"><span class="step-number">9</span>If this is a normal posting account, ensure <strong>Is Control Account</strong> is unchecked and <strong>Allows Manual Posting</strong> is toggled ON.</li>
            <li class="step-item"><span class="step-number">10</span>Click <span class="badge badge-primary">Create</span>. The account is immediately available for selection in voucher forms.</li>
        </ol>
    </div>
</div>

<!-- PROCEDURE 2 -->
<div class="procedure-box">
    <div class="procedure-header">Procedure 4.2: Preparing, Approving & Posting a Manual Journal Voucher (JV)</div>
    <div class="procedure-body">
        <ol class="step-list">
            <li class="step-item"><span class="step-number">1</span>Navigate to <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Vouchers / Journals</span> and click <span class="badge badge-primary">+ New Journal Entry</span>.</li>
            <li class="step-item"><span class="step-number">2</span>Select <strong>Voucher Type</strong> = <span class="code-inline">Journal</span> (<span class="code-inline">JV</span>) and select the current active <strong>Financial Period</strong>.</li>
            <li class="step-item"><span class="step-number">3</span>Select the <strong>Transaction Date</strong> and enter a detailed <strong>Description</strong> of the adjustment.</li>
            <li class="step-item"><span class="step-number">4</span>In the <strong>Double-entry lines</strong> repeater, configure the first line:
                <br>• Select the Debit Account (e.g. <span class="code-inline">5300 Office Rent</span>) and enter the <strong>Debit Amount</strong> (e.g. <span class="code-inline">75000</span>).
                <br>• Select any applicable dimension (e.g. <span class="code-inline">Cost Center</span>).
            </li>
            <li class="step-item"><span class="step-number">5</span>Configure the second line:
                <br>• Select the Credit Account (e.g. <span class="code-inline">1111 Head Office Cash</span>) and enter the <strong>Credit Amount</strong> (e.g. <span class="code-inline">75000</span>).
            </li>
            <li class="step-item"><span class="step-number">6</span>Click <span class="badge badge-primary">Create</span> to save the voucher in <span class="badge badge-gray">Draft</span> state.</li>
            <li class="step-item"><span class="step-number">7</span>Click the <span class="badge badge-warning">Submit</span> button in the top action bar to send the voucher for maker-checker review.</li>
            <li class="step-item"><span class="step-number">8</span>A different user with Finance Approver rights opens the submitted voucher and clicks <span class="badge badge-success">Approve</span>.</li>
            <li class="step-item"><span class="step-number">9</span>The Finance Controller clicks <span class="badge badge-primary">Post</span>. The system assigns <span class="code-inline">JV-2026-XXXXXX</span>, locks the record, and immediately updates the General Ledger.</li>
        </ol>
    </div>
</div>

<!-- PROCEDURE 3 -->
<div class="procedure-box">
    <div class="procedure-header">Procedure 4.3: Recording & Posting a Vendor Bill with 3-Way GRN Matching</div>
    <div class="procedure-body">
        <ol class="step-list">
            <li class="step-item"><span class="step-number">1</span>Prerequisites: The Purchase Order must be issued, and the Goods Receipt must be inspected and handed over to Accounts.</li>
            <li class="step-item"><span class="step-number">2</span>Navigate to <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Vendor Bills & Credit Notes</span> and click <span class="badge badge-primary">+ New Vendor Bill</span>.</li>
            <li class="step-item"><span class="step-number">3</span>Select <strong>Type</strong> = <span class="code-inline">Invoice</span> and select the issued <strong>Purchase Order</strong>.</li>
            <li class="step-item"><span class="step-number">4</span>Select the <strong>Vendor</strong> and enter the official <strong>Vendor Invoice Number</strong> from the physical bill.</li>
            <li class="step-item"><span class="step-number">5</span>In the <strong>Invoice Lines</strong> repeater, select the PO line, item, quantity, and agreed unit rate.</li>
            <li class="step-item"><span class="step-number">6</span>In the <strong>Deductions</strong> section, add any applicable Withholding Tax (WHT) or Retention deductions.</li>
            <li class="step-item"><span class="step-number">7</span>Click <span class="badge badge-primary">Create</span> to save in Draft, then click <span class="badge badge-warning">Submit</span>. FIFO GRN allocation occurs automatically.</li>
            <li class="step-item"><span class="step-number">8</span>The Accounts Reviewer clicks <span class="badge badge-primary">Review Match</span>. If quantities/rates match PO and GRN, status moves to <span class="badge badge-success">Reviewed</span>.</li>
            <li class="step-item"><span class="step-number">9</span>The Finance Manager clicks <span class="badge badge-success">Approve</span>, followed by the Controller clicking <span class="badge badge-primary">Post</span>.</li>
            <li class="step-item"><span class="step-number">10</span>The system posts the bill as <span class="code-inline">PUR-2026-XXXXXX</span>, clears GRNI (<span class="code-inline">2193</span>), and records Accounts Payable (<span class="code-inline">2110</span>).</li>
        </ol>
    </div>
</div>

<!-- PROCEDURE 4 -->
<div class="procedure-box">
    <div class="procedure-header">Procedure 4.4: Creating a Construction Running Bill (IPC / Progress Claim)</div>
    <div class="procedure-body">
        <ol class="step-list">
            <li class="step-item"><span class="step-number">1</span>Navigate to <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Customer Invoices & Credit Notes</span> $\rightarrow$ <span class="badge badge-primary">+ New Invoice</span>.</li>
            <li class="step-item"><span class="step-number">2</span>Select <strong>Type</strong> = <span class="code-inline">Invoice</span> and <strong>Category</strong> = <span class="code-inline">Running Bill</span>.</li>
            <li class="step-item"><span class="step-number">3</span>Select the <strong>Customer</strong> and the active construction <strong>Project</strong>.</li>
            <li class="step-item"><span class="step-number">4</span>Enter the official Consultant <strong>Certificate Number</strong>, <strong>Certificate Date</strong>, and certified <strong>Work Value</strong>.</li>
            <li class="step-item"><span class="step-number">5</span>In the <strong>Revenue Lines</strong> repeater, select <span class="code-inline">4100 Construction Revenue</span> and enter the gross work amount.</li>
            <li class="step-item"><span class="step-number">6</span>In the <strong>Running-bill deductions</strong> repeater, add:
                <br>• Contract Retention (e.g. 5% or 10% of certified work value).
                <br>• Mobilization Advance Recovery (if advance was received).
                <br>• Withholding Tax (WHT) deduction.
            </li>
            <li class="step-item"><span class="step-number">7</span>Click <span class="badge badge-primary">Create</span>, then click <span class="badge badge-warning">Submit</span>.</li>
            <li class="step-item"><span class="step-number">8</span>After independent approval, click <span class="badge badge-primary">Post</span>. The invoice receives number <span class="code-inline">RB-2026-XXXXXX</span> and updates AR and Revenue.</li>
        </ol>
    </div>
</div>

<!-- PROCEDURE 5 -->
<div class="procedure-box">
    <div class="procedure-header">Procedure 4.5: Executing a Treasury Outbound Vendor Bill Settlement</div>
    <div class="procedure-body">
        <ol class="step-list">
            <li class="step-item"><span class="step-number">1</span>Navigate to <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Payments, Receipts & Transfers</span> $\rightarrow$ <span class="badge badge-primary">+ New Transaction</span>.</li>
            <li class="step-item"><span class="step-number">2</span>Select <strong>Type</strong> = <span class="code-inline">Payment</span>, <strong>Purpose</strong> = <span class="code-inline">Settlement</span>, and <strong>Counterparty Type</strong> = <span class="code-inline">Party</span>.</li>
            <li class="step-item"><span class="step-number">3</span>Select the <strong>Party</strong> (Vendor) and enter the total payment <strong>Amount</strong>.</li>
            <li class="step-item"><span class="step-number">4</span>Select the <strong>Source Account</strong> (e.g. <span class="code-inline">1120 Bank</span>) and select the specific <strong>Company Bank Account</strong>.</li>
            <li class="step-item"><span class="step-number">5</span>Enter the Cheque / Instrument Number, Date, and Narration.</li>
            <li class="step-item"><span class="step-number">6</span>In the <strong>Open-item allocations</strong> section, select the posted Vendor Bill to settle and enter the allocated amount.</li>
            <li class="step-item"><span class="step-number">7</span>Click <span class="badge badge-primary">Create</span>, click <span class="badge badge-warning">Submit</span>, obtain Approval, and click <span class="badge badge-success">Post</span>.</li>
            <li class="step-item"><span class="step-number">8</span>The voucher posts as <span class="code-inline">PV-2026-XXXXXX</span>, debits Accounts Payable, credits Bank, and reduces the vendor bill's open balance.</li>
        </ol>
    </div>
</div>

<!-- PROCEDURE 6 -->
<div class="procedure-box">
    <div class="procedure-header">Procedure 4.6: Performing Bank Statement Import & Reconciliation</div>
    <div class="procedure-body">
        <ol class="step-list">
            <li class="step-item"><span class="step-number">1</span>Navigate to <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Bank Statements</span> $\rightarrow$ <span class="badge badge-primary">+ Import Statement</span>.</li>
            <li class="step-item"><span class="step-number">2</span>Select the <strong>Company Bank Account</strong>, specify period dates, and upload the normalized bank CSV file.</li>
            <li class="step-item"><span class="step-number">3</span>Click <span class="badge badge-primary">Upload & Parse</span>. Verify that statement lines load with zero parsing errors.</li>
            <li class="step-item"><span class="step-number">4</span>Navigate to <span class="badge badge-dark">Accounting</span> $\rightarrow$ <span class="badge badge-primary">Bank Reconciliation</span> $\rightarrow$ <span class="badge badge-primary">+ New Reconciliation</span>.</li>
            <li class="step-item"><span class="step-number">5</span>Select the Bank Account and the imported Bank Statement, then click <span class="badge badge-primary">Create</span>.</li>
            <li class="step-item"><span class="step-number">6</span>On the view screen, click <span class="badge badge-primary">Match Activity</span> to pair statement lines with corresponding posted GL bank vouchers.</li>
            <li class="step-item"><span class="step-number">7</span>For bank charges or interest appearing only on the bank statement, click <span class="badge badge-warning">Post Adjustment</span> to generate the required GL entry.</li>
            <li class="step-item"><span class="step-number">8</span>Once the statement balance exactly equals the adjusted GL balance ($\text{Difference} = 0.00$), click <span class="badge badge-success">Close</span>.</li>
        </ol>
    </div>
</div>

<!-- PROCEDURE 7 -->
<div class="procedure-box">
    <div class="procedure-header">Procedure 4.7: Managing Petty Cash Floats & Physical Cash Count</div>
    <div class="procedure-body">
        <ol class="step-list">
            <li class="step-item"><span class="step-number">1</span>Navigate to <span class="badge badge-dark">Accounts</span> $\rightarrow$ <span class="badge badge-primary">Petty Cash Register</span>.</li>
            <li class="step-item"><span class="step-number">2</span>To disburse daily expenses: Click <span class="badge badge-danger">Record Petty Expense</span>, select the Expense Head, enter amount, optional Project, and narration. Click Save.</li>
            <li class="step-item"><span class="step-number">3</span>To replenish float: Click <span class="badge badge-success">Top-up Float</span>, select Source (<span class="code-inline">Director Advance</span> or <span class="code-inline">Bank Transfer</span>), enter amount, and click Save.</li>
            <li class="step-item"><span class="step-number">4</span>At month-end / weekly close: Click <span class="badge badge-warning">Physical Reconciliation</span>.</li>
            <li class="step-item"><span class="step-number">5</span>Count physical notes/coins in the cash box and enter into <strong>Physical Cash Counted</strong>.</li>
            <li class="step-item"><span class="step-number">6</span>Enter any temporary IOUs/receipts held into <strong>On-Account Cash Held by Staff</strong>.</li>
            <li class="step-item"><span class="step-number">7</span>Click Save. If variance is zero, the register confirms exact reconciliation; if a shortage/surplus exists, it logs the variance for investigation.</li>
        </ol>
    </div>
</div>

<!-- PROCEDURE 8 -->
<div class="procedure-box">
    <div class="procedure-header">Procedure 4.8: Reversing an Erroneously Posted Voucher</div>
    <div class="procedure-body">
        <ol class="step-list">
            <li class="step-item"><span class="step-number">1</span>Open the posted document in its respective module (e.g. <span class="badge badge-primary">Vouchers / Journals</span>, <span class="badge badge-primary">Vendor Bills</span>, <span class="badge badge-primary">Customer Invoices</span>).</li>
            <li class="step-item"><span class="step-number">2</span>Confirm the document status is <span class="badge badge-primary">Posted</span>.</li>
            <li class="step-item"><span class="step-number">3</span>Click the red <span class="badge badge-danger">Reverse</span> button in the header action bar.</li>
            <li class="step-item"><span class="step-number">4</span>In the confirmation modal, select the <strong>Reversal Date</strong> (must be within an active open financial period).</li>
            <li class="step-item"><span class="step-number">5</span>Enter a mandatory, comprehensive <strong>Reversal Reason</strong> explaining why the reversal is necessary.</li>
            <li class="step-item"><span class="step-number">6</span>Click <span class="badge badge-danger">Confirm Reversal</span>.</li>
            <li class="step-item"><span class="step-number">7</span>The system creates a linked reversal entry (<span class="code-inline">REV-2026-XXXXXX</span>) swapping all debits and credits, changes the original status to <span class="badge badge-danger">Reversed</span>, releases any consumed GRN or invoice allocations, and updates ledger balances.</li>
        </ol>
    </div>
</div>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 5: END-TO-END WORKFLOWS & ACCOUNTING IMPACT                       -->
<!-- ========================================================================= -->
<div class="section-title">5. END-TO-END WORKFLOWS & DOUBLE-ENTRY IMPACTS</div>

<p>
    This section traces the complete accounting lifecycle across all operational modules with exact debit/credit mechanics.
</p>

<h2>5.1 Procurement to Payment (P2P) Accounting Trail</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Event / Step</th>
            <th>Triggering Software Action</th>
            <th>Debit Account</th>
            <th>Credit Account</th>
            <th>Balance Sheet / P&L Effect</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>1. PO Issuance</strong></td>
            <td><span class="code-inline">IssuePurchaseOrderAction</span></td>
            <td colspan="2" style="text-align: center; color: #64748b; font-style: italic;">No General Ledger Impact (Operational commitment only)</td>
            <td>Reserves PR quantity. Budget demand recorded.</td>
        </tr>
        <tr>
            <td><strong>2. GRN QA Handover</strong></td>
            <td><span class="code-inline">HandoverGoodsReceiptToAccountsAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">1196 Site Inventory</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">2193 GRNI Accrual</span></td>
            <td>Increases Current Assets (Stock) & Current Liabilities (GRNI).</td>
        </tr>
        <tr>
            <td><strong>3. Vendor Bill Posting</strong></td>
            <td><span class="code-inline">PostVendorBillAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">2193 GRNI Accrual</span><br><span class="badge badge-primary">Dr</span> <span class="code-inline">1190 Input GST</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">2110 Accounts Payable</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">2150 WHT Payable</span></td>
            <td>Clears GRNI liability, records tax asset & net vendor liability.</td>
        </tr>
        <tr>
            <td><strong>4. Vendor Settlement</strong></td>
            <td><span class="code-inline">PostTreasuryTransactionAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">2110 Accounts Payable</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">1120 Bank Account</span></td>
            <td>Decreases Current Liabilities (AP) & Current Assets (Bank).</td>
        </tr>
    </tbody>
</table>

<h2>5.2 Construction Project Costing & Material Issues</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Operational Event</th>
            <th>Software Action</th>
            <th>Debit Account</th>
            <th>Credit Account</th>
            <th>Accounting Result</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Site Material Issue</strong></td>
            <td><span class="code-inline">PostInventoryTransactionAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">7100s Direct Cost</span> (e.g. 7100 Cement)</td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">1196 Site Inventory</span></td>
            <td>Transfers material asset into active direct construction expense on Job Site.</td>
        </tr>
        <tr>
            <td><strong>Direct Site Expense</strong></td>
            <td><span class="code-inline">RecordQuickExpenseAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">7190 Direct Site Labour</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">1112 Site Petty Cash</span></td>
            <td>Records labour expense, consumes site cash float.</td>
        </tr>
        <tr>
            <td><strong>Machinery Rental Accrual</strong></td>
            <td><span class="code-inline">PostVendorBillAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">7200 Machinery Rental</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">2180 Rental Payable</span></td>
            <td>Charges equipment cost to project, establishes rental liability.</td>
        </tr>
    </tbody>
</table>

<h2>5.3 Order to Cash (O2C) & Progress Billing Trail</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Event / Step</th>
            <th>Triggering Software Action</th>
            <th>Debit Account</th>
            <th>Credit Account</th>
            <th>Financial Statement Impact</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>1. Client Advance Received</strong></td>
            <td><span class="code-inline">PostTreasuryTransactionAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">1120 Bank Account</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">2192 Customer Advance</span></td>
            <td>Increases Bank Asset & Unearned Mobilization Liability.</td>
        </tr>
        <tr>
            <td><strong>2. Running Bill Certified</strong></td>
            <td><span class="code-inline">PostCustomerInvoiceAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">1130 Accounts Rec</span> (Net)<br><span class="badge badge-primary">Dr</span> <span class="code-inline">1180 Retention Rec</span><br><span class="badge badge-primary">Dr</span> <span class="code-inline">1185 WHT Rec</span><br><span class="badge badge-primary">Dr</span> <span class="code-inline">2192 Customer Adv</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">4100 Construction Rev</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">2160 Output GST Payable</span></td>
            <td>Recognizes certified Revenue in P&L, records net AR, retention asset, WHT asset, and reduces customer advance liability.</td>
        </tr>
        <tr>
            <td><strong>3. Client Payment Receipt</strong></td>
            <td><span class="code-inline">PostTreasuryTransactionAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">1120 Bank Account</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">1130 Accounts Receivable</span></td>
            <td>Settles open invoice, increases cash in bank.</td>
        </tr>
    </tbody>
</table>

<h2>5.4 Payroll Accrual & Disbursal Trail</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Event</th>
            <th>Action</th>
            <th>Debit Account</th>
            <th>Credit Account</th>
            <th>Impact</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>1. Post Approved Payroll</strong></td>
            <td><span class="code-inline">PostPayrollRunAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">5100 Head Office Salaries</span><br><span class="badge badge-primary">Dr</span> <span class="code-inline">7190 Direct Labour (Site)</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">2140 Salary Payable</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">1140 Employee Advances</span><br><span class="badge badge-warning">Cr</span> <span class="code-inline">2150 WHT Payable</span></td>
            <td>Accrues gross salary expense across projects & admin, recovers employee loan installments, records net salary liability.</td>
        </tr>
        <tr>
            <td><strong>2. Disburse Salary</strong></td>
            <td><span class="code-inline">PostTreasuryTransactionAction</span></td>
            <td><span class="badge badge-primary">Dr</span> <span class="code-inline">2140 Salary Payable</span></td>
            <td><span class="badge badge-warning">Cr</span> <span class="code-inline">1120 Bank / 1111 Cash</span></td>
            <td>Clears salary liability via employee bank transfer or cash.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 6: FINANCIAL REPORTS & VERIFICATION CATALOG                       -->
<!-- ========================================================================= -->
<div class="section-title">6. FINANCIAL REPORTS & VERIFICATION CATALOG</div>

<p>
    YM-CMS features a suite of real-time financial reporting pages. Every report derives solely from posted General Ledger entries.
</p>

<h2>6.1 Core Financial Statements</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 20%;">Report Name</th>
            <th style="width: 25%;">Navigation Path</th>
            <th style="width: 20%;">Key Filters</th>
            <th style="width: 35%;">Auditing & Verification Purpose</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Trial Balance</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Financial Statements</span> $\rightarrow$ Trial Balance Tab</td>
            <td>Financial Year, Period Range, Cost Center.</td>
            <td>Displays Opening Balance, Period Debits, Period Credits, and Net Closing Balance for every active account. Proves $\sum \text{Debits} = \sum \text{Credits}$.</td>
        </tr>
        <tr>
            <td><strong>General Ledger</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Financial Statements</span> $\rightarrow$ General Ledger Tab</td>
            <td>Account ID, Date Range, Party, Project.</td>
            <td>Chronological transaction history of any selected account with running balances, voucher numbers, and narration memos.</td>
        </tr>
        <tr>
            <td><strong>Profit & Loss (P&L)</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Financial Statements</span> $\rightarrow$ Profit & Loss Tab</td>
            <td>Financial Year, Comparative Periods.</td>
            <td>Calculates Gross Profit ($\text{Revenue} - \text{Direct Project Costs}$) and Net Operating Profit ($\text{Gross Profit} - \text{Operating Overheads}$).</td>
        </tr>
        <tr>
            <td><strong>Balance Sheet</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Financial Statements</span> $\rightarrow$ Balance Sheet Tab</td>
            <td>As of Date / Fiscal Period.</td>
            <td>Presents financial position: Total Assets = Total Liabilities + Total Equity (including Current Year Net Profit/Loss).</td>
        </tr>
    </tbody>
</table>

<h2>6.2 Subledger Aging & Reconciliation Reports</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Report Name</th>
            <th>Navigation Path</th>
            <th>Columns / Metrics</th>
            <th>Operational Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Accounts Payable Aging</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Accounts Payable</span> $\rightarrow$ AP Aging</td>
            <td>Vendor Name, Total Outstanding, Current (Not Due), 1–30 Days, 31–60 Days, 61–90 Days, 90+ Days.</td>
            <td>Assists Treasury in scheduling supplier payments and managing cash flow obligations.</td>
        </tr>
        <tr>
            <td><strong>Vendor Ledger</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Accounts Payable</span> $\rightarrow$ Vendor Ledger</td>
            <td>Vendor Filter, Date Range, Bills, Deductions, Payments, Running Balance.</td>
            <td>Official statement sent to suppliers for monthly balance confirmation.</td>
        </tr>
        <tr>
            <td><strong>Accounts Receivable Aging</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Sales & Project Profitability</span> $\rightarrow$ AR Aging</td>
            <td>Customer Name, Total Billed, Current, Overdue Brackets (1–30, 31–60, 61–90, 90+).</td>
            <td>Credit control tool for following up on overdue client running bills.</td>
        </tr>
        <tr>
            <td><strong>Customer Ledger</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Sales & Project Profitability</span> $\rightarrow$ Customer Ledger</td>
            <td>Customer Filter, Date Range, Invoices, Retentions, Receipts, Net Balance.</td>
            <td>Client statement showing progress billing certifications vs payments.</td>
        </tr>
        <tr>
            <td><strong>Project Profitability</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Sales & Project Profitability</span> $\rightarrow$ Project Profitability</td>
            <td>Project Name, Contract Value, Billed Revenue, Material Cost, Labour Cost, Equipment, Net Margin %.</td>
            <td>Evaluates individual construction job profitability and cost performance.</td>
        </tr>
    </tbody>
</table>

<h2>6.3 Treasury, Bank Book & Executive Statements</h2>
<table class="data-table compact-table">
    <thead>
        <tr>
            <th>Report Name</th>
            <th>Navigation Path</th>
            <th>Layout & Features</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Daily Cash & Bank Position</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Daily Cash & Bank Statement</span></td>
            <td>Executive 5-fund statement: Head Office Cash, Site Petty Cash, Company Bank Accounts, Director Funds, Staff Claims. Displays Opening, Inflows, Outflows, and Closing Funds with Print stylesheet.</td>
        </tr>
        <tr>
            <td><strong>Bank Book Report</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Treasury & Banking</span> $\rightarrow$ Bank Book</td>
            <td>Chronological bank ledger by account showing cheque numbers, clearance dates, and running balances.</td>
        </tr>
        <tr>
            <td><strong>Petty Cash Register</strong></td>
            <td><span class="badge badge-dark">Accounts</span> $\rightarrow$ <span class="badge badge-primary">Petty Cash Register</span></td>
            <td>Imprest cash ledger with filterable date range, top-up tracking, disbursement analysis, and reconciliation logs.</td>
        </tr>
        <tr>
            <td><strong>Monthly Expense Summary Book</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Monthly Expense Summary</span></td>
            <td>10-sheet corporate roll-up: Admin Expenses, Bidding Expenses, Project Direct Costs, Shared Costs, and Funding Breakdown.</td>
        </tr>
        <tr>
            <td><strong>Consolidated Financial Reports</strong></td>
            <td><span class="badge badge-dark">Reports</span> $\rightarrow$ <span class="badge badge-primary">Group Consolidation</span></td>
            <td>Super-Admin multi-company rollup: Aggregates Trial Balance, Balance Sheet, and P&L across all authorized sister entities with Inter-Company eliminations.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 7: PRACTICAL BUSINESS SCENARIOS                                   -->
<!-- ========================================================================= -->
<div class="section-title">7. PRACTICAL BUSINESS SCENARIOS & WALKTHROUGHS</div>

<p>
    This section walks through realistic business scenarios with exact figures, click sequences, and accounting impacts.
</p>

<!-- SCENARIO 1 -->
<div class="procedure-box">
    <div class="procedure-header">Scenario 7.1: Procurement of 500 Bags of Cement for Construction Project C-21</div>
    <div class="procedure-body">
        <p><strong>Business Context:</strong> YM Construction issues a PO for 500 bags of Bestway Cement @ PKR 1,400/bag (Total PKR 700,000) for Project C-21. The site receives and accepts 480 bags (20 bags damaged and rejected). The vendor submits invoice for 480 bags (PKR 672,000) with 2% WHT. Accounts approves the bill and pays via Bank Alfalah cheque.</p>
        <p><strong>Step-by-Step Software Execution:</strong></p>
        <ol>
            <li><strong>Receive Goods:</strong> Storekeeper goes to <span class="badge badge-primary">Goods Receipts</span> $\rightarrow$ Creates GRN against PO. Receives 500 bags.</li>
            <li><strong>Inspect Goods:</strong> Site Engineer inspects: Enters 480 Accepted, 20 Rejected (Reason: "Torn bags & moisture hardened"). Clicks <span class="badge badge-success">Inspect</span>.</li>
            <li><strong>Accounts Handover:</strong> Store Supervisor clicks <span class="badge badge-primary">Handover to Accounts</span>.
                <br>$\rightarrow$ <em>Journal Generated:</em> <span class="badge badge-primary">Dr</span> <span class="code-inline">1196 Site Inventory</span> PKR 672,000  |  <span class="badge badge-warning">Cr</span> <span class="code-inline">2193 GRNI Accrual</span> PKR 672,000.
            </li>
            <li><strong>Vendor Bill Entry:</strong> AP Accountant goes to <span class="badge badge-primary">Vendor Bills</span> $\rightarrow$ Selects PO. Enters Vendor Inv # <span class="code-inline">BW-98421</span>. Line captures 480 bags @ PKR 1,400 = PKR 672,000. Under Deductions, adds 2% WHT = PKR 13,440. Net Payable = PKR 658,560. Clicks Submit.</li>
            <li><strong>Match Review & Post:</strong> Reviewer performs 3-Way Match (Passes). Approver approves. Controller posts.
                <br>$\rightarrow$ <em>Journal Posted (<span class="code-inline">PUR-2026-000142</span>):</em>
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-primary">Dr</span> <span class="code-inline">2193 GRNI Accrual</span> PKR 672,000
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-warning">Cr</span> <span class="code-inline">2110 Accounts Payable (Bestway)</span> PKR 658,560
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-warning">Cr</span> <span class="code-inline">2150 WHT Payable</span> PKR 13,440
            </li>
            <li><strong>Payment:</strong> Treasury Officer creates Payment Voucher in <span class="badge badge-primary">Payments, Receipts & Transfers</span>:
                <br>• Amount: PKR 658,560. Source: Bank Alfalah (1120).
                <br>• Allocation: Allocates PKR 658,560 against Bill <span class="code-inline">PUR-2026-000142</span>.
                <br>$\rightarrow$ <em>Journal Posted (<span class="code-inline">PV-2026-000088</span>):</em> <span class="badge badge-primary">Dr</span> <span class="code-inline">2110 Accounts Payable</span> PKR 658,560  |  <span class="badge badge-warning">Cr</span> <span class="code-inline">1120 Bank Alfalah</span> PKR 658,560.
            </li>
        </ol>
        <p><strong>Verification:</strong> Vendor Bill status is <span class="badge badge-success">Paid</span>. Vendor Ledger balance is PKR 0.00. Site Inventory shows 480 bags in stock.</p>
    </div>
</div>

<!-- SCENARIO 2 -->
<div class="procedure-box">
    <div class="procedure-header">Scenario 7.2: Issuing 300 Bags of Cement to Project C-21 Foundation Casting</div>
    <div class="procedure-body">
        <p><strong>Business Context:</strong> Site supervisor requisitions 300 bags of cement from site store for pouring foundation raft on Project C-21.</p>
        <ol>
            <li>Navigate to <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Inventory Transfers & Issues</span> $\rightarrow$ <span class="badge badge-primary">+ New Transaction</span>.</li>
            <li>Select <strong>Type</strong> = <span class="code-inline">Project Issue</span> and select <strong>Project</strong> = <span class="code-inline">C-21 Commercial Tower</span>.</li>
            <li>In Lines: Select Item = <span class="code-inline">Bestway Cement</span>, Quantity = <span class="code-inline">300</span>, Posting Account = <span class="code-inline">7100 Cement Cost</span>.</li>
            <li>Click Submit and Post. The system values the issue at the current moving average cost ($300 \times \text{PKR } 1,400 = \text{PKR } 420,000$).</li>
            <li><em>Journal Posted (<span class="code-inline">IA-2026-000034</span>):</em>
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-primary">Dr</span> <span class="code-inline">7100 Cement (Direct Project Cost) [Project: C-21]</span> PKR 420,000
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-warning">Cr</span> <span class="code-inline">1196 Site Inventory [Store: C-21 Site Store]</span> PKR 420,000
            </li>
        </ol>
        <p><strong>Verification:</strong> Project Expense Ledger for C-21 reflects PKR 420,000 under Materials. Store stock balance reduces to 180 bags.</p>
    </div>
</div>

<!-- SCENARIO 3 -->
<div class="procedure-box">
    <div class="procedure-header">Scenario 7.3: Submitting Running Bill #4 for Commercial Plaza Project</div>
    <div class="procedure-body">
        <p><strong>Business Context:</strong> YM Construction submits certified Running Bill #4 for Project C-21: Certified Work Value = PKR 2,500,000. Contract terms: 5% Retention (PKR 125,000), 10% Mobilization Recovery (PKR 250,000), 7.5% WHT (PKR 187,500). Net Receivable from Client = PKR 1,937,500.</p>
        <ol>
            <li>Navigate to <span class="badge badge-dark">Transactions</span> $\rightarrow$ <span class="badge badge-primary">Customer Invoices & Credit Notes</span> $\rightarrow$ <span class="badge badge-primary">+ New Invoice</span>.</li>
            <li>Select <strong>Type</strong> = <span class="code-inline">Invoice</span>, <strong>Category</strong> = <span class="code-inline">Running Bill</span>, Customer = <span class="code-inline">Al-Falah Properties</span>, Project = <span class="code-inline">C-21 Commercial Tower</span>.</li>
            <li>Enter Certificate # <span class="code-inline">IPC-004</span>, Work Value = <span class="code-inline">2500000</span>.</li>
            <li>Revenue Line: <span class="code-inline">4100 Construction Revenue</span> = PKR 2,500,000.</li>
            <li>Deductions:
                <br>• Retention (5%): PKR 125,000.
                <br>• Customer Advance Recovery (10%): PKR 250,000.
                <br>• WHT Receivable (7.5%): PKR 187,500.
            </li>
            <li>Submit, Approve, and Post.
                <br>$\rightarrow$ <em>Journal Posted (<span class="code-inline">RB-2026-000019</span>):</em>
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-primary">Dr</span> <span class="code-inline">1130 Accounts Receivable (Al-Falah)</span> PKR 1,937,500
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-primary">Dr</span> <span class="code-inline">1180 Retention Receivable [C-21]</span> PKR 125,000
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-primary">Dr</span> <span class="code-inline">1185 WHT Receivable</span> PKR 187,500
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-primary">Dr</span> <span class="code-inline">2192 Customer Mobilization Advance</span> PKR 250,000
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-warning">Cr</span> <span class="code-inline">4100 Construction Revenue [C-21]</span> PKR 2,500,000
            </li>
        </ol>
        <p><strong>Verification:</strong> Profit & Loss Statement displays PKR 2.5M revenue. AR Aging schedules PKR 1.937M under Current bracket. Mobilization liability reduces by PKR 250K.</p>
    </div>
</div>

<!-- SCENARIO 4 -->
<div class="procedure-box">
    <div class="procedure-header">Scenario 7.4: Director Pays PKR 300,000 Office Rent from Personal Bank Account</div>
    <div class="procedure-body">
        <p><strong>Business Context:</strong> Director personally transfers PKR 300,000 from personal account to pay 3 months head office rent.</p>
        <ol>
            <li>Navigate to <span class="badge badge-dark">Accounts</span> $\rightarrow$ <span class="badge badge-primary">Quick Expense Entry</span>.</li>
            <li>Select Date = Today, Category = <span class="code-inline">Office Rent</span>, Amount = <span class="code-inline">300000</span>.</li>
            <li>Select <strong>Paid Via / Funded By</strong> = <span class="code-inline">Director Advance / Funded</span>. Enter Narration = <span class="code-inline">3 months HO rent paid by Director</span>.</li>
            <li>Click Submit. The system automatically creates, balances, and submits the voucher:
                <br>$\rightarrow$ <em>Journal Posted (<span class="code-inline">JV-2026-000211</span>):</em>
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-primary">Dr</span> <span class="code-inline">5300 Office Rent</span> PKR 300,000
                <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="badge badge-warning">Cr</span> <span class="code-inline">2220 Director Loans / Financing</span> PKR 300,000
            </li>
        </ol>
        <p><strong>Verification:</strong> Office Rent expense is recognized immediately in P&L. Director Expense Ledger reflects PKR 300,000 credit balance owed to the director.</p>
    </div>
</div>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 8: RULES, POLICIES & SEGREGATION OF DUTIES                        -->
<!-- ========================================================================= -->
<div class="section-title">8. BUSINESS RULES, ACCOUNTING POLICIES & CONTROL MATRIX</div>

<p>
    The software strictly enforces the following accounting and operational rules. Violations will trigger validation exceptions and block transaction posting.
</p>

<h2>8.1 Multi-Company Tenancy & Boundary Rules</h2>
<ul>
    <li><strong>Tenant Isolation:</strong> No database query can span multiple companies unless executing within the authorized Super-Admin Consolidation Service.</li>
    <li><strong>Cross-Company Foreign Keys:</strong> A journal entry in YM Construction cannot link to a bank account, project, or party owned by BMC Construction. Such payloads are rejected by model validation policies.</li>
    <li><strong>Inter-Company Accounting:</strong> Inter-company fund movements or cost allocations must execute through paired bilateral vouchers utilizing accounts <span class="code-inline">1198 Due from Related Companies</span> and <span class="code-inline">2195 Due to Related Companies</span>.</li>
</ul>

<h2>8.2 Maker-Checker Segregation of Duties Matrix</h2>
<table class="data-table">
    <thead>
        <tr>
            <th>Functional Operation</th>
            <th>Preparer (Maker)</th>
            <th>Reviewer / Inspector</th>
            <th>Approver (Checker)</th>
            <th>Posting Authority</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Manual Journal Vouchers</strong></td>
            <td>Accounts Officer</td>
            <td>Senior Accountant</td>
            <td>Finance Manager (Must $\neq$ Maker)</td>
            <td>Financial Controller</td>
        </tr>
        <tr>
            <td><strong>Material Receiving & Stock</strong></td>
            <td>Store Receiver</td>
            <td>Site QA Engineer</td>
            <td>Store Supervisor</td>
            <td>Accounts Officer (Handover)</td>
        </tr>
        <tr>
            <td><strong>Vendor Bills (AP)</strong></td>
            <td>Procurement Officer</td>
            <td>AP Reviewer (3-Way Match)</td>
            <td>Finance Manager (Must $\neq$ Maker)</td>
            <td>Financial Controller</td>
        </tr>
        <tr>
            <td><strong>Customer Invoices (AR)</strong></td>
            <td>Billing Engineer</td>
            <td>Project Manager</td>
            <td>Finance Manager (Must $\neq$ Maker)</td>
            <td>Financial Controller</td>
        </tr>
        <tr>
            <td><strong>Treasury Payments</strong></td>
            <td>Treasury Officer</td>
            <td>Internal Auditor</td>
            <td>CFO / Director</td>
            <td>Bank Signatory / Cashier</td>
        </tr>
        <tr>
            <td><strong>Bank Reconciliation</strong></td>
            <td>Treasury Accountant</td>
            <td>Senior Accountant</td>
            <td>Finance Manager</td>
            <td>Financial Controller</td>
        </tr>
        <tr>
            <td><strong>Fixed Asset Depreciation</strong></td>
            <td>Asset Officer</td>
            <td>Senior Accountant</td>
            <td>Finance Manager</td>
            <td>Financial Controller</td>
        </tr>
        <tr>
            <td><strong>Fiscal Year-End Close</strong></td>
            <td>Head of Accounts</td>
            <td>External Auditor</td>
            <td>Chief Financial Officer</td>
            <td>Board of Directors</td>
        </tr>
    </tbody>
</table>

<h2>8.3 Ledger Posting & Validation Rules</h2>
<ul>
    <li><strong>Debit / Credit Equality:</strong> Total debit lines must mathematically equal total credit lines ($\sum Dr = \sum Cr$) at 4 decimal places. Off-balance vouchers cannot be submitted.</li>
    <li><strong>Active Leaf Node Rule:</strong> Postings can only occur on leaf accounts with no child accounts. Parent and summary accounts reject postings.</li>
    <li><strong>Control Account Rule:</strong> Accounts marked <span class="code-inline">is_control_account = true</span> reject manual journal voucher entry. They can only be posted via dedicated subledger workflows.</li>
    <li><strong>Open Financial Period Rule:</strong> Transactions must fall within an active <span class="badge badge-success">Open</span> financial period. Closed and Locked periods reject postings.</li>
    <li><strong>Zero Negative Inventory:</strong> Material issues or trading sales that would cause on-hand site inventory to fall below zero (<span class="code-inline">quantity < 0</span>) are blocked by database row locks.</li>
    <li><strong>No Statutory Rate Hardcoding:</strong> Sales tax and withholding tax rates must use active, effective-dated <span class="code-inline">TaxCode</span> records. No rate is hardcoded in the source code.</li>
</ul>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 9: TROUBLESHOOTING GUIDE                                          -->
<!-- ========================================================================= -->
<div class="section-title">9. COMPREHENSIVE DIAGNOSTIC & TROUBLESHOOTING GUIDE</div>

<p>
    Use this matrix to diagnose, resolve, and verify common accounting and operational issues encountered during daily system usage.
</p>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 20%;">Symptom / Error</th>
            <th style="width: 25%;">Underlying Root Cause</th>
            <th style="width: 35%;">Resolution Procedure</th>
            <th style="width: 20%;">Verification Step</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>"Cannot post journal: Period is closed or locked"</strong></td>
            <td>The transaction date falls within a monthly period whose status is <span class="badge badge-gray">Closed</span> or <span class="badge badge-danger">Locked</span>.</td>
            <td>1. Check transaction date.<br>2. If date is correct, an authorized manager must navigate to <span class="badge badge-primary">Financial Periods</span> and click <span class="badge badge-warning">Reopen Period</span> with a documented reason.</td>
            <td>Period status displays <span class="badge badge-success">Open</span>; retry posting.</td>
        </tr>
        <tr>
            <td><strong>"Maker cannot approve/post their own transaction"</strong></td>
            <td>Segregation of duties violation: The user attempting to approve or post created or submitted the document.</td>
            <td>A different user with appropriate checker/approver role credentials must log in and execute the approval action.</td>
            <td>Approval timestamp records the independent user ID.</td>
        </tr>
        <tr>
            <td><strong>"Total debits must equal total credits"</strong></td>
            <td>The sum of debit line amounts does not match the sum of credit line amounts.</td>
            <td>Review all voucher lines. Recalculate debits vs credits. Adjust line amounts or add balancing discount/tax line.</td>
            <td>Voucher header reflects $\Delta = 0.00$; Submit button enables.</td>
        </tr>
        <tr>
            <td><strong>"Account does not allow manual posting"</strong></td>
            <td>The selected account is a Control Account (e.g. 1130 AR, 2110 AP, 1196 Inventory) or a parent group account.</td>
            <td>1. Use the appropriate operational screen (e.g. Vendor Bill for AP, Customer Invoice for AR).<br>2. Or select an allowed leaf expense/liability account.</td>
            <td>Form saves without validation error.</td>
        </tr>
        <tr>
            <td><strong>"Insufficient stock on hand (Negative inventory prohibited)"</strong></td>
            <td>Attempted material issue or trading sale quantity exceeds physical stock available in the selected site store.</td>
            <td>1. Verify site store selection.<br>2. Check if pending Goods Receipts need QA inspection and Accounts Handover.<br>3. Or post an Inter-Site Transfer from Central Store.</td>
            <td>Site Inventory Balances report reflects sufficient on-hand quantity.</td>
        </tr>
        <tr>
            <td><strong>"3-Way Match Failed: Price or Quantity Variance"</strong></td>
            <td>Vendor Bill unit rate or quantity exceeds issued Purchase Order or accepted GRN quantity.</td>
            <td>1. Verify entered bill figures against physical delivery challan.<br>2. If legitimate, user with <span class="code-inline">Override AP Match</span> permission must check <span class="code-inline">override_mismatch</span> and provide reason.</td>
            <td>Bill advances to <span class="badge badge-success">Reviewed</span> status.</td>
        </tr>
        <tr>
            <td><strong>"Cannot close Bank Reconciliation: Difference > 0.00"</strong></td>
            <td>Bank statement closing balance does not equal adjusted General Ledger bank balance due to unmatched items.</td>
            <td>1. Ensure all statement lines are matched to GL entries.<br>2. Post adjustments for bank charges/tax via <span class="badge badge-warning">Post Adjustment</span>.<br>3. Verify no duplicate GL vouchers exist.</td>
            <td>Reconciliation header displays $\text{Variance} = 0.00$; Close button activates.</td>
        </tr>
        <tr>
            <td><strong>"Petty Cash Physical Count Mismatch"</strong></td>
            <td>Physical cash in box does not match system running balance.</td>
            <td>1. Check for unrecorded cash receipts/disbursements.<br>2. Verify on-account cash held by staff.<br>3. If unexplained, record physical count with notes; system logs variance audit.</td>
            <td>Petty Cash Reconciliation log records variance and notes.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- ========================================================================= -->
<!-- SECTION 10: QUICK REFERENCE GUIDE & TECHNICAL GLOSSARY                    -->
<!-- ========================================================================= -->
<div class="section-title">10. QUICK REFERENCE GUIDE & TECHNICAL GLOSSARY</div>

<h2>10.1 Voucher Type & Prefix Reference Table</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 12%;">Prefix</th>
            <th style="width: 25%;">Voucher Type</th>
            <th style="width: 20%;">Voucher Enum Key</th>
            <th style="width: 43%;">Standard Usage Description</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><strong>JV</strong></td><td>Journal Voucher</td><td><span class="code-inline">journal</span></td><td>General manual adjustments, accruals, prepayments, depreciation.</td></tr>
        <tr><td><strong>PV</strong></td><td>Payment Voucher</td><td><span class="code-inline">payment</span></td><td>Bank and cash disbursements, vendor settlements, expense payments.</td></tr>
        <tr><td><strong>RV</strong></td><td>Receipt Voucher</td><td><span class="code-inline">receipt</span></td><td>Bank and cash inflows, customer invoice receipts, advances.</td></tr>
        <tr><td><strong>CV</strong></td><td>Contra Voucher</td><td><span class="code-inline">contra</span></td><td>Transfers between same-company bank accounts and cash boxes.</td></tr>
        <tr><td><strong>PUR</strong></td><td>Purchase Voucher</td><td><span class="code-inline">purchase</span></td><td>Posted vendor invoices, subcontractor bills, direct material purchases.</td></tr>
        <tr><td><strong>SAL</strong></td><td>Sales Voucher</td><td><span class="code-inline">sales</span></td><td>Posted customer service invoices and commercial trading sales.</td></tr>
        <tr><td><strong>RB</strong></td><td>Running Bill</td><td><span class="code-inline">sales (running_bill)</span></td><td>Certified construction progress claims and client IPCs.</td></tr>
        <tr><td><strong>DN</strong></td><td>Debit Note</td><td><span class="code-inline">debit_note</span></td><td>Authorized debit adjustments to vendor or customer accounts.</td></tr>
        <tr><td><strong>CN</strong></td><td>Credit Note</td><td><span class="code-inline">credit_note</span></td><td>Authorized credit adjustments to vendor or customer accounts.</td></tr>
        <tr><td><strong>OB</strong></td><td>Opening Balance</td><td><span class="code-inline">opening_balance</span></td><td>Historical opening trial balance migration voucher.</td></tr>
        <tr><td><strong>PAY</strong></td><td>Payroll Voucher</td><td><span class="code-inline">payroll</span></td><td>Monthly salary accruals and staff advance recoveries.</td></tr>
        <tr><td><strong>DEP</strong></td><td>Depreciation Voucher</td><td><span class="code-inline">depreciation</span></td><td>Monthly automated straight-line asset depreciation runs.</td></tr>
        <tr><td><strong>IA</strong></td><td>Inventory Adjustment</td><td><span class="code-inline">inventory_adjustment</span></td><td>Material issues to jobs, store transfers, stock adjustments.</td></tr>
        <tr><td><strong>REV</strong></td><td>Reversal Voucher</td><td><span class="code-inline">reversal</span></td><td>Linked system reversal cancelling an erroneously posted voucher.</td></tr>
        <tr><td><strong>IC</strong></td><td>Inter-Company</td><td><span class="code-inline">inter_company</span></td><td>Bilateral shared cost allocations between group companies.</td></tr>
    </tbody>
</table>

<h2>10.2 Navigation Quick-Reference Cheat Sheet</h2>
<table class="data-table compact-table">
    <thead>
        <tr>
            <th>Destination Feature</th>
            <th>Filament Navigation Path</th>
            <th>URL Path</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><strong>Chart of Accounts</strong></td><td>Accounting $\rightarrow$ Chart of Accounts</td><td><span class="code-inline">/admin/accounts</span></td></tr>
        <tr><td><strong>Vouchers & Journals</strong></td><td>Accounting $\rightarrow$ Vouchers / Journals</td><td><span class="code-inline">/admin/journal-entries</span></td></tr>
        <tr><td><strong>Payments & Receipts</strong></td><td>Transactions $\rightarrow$ Payments, Receipts & Transfers</td><td><span class="code-inline">/admin/treasury-transactions</span></td></tr>
        <tr><td><strong>Vendor Bills (AP)</strong></td><td>Transactions $\rightarrow$ Vendor Bills & Credit Notes</td><td><span class="code-inline">/admin/vendor-bills</span></td></tr>
        <tr><td><strong>Customer Invoices (AR)</strong></td><td>Transactions $\rightarrow$ Customer Invoices & Credit Notes</td><td><span class="code-inline">/admin/customer-invoices</span></td></tr>
        <tr><td><strong>Goods Receipts & QA</strong></td><td>Transactions $\rightarrow$ Goods Receipts & Inspection</td><td><span class="code-inline">/admin/goods-receipts</span></td></tr>
        <tr><td><strong>Inventory Transfers & Issues</strong></td><td>Transactions $\rightarrow$ Inventory Transfers & Issues</td><td><span class="code-inline">/admin/inventory-transactions</span></td></tr>
        <tr><td><strong>Bank Statements</strong></td><td>Transactions $\rightarrow$ Bank Statements</td><td><span class="code-inline">/admin/bank-statements</span></td></tr>
        <tr><td><strong>Bank Reconciliation</strong></td><td>Accounting $\rightarrow$ Bank Reconciliation</td><td><span class="code-inline">/admin/bank-reconciliations</span></td></tr>
        <tr><td><strong>Fixed Assets Register</strong></td><td>Assets $\rightarrow$ Fixed Assets</td><td><span class="code-inline">/admin/fixed-assets</span></td></tr>
        <tr><td><strong>Depreciation Runs</strong></td><td>Assets $\rightarrow$ Depreciation Runs</td><td><span class="code-inline">/admin/depreciation-runs</span></td></tr>
        <tr><td><strong>Quick Expense Entry</strong></td><td>Accounts $\rightarrow$ Quick Expense Entry</td><td><span class="code-inline">/admin/quick-expense-entry</span></td></tr>
        <tr><td><strong>Petty Cash Register</strong></td><td>Accounts $\rightarrow$ Petty Cash Register</td><td><span class="code-inline">/admin/petty-cash-register</span></td></tr>
        <tr><td><strong>Director Expense Ledger</strong></td><td>Accounts $\rightarrow$ Director Expense Ledger</td><td><span class="code-inline">/admin/director-expense-ledger</span></td></tr>
        <tr><td><strong>Bidding Expense Ledger</strong></td><td>Accounts $\rightarrow$ Bidding & Tender Expenses</td><td><span class="code-inline">/admin/bidding-expense-ledger</span></td></tr>
        <tr><td><strong>Project Expense Ledger</strong></td><td>Projects $\rightarrow$ Project Expense Ledger</td><td><span class="code-inline">/admin/project-expense-ledger</span></td></tr>
        <tr><td><strong>Daily Cash & Bank Statement</strong></td><td>Reports $\rightarrow$ Daily Cash & Bank Statement</td><td><span class="code-inline">/admin/daily-cash-bank-position</span></td></tr>
        <tr><td><strong>Financial Statements (TB/GL/PL/BS)</strong></td><td>Reports $\rightarrow$ Financial Statements</td><td><span class="code-inline">/admin/accounting-reports</span></td></tr>
        <tr><td><strong>AP Reports & Aging</strong></td><td>Reports $\rightarrow$ Accounts Payable</td><td><span class="code-inline">/admin/accounts-payable-reports</span></td></tr>
        <tr><td><strong>AR Reports & Aging</strong></td><td>Reports $\rightarrow$ Sales & Project Profitability</td><td><span class="code-inline">/admin/sales-reports</span></td></tr>
        <tr><td><strong>Group Consolidation</strong></td><td>Reports $\rightarrow$ Group Consolidation</td><td><span class="code-inline">/admin/consolidated-reports</span></td></tr>
        <tr><td><strong>Year-End Closings</strong></td><td>Accounting $\rightarrow$ Year-end Closings</td><td><span class="code-inline">/admin/year-end-closings</span></td></tr>
    </tbody>
</table>

<h2>10.3 Key Accounting & Technical Terms</h2>
<ul>
    <li><strong>Control Account:</strong> An aggregated General Ledger account (such as Accounts Payable or Accounts Receivable) whose balance reflects the total of all individual accounts in a subsidiary ledger. Direct manual posting is blocked.</li>
    <li><strong>GRNI (Goods Received Not Invoiced):</strong> A temporary current liability account used to accrue material costs received on site before the official vendor invoice is matched and posted.</li>
    <li><strong>IPC (Interim Payment Certificate) / Running Bill:</strong> A progress billing claim in construction contracting certified by an independent supervising engineer/architect based on percentage of completed site work.</li>
    <li><strong>Retention Money:</strong> A contractually stipulated percentage (typically 5% to 10%) deducted from progress payments and retained by the employer to guarantee defect liability rectification.</li>
    <li><strong>Withholding Tax (WHT):</strong> Statutory income tax deducted at source from vendor payments and customer receipts, remitted to tax authorities.</li>
    <li><strong>Moving Weighted Average Cost:</strong> An inventory valuation methodology wherein unit cost is recalculated automatically upon every new material receipt: $\text{New Avg Rate} = (\text{Old Value} + \text{New Inward Value}) / (\text{Old Qty} + \text{New Qty})$.</li>
    <li><strong>Contra Voucher:</strong> An internal accounting voucher recording transfers of cash and bank funds between two accounts belonging strictly to the same corporate entity.</li>
    <li><strong>Maker-Checker:</strong> An authorization framework requiring a transaction to be initiated by one individual (Maker) and independently reviewed and authorized by a distinct individual (Checker).</li>
</ul>

<div style="margin-top: 30pt; padding-top: 10pt; border-top: 1px solid #cbd5e1; text-align: center; font-size: 7.5pt; color: #64748b;">
    <strong>YM Construction Management System (YM-CMS)</strong> &bull; Accounts & Financial Operations User Manual &bull; Confidential & Proprietary
</div>

</body>
</html>
HTML;

echo "Assembling PDF document...\n";

$options = new Options;
$options->set('isPhpEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfOutput = $dompdf->output();

$destWorkspace = __DIR__.'/docs/YM_Construction_Accounts_Module_User_Manual.pdf';
file_put_contents($destWorkspace, $pdfOutput);
echo "Successfully generated manual in workspace: {$destWorkspace} (".strlen($pdfOutput)." bytes)\n";

$artifactDir = '/Users/aaraifhanif/.gemini/antigravity/brain/0a5d3b2f-abd4-4d67-8478-44a16c86a591';
if (is_dir($artifactDir)) {
    $destArtifact = $artifactDir.'/YM_Construction_Accounts_Module_User_Manual.pdf';
    file_put_contents($destArtifact, $pdfOutput);
    echo "Successfully copied manual to artifact directory: {$destArtifact}\n";
}

echo "PDF Generation Complete!\n";
