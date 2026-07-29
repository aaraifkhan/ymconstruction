<?php

namespace App\Actions\HR;

use App\Enums\EmployeeWarningStatus;
use App\Models\EmployeeWarning;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransitionEmployeeWarningAction
{
    public function issue(EmployeeWarning $warning, User $actor): EmployeeWarning
    {
        Gate::forUser($actor)->authorize('issue', $warning);

        return DB::transaction(function () use ($warning, $actor): EmployeeWarning {
            $warning = EmployeeWarning::query()->whereKey($warning)->lockForUpdate()->firstOrFail();
            if ($warning->status !== EmployeeWarningStatus::Draft
                || (int) $warning->created_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'A draft warning requires an independent issuer.']);
            }
            $warning->update([
                'reference_number' => filled($warning->reference_number)
                    ? $warning->reference_number
                    : sprintf('WRN-%06d', $warning->getKey()),
                'status' => EmployeeWarningStatus::Issued,
                'issued_by_id' => $actor->getKey(),
                'issued_at' => now(),
            ]);
            $this->audit($warning, $actor, 'issued');

            return $warning;
        }, 3);
    }

    public function respond(EmployeeWarning $warning, string $response, User $actor): EmployeeWarning
    {
        Gate::forUser($actor)->authorize('respond', $warning);

        return $this->transition($warning, $actor, [EmployeeWarningStatus::Issued], [
            'status' => EmployeeWarningStatus::Responded,
            'response' => $response,
            'responded_by_id' => $actor->getKey(),
            'responded_at' => now(),
        ], 'responded');
    }

    public function acknowledge(EmployeeWarning $warning, User $actor): EmployeeWarning
    {
        Gate::forUser($actor)->authorize('acknowledge', $warning);

        return $this->transition($warning, $actor, [
            EmployeeWarningStatus::Issued, EmployeeWarningStatus::Responded,
        ], [
            'status' => EmployeeWarningStatus::Acknowledged,
            'acknowledged_by_id' => $actor->getKey(),
            'acknowledged_at' => now(),
        ], 'acknowledged');
    }

    public function close(EmployeeWarning $warning, string $notes, User $actor): EmployeeWarning
    {
        Gate::forUser($actor)->authorize('close', $warning);

        return $this->transition($warning, $actor, [EmployeeWarningStatus::Acknowledged], [
            'status' => EmployeeWarningStatus::Closed,
            'closure_notes' => $notes,
            'closed_by_id' => $actor->getKey(),
            'closed_at' => now(),
        ], 'closed');
    }

    /** @param list<EmployeeWarningStatus> $from */
    private function transition(
        EmployeeWarning $warning,
        User $actor,
        array $from,
        array $attributes,
        string $event,
    ): EmployeeWarning {
        return DB::transaction(function () use ($warning, $actor, $from, $attributes, $event): EmployeeWarning {
            $warning = EmployeeWarning::query()->whereKey($warning)->lockForUpdate()->firstOrFail();
            if (! in_array($warning->status, $from, true)) {
                throw ValidationException::withMessages(['status' => "Warning cannot be {$event} from its current state."]);
            }
            $warning->update($attributes);
            $this->audit($warning, $actor, $event);

            return $warning;
        }, 3);
    }

    private function audit(EmployeeWarning $warning, User $actor, string $event): void
    {
        activity('employee_warnings')->causedBy($actor)->performedOn($warning)
            ->event($event)->withProperties([
                'company_id' => $warning->company_id,
                'reference_number' => $warning->reference_number,
            ])->log("{$event} employee warning");
    }
}
