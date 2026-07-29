<?php

namespace App\Models;

use App\Enums\FinalSettlementComponentType;
use Database\Factories\FinalSettlementAccountMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'component_type', 'account_id', 'is_active'])]
class FinalSettlementAccountMapping extends Model
{
    /** @use HasFactory<FinalSettlementAccountMappingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $mapping): void {
            if ($mapping->component_type->usesEmployeeAdvancesMapping()) {
                throw ValidationException::withMessages([
                    'component_type' => 'Loan and Advance recoveries use the controlled Employee Advances mapping.',
                ]);
            }
            if (! Account::query()->whereKey($mapping->account_id)->where('company_id', $mapping->company_id)
                ->where('is_active', true)->where('allows_manual_posting', true)->exists()) {
                throw ValidationException::withMessages(['account_id' => 'Choose an active same-company posting account.']);
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
        return ['component_type' => FinalSettlementComponentType::class, 'is_active' => 'boolean'];
    }
}
