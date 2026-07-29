<?php

namespace App\Actions\HR;

use App\Enums\LeaveLedgerEntryType;
use App\Models\Employment;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AdjustLeaveBalanceAction
{
    public function handle(
        Employment $employment,
        LeaveType $leaveType,
        float $units,
        CarbonInterface $effectiveOn,
        string $reason,
        User $actor,
        LeaveLedgerEntryType $entryType = LeaveLedgerEntryType::Adjustment,
    ): LeaveLedgerEntry {
        return DB::transaction(function () use ($actor, $effectiveOn, $employment, $entryType, $leaveType, $reason, $units): LeaveLedgerEntry {
            Gate::forUser($actor)->authorize('adjust', [LeaveLedgerEntry::class, $employment]);

            if ((int) $employment->company_id !== (int) $leaveType->company_id) {
                throw ValidationException::withMessages(['leave_type_id' => 'The leave type must belong to the Employment company.']);
            }

            if ($units === 0.0 || blank($reason)) {
                throw ValidationException::withMessages(['units' => 'Non-zero units and a reason are required.']);
            }

            return LeaveLedgerEntry::query()->create([
                'company_id' => $employment->company_id,
                'employment_id' => $employment->getKey(),
                'leave_type_id' => $leaveType->getKey(),
                'entry_type' => $entryType,
                'effective_on' => $effectiveOn,
                'units' => $units,
                'reason' => $reason,
                'recorded_by_id' => $actor->getKey(),
            ]);
        }, 3);
    }
}
