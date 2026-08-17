<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Control Bar (Hidden on print) --}}
        <div class="print:hidden flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 flex-wrap">
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
                    <button 
                        type="button" 
                        wire:click="setThisFiscalYear" 
                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition"
                    >
                        FY to Date
                    </button>
                </div>
            </div>

            <button 
                type="button" 
                onclick="window.print()" 
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 rounded-lg shadow-sm transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Summary
            </button>
        </div>

        {{-- Printable Statement Container --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 shadow-sm space-y-6">
            {{-- Document Header --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4 text-center">
                <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">
                    {{ $report['company']->name ?? 'YM & BMC Group' }}
                </h2>
                <h3 class="text-base font-semibold text-primary-600 dark:text-primary-400 mt-1">
                    Monthly Consolidated Expense &amp; Cost Summary Book
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Period: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $report['from'] ?? '' }} to {{ $report['to'] ?? '' }}</span>
                </p>
            </div>

            {{-- 4 Pillar Metric Cards --}}
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">1. Head Office Expenses</div>
                    <div class="mt-2 text-xl font-bold text-gray-900 dark:text-white">
                        PKR {{ number_format((float) ($report['head_office_expenses']['total'] ?? 0), 2) }}
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/30 dark:bg-amber-950/20 p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">2. Bidding &amp; Tender Costs</div>
                    <div class="mt-2 text-xl font-bold text-amber-700 dark:text-amber-400">
                        PKR {{ number_format((float) ($report['bidding_expenses']['total'] ?? 0), 2) }}
                    </div>
                </div>

                <div class="rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/30 dark:bg-blue-950/20 p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">3. Projects Direct Costs</div>
                    <div class="mt-2 text-xl font-bold text-blue-700 dark:text-blue-400">
                        PKR {{ number_format((float) ($report['project_expenses']['total'] ?? 0), 2) }}
                    </div>
                </div>

                <div class="rounded-xl border border-primary-300 dark:border-primary-800 bg-primary-50/50 dark:bg-primary-950/30 p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-primary-800 dark:text-primary-300">Grand Total Expenditure</div>
                    <div class="mt-2 text-xl font-extrabold text-primary-900 dark:text-primary-100">
                        PKR {{ number_format((float) ($report['grand_total_expenditure'] ?? 0), 2) }}
                    </div>
                </div>
            </div>

            {{-- 1. Comprehensive Roll-up Table (matching Expense Book Summary Sheet) --}}
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 uppercase tracking-wider">
                    Consolidated Expenditure Summary by Category &amp; Stream
                </h4>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                                <th class="p-2.5 w-16">Code</th>
                                <th class="p-2.5">Expense Stream / Account Head</th>
                                <th class="p-2.5 text-center w-24">Vouchers</th>
                                <th class="p-2.5 text-right w-36">Amount (PKR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            {{-- Section A: Head Office / Operating --}}
                            <tr class="bg-gray-100/75 dark:bg-gray-800/75 font-bold">
                                <td class="p-2 text-gray-700 dark:text-gray-300" colspan="3">A. Head Office &amp; General Operating Expenses</td>
                                <td class="p-2 text-right font-bold text-gray-900 dark:text-white">
                                    PKR {{ number_format((float) ($report['head_office_expenses']['total'] ?? 0), 2) }}
                                </td>
                            </tr>
                            @forelse ($report['head_office_expenses']['items'] ?? [] as $code => $item)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="p-2 text-gray-500 font-mono pl-4">{{ $code }}</td>
                                    <td class="p-2 text-gray-800 dark:text-gray-200">{{ $item['label'] }}</td>
                                    <td class="p-2 text-center text-gray-500">{{ $item['count'] }}</td>
                                    <td class="p-2 text-right font-medium text-gray-900 dark:text-gray-100">
                                        {{ number_format((float) $item['total'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="p-2 pl-4 text-gray-400 italic" colspan="4">No head office expenses in this period.</td></tr>
                            @endforelse

                            {{-- Section B: Bidding & Tender Costs --}}
                            <tr class="bg-amber-50/60 dark:bg-amber-950/40 font-bold">
                                <td class="p-2 text-amber-900 dark:text-amber-200" colspan="3">B. Bidding &amp; Tender Pre-award Costs</td>
                                <td class="p-2 text-right font-bold text-amber-900 dark:text-amber-200">
                                    PKR {{ number_format((float) ($report['bidding_expenses']['total'] ?? 0), 2) }}
                                </td>
                            </tr>
                            @forelse ($report['bidding_expenses']['items'] ?? [] as $code => $item)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="p-2 text-gray-500 font-mono pl-4">{{ $code }}</td>
                                    <td class="p-2 text-gray-800 dark:text-gray-200">{{ $item['label'] }}</td>
                                    <td class="p-2 text-center text-gray-500">{{ $item['count'] }}</td>
                                    <td class="p-2 text-right font-medium text-amber-800 dark:text-amber-300">
                                        {{ number_format((float) $item['total'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="p-2 pl-4 text-gray-400 italic" colspan="4">No bidding expenses in this period.</td></tr>
                            @endforelse

                            {{-- Section C: Project-wise Direct Costs --}}
                            <tr class="bg-blue-50/60 dark:bg-blue-950/40 font-bold">
                                <td class="p-2 text-blue-900 dark:text-blue-200" colspan="3">C. Project-wise Direct Construction &amp; Site Costs</td>
                                <td class="p-2 text-right font-bold text-blue-900 dark:text-blue-200">
                                    PKR {{ number_format((float) ($report['project_expenses']['total'] ?? 0), 2) }}
                                </td>
                            </tr>
                            @forelse ($report['project_expenses']['by_project'] ?? [] as $pId => $item)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="p-2 text-gray-500 font-mono pl-4">{{ $item['code'] }}</td>
                                    <td class="p-2 text-gray-800 dark:text-gray-200 font-medium">{{ $item['name'] }}</td>
                                    <td class="p-2 text-center text-gray-500">{{ $item['count'] }}</td>
                                    <td class="p-2 text-right font-medium text-blue-800 dark:text-blue-300">
                                        {{ number_format((float) $item['total'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="p-2 pl-4 text-gray-400 italic" colspan="4">No project-allocated expenses in this period.</td></tr>
                            @endforelse

                            {{-- Grand Total Row --}}
                            <tr class="bg-primary-50 dark:bg-primary-950 font-bold text-sm border-t-2 border-primary-500">
                                <td class="p-3 text-primary-900 dark:text-primary-100" colspan="3">
                                    GRAND TOTAL CONSOLIDATED EXPENDITURE
                                </td>
                                <td class="p-3 text-right text-primary-900 dark:text-primary-100 text-base font-extrabold">
                                    PKR {{ number_format((float) ($report['grand_total_expenditure'] ?? 0), 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Funding Outflow Source Breakdown --}}
            <div class="space-y-3 pt-2">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 uppercase tracking-wider">
                    Disbursement &amp; Funding Sources Breakdown
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    @foreach ($report['funding_sources'] ?? [] as $key => $source)
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/70 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="text-[11px] text-gray-500 uppercase font-semibold">{{ $source['label'] }}</div>
                            <div class="text-base font-bold text-gray-900 dark:text-white mt-1">
                                PKR {{ number_format((float) $source['total'], 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Corporate Signatures Block --}}
            <div class="pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-3 gap-8 text-center text-xs">
                    <div>
                        <div class="border-b border-gray-400 dark:border-gray-500 pb-8 mb-2"></div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Prepared by (Accounts/Finance)</span>
                    </div>
                    <div>
                        <div class="border-b border-gray-400 dark:border-gray-500 pb-8 mb-2"></div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Checked &amp; Audited by</span>
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
