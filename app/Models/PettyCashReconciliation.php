<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'reconciliation_date',
    'system_expected_balance',
    'on_account_held',
    'physical_counted_cash',
    'difference',
    'explanation',
    'status',
    'reconciled_by_id',
    'reviewed_by_id',
    'reviewed_at',
])]
class PettyCashReconciliation extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'reconciliation_date' => 'date',
            'system_expected_balance' => 'decimal:4',
            'on_account_held' => 'decimal:4',
            'physical_counted_cash' => 'decimal:4',
            'difference' => 'decimal:4',
            'reviewed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('petty_cash_reconciliations');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
