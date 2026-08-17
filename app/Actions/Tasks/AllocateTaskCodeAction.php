<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class AllocateTaskCodeAction
{
    public function handle(int $companyId): string
    {
        return DB::transaction(function () use ($companyId): string {
            $year = now()->year;
            $prefix = "TSK-{$year}";

            $lastTask = Task::withTrashed()
                ->where('company_id', $companyId)
                ->where('task_code', 'LIKE', "{$prefix}-%")
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastTask && preg_match("/^{$prefix}-(\d+)$/", $lastTask->task_code, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }

            do {
                $taskCode = sprintf('%s-%s', $prefix, str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT));
                $nextNumber++;
            } while (Task::withTrashed()
                ->where('company_id', $companyId)
                ->where('task_code', $taskCode)
                ->exists());

            return $taskCode;
        }, 3);
    }
}
