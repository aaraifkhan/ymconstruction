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

            <div class="text-right">
                <span class="text-xs text-gray-500 block uppercase font-medium">Total Bidding Expense</span>
                <span class="text-xl font-extrabold text-primary-600 dark:text-primary-400">
                    PKR {{ number_format((float) ($report['total_bidding_expense'] ?? 0), 2) }}
                </span>
            </div>
        </div>

        {{-- Sub-head Summary Cards --}}
        @if (!empty($report['subhead_summary']))
            <x-filament::section heading="Bidding Cost Breakdown by Activity / Sub-head" icon="heroicon-o-chart-bar">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach ($report['subhead_summary'] as $code => $sub)
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg border border-gray-100 dark:border-gray-700/50">
                            <div class="text-xs text-gray-500 truncate">{{ $sub['label'] }} ({{ $code }})</div>
                            <div class="text-base font-bold text-gray-900 dark:text-white mt-1">PKR {{ number_format((float) $sub['total'], 2) }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">{{ $sub['count'] }} item(s)</div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Chronological Bidding Ledger Table --}}
        <x-filament::section heading="Bidding &amp; Tender Expense Ledger" icon="heroicon-o-document-text">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                            <th class="p-2.5 w-24">Date</th>
                            <th class="p-2.5 w-28">Voucher / Ref</th>
                            <th class="p-2.5">Tender / Bid Description</th>
                            <th class="p-2.5 w-48">Sub-head / Activity</th>
                            <th class="p-2.5 w-40">Paid From Fund</th>
                            <th class="p-2.5 w-32">Payee / Party</th>
                            <th class="p-2.5 text-right w-32 font-bold text-gray-900 dark:text-white">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($report['rows'] ?? [] as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-2.5 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="p-2.5 font-mono font-medium text-gray-800 dark:text-gray-200">{{ $row['voucher_number'] }}</td>
                                <td class="p-2.5 text-gray-900 dark:text-gray-100">{{ $row['description'] }}</td>
                                <td class="p-2.5 text-gray-700 dark:text-gray-300 font-medium">{{ $row['subhead'] }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['paid_from'] }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">{{ $row['party'] ?? '-' }}</td>
                                <td class="p-2.5 text-right font-bold text-gray-900 dark:text-white">
                                    {{ number_format((float) $row['amount'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-400">
                                    No bidding or tender expenses recorded in this date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
