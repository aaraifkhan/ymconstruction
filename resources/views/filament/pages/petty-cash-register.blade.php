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

        {{-- Float Metric Cards --}}
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Opening Float (B/F)</div>
                <div class="mt-2 text-xl font-bold text-gray-900 dark:text-white">
                    PKR {{ number_format((float) ($report['opening_balance'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/30 dark:bg-emerald-950/20 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Total Top-ups (Inflow)</div>
                <div class="mt-2 text-xl font-bold text-emerald-700 dark:text-emerald-400">
                    PKR {{ number_format((float) ($report['inflow_total'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/30 dark:bg-rose-950/20 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-400">Total Spent (Outflow)</div>
                <div class="mt-2 text-xl font-bold text-rose-700 dark:text-rose-400">
                    PKR {{ number_format((float) ($report['outflow_total'] ?? 0), 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-primary-300 dark:border-primary-800 bg-primary-50/50 dark:bg-primary-950/30 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-primary-800 dark:text-primary-300">Closing Float Balance</div>
                <div class="mt-2 text-xl font-extrabold text-primary-900 dark:text-primary-100">
                    PKR {{ number_format((float) ($report['closing_balance'] ?? 0), 2) }}
                </div>
            </div>
        </div>

        {{-- Chronological Petty Cash Register Table --}}
        <x-filament::section heading="Petty Cash Ledger &amp; Running Balance" icon="heroicon-o-table-cells">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                            <th class="p-2.5 w-24">Date</th>
                            <th class="p-2.5 w-28">Voucher / Ref</th>
                            <th class="p-2.5">Description / Particulars</th>
                            <th class="p-2.5 w-40">Expense Head / Account</th>
                            <th class="p-2.5 w-32">Project</th>
                            <th class="p-2.5 w-28">Expense Of</th>
                            <th class="p-2.5 text-right w-28 text-emerald-700 dark:text-emerald-400">Top-up (Dr)</th>
                            <th class="p-2.5 text-right w-28 text-rose-700 dark:text-rose-400">Expense (Cr)</th>
                            <th class="p-2.5 text-right w-32 font-bold bg-primary-50/40 dark:bg-primary-950/40 text-primary-900 dark:text-primary-200">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        {{-- Opening Balance Row --}}
                        <tr class="bg-gray-50/50 dark:bg-gray-800/50 font-medium italic">
                            <td class="p-2.5 text-gray-500">{{ $report['from'] ?? '' }}</td>
                            <td class="p-2.5 text-gray-500">-</td>
                            <td class="p-2.5 text-gray-700 dark:text-gray-300" colspan="4">Opening Balance Brought Forward</td>
                            <td class="p-2.5 text-right">-</td>
                            <td class="p-2.5 text-right">-</td>
                            <td class="p-2.5 text-right font-bold text-gray-900 dark:text-white bg-primary-50/30 dark:bg-primary-950/30">
                                {{ number_format((float) ($report['opening_balance'] ?? 0), 2) }}
                            </td>
                        </tr>

                        @forelse ($report['rows'] ?? [] as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-2.5 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="p-2.5 font-mono font-medium text-gray-800 dark:text-gray-200">{{ $row['voucher_number'] }}</td>
                                <td class="p-2.5 text-gray-900 dark:text-gray-100">{{ $row['description'] }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['category_head'] }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['project'] ?? '-' }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">
                                    @if ($row['expense_of'])
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                            {{ $row['expense_of'] }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="p-2.5 text-right font-medium text-emerald-700 dark:text-emerald-400">
                                    {{ bccomp((string)$row['inflow'], '0.0000', 4) > 0 ? number_format((float)$row['inflow'], 2) : '-' }}
                                </td>
                                <td class="p-2.5 text-right font-medium text-rose-700 dark:text-rose-400">
                                    {{ bccomp((string)$row['outflow'], '0.0000', 4) > 0 ? number_format((float)$row['outflow'], 2) : '-' }}
                                </td>
                                <td class="p-2.5 text-right font-bold text-primary-900 dark:text-primary-100 bg-primary-50/30 dark:bg-primary-950/30">
                                    {{ number_format((float) $row['running_balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-4 text-center text-gray-400">
                                    No petty cash transactions recorded in this date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Physical Reconciliations History --}}
        <x-filament::section heading="Physical Cash Reconciliations &amp; Verification" icon="heroicon-o-clipboard-document-check">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                            <th class="p-2.5 w-28">Date</th>
                            <th class="p-2.5 text-right">System Expected Float</th>
                            <th class="p-2.5 text-right">On-Account Held</th>
                            <th class="p-2.5 text-right">Physical Cash Counted</th>
                            <th class="p-2.5 text-right">Difference (Variance)</th>
                            <th class="p-2.5">Explanation / Notes</th>
                            <th class="p-2.5 w-32">Reconciled By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->recentReconciliations as $rec)
                            @php $diff = (float) $rec->difference; @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-2.5 text-gray-700 dark:text-gray-300 font-medium">{{ $rec->reconciliation_date->format('Y-m-d') }}</td>
                                <td class="p-2.5 text-right text-gray-900 dark:text-white font-medium">PKR {{ number_format((float) $rec->system_expected_balance, 2) }}</td>
                                <td class="p-2.5 text-right text-gray-600 dark:text-gray-400">PKR {{ number_format((float) $rec->on_account_held, 2) }}</td>
                                <td class="p-2.5 text-right text-emerald-700 dark:text-emerald-400 font-semibold">PKR {{ number_format((float) $rec->physical_counted_cash, 2) }}</td>
                                <td class="p-2.5 text-right font-bold {{ abs($diff) < 0.01 ? 'text-green-600' : ($diff > 0 ? 'text-red-600' : 'text-amber-600') }}">
                                    PKR {{ number_format($diff, 2) }}
                                    <span class="text-[10px] block font-normal">
                                        {{ abs($diff) < 0.01 ? 'Exact Match' : ($diff > 0 ? 'Shortage' : 'Surplus') }}
                                    </span>
                                </td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-300">{{ $rec->explanation ?: 'Routine verification' }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $rec->reconciledBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-400">
                                    No physical cash reconciliations recorded yet. Click "Physical Reconciliation" above to record one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
