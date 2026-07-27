<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\AssetStatus;
use App\Enums\DepreciationMethod;
use Database\Factories\AssetCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'code', 'name', 'cost_account_id', 'accumulated_depreciation_account_id',
    'depreciation_expense_account_id', 'disposal_gain_account_id', 'disposal_loss_account_id',
    'default_useful_life_months', 'depreciation_method', 'is_depreciable', 'is_active',
])]
class AssetCategory extends Model
{
    /** @use HasFactory<AssetCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = ['depreciation_method' => 'straight_line', 'is_depreciable' => true, 'is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            $expected = [
                'cost_account_id' => AccountType::Asset,
                'accumulated_depreciation_account_id' => AccountType::Asset,
                'depreciation_expense_account_id' => AccountType::Expense,
                'disposal_gain_account_id' => AccountType::Revenue,
                'disposal_loss_account_id' => AccountType::Expense,
            ];
            foreach ($expected as $field => $type) {
                if ($category->{$field} !== null && ! Account::query()->whereKey($category->{$field})
                    ->where('company_id', $category->company_id)->where('account_type', $type)
                    ->where('is_active', true)->where('allows_manual_posting', true)->exists()) {
                    throw ValidationException::withMessages([$field => "Choose an active same-company {$type->value} posting account."]);
                }
            }
            if ($category->is_depreciable && ($category->default_useful_life_months < 1
                || $category->accumulated_depreciation_account_id === null
                || $category->depreciation_expense_account_id === null)) {
                throw ValidationException::withMessages(['default_useful_life_months' => 'Depreciable categories require life, contra-asset, and expense mappings.']);
            }
            if ($category->exists && $category->assets()
                ->whereNotIn('status', [AssetStatus::Draft->value, AssetStatus::Rejected->value])->exists()) {
                $financialFields = [
                    'cost_account_id', 'accumulated_depreciation_account_id', 'depreciation_expense_account_id',
                    'disposal_gain_account_id', 'disposal_loss_account_id', 'default_useful_life_months',
                    'depreciation_method', 'is_depreciable',
                ];
                if (array_intersect(array_keys($category->getDirty()), $financialFields) !== []) {
                    throw ValidationException::withMessages([
                        'cost_account_id' => 'Category accounting terms are locked once an asset is submitted.',
                    ]);
                }
            }
        });

        static::deleting(function (self $category): void {
            if ($category->assets()->exists()) {
                throw ValidationException::withMessages(['status' => 'Categories in use cannot be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function costAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cost_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function disposalGainAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'disposal_gain_account_id');
    }

    public function disposalLossAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'disposal_loss_account_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    protected function casts(): array
    {
        return ['depreciation_method' => DepreciationMethod::class, 'is_depreciable' => 'boolean', 'is_active' => 'boolean'];
    }
}
