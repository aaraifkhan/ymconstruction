<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'key' => 'sm_department_operations',
                'name' => 'SM Department Operations',
                'icon' => 'heroicon-o-sparkles',
                'navigation_group' => 'SM Department Operations',
                'description' => 'Social Media, Creative & Marketing Operations, Specialized Tasks, 2-Tier Approvals, Daily 6 PM Matrix, and Performance Analytics.',
                'sort_order' => 5,
                'features' => [
                    'Department Head Dashboard & Command Center',
                    'Specialized Creative Teams (Social Media, Video, Design, Web, Sales)',
                    'Specialized Task Workflows with Platform & Format Fields',
                    '2-Tier Quality Approvals (Team Lead & Department Head)',
                    '6:00 PM Daily Attendance & Work Reporting Matrix',
                    'Employee Productivity & Performance Analytics Scorecard',
                ],
            ],
            [
                'key' => 'hr',
                'name' => 'Human Resources',
                'icon' => 'heroicon-o-user-group',
                'navigation_group' => 'HR Management',
                'description' => 'Employee directory, official employments, onboarding, attendance, leave management, payroll, appraisals, and clearances.',
                'sort_order' => 10,
                'features' => [
                    'Employee Directory & Official Employments',
                    'Biometric Device & Shift Attendance Management',
                    'Leave Applications & Balance Tracking',
                    'Salary Advances & Employee Financing',
                    'Payroll Processing & Salary Components',
                    'Performance Appraisals & Incident Warnings',
                    'Employee Custody & Final Clearance Settlements',
                    'HR Readiness & Group Compliance Reports',
                ],
            ],
            [
                'key' => 'accounts',
                'name' => 'Accounts & Financials',
                'icon' => 'heroicon-o-building-office-2',
                'navigation_group' => 'Accounts Management',
                'description' => 'Chart of Accounts, journal vouchers, fast expense entry, banking & treasury, and financial statements.',
                'sort_order' => 20,
                'features' => [
                    'Master Accounts Hub & Fast Expense Entry',
                    'Chart of Accounts & Multi-Level Ledgers',
                    'Journal Vouchers & Double-Entry Accounting',
                    'Banking, Cheque Registers & Bank Reconciliation',
                    'Trial Balance, Profit & Loss, Balance Sheet',
                    'Daily Cash & Bank Position Statements',
                    'Shared Cost Allocation & Inter-Company Vouchers',
                ],
            ],
            [
                'key' => 'projects',
                'name' => 'Project Management',
                'icon' => 'heroicon-o-briefcase',
                'navigation_group' => 'Projects Management',
                'description' => 'Construction projects, site management, procurement, material requests, and project budgeting.',
                'sort_order' => 30,
                'features' => [
                    'Project Sites & Master Contracts',
                    'Bill of Quantities (BOQ) & Project Costing',
                    'Material Requisitions & Site Deliveries',
                    'Subcontractor Management & Progress Billing',
                    'Project Profitability & Milestone Tracking',
                ],
            ],
            [
                'key' => 'documents',
                'name' => 'Document Management',
                'icon' => 'heroicon-o-folder-open',
                'navigation_group' => 'Document Management',
                'description' => 'Private, category-based versioned documents linked to companies and business entities.',
                'sort_order' => 40,
                'features' => [
                    'Company Document Folders & Categories',
                    'Confidential Document Security & Access Control',
                    'Document Expiry Tracking & Renewals',
                    'File Versioning & Revision History',
                ],
            ],
            [
                'key' => 'fixed_assets',
                'name' => 'Fixed Assets & Equipment',
                'icon' => 'heroicon-o-cube',
                'navigation_group' => 'Accounts Management',
                'description' => 'Fixed asset registry, depreciation schedules, equipment custody, and asset maintenance.',
                'sort_order' => 50,
                'features' => [
                    'Asset Registry & Barcode Tracking',
                    'Depreciation Calculations (Straight Line / WDV)',
                    'Equipment Custody Handover & Returns',
                    'Maintenance Logs & Repair Cost Tracking',
                ],
            ],
            [
                'key' => 'sales_crm',
                'name' => 'Sales & CRM',
                'icon' => 'heroicon-o-shopping-bag',
                'navigation_group' => 'Accounts Management',
                'description' => 'Customer management, quotations, sales orders, invoices, and receivables.',
                'sort_order' => 60,
                'features' => [
                    'Customer Directory & Credit Terms',
                    'Quotations & Sales Orders',
                    'Sales Invoicing & Payment Receipts',
                    'Accounts Receivable & Customer Aging Analysis',
                ],
            ],
            [
                'key' => 'purchases',
                'name' => 'Procurement & Purchases',
                'icon' => 'heroicon-o-shopping-cart',
                'navigation_group' => 'Accounts Management',
                'description' => 'Vendor management, purchase orders, goods receipt notes, and purchase bills.',
                'sort_order' => 70,
                'features' => [
                    'Vendor Directory & Approved Supplier Lists',
                    'Purchase Requisitions & Purchase Orders (PO)',
                    'Goods Receipt Notes (GRN) & Quality Check',
                    'Vendor Invoices & Accounts Payable Aging',
                ],
            ],
            [
                'key' => 'medical_billing',
                'name' => 'Medical Billing Operations',
                'icon' => 'heroicon-o-heart',
                'navigation_group' => 'Medical Billing',
                'description' => 'Specialized medical billing, EHR integrations, insurance claims processing, and revenue cycle management for 7 Orbit Medical Billing.',
                'sort_order' => 80,
                'features' => [
                    'Patient Demographics & Insurance Eligibility Verification',
                    'Medical Claims Creation & EDI Submissions',
                    'ERA / EOB Payment Posting & Remittance Advice',
                    'Denial Management & Claims Appeals Tracking',
                    'Provider Productivity & Revenue Cycle Reports',
                ],
            ],
        ];

        foreach ($modules as $module) {
            Module::query()->updateOrCreate(
                ['key' => $module['key']],
                [...$module, 'is_active' => true],
            );
        }
    }
}
