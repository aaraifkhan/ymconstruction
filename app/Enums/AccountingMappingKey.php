<?php

namespace App\Enums;

enum AccountingMappingKey: string
{
    case DefaultCash = 'default_cash';
    case BankAccounts = 'bank_accounts';
    case AccountsReceivable = 'accounts_receivable';
    case AccountsPayable = 'accounts_payable';
    case EmployeeAdvances = 'employee_advances';
    case VendorAdvances = 'vendor_advances';
    case InputTax = 'input_tax';
    case OutputTax = 'output_tax';
    case WhtReceivable = 'wht_receivable';
    case WhtPayable = 'wht_payable';
    case SalaryPayable = 'salary_payable';
    case RetentionReceivable = 'retention_receivable';
    case RetentionPayable = 'retention_payable';
    case CustomerAdvances = 'customer_advances';
    case Grni = 'grni';
    case SiteInventory = 'site_inventory';
    case WorkInProgress = 'work_in_progress';
    case DueFromRelatedCompanies = 'due_from_related_companies';
    case DueToRelatedCompanies = 'due_to_related_companies';
    case RetainedEarnings = 'retained_earnings';
    case CurrentYearResult = 'current_year_result';
}
