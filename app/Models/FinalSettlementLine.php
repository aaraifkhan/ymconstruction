<?php

namespace App\Models;

use App\Enums\FinalSettlementComponentNature;
use App\Enums\FinalSettlementComponentType;
use Database\Factories\FinalSettlementLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'final_settlement_id', 'line_number', 'component_type', 'nature',
    'employee_financing_id', 'employee_clearance_item_id', 'account_id', 'description',
    'quantity', 'rate', 'amount', 'source_reference', 'evidence_snapshot',
    'source_checksum', 'idempotency_key',
])]
#[Hidden(['quantity', 'rate', 'amount', 'evidence_snapshot'])]
class FinalSettlementLine extends Model
{
    /** @use HasFactory<FinalSettlementLineFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $settlement = FinalSettlement::query()->whereKey($line->final_settlement_id)
                ->where('company_id', $line->company_id)->first();
            if ($settlement === null || ! $settlement->isEditable()) {
                throw ValidationException::withMessages(['final_settlement_id' => 'Lines may only change on an editable same-company settlement.']);
            }
            if ($line->nature !== $line->component_type->nature()
                || bccomp((string) $line->amount, '0', 4) !== 1
                || bccomp((string) $line->quantity, '0', 4) !== 1
                || bccomp((string) $line->amount, bcmul((string) $line->quantity, (string) $line->rate, 4), 4) !== 0) {
                throw ValidationException::withMessages(['amount' => 'Settlement line nature and positive quantity × rate must reconcile.']);
            }
            if (! Account::query()->whereKey($line->account_id)->where('company_id', $line->company_id)
                ->where('is_active', true)->where('allows_manual_posting', true)->exists()) {
                throw ValidationException::withMessages(['account_id' => 'Choose an active same-company posting account.']);
            }
        });

        static::deleting(function (self $line): void {
            if (! $line->settlement()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Submitted settlement lines are immutable.']);
            }
        });
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(FinalSettlement::class, 'final_settlement_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function financing(): BelongsTo
    {
        return $this->belongsTo(EmployeeFinancing::class, 'employee_financing_id');
    }

    public function clearanceItem(): BelongsTo
    {
        return $this->belongsTo(EmployeeClearanceItem::class, 'employee_clearance_item_id');
    }

    protected function casts(): array
    {
        return [
            'component_type' => FinalSettlementComponentType::class,
            'nature' => FinalSettlementComponentNature::class,
            'quantity' => 'encrypted',
            'rate' => 'encrypted',
            'amount' => 'encrypted',
            'evidence_snapshot' => 'encrypted:array',
        ];
    }
}
