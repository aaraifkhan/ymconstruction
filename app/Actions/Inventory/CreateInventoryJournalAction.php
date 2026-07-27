<?php

namespace App\Actions\Inventory;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\FinancialPeriod;
use App\Models\GoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class CreateInventoryJournalAction
{
    public function __construct(private PostJournalEntryAction $postJournalEntry) {}

    /**
     * @param  array<int, array{account_id:int, debit:string, credit:string, description:string, party_id?:int|null, project_id?:int|null, project_site_id?:int|null}>  $lines
     */
    public function handle(
        GoodsReceipt|InventoryTransaction $source,
        CarbonInterface $transactionDate,
        string $reference,
        string $description,
        int $preparedById,
        int $approvedById,
        User $postingActor,
        array $lines,
    ): JournalEntry {
        $period = FinancialPeriod::query()
            ->where('company_id', $source->company_id)
            ->where('status', FinancialPeriodStatus::Open)
            ->whereDate('starts_on', '<=', $transactionDate)
            ->whereDate('ends_on', '>=', $transactionDate)
            ->with('financialYear')
            ->lockForUpdate()
            ->first();

        if ($period === null) {
            throw ValidationException::withMessages([
                'transaction_date' => 'An open financial period is required for the inventory posting date.',
            ]);
        }

        $idempotencyKey = class_basename($source).':'.$source->getKey().':inventory';
        $journal = JournalEntry::query()
            ->where('company_id', $source->company_id)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();

        if ($journal !== null) {
            return $journal->status === JournalStatus::Posted
                ? $journal
                : $this->postJournalEntry->handle($journal, $postingActor);
        }

        $journal = JournalEntry::query()->create([
            'company_id' => $source->company_id,
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => VoucherType::InventoryAdjustment,
            'idempotency_key' => $idempotencyKey,
            'status' => JournalStatus::Draft,
            'transaction_date' => $transactionDate,
            'reference' => $reference,
            'description' => $description,
            'currency_code' => 'PKR',
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'prepared_by_id' => $preparedById,
        ]);

        foreach ($lines as $index => $line) {
            $journal->lines()->create([
                'company_id' => $source->company_id,
                'line_number' => $index + 1,
                'account_id' => $line['account_id'],
                'description' => $line['description'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'party_id' => $line['party_id'] ?? null,
                'project_id' => $line['project_id'] ?? null,
                'project_site_id' => $line['project_site_id'] ?? null,
            ]);
        }

        $journal->update([
            'status' => JournalStatus::Approved,
            'submitted_by_id' => $preparedById,
            'submitted_at' => now(),
            'approved_by_id' => $approvedById,
            'approved_at' => now(),
        ]);

        return $this->postJournalEntry->handle($journal, $postingActor);
    }
}
