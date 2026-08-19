<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filament Filter Schema --}}
        {{ $this->form }}

        @if($data = $this->analyticsData)
            {{-- Productivity Score Banner --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 via-primary-700 to-indigo-800 p-6 text-white shadow-xl">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-widest text-primary-200">Executive Productivity & Performance Profile</div>
                        <h2 class="text-2xl font-black mt-1">{{ $data['employee_name'] }}</h2>
                        <div class="text-sm text-primary-100 font-mono mt-0.5">
                            {{ $data['employee_code'] }} • {{ $data['designation'] }} ({{ $data['team'] }})
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl border border-white/20 bg-white/10 px-6 py-3 text-center backdrop-blur-md">
                            <div class="text-xs font-bold uppercase tracking-wider text-primary-200">Composite Score</div>
                            <div class="text-3xl font-black text-white mt-0.5">
                                {{ $data['productivity_score'] }}<span class="text-lg font-normal text-primary-200">/100</span>
                            </div>
                            <div class="text-[11px] font-semibold text-primary-200 uppercase mt-0.5">
                                @if($data['productivity_score'] >= 85)
                                    🌟 Top Performer
                                @elseif($data['productivity_score'] >= 70)
                                    ⚡ Solid Performer
                                @else
                                    ⚠️ Needs Improvement
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filament Native Stats Overview Widget --}}
            @livewire(\App\Filament\Widgets\EmployeePerformanceStatsWidget::class, [
                'selectedEmploymentId' => $this->selectedEmploymentId,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ], key('emp-stats-' . $this->selectedEmploymentId . '-' . $this->startDate . '-' . $this->endDate))

            {{-- Two Table Widgets: Assigned Tasks & Daily Work Reports --}}
            <div class="space-y-6">
                @livewire(\App\Filament\Widgets\EmployeePerformanceTasksWidget::class, [
                    'selectedEmploymentId' => $this->selectedEmploymentId,
                    'startDate' => $this->startDate,
                    'endDate' => $this->endDate,
                ], key('emp-tasks-' . $this->selectedEmploymentId . '-' . $this->startDate . '-' . $this->endDate))

                @livewire(\App\Filament\Widgets\EmployeePerformanceReportsWidget::class, [
                    'selectedEmploymentId' => $this->selectedEmploymentId,
                    'startDate' => $this->startDate,
                    'endDate' => $this->endDate,
                ], key('emp-reports-' . $this->selectedEmploymentId . '-' . $this->startDate . '-' . $this->endDate))
            </div>
        @else
            <div class="rounded-xl border border-gray-100 bg-white p-8 text-center text-gray-400 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                Please select an active employee from the filter above to view their performance analytics and delivery breakdown.
            </div>
        @endif
    </div>
</x-filament-panels::page>
