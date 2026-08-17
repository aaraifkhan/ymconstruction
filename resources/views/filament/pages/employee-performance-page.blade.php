<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Bar --}}
        <div class="bg-white dark:bg-gray-900 shadow rounded-xl p-4 border border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4 flex-wrap">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Select Employee</label>
                    <select wire:model.live="selectedEmploymentId" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 min-w-[240px]">
                        @foreach($this->employees as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Start Date</label>
                    <input type="date" wire:model.live="startDate" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">End Date</label>
                    <input type="date" wire:model.live="endDate" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>
            <div class="text-xs text-gray-400">
                Period: <span class="font-bold text-gray-700 dark:text-gray-200">{{ $startDate }}</span> to <span class="font-bold text-gray-700 dark:text-gray-200">{{ $endDate }}</span>
            </div>
        </div>

        @if($data = $this->analyticsData)
            {{-- Main Scorecard Header --}}
            <div class="bg-gradient-to-r from-primary-600 via-primary-700 to-indigo-800 text-white rounded-2xl p-6 shadow-lg flex flex-wrap items-center justify-between gap-6">
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-primary-200">Productivity Scorecard</div>
                    <h2 class="text-2xl font-black mt-1">{{ $data['employee_name'] }}</h2>
                    <div class="text-sm text-primary-100 font-mono mt-0.5">
                        {{ $data['employee_code'] }} • {{ $data['designation'] }} ({{ $data['team'] }})
                    </div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl px-6 py-4 border border-white/20 text-center">
                    <div class="text-xs font-bold uppercase text-primary-200">Composite Score</div>
                    <div class="text-4xl font-extrabold text-white mt-1">{{ $data['productivity_score'] }}<span class="text-xl font-normal text-primary-200">/100</span></div>
                    <div class="text-[10px] text-primary-200 uppercase font-semibold mt-0.5">
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

            {{-- 6 Key Performance Metrics --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Tasks Assigned vs Completed --}}
                <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="text-xs uppercase font-bold text-gray-500">Tasks Completed vs Assigned</div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">
                        {{ $data['tasks_completed'] }} <span class="text-lg font-normal text-gray-400">/ {{ $data['tasks_assigned'] }}</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs font-semibold">
                        <span class="text-gray-500">Completion Rate</span>
                        <span class="text-primary-600 dark:text-primary-400 font-bold">{{ $data['task_completion_rate'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full mt-1.5 overflow-hidden">
                        <div class="bg-primary-600 h-2 rounded-full" style="width: {{ min(100, $data['task_completion_rate']) }}%"></div>
                    </div>
                </div>

                {{-- Turnaround Time --}}
                <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="text-xs uppercase font-bold text-gray-500">Avg Task Turnaround Time</div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">
                        {{ $data['avg_turnaround_hours'] }} <span class="text-base font-normal text-gray-400">hrs</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-2">
                        From task creation to final completion
                    </div>
                </div>

                {{-- Overdue Tasks --}}
                <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="text-xs uppercase font-bold text-gray-500">Overdue Task Count</div>
                    <div class="text-3xl font-extrabold {{ $data['tasks_overdue'] > 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-2">
                        {{ $data['tasks_overdue'] }}
                    </div>
                    <div class="text-xs text-gray-400 mt-2">
                        {{ $data['tasks_overdue'] == 0 ? '✅ 100% on-time delivery' : '⚠️ Tasks exceeding deadline' }}
                    </div>
                </div>

                {{-- Revision Rate --}}
                <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="text-xs uppercase font-bold text-gray-500">Revisions Incurred</div>
                    <div class="text-3xl font-extrabold text-amber-600 mt-2">
                        {{ $data['total_revisions'] }}
                    </div>
                    <div class="text-xs text-gray-400 mt-2">
                        Avg <span class="font-bold text-gray-700 dark:text-gray-300">{{ $data['avg_revisions_per_task'] }}</span> revisions per completed task
                    </div>
                </div>

                {{-- Daily Reports On-Time --}}
                <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="text-xs uppercase font-bold text-gray-500">Daily Work Reports On-Time</div>
                    <div class="text-3xl font-extrabold text-emerald-600 mt-2">
                        {{ $data['reports_submitted_on_time'] }} <span class="text-sm font-normal text-gray-400">on-time / {{ $data['reports_submitted_late'] }} late</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs font-semibold">
                        <span class="text-gray-500">On-Time Submission Rate</span>
                        <span class="text-emerald-600 font-bold">{{ $data['on_time_report_rate'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full mt-1.5 overflow-hidden">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ min(100, $data['on_time_report_rate']) }}%"></div>
                    </div>
                </div>

                {{-- Total Reports Submitted --}}
                <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="text-xs uppercase font-bold text-gray-500">Total Work Days Reported</div>
                    <div class="text-3xl font-extrabold text-indigo-600 mt-2">
                        {{ $data['total_reports_submitted'] }}
                    </div>
                    <div class="text-xs text-gray-400 mt-2">
                        Mandatory 6:00 PM daily check-ins
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 p-8 rounded-xl text-center text-gray-400 border border-gray-100 dark:border-gray-800">
                Please select an employee to view performance metrics.
            </div>
        @endif
    </div>
</x-filament-panels::page>
