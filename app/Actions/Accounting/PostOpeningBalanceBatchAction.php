<?php

namespace App\Actions\Accounting;

use App\Enums\JournalStatus;
use App\Enums\OpeningBalanceStatus;
use App\Enums\VoucherType;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\OpeningBalanceBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostOpeningBalanceBatchAction
{
    public function __construct(private PostJournalEntryAction $postJournal) {}

    public function handle(OpeningBalanceBatch $batch, User $actor): JournalEntry
    {
        Gate::forUser($actor)->authorize('post', $batch);

        return DB::transaction(function () use ($batch, $actor): JournalEntry {
            $batch = OpeningBalanceBatch::query()->with('lines')->whereKey($batch)->lockForUpdate()->firstOrFail();
            if ($batch->status === OpeningBalanceStatus::Posted) {
                return $batch->journalEntry()->firstOrFail();
            }
            if ($batch->status !== OpeningBalanceStatus::Validated) {
                throw ValidationException::withMessages(['status' => 'Only a validated opening-balance batch may be posted.']);
            }
            if ((int) $batch->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['posted_by_id' => 'The preparer cannot post the same opening-balance batch.']);
            }

            $entry = JournalEntry::create([
                'company_id' => $batch->company_id,
                'financial_year_id' => $batch->financial_year_id,
                'financial_period_id' => $batch->financial_period_id,
                'voucher_type' => VoucherType::OpeningBalance,
                'idempotency_key' => $batch->idempotency_key,
                'status' => JournalStatus::Draft,
                'transaction_date' => $batch->opening_date,
                'reference' => $batch->source_name,
                'description' => 'Opening balances'.($batch->source_name ? " — {$batch->source_name}" : ''),
                'currency_code' => 'PKR',
                'source_type' => OpeningBalanceBatch::class,
                'source_id' => $batch->getKey(),
                'prepared_by_id' => $batch->prepared_by_id,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);

            foreach ($batch->lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->getKey(), 'company_id' => $entry->company_id,
                    'line_number' => $line->line_number, 'account_id' => $line->account_id,
                    'description' => $line->description, 'debit' => $line->debit, 'credit' => $line->credit,
                    'party_id' => $line->party_id, 'project_id' => $line->project_id, 'cost_center_id' => $line->cost_center_id,
                ]);
            }

            $entry->update(['status' => JournalStatus::Approved]);
            $entry = $this->postJournal->handle($entry, $actor);
            $batch->update([
                'status' => OpeningBalanceStatus::Posted, 'posted_by_id' => $actor->getKey(),
                'posted_at' => now(), 'journal_entry_id' => $entry->getKey(),
            ]);
            activity('opening_balances')->causedBy($actor)->performedOn($batch)->event('posted')
                ->withProperties(['company_id' => $batch->company_id, 'journal_entry_id' => $entry->getKey(), 'voucher_number' => $entry->voucher_number])
                ->log('posted opening-balance batch');

            return $entry;
        }, attempts: 3);
    }
}
