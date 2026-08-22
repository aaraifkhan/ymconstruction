<?php

namespace App\Filament\Pages;

use App\Models\Employment;
use App\Services\CalculateEmployeeProductivityService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EmployeePerformancePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static \UnitEnum|string|null $navigationGroup = 'SM Department Operations';

    protected static ?string $navigationLabel = 'Performance & Productivity Analytics';

    protected static ?string $title = 'Employee Performance & Productivity Analytics';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.employee-performance-page';

    public ?int $selectedEmploymentId = null;

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();

        $tenantId = Filament::getTenant()?->id;
        $userEmpId = auth()->user()?->employee?->employments()->where('company_id', $tenantId)->value('id');
        $this->selectedEmploymentId = $userEmpId ?? Employment::query()->where('company_id', $tenantId)->where('employment_status', '!=', 'ended')->value('id');

        $this->form->fill([
            'selectedEmploymentId' => $this->selectedEmploymentId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $company = Filament::getTenant();

        return $schema
            ->components([
                Section::make('Filter & Scope Parameters')
                    ->description('Select an employee and reporting period to analyze composite productivity metrics and delivery breakdown.')
                    ->icon('heroicon-o-funnel')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('selectedEmploymentId')
                            ->label('Employee')
                            ->options(function () use ($company): array {
                                return Employment::query()
                                    ->where('company_id', $company?->id)
                                    ->where('employment_status', '!=', 'ended')
                                    ->with(['employee', 'designation', 'department'])
                                    ->get()
                                    ->mapWithKeys(fn (Employment $e): array => [
                                        $e->id => "{$e->employee?->full_name} ({$e->employee_code}) — ".($e->designation?->name ?? 'Staff'),
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->selectedEmploymentId = $state ? (int) $state : null),

                        DatePicker::make('startDate')
                            ->label('From Date')
                            ->default($this->startDate)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->startDate = $state),

                        DatePicker::make('endDate')
                            ->label('To Date')
                            ->default($this->endDate)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->endDate = $state),
                    ]),
            ]);
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
