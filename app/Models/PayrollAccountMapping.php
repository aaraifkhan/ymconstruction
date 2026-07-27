<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\PayrollAccountComponent;
use Database\Factories\PayrollAccountMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'component', 'account_id', 'is_active'])]
class PayrollAccountMapping extends Model
{
    /** @use HasFactory<PayrollAccountMappingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $mapping): void {
            $account = Account::query()->whereKey($mapping->account_id)
                ->where('company_id', $mapping->company_id)->where('is_active', true)
                ->where('allows_manual_posting', true)->first();
            $expectedType = $mapping->component === PayrollAccountComponent::OtherDeduction
                ? AccountType::Liability
                : AccountType::Expense;

            if ($account === null || $account->account_type !== $expectedType) {
                throw ValidationException::withMessages([
                    'account_id' => "Choose an active same-company {$expectedType->value} posting account.",
                ]);
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

    protected function casts(): array
    {
        return ['component' => PayrollAccountComponent::class, 'is_active' => 'boolean'];
    }
}
