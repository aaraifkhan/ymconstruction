<?php

namespace App\Actions\HR;

use App\Enums\ClearanceArea;
use App\Enums\ClearanceSourceKind;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Enums\EmployeeClearanceItemStatus;
use App\Enums\EmployeeClearanceStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingType;
use App\Enums\EmploymentSeparationStatus;
use App\Models\ClearanceChecklistTemplate;
use App\Models\EmployeeAssetCustody;
use App\Models\EmployeeClearance;
use App\Models\EmployeeClearanceItem;
use App\Models\EmployeeFinancing;
use App\Models\EmploymentSeparation;
use App\Models\LeaveLedgerEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ManageEmployeeClearanceAction
{
    public function prepare(EmploymentSeparation $separation, User $actor): EmployeeClearance
    {
        Gate::forUser($actor)->authorize('prepare', [EmployeeClearance::class, $separation]);

        return DB::transaction(function () use ($separation, $actor): EmployeeClearance {
            $separation = EmploymentSeparation::query()->whereKey($separation)->lockForUpdate()->firstOrFail();
            if ($separation->status !== EmploymentSeparationStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Clearance may only be prepared for an approved separation.']);
            }
            $clearance = EmployeeClearance::query()->firstOrCreate(
                ['employment_separation_id' => $separation->getKey()],
                [
                    'company_id' => $separation->company_id,
                    'employment_id' => $separation->employment_id,
                    'reference_number' => sprintf('CLR-%06d', $separation->getKey()),
                    'prepared_by_id' => $actor->getKey(),
                ],
            );
            $this->syncItems($clearance);
            $this->audit($clearance, $actor, 'prepared');

            return $clearance->refresh()->load('items');
        }, 3);
    }

    public function refresh(EmployeeClearance $clearance, User $actor): EmployeeClearance
    {
        Gate::forUser($actor)->authorize('refresh', $clearance);

        return DB::transaction(function () use ($clearance, $actor): EmployeeClearance {
            $clearance = EmployeeClearance::query()->whereKey($clearance)->lockForUpdate()->firstOrFail();
            if ($clearance->status === EmployeeClearanceStatus::Completed) {
                throw ValidationException::withMessages(['status' => 'Completed clearance evidence is immutable.']);
            }
            $this->syncItems($clearance);
            $this->audit($clearance, $actor, 'refreshed');

            return $clearance->refresh()->load('items');
        }, 3);
    }

    public function submit(EmployeeClearance $clearance, User $actor): EmployeeClearance
    {
        Gate::forUser($actor)->authorize('submit', $clearance);

        return DB::transaction(function () use ($clearance, $actor): EmployeeClearance {
            $clearance = EmployeeClearance::query()->whereKey($clearance)->lockForUpdate()->firstOrFail();
            if ($clearance->status !== EmployeeClearanceStatus::Draft || ! $clearance->items()->exists()) {
                throw ValidationException::withMessages(['status' => 'Only a prepared draft clearance may be submitted.']);
            }
            $clearance->update([
                'status' => EmployeeClearanceStatus::InProgress,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
            ]);
            $this->audit($clearance, $actor, 'submitted');

            return $clearance->refresh();
        }, 3);
    }

    public function decideItem(
        EmployeeClearanceItem $item,
        EmployeeClearanceItemStatus $decision,
        ?string $notes,
        User $actor,
        ?string $recoveryAmount = null,
        ?string $recoveryNotes = null,
    ): EmployeeClearanceItem {
        Gate::forUser($actor)->authorize('decide', $item);
        if ($decision === EmployeeClearanceItemStatus::Waived) {
            Gate::forUser($actor)->authorize('waive', $item);
        }
        if ($decision === EmployeeClearanceItemStatus::RecoveryRecommended) {
            Gate::forUser($actor)->authorize('recommendRecovery', $item);
        }

        return DB::transaction(function () use ($item, $decision, $notes, $actor, $recoveryAmount, $recoveryNotes): EmployeeClearanceItem {
            $item = EmployeeClearanceItem::query()->whereKey($item)->lockForUpdate()->firstOrFail();
            $clearance = EmployeeClearance::query()->whereKey($item->employee_clearance_id)->lockForUpdate()->firstOrFail();
            if (! in_array($clearance->status, [
                EmployeeClearanceStatus::InProgress, EmployeeClearanceStatus::Blocked,
            ], true)
                || $decision === EmployeeClearanceItemStatus::Pending
                || ($decision === EmployeeClearanceItemStatus::Waived && blank($notes))
                || ($decision === EmployeeClearanceItemStatus::RecoveryRecommended
                    && ($recoveryAmount === null || bccomp($recoveryAmount, '0', 4) !== 1))) {
                throw ValidationException::withMessages(['status' => 'This departmental clearance decision is incomplete or not allowed.']);
            }
            $item->update([
                'status' => $decision,
                'decision_notes' => $notes,
                'recovery_recommendation_amount' => $decision === EmployeeClearanceItemStatus::RecoveryRecommended
                    ? $recoveryAmount : null,
                'recovery_recommendation_notes' => $decision === EmployeeClearanceItemStatus::RecoveryRecommended
                    ? $recoveryNotes : null,
                'decided_by_id' => $actor->getKey(),
                'decided_at' => now(),
            ]);
            $clearance->update([
                'status' => $clearance->items()->where('status', EmployeeClearanceItemStatus::Blocked)->exists()
                    ? EmployeeClearanceStatus::Blocked
                    : EmployeeClearanceStatus::InProgress,
            ]);
            $this->audit($clearance, $actor, 'item_decided', [
                'item_id' => $item->getKey(),
                'area' => $item->area->value,
                'decision' => $decision->value,
                'financial_posting_created' => false,
            ]);

            return $item->refresh();
        }, 3);
    }

    public function complete(EmployeeClearance $clearance, User $actor): EmployeeClearance
    {
        Gate::forUser($actor)->authorize('complete', $clearance);

        return DB::transaction(function () use ($clearance, $actor): EmployeeClearance {
            $clearance = EmployeeClearance::query()->whereKey($clearance)->lockForUpdate()->firstOrFail();
            $this->syncItems($clearance);
            $hasBlockingItems = $clearance->items()->where('is_mandatory', true)
                ->whereNotIn('status', [
                    EmployeeClearanceItemStatus::Cleared->value,
                    EmployeeClearanceItemStatus::RecoveryRecommended->value,
                    EmployeeClearanceItemStatus::Waived->value,
                ])->exists();
            if (! in_array($clearance->status, [
                EmployeeClearanceStatus::InProgress, EmployeeClearanceStatus::Blocked,
            ], true) || $hasBlockingItems
                || (int) $clearance->submitted_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages([
                    'status' => 'Completion requires every mandatory area to resolve its items and an independent completer.',
                ]);
            }
            $clearance->update([
                'status' => EmployeeClearanceStatus::Completed,
                'completed_by_id' => $actor->getKey(),
                'completed_at' => now(),
            ]);
            $this->audit($clearance, $actor, 'completed', ['financial_posting_created' => false]);

            return $clearance->refresh();
        }, 3);
    }

    private function syncItems(EmployeeClearance $clearance): void
    {
        $definitions = $this->systemDefinitions($clearance);
        $templates = ClearanceChecklistTemplate::query()
            ->where('company_id', $clearance->company_id)->where('is_active', true)
            ->orderBy('sort_order')->get();
        foreach ($templates as $template) {
            $definitions->push([
                'clearance_checklist_template_id' => $template->getKey(),
                'source_kind' => ClearanceSourceKind::Configured,
                'source_key' => "configured:{$template->getKey()}",
                'area' => $template->area,
                'name' => $template->name,
                'is_mandatory' => $template->is_mandatory,
                'obligation_snapshot' => ['code' => $template->code, 'description' => $template->description],
            ]);
        }

        $activeKeys = [];
        foreach ($definitions as $definition) {
            $activeKeys[] = $definition['source_key'];
            EmployeeClearanceItem::query()->updateOrCreate(
                [
                    'employee_clearance_id' => $clearance->getKey(),
                    'source_key' => $definition['source_key'],
                ],
                ['company_id' => $clearance->company_id, ...$definition],
            );
        }
        EmployeeClearanceItem::query()
            ->where('employee_clearance_id', $clearance->getKey())
            ->whereNotIn('source_key', $activeKeys)
            ->whereIn('status', [
                EmployeeClearanceItemStatus::Pending->value,
                EmployeeClearanceItemStatus::Blocked->value,
            ])->update([
                'status' => EmployeeClearanceItemStatus::Cleared->value,
                'decision_notes' => encrypt('Underlying obligation resolved during clearance refresh.'),
                'decided_at' => now(),
            ]);
        $clearance->update([
            'source_checksum' => hash('sha256', json_encode($activeKeys, JSON_THROW_ON_ERROR)),
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function systemDefinitions(EmployeeClearance $clearance): Collection
    {
        $definitions = collect();
        $custodies = EmployeeAssetCustody::query()
            ->with('fixedAsset:id,asset_number,name')
            ->where('company_id', $clearance->company_id)
            ->where('employment_id', $clearance->employment_id)
            ->whereIn('status', [
                EmployeeAssetCustodyStatus::Issued->value,
                EmployeeAssetCustodyStatus::Acknowledged->value,
                EmployeeAssetCustodyStatus::ReturnPending->value,
                EmployeeAssetCustodyStatus::Exception->value,
            ])->get();
        foreach ($custodies as $custody) {
            $definitions->push([
                'source_kind' => ClearanceSourceKind::Asset,
                'source_key' => "asset:{$custody->getKey()}",
                'area' => ClearanceArea::Assets,
                'name' => "Return asset {$custody->fixedAsset->asset_number}: {$custody->fixedAsset->name}",
                'is_mandatory' => true,
                'obligation_snapshot' => [
                    'custody_id' => $custody->getKey(),
                    'asset_id' => $custody->fixed_asset_id,
                    'status' => $custody->status->value,
                    'exception_type' => $custody->exception_type?->value,
                ],
            ]);
        }

        $financings = EmployeeFinancing::query()
            ->where('company_id', $clearance->company_id)
            ->where('employment_id', $clearance->employment_id)
            ->whereIn('status', [
                EmployeeFinancingStatus::DisbursementPending->value,
                EmployeeFinancingStatus::Active->value,
            ])->get();
        foreach ($financings as $financing) {
            if (bccomp($financing->outstandingAmount(), '0', 4) !== 1) {
                continue;
            }
            $kind = $financing->type === EmployeeFinancingType::Loan
                ? ClearanceSourceKind::Loan : ClearanceSourceKind::Advance;
            $definitions->push([
                'source_kind' => $kind,
                'source_key' => "{$kind->value}:{$financing->getKey()}",
                'area' => $kind === ClearanceSourceKind::Loan ? ClearanceArea::Loans : ClearanceArea::Finance,
                'name' => "{$financing->type->label()} {$financing->reference_number} settlement",
                'is_mandatory' => true,
                'obligation_snapshot' => [
                    'financing_id' => $financing->getKey(),
                    'outstanding_amount' => $financing->outstandingAmount(),
                    'currency_code' => $financing->currency_code,
                ],
            ]);
        }

        $leaveEntries = LeaveLedgerEntry::query()
            ->where('company_id', $clearance->company_id)
            ->where('employment_id', $clearance->employment_id);
        $definitions->push([
            'source_kind' => ClearanceSourceKind::Leave,
            'source_key' => 'leave:review',
            'area' => ClearanceArea::Hr,
            'name' => 'Leave balance and encashment review',
            'is_mandatory' => true,
            'obligation_snapshot' => [
                'ledger_entries' => (clone $leaveEntries)->count(),
                'net_units' => (string) (clone $leaveEntries)->sum('units'),
            ],
        ]);
        $definitions->push([
            'source_kind' => ClearanceSourceKind::Handover,
            'source_key' => 'handover:review',
            'area' => ClearanceArea::Manager,
            'name' => 'Work, records, and responsibility handover',
            'is_mandatory' => true,
            'obligation_snapshot' => [
                'separation_id' => $clearance->employment_separation_id,
                'handover_notes_recorded' => filled($clearance->separation()->value('handover_notes')),
            ],
        ]);

        return $definitions;
    }

    /** @param array<string, mixed> $properties */
    private function audit(EmployeeClearance $clearance, User $actor, string $event, array $properties = []): void
    {
        activity('employee_clearances')->causedBy($actor)->performedOn($clearance)
            ->event($event)->withProperties([
                'company_id' => $clearance->company_id,
                'reference_number' => $clearance->reference_number,
                ...$properties,
            ])->log("{$event} employee clearance");
    }
}
