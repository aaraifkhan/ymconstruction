<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Control Bar (hidden on print) --}}
        <div class="print:hidden flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Statement Date:</span>
                <input 
                    type="date" 
                    wire:model.live="reportDate" 
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500"
                />
                <div class="flex items-center gap-1">
                    <button 
                        type="button" 
                        wire:click="previousDay" 
                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition"
                    >
                        ← Prev Day
                    </button>
                    <button 
                        type="button" 
                        wire:click="setToday" 
                        class="px-3 py-1.5 text-xs font-medium bg-primary-50 dark:bg-primary-950 text-primary-600 dark:text-primary-400 hover:bg-primary-100 rounded-lg transition"
                    >
                        Today
                    </button>
                    <button 
                        type="button" 
                        wire:click="nextDay" 
                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition"
                    >
                        Next Day →
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="window.print()" 
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 rounded-lg shadow-sm transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Statement
                </button>
            </div>
        </div>

        {{-- Printable Statement Container --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 shadow-sm space-y-6">
            {{-- Document Header --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4 text-center">
                <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">
                    {{ $report['company']->name ?? 'YM & BMC Group' }}
                </h2>
                <h3 class="text-base font-semibold text-primary-600 dark:text-primary-400 mt-1">
                    Daily Income &amp; Expense Statement / Cash &amp; Bank Position
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    As on Date: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($reportDate)->format('l, d F Y') }}</span>
                </p>
            </div>

            {{-- 1. Fund Balance Summary Grid --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                    Summary of Cash &amp; Bank Balances
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse border border-gray-200 dark:border-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold text-center border-b border-gray-200 dark:border-gray-700">
                                <th class="p-3 text-left border-r border-gray-200 dark:border-gray-700 w-1/4">Particulars</th>
                                @foreach ($report['funds'] ?? [] as $fund)
                                    <th class="p-3 border-r border-gray-200 dark:border-gray-700">
                                        <div>{{ $fund['name'] }}</div>
                                        <div class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $fund['code'] }}</div>
                                    </th>
                                @endforeach
                                <th class="p-3 text-right bg-primary-50 dark:bg-primary-950 text-primary-900 dark:text-primary-200">Total (PKR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            {{-- Row 1: Opening Balances --}}
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-3 font-medium text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700">
                                    Opening Balances (B/F)
                                </td>
                                @foreach ($report['funds'] ?? [] as $fund)
                                    <td class="p-3 text-right border-r border-gray-200 dark:border-gray-700">
                                        {{ number_format((float) $fund['opening_balance'], 2) }}
                                    </td>
                                @endforeach
                                <td class="p-3 text-right font-semibold bg-gray-50/50 dark:bg-gray-800/50">
                                    {{ number_format((float) ($report['totals']['opening_balance'] ?? 0), 2) }}
                                </td>
                            </tr>

                            {{-- Row 2: Total Receipts of the Day --}}
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-3 font-medium text-emerald-700 dark:text-emerald-400 border-r border-gray-200 dark:border-gray-700">
                                    Total Receipts of the Day
                                </td>
                                @foreach ($report['funds'] ?? [] as $fund)
                                    <td class="p-3 text-right text-emerald-600 dark:text-emerald-400 border-r border-gray-200 dark:border-gray-700">
                                        {{ number_format((float) $fund['receipts_total'], 2) }}
                                    </td>
                                @endforeach
                                <td class="p-3 text-right font-semibold text-emerald-700 dark:text-emerald-400 bg-gray-50/50 dark:bg-gray-800/50">
                                    {{ number_format((float) ($report['totals']['receipts_total'] ?? 0), 2) }}
                                </td>
                            </tr>

                            {{-- Row 3: Grand Total (Opening + Receipts) --}}
                            <tr class="bg-gray-50/75 dark:bg-gray-800/75 font-medium">
                                <td class="p-3 text-gray-800 dark:text-gray-200 border-r border-gray-200 dark:border-gray-700">
                                    Grand Total (Opening Bal + Receipts)
                                </td>
                                @foreach ($report['funds'] ?? [] as $fund)
                                    @php $subtotal = bcadd((string)$fund['opening_balance'], (string)$fund['receipts_total'], 4); @endphp
                                    <td class="p-3 text-right border-r border-gray-200 dark:border-gray-700">
                                        {{ number_format((float) $subtotal, 2) }}
                                    </td>
                                @endforeach
                                <td class="p-3 text-right font-semibold bg-primary-50/50 dark:bg-primary-950/50">
                                    {{ number_format((float) ($report['totals']['grand_total'] ?? 0), 2) }}
                                </td>
                            </tr>

                            {{-- Row 4: Total Payments of the Day --}}
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-3 font-medium text-rose-700 dark:text-rose-400 border-r border-gray-200 dark:border-gray-700">
                                    Total Payments of the Day
                                </td>
                                @foreach ($report['funds'] ?? [] as $fund)
                                    <td class="p-3 text-right text-rose-600 dark:text-rose-400 border-r border-gray-200 dark:border-gray-700">
                                        {{ number_format((float) $fund['payments_total'], 2) }}
                                    </td>
                                @endforeach
                                <td class="p-3 text-right font-semibold text-rose-700 dark:text-rose-400 bg-gray-50/50 dark:bg-gray-800/50">
                                    {{ number_format((float) ($report['totals']['payments_total'] ?? 0), 2) }}
                                </td>
                            </tr>

                            {{-- Row 5: Closing Balances --}}
                            <tr class="bg-primary-50 dark:bg-primary-950 font-bold border-t-2 border-primary-500">
                                <td class="p-3 text-primary-900 dark:text-primary-100 border-r border-gray-200 dark:border-gray-700">
                                    Closing Balances (C/F)
                                </td>
                                @foreach ($report['funds'] ?? [] as $fund)
                                    <td class="p-3 text-right text-primary-900 dark:text-primary-100 border-r border-gray-200 dark:border-gray-700">
                                        {{ number_format((float) $fund['closing_balance'], 2) }}
                                    </td>
                                @endforeach
                                <td class="p-3 text-right text-primary-900 dark:text-primary-100 text-base font-extrabold">
                                    {{ number_format((float) ($report['totals']['closing_balance'] ?? 0), 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Itemized Receipts of the Day --}}
            <div>
                <h4 class="text-sm font-semibold text-emerald-800 dark:text-emerald-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    Receipts of the Day (Inflow)
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700">
                                <th class="p-2 w-16">Time</th>
                                <th class="p-2 w-28">Voucher / Ref</th>
                                <th class="p-2">Particulars / Narration</th>
                                <th class="p-2 w-36">Received In Fund</th>
                                <th class="p-2 w-40">Project / Party</th>
                                <th class="p-2 text-right w-32">Amount (PKR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($report['receipt_items'] ?? [] as $item)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                    <td class="p-2 text-gray-500">{{ $item['time'] }}</td>
                                    <td class="p-2 font-mono font-medium text-gray-700 dark:text-gray-300">{{ $item['voucher_number'] }}</td>
                                    <td class="p-2 text-gray-900 dark:text-gray-100">{{ $item['particulars'] }}</td>
                                    <td class="p-2 text-gray-600 dark:text-gray-400">{{ $item['fund_name'] }}</td>
                                    <td class="p-2 text-gray-600 dark:text-gray-400">{{ $item['project'] ?? $item['party'] ?? '-' }}</td>
                                    <td class="p-2 text-right font-medium text-emerald-700 dark:text-emerald-400">
                                        {{ number_format((float) $item['amount'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="p-4 text-center text-gray-400 dark:text-gray-500" colspan="6">
                                        No cash or bank receipts recorded on this day.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 3. Itemized Payments of the Day --}}
            <div>
                <h4 class="text-sm font-semibold text-rose-800 dark:text-rose-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span>
                    Payments of the Day (Outflow / Expenses)
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700">
                                <th class="p-2 w-16">Time</th>
                                <th class="p-2 w-28">Voucher / Ref</th>
                                <th class="p-2">Particulars / Head / Description</th>
                                <th class="p-2 w-36">Paid From Fund</th>
                                <th class="p-2 w-40">Project / Party</th>
                                <th class="p-2 text-right w-32">Amount (PKR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($report['payment_items'] ?? [] as $item)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                    <td class="p-2 text-gray-500">{{ $item['time'] }}</td>
                                    <td class="p-2 font-mono font-medium text-gray-700 dark:text-gray-300">{{ $item['voucher_number'] }}</td>
                                    <td class="p-2 text-gray-900 dark:text-gray-100">{{ $item['particulars'] }}</td>
                                    <td class="p-2 text-gray-600 dark:text-gray-400">{{ $item['fund_name'] }}</td>
                                    <td class="p-2 text-gray-600 dark:text-gray-400">{{ $item['project'] ?? $item['party'] ?? '-' }}</td>
                                    <td class="p-2 text-right font-medium text-rose-700 dark:text-rose-400">
                                        {{ number_format((float) $item['amount'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="p-4 text-center text-gray-400 dark:text-gray-500" colspan="6">
                                        No cash or bank disbursements recorded on this day.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. Corporate Signatures Block --}}
            <div class="pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-3 gap-8 text-center text-xs">
                    <div>
                        <div class="border-b border-gray-400 dark:border-gray-500 pb-8 mb-2"></div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Prepared by (Accounts/Finance)</span>
                    </div>
                    <div>
                        <div class="border-b border-gray-400 dark:border-gray-500 pb-8 mb-2"></div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Verified by (Finance Manager)</span>
                    </div>
                    <div>
                        <div class="border-b border-gray-400 dark:border-gray-500 pb-8 mb-2"></div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Approved by (Director)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
