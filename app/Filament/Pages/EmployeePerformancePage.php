<?php

namespace App\Filament\Pages;

use App\Models\Employment;
use App\Services\CalculateEmployeeProductivityService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class EmployeePerformancePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static \UnitEnum|string|null $navigationGroup = 'Department Operations';

    protected static ?string $navigationLabel = 'Performance & Productivity Analytics';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.employee-performance-page';

    public ?int $selectedEmploymentId = null;

    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();

        $userEmpId = auth()->user()?->employee?->employments()->where('company_id', Filament::getTenant()?->id)->value('id');
        $this->selectedEmploymentId = $userEmpId ?? Employment::query()->where('company_id', Filament::getTenant()?->id)->value('id');
    }

    public function getEmployeesProperty(): array
    {
        $company = Filament::getTenant();
        if (! $company) {
            return [];
        }

        return Employment::query()
            ->where('company_id', $company->id)
            ->where('employment_status', '!=', 'ended')
            ->with('employee')
            ->get()
            ->mapWithKeys(fn (Employment $e) => [$e->id => "{$e->employee?->full_name} ({$e->employee_code})"])
            ->toArray();
    }

    public function getAnalyticsDataProperty(): ?array
    {
        if (! $this->selectedEmploymentId) {
            return null;
        }

        $employment = Employment::with(['employee', 'department', 'designation', 'teamMemberships.team'])->find($this->selectedEmploymentId);
        if (! $employment) {
            return null;
        }

        $service = app(CalculateEmployeeProductivityService::class);
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate)->endOfDay();

        $metrics = $service->calculate($employment, $start, $end);
        $metrics['designation'] = $employment->designation?->name ?? '—';
        $metrics['department'] = $employment->department?->name ?? '—';
        $metrics['team'] = $employment->teamMemberships->where('is_active', true)->first()?->team?->name ?? 'General';

        return $metrics;
    }
}
