<?php

namespace App\Actions\AccountsPayable;

use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Enums\VendorBillStatus;
use App\Models\User;
use App\Models\VendorBill;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseVendorBillAction
{
    public function __construct(private ReverseJournalEntryAction $reverseJournalEntry) {}

    public function handle(
        VendorBill $bill,
        User $actor,
        CarbonInterface $reversalDate,
        string $reason,
    ): VendorBill {
        Gate::forUser($actor)->authorize('reverse', $bill);

        return DB::transaction(function () use ($actor, $bill, $reason, $reversalDate): VendorBill {
            $bill = VendorBill::query()->whereKey($bill)->lockForUpdate()->firstOrFail();
            if ($bill->status === VendorBillStatus::Reversed) {
                return $bill;
            }
            if ($bill->status !== VendorBillStatus::Posted || $bill->journal_entry_id === null) {
                throw ValidationException::withMessages(['status' => 'Only a posted Vendor Bill may be reversed.']);
            }

            $reversal = $this->reverseJournalEntry->handle(
                $bill->journalEntry()->firstOrFail(),
                $actor,
                $reversalDate,
                $reason,
            );
            $bill->update([
                'status' => VendorBillStatus::Reversed,
                'reversal_journal_entry_id' => $reversal->getKey(),
                'reversed_by_id' => $actor->getKey(),
                'reversed_at' => now(),
            ]);

            activity('vendor_bills')->causedBy($actor)->performedOn($bill)->event('reversed')
                ->withProperties([
                    'company_id' => $bill->company_id,
                    'journal_entry_id' => $bill->journal_entry_id,
                    'reversal_journal_entry_id' => $reversal->getKey(),
                    'reason' => $reason,
                ])->log('reversed Vendor Bill posting');

            return $bill->refresh();
        }, attempts: 3);
    }
}
