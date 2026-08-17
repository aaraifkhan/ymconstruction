<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Project & Date Filter Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Project / Site:</span>
                    <select 
                        wire:model.live="selectedProjectId" 
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm font-medium"
                    >
                        @forelse ($this->projects as $p)
                            <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                        @empty
                            <option value="">No projects found</option>
                        @endforelse
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Date Range:</span>
                    <input 
                        type="date" 
                        wire:model.live="fromDate" 
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs shadow-sm"
                    />
                    <span class="text-gray-400 text-xs">to</span>
                    <input 
                        type="date" 
                        wire:model.live="toDate" 
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs shadow-sm"
                    />
                    @if (!empty($fromDate) || !empty($toDate))
                        <button 
                            type="button" 
                            wire:click="clearDates" 
                            class="text-xs text-gray-500 hover:text-red-500 underline ml-1"
                        >
                            Clear
                        </button>
                    @endif
                </div>
            </div>

            <div class="text-right">
                <span class="text-xs text-gray-500 block uppercase font-medium">Total Incurred Cost</span>
                <span class="text-2xl font-extrabold text-primary-600 dark:text-primary-400">
                    PKR {{ number_format((float) ($report['total_project_cost'] ?? 0), 2) }}
                </span>
            </div>
        </div>

        {{-- 4 Cost Pillar Cards --}}
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/30 dark:bg-blue-950/20 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Materials (Cement/Steel/Sand)</div>
                <div class="mt-2 text-xl font-bold text-blue-700 dark:text-blue-400">
                    PKR {{ number_format((float) ($report['total_materials'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/30 dark:bg-amber-950/20 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">Labor &amp; Machinery</div>
                <div class="mt-2 text-xl font-bold text-amber-700 dark:text-amber-400">
                    PKR {{ number_format((float) ($report['total_labor_equipment'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-teal-200 dark:border-teal-900/50 bg-teal-50/30 dark:bg-teal-950/20 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-teal-700 dark:text-teal-400">Site Overheads &amp; Utilities</div>
                <div class="mt-2 text-xl font-bold text-teal-700 dark:text-teal-400">
                    PKR {{ number_format((float) ($report['total_site_overheads'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-primary-300 dark:border-primary-800 bg-primary-50/50 dark:bg-primary-950/30 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-primary-800 dark:text-primary-300">Total Project Expense</div>
                <div class="mt-2 text-xl font-extrabold text-primary-900 dark:text-primary-100">
                    PKR {{ number_format((float) ($report['total_project_cost'] ?? 0), 2) }}
                </div>
            </div>
        </div>

        {{-- Category Breakdown Grid --}}
        @if (!empty($report['category_breakdown']))
            <x-filament::section heading="Cost Breakdown by Construction Head" icon="heroicon-o-chart-pie">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach ($report['category_breakdown'] as $code => $cat)
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg border border-gray-100 dark:border-gray-700/50">
                            <div class="text-xs text-gray-500 truncate">{{ $cat['label'] }} ({{ $code }})</div>
                            <div class="text-base font-bold text-gray-900 dark:text-white mt-1">PKR {{ number_format((float) $cat['total'], 2) }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">{{ $cat['count'] }} line(s) · {{ ucfirst(str_replace('_', ' ', $cat['group'])) }}</div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Chronological Itemized Project Expenses Table --}}
        <x-filament::section heading="Itemized Project Expense Ledger" icon="heroicon-o-table-cells">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                            <th class="p-2.5 w-24">Date</th>
                            <th class="p-2.5 w-28">Voucher / Ref</th>
                            <th class="p-2.5">Description / Particulars</th>
                            <th class="p-2.5 w-44">Construction Head</th>
                            <th class="p-2.5 w-40">Paid From Fund</th>
                            <th class="p-2.5 w-36">Supplier / Payee</th>
                            <th class="p-2.5 text-right w-32 font-bold text-gray-900 dark:text-white">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($report['rows'] ?? [] as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-2.5 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="p-2.5 font-mono font-medium text-gray-800 dark:text-gray-200">{{ $row['voucher_number'] }}</td>
                                <td class="p-2.5 text-gray-900 dark:text-gray-100">{{ $row['description'] }}</td>
                                <td class="p-2.5 text-gray-700 dark:text-gray-300 font-medium">
                                    {{ $row['category'] }}
                                    <span class="text-[10px] text-gray-400 block">{{ $row['category_code'] }}</span>
                                </td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['paid_from'] }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['party'] ?? '-' }}</td>
                                <td class="p-2.5 text-right font-bold text-gray-900 dark:text-white">
                                    {{ number_format((float) $row['amount'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-400">
                                    No expenses recorded for this project in the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
