<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FoundationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            'DeleteAny:User',
            'Restore:User',
            'RestoreAny:User',
            'ForceDelete:User',
            'ForceDeleteAny:User',
            'ResetPassword:User',
            'ViewAny:Role',
            'View:Role',
            'Create:Role',
            'Update:Role',
            'Delete:Role',
            'DeleteAny:Role',
            'ViewAny:Activity',
            'View:Activity',
            'ViewAny:Company',
            'View:Company',
            'Create:Company',
            'Update:Company',
            'Delete:Company',
            'DeleteAny:Company',
            'Restore:Company',
            'RestoreAny:Company',
            'ManageMembers:Company',
            'ViewAny:Module',
            'View:Module',
            'Create:Module',
            'Update:Module',
            'Delete:Module',
            'DeleteAny:Module',
            'ViewAny:CompanyModule',
            'View:CompanyModule',
            'Create:CompanyModule',
            'Update:CompanyModule',
            'Delete:CompanyModule',
            'DeleteAny:CompanyModule',
            'ViewAny:CompanyBankAccount',
            'View:CompanyBankAccount',
            'Create:CompanyBankAccount',
            'Update:CompanyBankAccount',
            'Delete:CompanyBankAccount',
            'DeleteAny:CompanyBankAccount',
            'Restore:CompanyBankAccount',
            'RestoreAny:CompanyBankAccount',
            'ViewSensitive:CompanyBankAccount',
            'ViewAny:DocumentCategory',
            'View:DocumentCategory',
            'Create:DocumentCategory',
            'Update:DocumentCategory',
            'Delete:DocumentCategory',
            'DeleteAny:DocumentCategory',
            'Restore:DocumentCategory',
            'RestoreAny:DocumentCategory',
            'ViewAny:Document',
            'View:Document',
            'Create:Document',
            'Update:Document',
            'Delete:Document',
            'DeleteAny:Document',
            'Restore:Document',
            'RestoreAny:Document',
            'ViewSensitive:Document',
            'Download:Document',
            'Preview:Document',
            'UploadVersion:Document',
            'Verify:Document',
            'Approve:Document',
            'Reject:Document',
            'ViewAny:Employee',
            'View:Employee',
            'Create:Employee',
            'Update:Employee',
            'Delete:Employee',
            'Restore:Employee',
            'ViewIdentity:Employee',
            'ViewContact:Employee',
            'ViewMedical:Employee',
            'ManageSensitive:Employee',
            'ViewAny:Department',
            'View:Department',
            'Create:Department',
            'Update:Department',
            'Delete:Department',
            'Restore:Department',
            'RestoreAny:Department',
            'ViewAny:Designation',
            'View:Designation',
            'Create:Designation',
            'Update:Designation',
            'Delete:Designation',
            'Restore:Designation',
            'RestoreAny:Designation',
            'ViewAny:Employment',
            'View:Employment',
            'Create:Employment',
            'Update:Employment',
            'Delete:Employment',
            'DeleteAny:Employment',
            'Restore:Employment',
            'RestoreAny:Employment',
            'ViewHrNotes:Employment',
            'ManageHrVerification:Employment',
            'ViewAny:EmployeeEmergencyContact',
            'View:EmployeeEmergencyContact',
            'Create:EmployeeEmergencyContact',
            'Update:EmployeeEmergencyContact',
            'Delete:EmployeeEmergencyContact',
            'Restore:EmployeeEmergencyContact',
            'ViewAny:EmployeeQualification',
            'View:EmployeeQualification',
            'Create:EmployeeQualification',
            'Update:EmployeeQualification',
            'Delete:EmployeeQualification',
            'Restore:EmployeeQualification',
            'ViewAny:EmployeeExperience',
            'View:EmployeeExperience',
            'Create:EmployeeExperience',
            'Update:EmployeeExperience',
            'Delete:EmployeeExperience',
            'Restore:EmployeeExperience',
            'ViewAny:EmployeeBankAccount',
            'View:EmployeeBankAccount',
            'Create:EmployeeBankAccount',
            'Update:EmployeeBankAccount',
            'Delete:EmployeeBankAccount',
            'Restore:EmployeeBankAccount',
            'ViewSensitive:EmployeeBankAccount',
            'ViewAny:JoiningLetterTemplate',
            'View:JoiningLetterTemplate',
            'Create:JoiningLetterTemplate',
            'Update:JoiningLetterTemplate',
            'Delete:JoiningLetterTemplate',
            'Restore:JoiningLetterTemplate',
            'ViewAny:JoiningLetter',
            'View:JoiningLetter',
            'Create:JoiningLetter',
            'Update:JoiningLetter',
            'Delete:JoiningLetter',
            'Restore:JoiningLetter',
            'ViewSensitive:JoiningLetter',
            'ViewCompensation:JoiningLetter',
            'ManageCompensation:JoiningLetter',
            'Regenerate:JoiningLetter',
            'Submit:JoiningLetter',
            'Approve:JoiningLetter',
            'Reject:JoiningLetter',
            'Issue:JoiningLetter',
            'RecordAcceptance:JoiningLetter',
            'ViewAny:EmploymentCompensation',
            'View:EmploymentCompensation',
            'Create:EmploymentCompensation',
            'Update:EmploymentCompensation',
            'Delete:EmploymentCompensation',
            'Restore:EmploymentCompensation',
            'ViewAmounts:EmploymentCompensation',
            'ManageAmounts:EmploymentCompensation',
            'Submit:EmploymentCompensation',
            'Approve:EmploymentCompensation',
            'Reject:EmploymentCompensation',
            'ViewAny:PayrollRun',
            'View:PayrollRun',
            'Create:PayrollRun',
            'Update:PayrollRun',
            'Delete:PayrollRun',
            'Restore:PayrollRun',
            'ViewAmounts:PayrollRun',
            'GenerateEntries:PayrollRun',
            'Submit:PayrollRun',
            'Approve:PayrollRun',
            'Reject:PayrollRun',
            'Post:PayrollRun',
            'Reverse:PayrollRun',
            'View:PayrollReports',
            'MarkPaid:PayrollRun',
            'Lock:PayrollRun',
            'ViewAny:PayrollEntry',
            'View:PayrollEntry',
            'Update:PayrollEntry',
            'ViewAmounts:PayrollEntry',
            'View:MyProfile',
            'View:Settings',
            'Update:Settings',
            'View:UserStatsOverview',
            'View:RoleStatsOverview',
        ];

        $softDeletableMasterDataSubjects = [
            'Party',
            'PartyContact',
            'CostCenter',
            'UnitOfMeasure',
            'ItemCategory',
            'TaxCode',
            'Item',
            'Project',
            'ProjectSite',
            'ProjectBudget',
        ];

        foreach ($softDeletableMasterDataSubjects as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'RestoreAny'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
            $permissions[] = "{$ability}:ProjectBudgetLine";
        }

        $permissions[] = 'Approve:ProjectBudget';

        foreach (['Account', 'AccountingSetting', 'AccountingMapping', 'FinancialYear', 'FinancialPeriod', 'VoucherSequence', 'AccountTemplate'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'RestoreAny'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        array_push($permissions, 'Preview:Account', 'Provision:Account', 'Close:FinancialPeriod', 'Lock:FinancialPeriod', 'Reopen:FinancialPeriod', 'Reserve:VoucherSequence');

        foreach (['JournalEntry', 'JournalLine', 'OpeningBalanceBatch', 'OpeningBalanceLine'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        array_push(
            $permissions,
            'Submit:JournalEntry',
            'Approve:JournalEntry',
            'Reject:JournalEntry',
            'Post:JournalEntry',
            'Reverse:JournalEntry',
            'Validate:OpeningBalanceBatch',
            'Post:OpeningBalanceBatch',
            'View:AccountingReports',
        );

        foreach (['PurchaseRequisition', 'PurchaseRequisitionLine', 'PurchaseOrder', 'PurchaseOrderLine', 'ProcurementApprovalRule'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        foreach (['ViewAny', 'View'] as $ability) {
            $permissions[] = "{$ability}:ProcurementApprovalStep";
        }

        array_push(
            $permissions,
            'Submit:PurchaseRequisition',
            'Approve:PurchaseRequisition',
            'Reject:PurchaseRequisition',
            'Cancel:PurchaseRequisition',
            'Submit:PurchaseOrder',
            'Approve:PurchaseOrder',
            'Reject:PurchaseOrder',
            'Issue:PurchaseOrder',
            'Cancel:PurchaseOrder',
            'ApproveLevelOne:Procurement',
            'ApproveLevelTwo:Procurement',
        );

        foreach (['GoodsReceipt', 'GoodsReceiptLine', 'InventoryTransaction', 'InventoryTransactionLine'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        foreach (['InventoryBalance', 'InventoryMovement'] as $subject) {
            foreach (['ViewAny', 'View'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        array_push(
            $permissions,
            'Receive:GoodsReceipt',
            'Inspect:GoodsReceipt',
            'Handover:GoodsReceipt',
            'ReturnRejected:GoodsReceipt',
            'Post:InventoryTransaction',
        );

        foreach ([
            'ApMatchingSetting',
            'VendorBill',
            'VendorBillLine',
            'VendorBillReceiptAllocation',
            'VendorBillDeduction',
        ] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        array_push(
            $permissions,
            'Submit:VendorBill',
            'ReviewMatch:VendorBill',
            'OverrideMatch:VendorBill',
            'Approve:VendorBill',
            'Reject:VendorBill',
            'Post:VendorBill',
            'Reverse:VendorBill',
            'View:AccountsPayableReports',
        );

        foreach (['TreasuryTransaction', 'TreasuryAllocation', 'BankStatement', 'BankReconciliation'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
            $permissions[] = "{$ability}:PayrollAccountMapping";
        }

        foreach (['BankStatementLine', 'BankReconciliationMatch'] as $subject) {
            foreach (['ViewAny', 'View'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        array_push(
            $permissions,
            'Submit:TreasuryTransaction',
            'Approve:TreasuryTransaction',
            'Reject:TreasuryTransaction',
            'Post:TreasuryTransaction',
            'Reverse:TreasuryTransaction',
            'Import:BankStatement',
            'Match:BankReconciliation',
            'Unmatch:BankReconciliation',
            'Adjust:BankReconciliation',
            'Close:BankReconciliation',
            'Reopen:BankReconciliation',
            'View:TreasuryReports',
        );

        foreach (['CustomerInvoice', 'CustomerInvoiceLine', 'CustomerInvoiceAdjustment'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        array_push(
            $permissions,
            'Submit:CustomerInvoice',
            'Approve:CustomerInvoice',
            'Reject:CustomerInvoice',
            'Post:CustomerInvoice',
            'Reverse:CustomerInvoice',
            'View:SalesReports',
        );

        foreach (['AssetCategory', 'FixedAsset'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'RestoreAny'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        foreach (['DepreciationRun', 'AssetDisposal'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        array_push(
            $permissions,
            'Submit:FixedAsset',
            'Approve:FixedAsset',
            'Reject:FixedAsset',
            'Capitalize:FixedAsset',
            'Transfer:FixedAsset',
            'Generate:DepreciationRun',
            'Submit:DepreciationRun',
            'Approve:DepreciationRun',
            'Post:DepreciationRun',
            'Reverse:DepreciationRun',
            'Approve:AssetDisposal',
            'Post:AssetDisposal',
            'Reverse:AssetDisposal',
            'View:FixedAssetReports',
        );

        foreach (['IntercompanyTransaction', 'YearEndClosing', 'OpeningBalanceMigration'] as $subject) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $ability) {
                $permissions[] = "{$ability}:{$subject}";
            }
        }

        foreach (['ViewAny', 'View'] as $ability) {
            $permissions[] = "{$ability}:OpeningBalanceMigrationRow";
        }

        array_push(
            $permissions,
            'Submit:IntercompanyTransaction',
            'ApproveOrigin:IntercompanyTransaction',
            'ApproveCounterparty:IntercompanyTransaction',
            'Reject:IntercompanyTransaction',
            'Post:IntercompanyTransaction',
            'Reverse:IntercompanyTransaction',
            'Approve:YearEndClosing',
            'Post:YearEndClosing',
            'Reverse:YearEndClosing',
            'Validate:OpeningBalanceMigration',
            'Import:OpeningBalanceMigration',
            'Reverse:OpeningBalanceMigration',
            'View:ConsolidatedReports',
            'View:IntercompanyReconciliationReports',
            'View:AccountingIntegrityReports',
            'Export:FinancialReports',
        );

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
