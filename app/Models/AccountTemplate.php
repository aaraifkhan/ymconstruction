<?php

namespace App\Models;

use App\Enums\AccountingProfile;
use App\Enums\AccountType;
use App\Enums\NormalBalance;
use Database\Factories\AccountTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['parent_id', 'code', 'name', 'account_type', 'reporting_group', 'normal_balance', 'system_key', 'activation_profiles', 'is_control_account', 'allows_manual_posting', 'is_active', 'sort_order'])]
class AccountTemplate extends Model
{
    /** @use HasFactory<AccountTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (self $account): void {
            $account->validateHierarchy();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function isEnabledFor(AccountingProfile $profile): bool
    {
        return $this->activation_profiles === null || in_array($profile->value, $this->activation_profiles, true);
    }

    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
            'activation_profiles' => 'array',
            'is_control_account' => 'boolean',
            'allows_manual_posting' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    private function validateHierarchy(): void
    {
        if ($this->is_control_account && $this->allows_manual_posting) {
            throw ValidationException::withMessages(['allows_manual_posting' => 'Control accounts cannot allow manual posting.']);
        }

        if ($this->parent_id === null) {
            return;
        }

        $ancestor = self::withTrashed()->find($this->parent_id);
        $visited = [];

        while ($ancestor !== null) {
            if ($ancestor->account_type !== $this->account_type) {
                throw ValidationException::withMessages(['parent_id' => 'Parent and child account types must match.']);
            }

            if ($this->exists && $ancestor->is($this)) {
                throw ValidationException::withMessages(['parent_id' => 'An account cannot be its own ancestor.']);
            }

            if (in_array($ancestor->getKey(), $visited, true)) {
                throw ValidationException::withMessages(['parent_id' => 'The account hierarchy contains a cycle.']);
            }

            $visited[] = $ancestor->getKey();
            $ancestor = $ancestor->parent()->withTrashed()->first();
        }
    }
}
