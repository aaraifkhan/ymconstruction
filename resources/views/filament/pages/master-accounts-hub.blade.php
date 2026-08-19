<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Live Multi-Company Financial Overview Cards --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="w-5 h-5 text-primary-500" />
                    Live Group Companies Financial Position
                </h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Real-time ledger &amp; bank snapshot</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach ($this->companySummaries as $summary)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100 truncate" title="{{ $summary['name'] }}">
                                {{ $summary['name'] }}
                            </span>
                            <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                                {{ $summary['code'] }}
                            </span>
                        </div>

                        <div class="mt-3 space-y-1.5 text-xs">
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>💵 Cash in Hand:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100 font-mono">
                                    PKR {{ number_format($summary['cash_balance'], 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>🏦 Bank Balance:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100 font-mono">
                                    PKR {{ number_format($summary['bank_balance'], 2) }}
                                </span>
                            </div>
                            <div class="border-t border-gray-100 dark:border-gray-800 pt-1.5 flex justify-between items-center font-semibold text-gray-900 dark:text-gray-100">
                                <span>Total Liquid Funds:</span>
                                <span class="text-primary-600 dark:text-primary-400 font-mono">
                                    PKR {{ number_format($summary['total_liquid'], 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-t border-dashed border-gray-200 dark:border-gray-800 flex justify-between items-center text-[11px]">
                            <span class="text-gray-500">Today's Expenses: <strong>PKR {{ number_format($summary['today_expenses'], 2) }}</strong></span>
                            @if ($summary['pending_vouchers'] > 0)
                                <span class="text-amber-600 dark:text-amber-400 font-medium">⚠️ {{ $summary['pending_vouchers'] }} Pending</span>
                            @else
                                <span class="text-emerald-600 dark:text-emerald-400">✓ Up to date</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Universal Multi-Company Transaction Form --}}
        <form wire:submit.prevent="submit" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end gap-3">
                <x-filament::button type="submit" size="lg" icon="heroicon-o-paper-airplane">
                    Submit &amp; Post into Selected Company Ledger
                </x-filament::button>
            </div>
        </form>

        {{-- Recent Cross-Company Transactions --}}
        <x-filament::section heading="Recent Cross-Company Accounting Entries" icon="heroicon-o-clock">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400 font-medium text-xs">
                            <th class="p-2.5">Date</th>
                            <th class="p-2.5">Company</th>
                            <th class="p-2.5">Voucher #</th>
                            <th class="p-2.5">Type</th>
                            <th class="p-2.5">Particulars / Description</th>
                            <th class="p-2.5 text-right">Debit Total (PKR)</th>
                            <th class="p-2.5 text-center">Status</th>
                            <th class="p-2.5">Prepared By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->recentCrossCompanyEntries as $entry)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 text-xs">
                                <td class="p-2.5 text-gray-600 dark:text-gray-300">
                                    {{ $entry->transaction_date?->format('Y-m-d') ?? '-' }}
                                </td>
                                <td class="p-2.5 font-medium">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 font-semibold text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                        {{ $entry->company?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="p-2.5 font-mono font-medium text-gray-800 dark:text-gray-200">
                                    {{ $entry->voucher_number ?? 'Draft' }}
                                </td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">
                                    {{ $entry->voucher_type?->getLabel() ?? 'Journal' }}
                                </td>
                                <td class="p-2.5 text-gray-900 dark:text-gray-100 max-w-sm truncate" title="{{ $entry->description }}">
                                    {{ $entry->description }}
                                </td>
                                <td class="p-2.5 text-right font-semibold font-mono text-gray-900 dark:text-gray-100">
                                    {{ number_format((float) $entry->debit_total, 2) }}
                                </td>
                                <td class="p-2.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium 
                                        @if($entry->status->value === 'posted') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                                        @elseif($entry->status->value === 'submitted') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                                        @elseif($entry->status->value === 'approved') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 @endif">
                                        {{ $entry->status->getLabel() }}
                                    </span>
                                </td>
                                <td class="p-2.5 text-gray-500 dark:text-gray-400">
                                    {{ $entry->preparedBy?->name ?? 'System' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-400">
                                    No cross-company transactions recorded yet. Use the form above to post entries.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
