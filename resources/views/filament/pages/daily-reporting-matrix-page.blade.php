<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Bar --}}
        <div class="bg-white dark:bg-gray-900 shadow rounded-xl p-4 border border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4 flex-wrap">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Select Date</label>
                    <input type="date" wire:model.live="selectedDate" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Filter by Team</label>
                    <select wire:model.live="selectedTeamId" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Teams & Departments</option>
                        @foreach($this->teams as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs text-gray-500">Mandatory Submission Cutoff:</span>
                <span class="ml-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    6:00 PM Daily
                </span>
            </div>
        </div>

        {{-- Summary Cards --}}
        @php
            $summary = $this->matrixData['summary'];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800">
                <div class="text-xs uppercase font-bold text-gray-500">Total Active Employees</div>
                <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $summary['total'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 p-5 rounded-xl shadow-sm border border-emerald-100 dark:border-emerald-950/40">
                <div class="text-xs uppercase font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span> On-Time (<= 6 PM)
                </div>
                <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ $summary['on_time'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 p-5 rounded-xl shadow-sm border border-amber-100 dark:border-amber-950/40">
                <div class="text-xs uppercase font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Late (> 6 PM)
                </div>
                <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ $summary['late'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 p-5 rounded-xl shadow-sm border border-rose-100 dark:border-rose-950/40">
                <div class="text-xs uppercase font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Missing Submissions
                </div>
                <div class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 mt-2">{{ $summary['missing'] }}</div>
            </div>
        </div>

        {{-- Visual Attendance & Submission Matrix Table --}}
        <div class="bg-white dark:bg-gray-900 shadow rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">Department Work & Reporting Matrix</h3>
                <span class="text-xs text-gray-400">{{ count($this->matrixData['records']) }} Members Listed</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="px-6 py-3.5">Employee</th>
                            <th class="px-6 py-3.5">Team</th>
                            <th class="px-6 py-3.5">Reporting Status</th>
                            <th class="px-6 py-3.5">Submitted At</th>
                            <th class="px-6 py-3.5">Day Progress</th>
                            <th class="px-6 py-3.5">Deliverables</th>
                            <th class="px-6 py-3.5">Active Blockers</th>
                            <th class="px-6 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($this->matrixData['records'] as $r)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $r['employee_name'] }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $r['employee_code'] }} • {{ $r['designation'] }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                        {{ $r['team'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($r['status'] === 'on_time')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">
                                            🟢 On Time
                                        </span>
                                    @elseif($r['status'] === 'late')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">
                                            🟠 Late Submission
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400">
                                            🔴 Not Submitted
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">
                                    {{ $r['submitted_at'] }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $r['progress'] }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold">{{ $r['progress'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $r['deliverables'] }}</span> items
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate text-xs text-rose-600 dark:text-rose-400">
                                    {{ $r['blockers'] ?? 'None' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($r['report_id'])
                                        <a href="{{ route('filament.admin.resources.daily-work-reports.view', ['tenant' => Filament::getTenant()->id, 'record' => $r['report_id']]) }}" class="text-primary-600 hover:text-primary-800 font-semibold text-xs inline-flex items-center gap-1">
                                            View Report &rarr;
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400 italic">No Report</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                    No employees found in this team or company.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
