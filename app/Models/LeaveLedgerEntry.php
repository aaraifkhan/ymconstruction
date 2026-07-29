<?php

namespace App\Models;

use App\Enums\LeaveLedgerEntryType;
use Database\Factories\LeaveLedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'leave_type_id', 'entry_type', 'effective_on',
    'units', 'source_type', 'source_id', 'reason', 'recorded_by_id',
])]
class LeaveLedgerEntry extends Model
{
    /** @use HasFactory<LeaveLedgerEntryFactory> */
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::creating(function (LeaveLedgerEntry $entry): void {
            if ((float) $entry->units === 0.0) {
                throw ValidationException::withMessages(['units' => 'Leave ledger units cannot be zero.']);
            }

            foreach (['employment_id' => Employment::class, 'leave_type_id' => LeaveType::class] as $field => $model) {
                if (! $model::query()->whereKey($entry->{$field})->where('company_id', $entry->company_id)->exists()) {
                    throw ValidationException::withMessages([$field => 'The selected record must belong to the same company.']);
                }
            }
        });

        static::updating(fn () => throw ValidationException::withMessages(['units' => 'Leave ledger entries are immutable.']));
        static::deleting(fn () => throw ValidationException::withMessages(['units' => 'Leave ledger entries cannot be deleted.']));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('leave_ledger_entries')->logFillable()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'entry_type' => LeaveLedgerEntryType::class,
            'effective_on' => 'date',
            'units' => 'decimal:2',
        ];
    }
}
