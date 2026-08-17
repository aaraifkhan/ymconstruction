<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DailyWorkReport;
use App\Models\Employment;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckDailyReportDeadlineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'work-reports:check-deadline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check 6:00 PM daily report submissions and alert leads/heads for missing reports.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->toDateString();
        $this->info("Checking daily work report submissions for {$today}...");

        $companies = Company::query()->active()->get();

        foreach ($companies as $company) {
            $employments = Employment::query()
                ->where('company_id', $company->id)
                ->where('employment_status', '!=', 'ended')
                ->with('employee')
                ->get();

            $submittedIds = DailyWorkReport::query()
                ->where('company_id', $company->id)
                ->where('report_date', $today)
                ->pluck('employment_id')
                ->toArray();

            $missing = $employments->reject(fn (Employment $e) => in_array($e->id, $submittedIds, true));

            if ($missing->isNotEmpty()) {
                $missingNames = $missing->map(fn (Employment $e) => $e->employee?->full_name ?? $e->employee_code)->implode(', ');
                $this->warn("[Company: {$company->name}] Missing reports ({$missing->count()}): {$missingNames}");

                // Broadcast notification to super admins and department heads of the company
                $companyUsers = $company->users()->get();
                foreach ($companyUsers as $u) {
                    if ($u->hasRole('super_admin') || $u->hasRole('department_head') || $u->can('ViewAll:DailyWorkReport')) {
                        Notification::make()
                            ->title("⚠️ Daily Reports Missing ({$missing->count()} staff)")
                            ->body("The following employees have not submitted their 6:00 PM daily reports: {$missingNames}")
                            ->warning()
                            ->sendToDatabase($u);
                    }
                }
            } else {
                $this->info("[Company: {$company->name}] All employees submitted on-time!");
            }
        }

        return self::SUCCESS;
    }
}
