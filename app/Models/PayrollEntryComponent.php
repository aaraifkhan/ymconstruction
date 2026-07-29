<?php

namespace App\Models;

use App\Enums\PayrollAccountComponent;
use App\Enums\PayrollComponentNature;
use App\Enums\PayrollComponentType;
use App\Enums\PayrollRunStatus;
use Database\Factories\PayrollEntryComponentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'payroll_entry_id', 'employment_id', 'type', 'nature',
    'source_type', 'source_id', 'quantity', 'rate', 'amount', 'account_component',
    'source_checksum', 'evidence_snapshot', 'idempotency_key',
])]
#[Hidden(['rate', 'amount', 'evidence_snapshot'])]
class PayrollEntryComponent extends Model
{
    /** @use HasFactory<PayrollEntryComponentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $component): void {
            $entry = PayrollEntry::query()->whereKey($component->payroll_entry_id)
                ->where('company_id', $component->company_id)
                ->where('employment_id', $component->employment_id)->first();
            if ($entry === null || bccomp((string) $component->amount, '0', 4) !== 1
                || bccomp((string) $component->quantity, '0', 4) === -1) {
                throw ValidationException::withMessages(['amount' => 'Component requires a matching Payroll Entry and positive amount.']);
            }
        });
        static::updating(fn () => throw ValidationException::withMessages([
            'type' => 'Payroll component snapshots are immutable; regenerate draft Payroll.',
        ]));
        static::deleting(function (self $component): void {
            $status = $component->payrollEntry()->withTrashed()->first()?->payrollRun?->status;
            if (! in_array($status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true)) {
                throw ValidationException::withMessages(['type' => 'Submitted Payroll component snapshots cannot be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollEntry(): BelongsTo
    {
        return $this->belongsTo(PayrollEntry::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'type' => PayrollComponentType::class,
            'nature' => PayrollComponentNature::class,
            'quantity' => 'decimal:4',
            'rate' => 'encrypted',
            'amount' => 'encrypted',
            'account_component' => PayrollAccountComponent::class,
            'evidence_snapshot' => 'encrypted:array',
        ];
    }
}
