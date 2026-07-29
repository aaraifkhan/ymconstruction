<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingStatus;
use App\Models\EmployeeFinancing;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveEmployeeFinancingAction
{
    public function __construct(private BuildEmployeeFinancingScheduleAction $buildSchedule) {}

    public function handle(EmployeeFinancing $financing, User $actor): EmployeeFinancing
    {
        Gate::forUser($actor)->authorize('approve', $financing);

        return DB::transaction(function () use ($financing, $actor): EmployeeFinancing {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if ($financing->status !== EmployeeFinancingStatus::Requested) {
                throw ValidationException::withMessages(['status' => 'Only requested financing may be approved.']);
            }
            if ((int) $financing->requested_by_id === (int) $actor->getKey()
                || (int) $financing->submitted_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['approved_by_id' => 'The requester or submitter cannot approve the same financing.']);
            }
            $this->buildSchedule->handle(
                $financing,
                (string) $financing->principal_amount,
                (string) $financing->finance_charge,
                $financing->installment_count,
                CarbonImmutable::parse($financing->first_due_date),
                1,
            );
            $financing->update([
                'status' => EmployeeFinancingStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            activity('employee_financings')->causedBy($actor)->performedOn($financing)->event('approved')
                ->withProperties(['company_id' => $financing->company_id, 'total_repayable' => $financing->total_repayable])
                ->log('approved employee financing');

            return $financing->refresh();
        }, 3);
    }
}
