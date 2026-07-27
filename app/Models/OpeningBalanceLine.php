<?php

namespace App\Models;

use App\Enums\OpeningBalanceStatus;
use Database\Factories\OpeningBalanceLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'opening_balance_batch_id', 'company_id', 'line_number', 'account_id', 'description',
    'debit', 'credit', 'party_id', 'project_id', 'cost_center_id',
])]
class OpeningBalanceLine extends Model
{
    /** @use HasFactory<OpeningBalanceLineFactory> */
    use HasFactory;

    protected $attributes = ['debit' => 0, 'credit' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $batch = OpeningBalanceBatch::query()->find($line->opening_balance_batch_id);
            if ($batch === null || (int) $batch->company_id !== (int) $line->company_id || $batch->status !== OpeningBalanceStatus::Draft) {
                throw ValidationException::withMessages(['opening_balance_batch_id' => 'Lines may only change on a draft batch in the same company.']);
            }

            $debitPositive = bccomp((string) $line->debit, '0', 4) === 1;
            $creditPositive = bccomp((string) $line->credit, '0', 4) === 1;
            if ($debitPositive === $creditPositive) {
                throw ValidationException::withMessages(['debit' => 'Each opening line requires a positive debit or positive credit, never both.']);
            }

            if (! Account::query()->whereKey($line->account_id)->where('company_id', $line->company_id)->exists()) {
                throw ValidationException::withMessages(['account_id' => 'The account must belong to the opening-balance company.']);
            }

            foreach (['party_id' => Party::class, 'project_id' => Project::class, 'cost_center_id' => CostCenter::class] as $field => $model) {
                if ($line->{$field} !== null && ! $model::query()->whereKey($line->{$field})->where('company_id', $line->company_id)->exists()) {
                    throw ValidationException::withMessages([$field => 'Every opening-balance dimension must belong to the company.']);
                }
            }
        });

        static::deleting(function (self $line): void {
            if ($line->batch()->value('status') !== OpeningBalanceStatus::Draft->value) {
                throw ValidationException::withMessages(['opening_balance_batch_id' => 'Validated or posted opening lines are immutable.']);
            }
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OpeningBalanceBatch::class, 'opening_balance_batch_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    protected function casts(): array
    {
        return ['debit' => 'decimal:4', 'credit' => 'decimal:4', 'line_number' => 'integer'];
    }
}
