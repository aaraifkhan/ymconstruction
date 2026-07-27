<?php

namespace App\Models;

use App\Enums\IntercompanyDirection;
use App\Enums\IntercompanyStatus;
use Database\Factories\IntercompanyTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'counterparty_company_id', 'idempotency_key', 'transaction_date', 'direction',
    'amount', 'origin_offset_account_id', 'counterparty_offset_account_id', 'reference',
    'description', 'status', 'prepared_by_id', 'origin_approved_by_id', 'origin_approved_at',
    'counterparty_approved_by_id', 'counterparty_approved_at', 'rejected_by_id', 'rejected_at',
    'rejection_reason', 'posted_by_id', 'posted_at', 'origin_journal_entry_id',
    'counterparty_journal_entry_id', 'origin_reversal_entry_id', 'counterparty_reversal_entry_id',
    'reversed_by_id', 'reversed_at', 'reversal_reason',
])]
class IntercompanyTransaction extends Model
{
    /** @use HasFactory<IntercompanyTransactionFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['status' => IntercompanyStatus::Draft->value];

    protected static function booted(): void
    {
        static::saving(function (self $transaction): void {
            if ((int) $transaction->company_id === (int) $transaction->counterparty_company_id) {
                throw ValidationException::withMessages(['counterparty_company_id' => 'Inter-company counterparties must be different companies.']);
            }
            if (bccomp((string) $transaction->amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'Inter-company amount must be greater than zero.']);
            }
            foreach ([
                'origin_offset_account_id' => $transaction->company_id,
                'counterparty_offset_account_id' => $transaction->counterparty_company_id,
            ] as $field => $companyId) {
                if (! Account::query()->whereKey($transaction->{$field})->where('company_id', $companyId)->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages([$field => 'Each offset account must be an active account of its own company.']);
                }
            }
            if ($transaction->exists) {
                $persisted = self::query()->whereKey($transaction)->firstOrFail();
                if (in_array($persisted->status, [IntercompanyStatus::Posted, IntercompanyStatus::Reversed], true)) {
                    $workflowFields = [
                        'status', 'origin_reversal_entry_id', 'counterparty_reversal_entry_id',
                        'reversed_by_id', 'reversed_at', 'reversal_reason', 'updated_at',
                    ];
                    if ($persisted->status === IntercompanyStatus::Reversed || array_diff(array_keys($transaction->getDirty()), $workflowFields) !== []) {
                        throw ValidationException::withMessages(['status' => 'Posted inter-company transactions are immutable except through controlled reversal.']);
                    }
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function counterpartyCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'counterparty_company_id');
    }

    public function originOffsetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'origin_offset_account_id');
    }

    public function counterpartyOffsetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'counterparty_offset_account_id');
    }

    public function originJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'origin_journal_entry_id');
    }

    public function counterpartyJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'counterparty_journal_entry_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('intercompany')->logOnly([
            'company_id', 'counterparty_company_id', 'transaction_date', 'direction', 'amount',
            'status', 'origin_approved_by_id', 'counterparty_approved_by_id', 'posted_by_id',
            'origin_journal_entry_id', 'counterparty_journal_entry_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'direction' => IntercompanyDirection::class,
            'status' => IntercompanyStatus::class,
            'amount' => 'decimal:4',
            'origin_approved_at' => 'datetime',
            'counterparty_approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
