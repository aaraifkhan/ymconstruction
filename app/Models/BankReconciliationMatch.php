<?php

namespace App\Models;

use App\Enums\JournalStatus;
use Database\Factories\BankReconciliationMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'bank_reconciliation_id', 'company_id', 'bank_statement_line_id',
    'journal_line_id', 'amount', 'matched_by_id', 'matched_at',
])]
class BankReconciliationMatch extends Model
{
    /** @use HasFactory<BankReconciliationMatchFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $match): void {
            $reconciliation = BankReconciliation::query()->find($match->bank_reconciliation_id);
            $statementLine = BankStatementLine::query()->find($match->bank_statement_line_id);
            $journalLine = JournalLine::query()->with('journalEntry')->find($match->journal_line_id);
            if ($reconciliation === null || ! $reconciliation->isOpen()
                || (int) $reconciliation->company_id !== (int) $match->company_id
                || $statementLine === null || (int) $statementLine->company_id !== (int) $match->company_id
                || (int) $statementLine->company_bank_account_id !== (int) $reconciliation->company_bank_account_id
                || $journalLine === null || (int) $journalLine->company_id !== (int) $match->company_id
                || (int) $journalLine->company_bank_account_id !== (int) $reconciliation->company_bank_account_id) {
                throw ValidationException::withMessages(['journal_line_id' => 'Match requires open same-company statement and posted bank journal evidence.']);
            }
            if (! in_array($journalLine->journalEntry->status, [JournalStatus::Posted, JournalStatus::Reversed], true)
                || bccomp((string) $match->amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'Match amount must be positive and reference posted bank activity.']);
            }
        });

        static::deleting(function (self $match): void {
            if (! $match->bankReconciliation()->firstOrFail()->isOpen()) {
                throw ValidationException::withMessages(['status' => 'Closed reconciliation matches are immutable.']);
            }
        });
    }

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankStatementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class);
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class);
    }

    public function matchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by_id');
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:4', 'matched_at' => 'datetime'];
    }
}
