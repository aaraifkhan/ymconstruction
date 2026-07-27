<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'parent_id', 'account_template_id', 'code', 'name', 'account_type', 'reporting_group', 'normal_balance', 'system_key', 'is_control_account', 'allows_manual_posting', 'is_system_generated', 'is_active', 'sort_order'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (self $account): void {
            $account->validateHierarchy();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AccountTemplate::class, 'account_template_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(AccountingMapping::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
            'is_control_account' => 'boolean',
            'allows_manual_posting' => 'boolean',
            'is_system_generated' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    private function validateHierarchy(): void
    {
        if ($this->exists && $this->isDirty(['company_id', 'parent_id', 'code', 'account_type', 'normal_balance', 'system_key', 'is_control_account'])
            && $this->journalLines()->whereHas('journalEntry', fn ($query) => $query->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed]))->exists()) {
            throw ValidationException::withMessages(['account' => 'An account used by posted journals cannot be structurally changed.']);
        }

        if (($this->is_control_account || ($this->exists && $this->children()->exists())) && $this->allows_manual_posting) {
            throw ValidationException::withMessages(['allows_manual_posting' => 'Control and parent accounts cannot allow manual posting.']);
        }

        if ($this->parent_id === null) {
            return;
        }

        $ancestor = self::withTrashed()->find($this->parent_id);
        $visited = [];

        while ($ancestor !== null) {
            if ((int) $ancestor->company_id !== (int) $this->company_id) {
                throw ValidationException::withMessages(['parent_id' => 'The parent account must belong to the same company.']);
            }
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
