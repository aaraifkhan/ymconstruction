<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'parent_company_id',
    'name',
    'legal_name',
    'slug',
    'registration_number',
    'tax_number',
    'email',
    'phone',
    'website',
    'address',
    'city',
    'country_code',
    'currency_code',
    'timezone',
    'logo_path',
    'is_active',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'country_code' => 'PK',
        'currency_code' => 'PKR',
        'timezone' => 'Asia/Karachi',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (Company $company): void {
            if ($company->parent_company_id === null) {
                return;
            }

            $visitedCompanyIds = [];
            $ancestor = Company::withTrashed()->find($company->parent_company_id);

            while ($ancestor !== null) {
                if ($company->exists && $ancestor->is($company)) {
                    throw ValidationException::withMessages([
                        'parent_company_id' => 'A company cannot be placed below itself or one of its sub-companies.',
                    ]);
                }

                if (in_array($ancestor->getKey(), $visitedCompanyIds, true)) {
                    throw ValidationException::withMessages([
                        'parent_company_id' => 'The selected company hierarchy already contains a circular relationship.',
                    ]);
                }

                $visitedCompanyIds[] = $ancestor->getKey();
                $ancestor = $ancestor->parentCompany()->withTrashed()->first();
            }
        });
    }

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_company_id');
    }

    public function childCompanies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_company_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['is_active', 'can_access_descendants'])
            ->withTimestamps();
    }

    public function companyModules(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(CompanyBankAccount::class);
    }

    public function documentCategories(): HasMany
    {
        return $this->hasMany(DocumentCategory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    public function joiningLetterTemplates(): HasMany
    {
        return $this->hasMany(JoiningLetterTemplate::class);
    }

    public function joiningLetters(): HasMany
    {
        return $this->hasMany(JoiningLetter::class);
    }

    public function employmentCompensations(): HasMany
    {
        return $this->hasMany(EmploymentCompensation::class);
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }

    public function partyContacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function projectSites(): HasMany
    {
        return $this->hasMany(ProjectSite::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function unitsOfMeasure(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class);
    }

    public function itemCategories(): HasMany
    {
        return $this->hasMany(ItemCategory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function taxCodes(): HasMany
    {
        return $this->hasMany(TaxCode::class);
    }

    public function projectBudgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    public function projectBudgetLines(): HasMany
    {
        return $this->hasMany(ProjectBudgetLine::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function accountingSettings(): HasMany
    {
        return $this->hasMany(AccountingSetting::class);
    }

    public function accountingMappings(): HasMany
    {
        return $this->hasMany(AccountingMapping::class);
    }

    public function payrollAccountMappings(): HasMany
    {
        return $this->hasMany(PayrollAccountMapping::class);
    }

    public function assetCategories(): HasMany
    {
        return $this->hasMany(AssetCategory::class);
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    public function depreciationRuns(): HasMany
    {
        return $this->hasMany(DepreciationRun::class);
    }

    public function assetDisposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function financialYears(): HasMany
    {
        return $this->hasMany(FinancialYear::class);
    }

    public function financialPeriods(): HasMany
    {
        return $this->hasMany(FinancialPeriod::class);
    }

    public function voucherSequences(): HasMany
    {
        return $this->hasMany(VoucherSequence::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function openingBalanceBatches(): HasMany
    {
        return $this->hasMany(OpeningBalanceBatch::class);
    }

    public function openingBalanceLines(): HasMany
    {
        return $this->hasMany(OpeningBalanceLine::class);
    }

    public function originatedIntercompanyTransactions(): HasMany
    {
        return $this->hasMany(IntercompanyTransaction::class);
    }

    public function counterpartyIntercompanyTransactions(): HasMany
    {
        return $this->hasMany(IntercompanyTransaction::class, 'counterparty_company_id');
    }

    public function yearEndClosings(): HasMany
    {
        return $this->hasMany(YearEndClosing::class);
    }

    public function openingBalanceMigrations(): HasMany
    {
        return $this->hasMany(OpeningBalanceMigration::class);
    }

    public function procurementSequences(): HasMany
    {
        return $this->hasMany(ProcurementSequence::class);
    }

    public function procurementApprovalRules(): HasMany
    {
        return $this->hasMany(ProcurementApprovalRule::class);
    }

    public function procurementApprovalSteps(): HasMany
    {
        return $this->hasMany(ProcurementApprovalStep::class);
    }

    public function purchaseRequisitions(): HasMany
    {
        return $this->hasMany(PurchaseRequisition::class);
    }

    public function purchaseRequisitionLines(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionLine::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function goodsReceiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function inventoryTransactionLines(): HasMany
    {
        return $this->hasMany(InventoryTransactionLine::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function apMatchingSettings(): HasMany
    {
        return $this->hasMany(ApMatchingSetting::class);
    }

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    public function vendorBillLines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class);
    }

    public function vendorBillReceiptAllocations(): HasMany
    {
        return $this->hasMany(VendorBillReceiptAllocation::class);
    }

    public function vendorBillDeductions(): HasMany
    {
        return $this->hasMany(VendorBillDeduction::class);
    }

    public function treasuryTransactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    public function treasuryAllocations(): HasMany
    {
        return $this->hasMany(TreasuryAllocation::class);
    }

    public function bankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class);
    }

    public function bankStatementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function bankReconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function bankReconciliationMatches(): HasMany
    {
        return $this->hasMany(BankReconciliationMatch::class);
    }

    public function salesSequences(): HasMany
    {
        return $this->hasMany(SalesSequence::class);
    }

    public function customerInvoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class);
    }

    public function customerInvoiceLines(): HasMany
    {
        return $this->hasMany(CustomerInvoiceLine::class);
    }

    public function customerInvoiceAdjustments(): HasMany
    {
        return $this->hasMany(CustomerInvoiceAdjustment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('companies')
            ->logOnly([
                'parent_company_id',
                'name',
                'legal_name',
                'slug',
                'registration_number',
                'tax_number',
                'email',
                'phone',
                'website',
                'address',
                'city',
                'country_code',
                'currency_code',
                'timezone',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
