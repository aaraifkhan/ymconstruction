<?php

namespace App\Actions\HR;

use App\Enums\AssetCustodyEventType;
use App\Enums\AssetCustodyExceptionType;
use App\Enums\AssetStatus;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Models\AssetTransfer;
use App\Models\EmployeeAssetCustody;
use App\Models\EmployeeAssetCustodyEvent;
use App\Models\Employment;
use App\Models\FixedAsset;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransitionEmployeeAssetCustodyAction
{
    public function issue(EmployeeAssetCustody $custody, User $actor): EmployeeAssetCustody
    {
        Gate::forUser($actor)->authorize('issue', $custody);

        return DB::transaction(function () use ($custody, $actor): EmployeeAssetCustody {
            $custody = EmployeeAssetCustody::query()->whereKey($custody)->lockForUpdate()->firstOrFail();
            $asset = FixedAsset::query()->whereKey($custody->fixed_asset_id)->lockForUpdate()->firstOrFail();

            if ($custody->status !== EmployeeAssetCustodyStatus::Draft
                || $asset->status !== AssetStatus::Active
                || ($custody->prepared_by_id !== null && (int) $custody->prepared_by_id === (int) $actor->getKey())
                || EmployeeAssetCustody::query()
                    ->where('fixed_asset_id', $asset->getKey())
                    ->whereNot('id', $custody->getKey())
                    ->whereIn('status', $this->openStatuses())
                    ->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Issue requires an active unassigned asset, draft custody, and an independent issuer.',
                ]);
            }

            $custody->update([
                'reference_number' => filled($custody->reference_number)
                    ? $custody->reference_number
                    : sprintf('ACI-%06d', $custody->getKey()),
                'status' => EmployeeAssetCustodyStatus::Issued,
                'issued_by_id' => $actor->getKey(),
                'issued_at' => now(),
            ]);
            $asset->update([
                'custodian_employment_id' => $custody->employment_id,
                'location' => $custody->issued_location ?? $asset->location,
            ]);
            $this->recordEvent($custody, AssetCustodyEventType::Issued, $custody->issued_on, $actor);

            return $custody->refresh();
        }, 3);
    }

    public function acknowledge(EmployeeAssetCustody $custody, User $actor): EmployeeAssetCustody
    {
        Gate::forUser($actor)->authorize('acknowledge', $custody);

        return DB::transaction(function () use ($custody, $actor): EmployeeAssetCustody {
            $custody = EmployeeAssetCustody::query()->whereKey($custody)->lockForUpdate()->firstOrFail();
            $employment = $custody->employment()->with('employee')->firstOrFail();
            if ($custody->status !== EmployeeAssetCustodyStatus::Issued
                || (int) $employment->employee?->user_id !== (int) $actor->getKey()
                || (int) $custody->issued_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages([
                    'status' => 'Only the receiving employee may independently acknowledge an issued asset.',
                ]);
            }
            $custody->update([
                'status' => EmployeeAssetCustodyStatus::Acknowledged,
                'acknowledged_by_id' => $actor->getKey(),
                'acknowledged_at' => now(),
            ]);
            $this->recordEvent($custody, AssetCustodyEventType::Acknowledged, now(), $actor);

            return $custody->refresh();
        }, 3);
    }

    public function requestReturn(EmployeeAssetCustody $custody, string $reason, User $actor): EmployeeAssetCustody
    {
        Gate::forUser($actor)->authorize('requestReturn', $custody);

        return DB::transaction(function () use ($custody, $reason, $actor): EmployeeAssetCustody {
            $custody = EmployeeAssetCustody::query()->whereKey($custody)->lockForUpdate()->firstOrFail();
            if (! in_array($custody->status, [
                EmployeeAssetCustodyStatus::Issued, EmployeeAssetCustodyStatus::Acknowledged,
            ], true) || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'Only an open custody may enter return processing.']);
            }
            $custody->update([
                'status' => EmployeeAssetCustodyStatus::ReturnPending,
                'return_requested_by_id' => $actor->getKey(),
                'return_requested_at' => now(),
            ]);
            $this->recordEvent($custody, AssetCustodyEventType::ReturnRequested, now(), $actor, $reason);

            return $custody->refresh();
        }, 3);
    }

    public function acceptReturn(
        EmployeeAssetCustody $custody,
        CarbonInterface $returnedOn,
        string $condition,
        ?string $notes,
        User $actor,
    ): EmployeeAssetCustody {
        Gate::forUser($actor)->authorize('acceptReturn', $custody);

        return DB::transaction(function () use ($custody, $returnedOn, $condition, $notes, $actor): EmployeeAssetCustody {
            $custody = EmployeeAssetCustody::query()->whereKey($custody)->lockForUpdate()->firstOrFail();
            $asset = FixedAsset::query()->whereKey($custody->fixed_asset_id)->lockForUpdate()->firstOrFail();
            if (! in_array($custody->status, [
                EmployeeAssetCustodyStatus::ReturnPending, EmployeeAssetCustodyStatus::Exception,
            ], true)
                || (int) $custody->return_requested_by_id === (int) $actor->getKey()
                || $returnedOn->lt($custody->issued_on)
                || blank($condition)) {
                throw ValidationException::withMessages([
                    'status' => 'Return acceptance requires a valid date, condition, and independent receiver.',
                ]);
            }
            $custody->update([
                'status' => EmployeeAssetCustodyStatus::Returned,
                'returned_on' => $returnedOn,
                'return_condition' => $condition,
                'return_notes' => $notes,
                'returned_by_id' => $actor->getKey(),
                'returned_at' => now(),
            ]);
            $asset->update(['custodian_employment_id' => null]);
            $this->recordEvent($custody, AssetCustodyEventType::Returned, $returnedOn, $actor, $notes);

            return $custody->refresh();
        }, 3);
    }

    public function transfer(
        EmployeeAssetCustody $custody,
        Employment $toEmployment,
        CarbonInterface $effectiveOn,
        string $condition,
        ?string $reason,
        User $actor,
    ): EmployeeAssetCustody {
        Gate::forUser($actor)->authorize('transfer', $custody);

        return DB::transaction(function () use ($custody, $toEmployment, $effectiveOn, $condition, $reason, $actor): EmployeeAssetCustody {
            $custody = EmployeeAssetCustody::query()->whereKey($custody)->lockForUpdate()->firstOrFail();
            $asset = FixedAsset::query()->whereKey($custody->fixed_asset_id)->lockForUpdate()->firstOrFail();
            $toEmployment = Employment::query()->whereKey($toEmployment)->lockForUpdate()->firstOrFail();
            if (! in_array($custody->status, [
                EmployeeAssetCustodyStatus::Issued, EmployeeAssetCustodyStatus::Acknowledged,
            ], true)
                || (int) $toEmployment->company_id !== (int) $custody->company_id
                || (int) $toEmployment->getKey() === (int) $custody->employment_id
                || $effectiveOn->lt($custody->issued_on)
                || blank($condition)) {
                throw ValidationException::withMessages(['employment_id' => 'Transfer requires another same-company Employment and valid evidence.']);
            }

            $custody->update(['status' => EmployeeAssetCustodyStatus::Transferred]);
            $this->recordEvent($custody, AssetCustodyEventType::Transferred, $effectiveOn, $actor, $reason);
            AssetTransfer::query()->create([
                'company_id' => $custody->company_id,
                'fixed_asset_id' => $asset->getKey(),
                'from_custodian_employment_id' => $custody->employment_id,
                'to_custodian_employment_id' => $toEmployment->getKey(),
                'from_project_id' => $asset->project_id,
                'to_project_id' => $asset->project_id,
                'from_project_site_id' => $asset->project_site_id,
                'to_project_site_id' => $asset->project_site_id,
                'from_cost_center_id' => $asset->cost_center_id,
                'to_cost_center_id' => $asset->cost_center_id,
                'from_location' => $asset->location,
                'to_location' => $asset->location,
                'effective_on' => $effectiveOn,
                'reason' => $reason,
                'transferred_by_id' => $actor->getKey(),
            ]);
            $newCustody = EmployeeAssetCustody::query()->create([
                'company_id' => $custody->company_id,
                'fixed_asset_id' => $asset->getKey(),
                'employment_id' => $toEmployment->getKey(),
                'reference_number' => sprintf('ACI-%06d-T', $custody->getKey()),
                'status' => EmployeeAssetCustodyStatus::Issued,
                'issued_on' => $effectiveOn,
                'issued_condition' => $condition,
                'accessories' => $custody->accessories,
                'issued_location' => $asset->location,
                'issue_notes' => $reason,
                'prepared_by_id' => $actor->getKey(),
                'issued_by_id' => $actor->getKey(),
                'issued_at' => now(),
            ]);
            $asset->update(['custodian_employment_id' => $toEmployment->getKey()]);
            $this->recordEvent($newCustody, AssetCustodyEventType::Issued, $effectiveOn, $actor, $reason);

            return $newCustody->refresh();
        }, 3);
    }

    public function reportException(
        EmployeeAssetCustody $custody,
        AssetCustodyExceptionType $type,
        string $notes,
        ?string $recommendedAmount,
        ?string $recommendationNotes,
        User $actor,
    ): EmployeeAssetCustody {
        Gate::forUser($actor)->authorize('reportException', $custody);

        return DB::transaction(function () use ($custody, $type, $notes, $recommendedAmount, $recommendationNotes, $actor): EmployeeAssetCustody {
            $custody = EmployeeAssetCustody::query()->whereKey($custody)->lockForUpdate()->firstOrFail();
            if (! in_array($custody->status, [
                EmployeeAssetCustodyStatus::Issued, EmployeeAssetCustodyStatus::Acknowledged,
                EmployeeAssetCustodyStatus::ReturnPending,
            ], true) || blank($notes)
                || ($recommendedAmount !== null && bccomp($recommendedAmount, '0', 4) === -1)) {
                throw ValidationException::withMessages(['status' => 'Damage or loss requires an open custody and complete evidence.']);
            }
            $custody->update([
                'status' => EmployeeAssetCustodyStatus::Exception,
                'exception_type' => $type,
                'exception_notes' => $notes,
                'recovery_recommendation_amount' => $recommendedAmount,
                'recovery_recommendation_notes' => $recommendationNotes,
            ]);
            $event = $type === AssetCustodyExceptionType::Damage
                ? AssetCustodyEventType::DamageReported
                : AssetCustodyEventType::LossReported;
            $this->recordEvent($custody, $event, now(), $actor, $notes);

            return $custody->refresh();
        }, 3);
    }

    public function resolveException(EmployeeAssetCustody $custody, string $reason, User $actor): EmployeeAssetCustody
    {
        Gate::forUser($actor)->authorize('resolveException', $custody);

        return DB::transaction(function () use ($custody, $reason, $actor): EmployeeAssetCustody {
            $custody = EmployeeAssetCustody::query()->whereKey($custody)->lockForUpdate()->firstOrFail();
            if ($custody->status !== EmployeeAssetCustodyStatus::Exception || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'Only an evidenced exception may be resolved.']);
            }
            $custody->update([
                'status' => EmployeeAssetCustodyStatus::Acknowledged,
                'exception_type' => null,
                'exception_notes' => null,
            ]);
            $this->recordEvent($custody, AssetCustodyEventType::ExceptionResolved, now(), $actor, $reason);

            return $custody->refresh();
        }, 3);
    }

    private function recordEvent(
        EmployeeAssetCustody $custody,
        AssetCustodyEventType $event,
        CarbonInterface $effectiveOn,
        User $actor,
        ?string $reason = null,
    ): void {
        EmployeeAssetCustodyEvent::query()->create([
            'company_id' => $custody->company_id,
            'employee_asset_custody_id' => $custody->getKey(),
            'fixed_asset_id' => $custody->fixed_asset_id,
            'employment_id' => $custody->employment_id,
            'event_type' => $event,
            'effective_on' => $effectiveOn,
            'snapshot' => [
                'status' => $custody->status->value,
                'condition' => $custody->return_condition ?? $custody->issued_condition,
                'location' => $custody->issued_location,
                'exception_type' => $custody->exception_type?->value,
                'recovery_recommendation_amount' => $custody->recovery_recommendation_amount,
            ],
            'reason' => $reason,
            'actor_id' => $actor->getKey(),
        ]);
    }

    /** @return list<string> */
    private function openStatuses(): array
    {
        return array_map(
            fn (EmployeeAssetCustodyStatus $status): string => $status->value,
            array_filter(EmployeeAssetCustodyStatus::cases(), fn (EmployeeAssetCustodyStatus $status): bool => $status->isOpen()),
        );
    }
}
