<?php

namespace App\Models;

use App\Enums\AccountingMappingKey;
use Database\Factories\AccountingMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'account_id', 'system_key', 'company_bank_account_id', 'is_active'])]
class AccountingMapping extends Model
{
    /** @use HasFactory<AccountingMappingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $mapping): void {
            if (($mapping->system_key === null) === ($mapping->company_bank_account_id === null)) {
                throw ValidationException::withMessages(['system_key' => 'Choose exactly one mapping target: system key or bank account.']);
            }
            if ((int) $mapping->account?->company_id !== (int) $mapping->company_id || ($mapping->bankAccount && (int) $mapping->bankAccount->company_id !== (int) $mapping->company_id)) {
                throw ValidationException::withMessages(['account_id' => 'Mapped records must belong to the same company.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id');
    }

    protected function casts(): array
    {
        return ['system_key' => AccountingMappingKey::class, 'is_active' => 'boolean'];
    }
}
