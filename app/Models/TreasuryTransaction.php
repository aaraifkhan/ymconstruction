<?php

namespace App\Models;

use App\Enums\AccountingMappingKey;
use App\Enums\TreasuryCounterpartyType;
use App\Enums\TreasuryInstrumentType;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryStatus;
use App\Enums\TreasuryTransactionType;
use Database\Factories\TreasuryTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'party_id', 'employment_id', 'employee_financing_id', 'source_account_id', 'destination_account_id',
    'source_company_bank_account_id', 'destination_company_bank_account_id', 'transaction_number',
    'type', 'purpose', 'counterparty_type', 'transaction_date', 'value_date', 'status',
    'currency_code', 'amount', 'allocated_amount', 'unallocated_amount', 'instrument_type',
    'instrument_number', 'instrument_date', 'bank_reference', 'external_reference', 'description',
    'notes', 'prepared_by_id', 'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at',
    'rejected_by_id', 'rejected_at', 'rejection_reason', 'posted_by_id', 'posted_at',
    'journal_entry_id', 'reversal_journal_entry_id', 'reversed_by_id', 'reversed_at',
])]
class TreasuryTransaction extends Model
{
    /** @use HasFactory<TreasuryTransactionFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => TreasuryStatus::Draft->value,
        'purpose' => TreasuryPurpose::Settlement->value,
        'currency_code' => 'PKR',
        'allocated_amount' => 0,
        'unallocated_amount' => 0,
        'instrument_type' => TreasuryInstrumentType::Electronic->value,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $transaction): void {
            if ($transaction->currency_code !== 'PKR' || bccomp((string) $transaction->amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'Treasury transactions require a positive PKR amount.']);
            }

            $transaction->validateCounterparty();
            $transaction->validateLiquidAccounts();

            if (! $transaction->exists) {
                return;
            }

            $persistedStatus = self::query()->whereKey($transaction)->value('status');
            if (in_array($persistedStatus, [TreasuryStatus::Draft->value, TreasuryStatus::Rejected->value], true)) {
                return;
            }

            $workflowFields = [
                'transaction_number', 'status', 'allocated_amount', 'unallocated_amount',
                'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at',
                'rejected_by_id', 'rejected_at', 'rejection_reason', 'posted_by_id', 'posted_at',
                'journal_entry_id', 'reversal_journal_entry_id', 'reversed_by_id', 'reversed_at',
                'updated_at',
            ];
            if (array_diff(array_keys($transaction->getDirty()), $workflowFields) !== []) {
                throw ValidationException::withMessages(['status' => 'Submitted treasury details are immutable outside controlled workflow actions.']);
            }
        });

        static::deleting(function (self $transaction): void {
            if (! $transaction->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected treasury transactions may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function employeeFinancing(): BelongsTo
    {
        return $this->belongsTo(EmployeeFinancing::class);
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    public function sourceCompanyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'source_company_bank_account_id');
    }

    public function destinationCompanyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'destination_company_bank_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TreasuryAllocation::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [TreasuryStatus::Draft, TreasuryStatus::Rejected], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('treasury_transactions')->logOnly([
            'company_id', 'party_id', 'employment_id', 'source_account_id',
            'destination_account_id', 'source_company_bank_account_id',
            'destination_company_bank_account_id', 'transaction_number', 'type', 'purpose',
            'counterparty_type', 'transaction_date', 'value_date', 'status', 'amount',
            'allocated_amount', 'unallocated_amount', 'instrument_type', 'instrument_number',
            'instrument_date', 'bank_reference', 'external_reference', 'description',
            'prepared_by_id', 'submitted_by_id', 'approved_by_id', 'rejected_by_id',
            'rejection_reason', 'posted_by_id', 'journal_entry_id', 'reversal_journal_entry_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => TreasuryTransactionType::class,
            'purpose' => TreasuryPurpose::class,
            'counterparty_type' => TreasuryCounterpartyType::class,
            'status' => TreasuryStatus::class,
            'instrument_type' => TreasuryInstrumentType::class,
            'transaction_date' => 'date',
            'value_date' => 'date',
            'instrument_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
            'amount' => 'decimal:4',
            'allocated_amount' => 'decimal:4',
            'unallocated_amount' => 'decimal:4',
        ];
    }

    private function validateCounterparty(): void
    {
        if ($this->employee_financing_id !== null && ! EmployeeFinancing::query()
            ->whereKey($this->employee_financing_id)
            ->where('company_id', $this->company_id)
            ->where('employment_id', $this->employment_id)
            ->exists()) {
            throw ValidationException::withMessages(['employee_financing_id' => 'Choose financing for the same company and Employment.']);
        }
        if ($this->counterparty_type === TreasuryCounterpartyType::Party) {
            if ($this->party_id === null || $this->employment_id !== null
                || ! Party::query()->whereKey($this->party_id)->where('company_id', $this->company_id)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['party_id' => 'Choose an active same-company Party counterparty.']);
            }
        } elseif ($this->counterparty_type === TreasuryCounterpartyType::Employment) {
            if ($this->employment_id === null || $this->party_id !== null
                || ! Employment::query()->whereKey($this->employment_id)->where('company_id', $this->company_id)->exists()) {
                throw ValidationException::withMessages(['employment_id' => 'Choose a same-company Employment counterparty.']);
            }
        } elseif ($this->party_id !== null || $this->employment_id !== null) {
            throw ValidationException::withMessages(['counterparty_type' => 'Select the counterparty type for the chosen counterparty.']);
        }
    }

    private function validateLiquidAccounts(): void
    {
        if ($this->type === TreasuryTransactionType::Payment) {
            $this->assertLiquidAccount($this->source_account_id, $this->source_company_bank_account_id, 'source_account_id');
        } elseif ($this->type === TreasuryTransactionType::Receipt) {
            $this->assertLiquidAccount($this->destination_account_id, $this->destination_company_bank_account_id, 'destination_account_id');
        } else {
            $this->assertLiquidAccount($this->source_account_id, $this->source_company_bank_account_id, 'source_account_id');
            $this->assertLiquidAccount($this->destination_account_id, $this->destination_company_bank_account_id, 'destination_account_id');
            if ((int) $this->source_account_id === (int) $this->destination_account_id) {
                throw ValidationException::withMessages(['destination_account_id' => 'Transfer source and destination accounts must differ.']);
            }
            if ($this->party_id !== null || $this->employment_id !== null) {
                throw ValidationException::withMessages(['counterparty_type' => 'Same-company transfers do not use a counterparty.']);
            }
        }
    }

    private function assertLiquidAccount(?int $accountId, ?int $bankAccountId, string $field): void
    {
        $account = Account::query()->whereKey($accountId)->where('company_id', $this->company_id)
            ->where('is_active', true)->where('allows_manual_posting', true)->first();
        if ($account === null) {
            throw ValidationException::withMessages([$field => 'Choose an active same-company cash or bank posting account.']);
        }

        $mapping = AccountingMapping::query()->where('company_id', $this->company_id)
            ->where('account_id', $account->getKey())->where('is_active', true)
            ->where(function ($query) use ($bankAccountId): void {
                if ($bankAccountId === null) {
                    $query->where('system_key', AccountingMappingKey::DefaultCash)
                        ->whereNull('company_bank_account_id');
                } else {
                    $query->where('company_bank_account_id', $bankAccountId);
                }
            })->exists();

        if (! $mapping) {
            throw ValidationException::withMessages([$field => 'The selected cash/bank account must use its active accounting mapping.']);
        }
    }
}
