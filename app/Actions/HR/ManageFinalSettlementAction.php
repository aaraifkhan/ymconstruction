<?php

namespace App\Actions\HR;

use App\Enums\AccountingMappingKey;
use App\Enums\EmployeeClearanceItemStatus;
use App\Enums\EmployeeClearanceStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingType;
use App\Enums\EmploymentSeparationStatus;
use App\Enums\FinalSettlementComponentNature;
use App\Enums\FinalSettlementComponentType;
use App\Enums\FinalSettlementStatus;
use App\Models\AccountingMapping;
use App\Models\EmployeeClearance;
use App\Models\EmployeeFinancing;
use App\Models\EmploymentSeparation;
use App\Models\FinalSettlement;
use App\Models\FinalSettlementAccountMapping;
use App\Models\FinalSettlementLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ManageFinalSettlementAction
{
    public function prepare(EmploymentSeparation $separation, User $actor): FinalSettlement
    {
        Gate::forUser($actor)->authorize('prepare', [FinalSettlement::class, $separation]);

        return DB::transaction(function () use ($separation, $actor): FinalSettlement {
            $separation = EmploymentSeparation::query()->whereKey($separation)->lockForUpdate()->firstOrFail();
            $clearance = EmployeeClearance::query()->where('employment_separation_id', $separation->getKey())
                ->lockForUpdate()->first();
            if ($separation->status !== EmploymentSeparationStatus::Approved
                || $clearance?->status !== EmployeeClearanceStatus::Completed) {
                throw ValidationException::withMessages([
                    'clearance' => 'Final Settlement requires an approved separation and completed clearance.',
                ]);
            }

            $settlement = FinalSettlement::query()->firstOrCreate(
                ['employment_separation_id' => $separation->getKey()],
                [
                    'company_id' => $separation->company_id,
                    'employment_id' => $separation->employment_id,
                    'employee_clearance_id' => $clearance->getKey(),
                    'reference_number' => sprintf('FNS-%06d', $separation->getKey()),
                    'cutoff_date' => $separation->approved_last_working_date,
                    'earning_total' => '0.0000',
                    'recovery_total' => '0.0000',
                    'net_amount' => '0.0000',
                    'prepared_by_id' => $actor->getKey(),
                ],
            );
            if ($settlement->isEditable()) {
                $this->syncSourceRecoveries($settlement);
                $this->recalculate($settlement);
            }
            $this->audit($settlement, $actor, 'prepared');

            return $settlement->refresh()->load('lines');
        }, 3);
    }

    /** @param array<string, mixed> $evidence */
    public function addApprovedComponent(
        FinalSettlement $settlement,
        FinalSettlementComponentType $type,
        string $quantity,
        string $rate,
        string $description,
        string $sourceReference,
        array $evidence,
        User $actor,
    ): FinalSettlementLine {
        Gate::forUser($actor)->authorize('update', $settlement);

        return DB::transaction(function () use (
            $settlement, $type, $quantity, $rate, $description, $sourceReference, $evidence, $actor,
        ): FinalSettlementLine {
            $settlement = FinalSettlement::query()->whereKey($settlement)->lockForUpdate()->firstOrFail();
            if (! $settlement->isEditable() || blank($sourceReference) || $evidence === []) {
                throw ValidationException::withMessages([
                    'source_reference' => 'Editable settlement, approved source reference, and evidence are required.',
                ]);
            }
            if ($type->usesEmployeeAdvancesMapping()) {
                throw ValidationException::withMessages([
                    'component_type' => 'Loan and Advance recoveries are synchronized from their approved subledger.',
                ]);
            }
            $amount = bcmul($quantity, $rate, 4);
            if (bccomp($quantity, '0', 4) !== 1 || bccomp($rate, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'Quantity and rate must be positive.']);
            }
            $sourceChecksum = hash('sha256', json_encode([
                'type' => $type->value,
                'quantity' => $quantity,
                'rate' => $rate,
                'reference' => $sourceReference,
                'evidence' => $evidence,
            ], JSON_THROW_ON_ERROR));
            $line = $settlement->lines()->create([
                'company_id' => $settlement->company_id,
                'line_number' => ((int) $settlement->lines()->max('line_number')) + 1,
                'component_type' => $type,
                'nature' => $type->nature(),
                'account_id' => $this->accountId($settlement->company_id, $type),
                'description' => $description,
                'quantity' => $quantity,
                'rate' => $rate,
                'amount' => $amount,
                'source_reference' => $sourceReference,
                'evidence_snapshot' => $evidence,
                'source_checksum' => $sourceChecksum,
                'idempotency_key' => "approved:{$sourceChecksum}",
            ]);
            $this->recalculate($settlement);
            $this->audit($settlement, $actor, 'component_added', [
                'line_id' => $line->getKey(), 'component_type' => $type->value,
            ]);

            return $line->refresh();
        }, 3);
    }

    public function submit(FinalSettlement $settlement, User $actor): FinalSettlement
    {
        Gate::forUser($actor)->authorize('submit', $settlement);

        return DB::transaction(function () use ($settlement, $actor): FinalSettlement {
            $settlement = FinalSettlement::query()->whereKey($settlement)->lockForUpdate()->firstOrFail();
            $this->assertSourcesCurrent($settlement);
            if (! $settlement->isEditable() || ! $settlement->lines()->exists()
                || (int) $settlement->prepared_by_id !== (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'Only the preparer may submit a populated draft or rejected settlement.']);
            }
            $this->recalculate($settlement);
            $settlement->update([
                'status' => FinalSettlementStatus::Submitted,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'reviewed_by_id' => null,
                'reviewed_at' => null,
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            $this->audit($settlement, $actor, 'submitted');

            return $settlement->refresh();
        }, 3);
    }

    public function review(FinalSettlement $settlement, User $actor): FinalSettlement
    {
        Gate::forUser($actor)->authorize('review', $settlement);

        return DB::transaction(function () use ($settlement, $actor): FinalSettlement {
            $settlement = FinalSettlement::query()->whereKey($settlement)->lockForUpdate()->firstOrFail();
            $this->assertSourcesCurrent($settlement);
            if ($settlement->status !== FinalSettlementStatus::Submitted
                || (int) $settlement->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'Review requires a submitted settlement and an independent reviewer.']);
            }
            $settlement->update([
                'status' => FinalSettlementStatus::Reviewed,
                'reviewed_by_id' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);
            $this->audit($settlement, $actor, 'reviewed');

            return $settlement->refresh();
        }, 3);
    }

    public function approve(FinalSettlement $settlement, User $actor): FinalSettlement
    {
        Gate::forUser($actor)->authorize('approve', $settlement);

        return DB::transaction(function () use ($settlement, $actor): FinalSettlement {
            $settlement = FinalSettlement::query()->whereKey($settlement)->lockForUpdate()->firstOrFail();
            $this->assertSourcesCurrent($settlement);
            if ($settlement->status !== FinalSettlementStatus::Reviewed
                || in_array((int) $actor->getKey(), [
                    (int) $settlement->prepared_by_id, (int) $settlement->reviewed_by_id,
                ], true)) {
                throw ValidationException::withMessages(['status' => 'Approval requires an independently reviewed settlement and a third actor.']);
            }
            $settlement->update([
                'status' => FinalSettlementStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            $this->audit($settlement, $actor, 'approved');

            return $settlement->refresh();
        }, 3);
    }

    public function reject(FinalSettlement $settlement, User $actor, string $reason): FinalSettlement
    {
        Gate::forUser($actor)->authorize('reject', $settlement);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        return DB::transaction(function () use ($settlement, $actor, $reason): FinalSettlement {
            $settlement = FinalSettlement::query()->whereKey($settlement)->lockForUpdate()->firstOrFail();
            if (! in_array($settlement->status, [
                FinalSettlementStatus::Submitted, FinalSettlementStatus::Reviewed,
            ], true)) {
                throw ValidationException::withMessages(['status' => 'Only a submitted or reviewed settlement may be rejected.']);
            }
            $settlement->update([
                'status' => FinalSettlementStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            $this->audit($settlement, $actor, 'rejected');

            return $settlement->refresh();
        }, 3);
    }

    private function syncSourceRecoveries(FinalSettlement $settlement): void
    {
        $financings = EmployeeFinancing::query()->where('company_id', $settlement->company_id)
            ->where('employment_id', $settlement->employment_id)
            ->where('status', EmployeeFinancingStatus::Active)->lockForUpdate()->get()
            ->filter(fn (EmployeeFinancing $financing): bool => bccomp($financing->outstandingAmount(), '0', 4) === 1);
        $financingLines = $settlement->lines()->whereNotNull('employee_financing_id');
        $financings->isEmpty()
            ? $financingLines->delete()
            : $financingLines->whereNotIn('employee_financing_id', $financings->pluck('id'))->delete();
        foreach ($financings as $financing) {
            $amount = $financing->outstandingAmount();
            $type = $financing->type === EmployeeFinancingType::Loan
                ? FinalSettlementComponentType::LoanRecovery
                : FinalSettlementComponentType::AdvanceRecovery;
            $snapshot = [
                'financing_id' => $financing->getKey(),
                'reference_number' => $financing->reference_number,
                'outstanding_amount' => $amount,
                'cutoff_date' => $settlement->cutoff_date->toDateString(),
            ];
            $this->upsertSourceLine(
                $settlement,
                $type,
                $amount,
                "Outstanding {$financing->type->label()} {$financing->reference_number}",
                "financing:{$financing->getKey()}",
                $snapshot,
                employeeFinancingId: $financing->getKey(),
            );
        }

        $items = $settlement->clearance->items()
            ->where('status', EmployeeClearanceItemStatus::RecoveryRecommended)->lockForUpdate()->get()
            ->filter(fn ($item): bool => bccomp((string) $item->recovery_recommendation_amount, '0', 4) === 1);
        $clearanceLines = $settlement->lines()->whereNotNull('employee_clearance_item_id');
        $items->isEmpty()
            ? $clearanceLines->delete()
            : $clearanceLines->whereNotIn('employee_clearance_item_id', $items->pluck('id'))->delete();
        foreach ($items as $item) {
            $amount = (string) $item->recovery_recommendation_amount;
            $this->upsertSourceLine(
                $settlement,
                FinalSettlementComponentType::AssetRecovery,
                $amount,
                $item->name,
                "clearance:{$item->getKey()}",
                [
                    'clearance_item_id' => $item->getKey(),
                    'source_key' => $item->source_key,
                    'decision_notes' => $item->decision_notes,
                    'recovery_notes' => $item->recovery_recommendation_notes,
                ],
                employeeClearanceItemId: $item->getKey(),
            );
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function upsertSourceLine(
        FinalSettlement $settlement,
        FinalSettlementComponentType $type,
        string $amount,
        string $description,
        string $sourceReference,
        array $snapshot,
        ?int $employeeFinancingId = null,
        ?int $employeeClearanceItemId = null,
    ): void {
        $checksum = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
        FinalSettlementLine::query()->updateOrCreate(
            ['final_settlement_id' => $settlement->getKey(), 'idempotency_key' => $sourceReference],
            [
                'company_id' => $settlement->company_id,
                'line_number' => FinalSettlementLine::query()
                    ->where('final_settlement_id', $settlement->getKey())
                    ->where('idempotency_key', $sourceReference)->value('line_number')
                    ?? ((int) $settlement->lines()->max('line_number')) + 1,
                'component_type' => $type,
                'nature' => $type->nature(),
                'employee_financing_id' => $employeeFinancingId,
                'employee_clearance_item_id' => $employeeClearanceItemId,
                'account_id' => $this->accountId($settlement->company_id, $type),
                'description' => $description,
                'quantity' => '1.0000',
                'rate' => $amount,
                'amount' => $amount,
                'source_reference' => $sourceReference,
                'evidence_snapshot' => $snapshot,
                'source_checksum' => $checksum,
            ],
        );
    }

    private function accountId(int $companyId, FinalSettlementComponentType $type): int
    {
        if ($type->usesEmployeeAdvancesMapping()) {
            $accountId = AccountingMapping::query()->where('company_id', $companyId)
                ->where('system_key', AccountingMappingKey::EmployeeAdvances)
                ->where('is_active', true)->value('account_id');
        } else {
            $accountId = FinalSettlementAccountMapping::query()->where('company_id', $companyId)
                ->where('component_type', $type)->where('is_active', true)->value('account_id');
        }
        if ($accountId === null) {
            throw ValidationException::withMessages([
                'account_mapping' => "Missing active {$type->label()} Final Settlement account mapping.",
            ]);
        }

        return (int) $accountId;
    }

    private function recalculate(FinalSettlement $settlement): void
    {
        $lines = $settlement->lines()->get();
        $earnings = $lines->where('nature', FinalSettlementComponentNature::Earning)
            ->reduce(fn (string $total, FinalSettlementLine $line): string => bcadd($total, (string) $line->amount, 4), '0.0000');
        $recoveries = $lines->where('nature', FinalSettlementComponentNature::Recovery)
            ->reduce(fn (string $total, FinalSettlementLine $line): string => bcadd($total, (string) $line->amount, 4), '0.0000');
        $difference = bcsub($earnings, $recoveries, 4);
        $settlement->update([
            'earning_total' => $earnings,
            'recovery_total' => $recoveries,
            'net_amount' => ltrim($difference, '-') ?: '0.0000',
            'balance_direction' => bccomp($difference, '0', 4) === -1 ? 'receivable' : 'payable',
            'source_checksum' => hash('sha256', $lines->pluck('source_checksum')->implode('|')),
        ]);
    }

    private function assertSourcesCurrent(FinalSettlement $settlement): void
    {
        $clearance = $settlement->clearance()->lockForUpdate()->firstOrFail();
        if ($clearance->status !== EmployeeClearanceStatus::Completed
            || $clearance->items()->where('is_mandatory', true)
                ->whereNotIn('status', [
                    EmployeeClearanceItemStatus::Cleared->value,
                    EmployeeClearanceItemStatus::RecoveryRecommended->value,
                    EmployeeClearanceItemStatus::Waived->value,
                ])->exists()) {
            throw ValidationException::withMessages(['clearance' => 'Mandatory clearance must remain completed and fully resolved.']);
        }

        $financingLines = $settlement->lines()->whereNotNull('employee_financing_id')->get();
        $activeFinancings = EmployeeFinancing::query()
            ->where('company_id', $settlement->company_id)
            ->where('employment_id', $settlement->employment_id)
            ->where('status', EmployeeFinancingStatus::Active)
            ->lockForUpdate()
            ->get()
            ->filter(fn (EmployeeFinancing $financing): bool => bccomp($financing->outstandingAmount(), '0', 4) === 1);
        if ($financingLines->pluck('employee_financing_id')->sort()->values()->all()
            !== $activeFinancings->pluck('id')->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'financing' => 'Active financing sources changed; refresh the draft settlement before submission.',
            ]);
        }
        foreach ($financingLines as $line) {
            $financing = EmployeeFinancing::query()->whereKey($line->employee_financing_id)->lockForUpdate()->firstOrFail();
            if (bccomp((string) $line->amount, $financing->outstandingAmount(), 4) !== 0) {
                throw ValidationException::withMessages(['financing' => 'Financing balance changed; refresh the draft settlement before submission.']);
            }
        }

        $recoveryItems = $clearance->items()
            ->where('status', EmployeeClearanceItemStatus::RecoveryRecommended)
            ->lockForUpdate()
            ->get()
            ->filter(fn ($item): bool => bccomp((string) $item->recovery_recommendation_amount, '0', 4) === 1);
        $recoveryLines = $settlement->lines()->whereNotNull('employee_clearance_item_id')->get();
        if ($recoveryLines->pluck('employee_clearance_item_id')->sort()->values()->all()
            !== $recoveryItems->pluck('id')->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'clearance' => 'Clearance recovery sources changed; refresh the draft settlement before submission.',
            ]);
        }
        foreach ($recoveryLines as $line) {
            $item = $recoveryItems->firstWhere('id', $line->employee_clearance_item_id);
            if ($item === null
                || bccomp((string) $line->amount, (string) $item->recovery_recommendation_amount, 4) !== 0) {
                throw ValidationException::withMessages([
                    'clearance' => 'Clearance recovery amount changed; refresh the draft settlement before submission.',
                ]);
            }
        }
    }

    /** @param array<string, mixed> $properties */
    private function audit(FinalSettlement $settlement, User $actor, string $event, array $properties = []): void
    {
        activity('final_settlements')->causedBy($actor)->performedOn($settlement)->event($event)
            ->withProperties([
                'company_id' => $settlement->company_id,
                'reference_number' => $settlement->reference_number,
                ...$properties,
            ])->log("{$event} final settlement");
    }
}
