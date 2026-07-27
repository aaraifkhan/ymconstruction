<?php

namespace App\Models;

use App\Enums\BankAccountType;
use Database\Factories\CompanyBankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'bank_name',
    'branch_name',
    'branch_code',
    'account_title',
    'account_number',
    'iban',
    'swift_code',
    'currency_code',
    'account_type',
    'is_default_for_payroll',
    'is_active',
    'notes',
])]
class CompanyBankAccount extends Model
{
    /** @use HasFactory<CompanyBankAccountFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'currency_code' => 'PKR',
        'account_type' => BankAccountType::Current->value,
        'is_default_for_payroll' => false,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saved(function (CompanyBankAccount $bankAccount): void {
            if (! $bankAccount->is_default_for_payroll) {
                return;
            }

            CompanyBankAccount::query()
                ->whereBelongsTo($bankAccount->company)
                ->whereKeyNot($bankAccount)
                ->where('is_default_for_payroll', true)
                ->update(['is_default_for_payroll' => false]);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accountingMapping(): HasOne
    {
        return $this->hasOne(AccountingMapping::class);
    }

    public function bankStatements(): HasMany
    {
        return $this->hasMany(BankStatement::class);
    }

    public function bankReconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function maskedAccountNumber(): ?string
    {
        return $this->maskSensitiveValue($this->account_number);
    }

    public function maskedIban(): ?string
    {
        return $this->maskSensitiveValue($this->iban);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('company_bank_accounts')
            ->logOnly([
                'company_id',
                'bank_name',
                'branch_name',
                'branch_code',
                'account_title',
                'swift_code',
                'currency_code',
                'account_type',
                'is_default_for_payroll',
                'is_active',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'iban' => 'encrypted',
            'account_type' => BankAccountType::class,
            'is_default_for_payroll' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    private function maskSensitiveValue(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $visibleCharacters = mb_substr($value, -4);

        return '•••• '.$visibleCharacters;
    }
}
