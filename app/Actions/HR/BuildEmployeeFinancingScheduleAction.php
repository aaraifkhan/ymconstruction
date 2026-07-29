<?php

namespace App\Actions\HR;

use App\Models\EmployeeFinancing;
use Carbon\CarbonImmutable;

class BuildEmployeeFinancingScheduleAction
{
    public function handle(
        EmployeeFinancing $financing,
        string $principal,
        string $financeCharge,
        int $installmentCount,
        CarbonImmutable $firstDueDate,
        int $version,
    ): void {
        $principalPart = bcdiv($principal, (string) $installmentCount, 4);
        $chargePart = bcdiv($financeCharge, (string) $installmentCount, 4);
        $principalAllocated = '0.0000';
        $chargeAllocated = '0.0000';

        for ($number = 1; $number <= $installmentCount; $number++) {
            $isLast = $number === $installmentCount;
            $principalDue = $isLast ? bcsub($principal, $principalAllocated, 4) : $principalPart;
            $chargeDue = $isLast ? bcsub($financeCharge, $chargeAllocated, 4) : $chargePart;
            $financing->installments()->create([
                'company_id' => $financing->company_id,
                'schedule_version' => $version,
                'installment_number' => $number,
                'due_date' => $firstDueDate->addMonthsNoOverflow($number - 1),
                'principal_due' => $principalDue,
                'finance_charge_due' => $chargeDue,
                'total_due' => bcadd($principalDue, $chargeDue, 4),
            ]);
            $principalAllocated = bcadd($principalAllocated, $principalDue, 4);
            $chargeAllocated = bcadd($chargeAllocated, $chargeDue, 4);
        }
    }
}
