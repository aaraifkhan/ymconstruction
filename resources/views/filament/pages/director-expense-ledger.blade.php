<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Period:</span>
                <input 
                    type="date" 
                    wire:model.live="fromDate" 
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm"
                />
                <span class="text-gray-500 text-sm">to</span>
                <input 
                    type="date" 
                    wire:model.live="toDate" 
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm"
                />
                <div class="flex items-center gap-1 ml-2">
                    <button 
                        type="button" 
                        wire:click="setThisMonth" 
                        class="px-3 py-1.5 text-xs font-medium bg-primary-50 dark:bg-primary-950 text-primary-600 dark:text-primary-400 hover:bg-primary-100 rounded-lg transition"
                    >
                        This Month
                    </button>
                    <button 
                        type="button" 
                        wire:click="setLastMonth" 
                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition"
                    >
                        Last Month
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary Metric Cards --}}
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Opening Due to Director (B/F)</div>
                <div class="mt-2 text-xl font-bold text-gray-900 dark:text-white">
                    PKR {{ number_format((float) ($report['opening_due_to_director'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-purple-200 dark:border-purple-900/50 bg-purple-50/30 dark:bg-purple-950/20 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-purple-700 dark:text-purple-400">Total Funded by Director (Cr)</div>
                <div class="mt-2 text-xl font-bold text-purple-700 dark:text-purple-400">
                    PKR {{ number_format((float) ($report['total_funded_by_director'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/30 dark:bg-emerald-950/20 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Total Reimbursed / Repaid (Dr)</div>
                <div class="mt-2 text-xl font-bold text-emerald-700 dark:text-emerald-400">
                    PKR {{ number_format((float) ($report['total_reimbursed'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-primary-300 dark:border-primary-800 bg-primary-50/50 dark:bg-primary-950/30 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-primary-800 dark:text-primary-300">Closing Net Due to Director</div>
                <div class="mt-2 text-xl font-extrabold text-primary-900 dark:text-primary-100">
                    PKR {{ number_format((float) ($report['closing_due_to_director'] ?? 0), 2) }}
                </div>
            </div>
        </div>

        {{-- Category Breakdown Cards --}}
        @if (!empty($report['categories_summary']))
            <x-filament::section heading="Expense Breakdown by Head (Funded by Director)" icon="heroicon-o-chart-pie">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach ($report['categories_summary'] as $code => $cat)
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg border border-gray-100 dark:border-gray-700/50">
                            <div class="text-xs text-gray-500 truncate">{{ $cat['label'] }} ({{ $code }})</div>
                            <div class="text-base font-bold text-gray-900 dark:text-white mt-1">PKR {{ number_format((float) $cat['total'], 2) }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">{{ $cat['count'] }} transaction(s)</div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Detailed Chronological Ledger Table --}}
        <x-filament::section heading="Director Current Account &amp; Expense Ledger" icon="heroicon-o-document-text">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                            <th class="p-2.5 w-24">Date</th>
                            <th class="p-2.5 w-28">Voucher / Ref</th>
                            <th class="p-2.5">Description / Purpose</th>
                            <th class="p-2.5 w-40">Expense Head</th>
                            <th class="p-2.5 w-32">Project</th>
                            <th class="p-2.5 text-right w-28 text-emerald-700 dark:text-emerald-400">Reimbursed (Dr)</th>
                            <th class="p-2.5 text-right w-28 text-purple-700 dark:text-purple-400">Funded (Cr)</th>
                            <th class="p-2.5 text-right w-32 font-bold bg-primary-50/40 dark:bg-primary-950/40 text-primary-900 dark:text-primary-200">Net Due to Director</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        {{-- Opening Row --}}
                        <tr class="bg-gray-50/50 dark:bg-gray-800/50 font-medium italic">
                            <td class="p-2.5 text-gray-500">{{ $report['from'] ?? '' }}</td>
                            <td class="p-2.5 text-gray-500">-</td>
                            <td class="p-2.5 text-gray-700 dark:text-gray-300" colspan="3">Opening Balance (Due to Director Brought Forward)</td>
                            <td class="p-2.5 text-right">-</td>
                            <td class="p-2.5 text-right">-</td>
                            <td class="p-2.5 text-right font-bold text-gray-900 dark:text-white bg-primary-50/30 dark:bg-primary-950/30">
                                {{ number_format((float) ($report['opening_due_to_director'] ?? 0), 2) }}
                            </td>
                        </tr>

                        @forelse ($report['rows'] ?? [] as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-2.5 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="p-2.5 font-mono font-medium text-gray-800 dark:text-gray-200">{{ $row['voucher_number'] }}</td>
                                <td class="p-2.5 text-gray-900 dark:text-gray-100">{{ $row['description'] }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['category'] }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['project'] ?? '-' }}</td>
                                <td class="p-2.5 text-right font-medium text-emerald-700 dark:text-emerald-400">
                                    {{ bccomp((string)$row['reimbursed_debit'], '0.0000', 4) > 0 ? number_format((float)$row['reimbursed_debit'], 2) : '-' }}
                                </td>
                                <td class="p-2.5 text-right font-medium text-purple-700 dark:text-purple-400">
                                    {{ bccomp((string)$row['funded_credit'], '0.0000', 4) > 0 ? number_format((float)$row['funded_credit'], 2) : '-' }}
                                </td>
                                <td class="p-2.5 text-right font-bold text-primary-900 dark:text-primary-100 bg-primary-50/30 dark:bg-primary-950/30">
                                    {{ number_format((float) $row['net_due'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-400">
                                    No director-funded expenses or reimbursements recorded in this date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
