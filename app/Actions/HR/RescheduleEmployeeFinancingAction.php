<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingInstallmentStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingInstallment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RescheduleEmployeeFinancingAction
{
    public function __construct(private BuildEmployeeFinancingScheduleAction $buildSchedule) {}

    public function handle(
        EmployeeFinancing $financing,
        int $installmentCount,
        CarbonImmutable $firstDueDate,
        string $reason,
        User $actor,
    ): EmployeeFinancing {
        Gate::forUser($actor)->authorize('reschedule', $financing);

        return DB::transaction(function () use ($financing, $installmentCount, $firstDueDate, $reason, $actor): EmployeeFinancing {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if ($financing->status !== EmployeeFinancingStatus::Active || $installmentCount < 1 || trim($reason) === '') {
                throw ValidationException::withMessages(['installment_count' => 'Active financing requires installments and a reschedule reason.']);
            }
            $active = EmployeeFinancingInstallment::query()
                ->where('employee_financing_id', $financing->getKey())
                ->whereNotIn('status', [
                    EmployeeFinancingInstallmentStatus::Paid,
                    EmployeeFinancingInstallmentStatus::Waived,
                    EmployeeFinancingInstallmentStatus::Superseded,
                ])->lockForUpdate()->get();
            $outstandingPrincipal = '0.0000';
            $outstandingCharge = '0.0000';
            foreach ($active as $installment) {
                $outstandingPrincipal = bcadd($outstandingPrincipal, bcsub(
                    (string) $installment->principal_due,
                    bcadd((string) $installment->principal_recovered, (string) $installment->principal_waived, 4),
                    4,
                ), 4);
                $outstandingCharge = bcadd($outstandingCharge, bcsub(
                    (string) $installment->finance_charge_due,
                    bcadd((string) $installment->finance_charge_recovered, (string) $installment->finance_charge_waived, 4),
                    4,
                ), 4);
            }
            EmployeeFinancingInstallment::query()->whereKey($active->modelKeys())->update([
                'status' => EmployeeFinancingInstallmentStatus::Superseded->value,
                'updated_at' => now(),
            ]);
            $version = (int) $financing->installments()->max('schedule_version') + 1;
            $this->buildSchedule->handle(
                $financing,
                $outstandingPrincipal,
                $outstandingCharge,
                $installmentCount,
                $firstDueDate,
                $version,
            );
            activity('employee_financings')->causedBy($actor)->performedOn($financing)->event('rescheduled')
                ->withProperties([
                    'company_id' => $financing->company_id,
                    'schedule_version' => $version,
                    'installment_count' => $installmentCount,
                    'reason' => trim($reason),
                ])->log('rescheduled employee financing');

            return $financing->refresh();
        }, 3);
    }
}
