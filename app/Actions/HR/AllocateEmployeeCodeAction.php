<?php

namespace App\Actions\HR;

use App\Models\EmployeeCodeSequence;
use App\Models\Employment;
use Illuminate\Support\Facades\DB;

class AllocateEmployeeCodeAction
{
    public function handle(int $companyId): string
    {
        return DB::transaction(function () use ($companyId): string {
            DB::table('employee_code_sequences')->insertOrIgnore([
                'company_id' => $companyId,
                'prefix' => 'EMP',
                'padding' => 5,
                'next_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = EmployeeCodeSequence::query()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->firstOrFail();

            do {
                $employeeCode = sprintf(
                    '%s-%s',
                    $sequence->prefix,
                    str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT),
                );
                $sequence->increment('next_number');
            } while (Employment::withTrashed()
                ->where('company_id', $companyId)
                ->where('employee_code', $employeeCode)
                ->exists());

            return $employeeCode;
        }, 3);
    }
}
