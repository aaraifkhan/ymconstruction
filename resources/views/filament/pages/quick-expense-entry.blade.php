<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="submit" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end gap-3">
                <x-filament::button type="submit" size="lg" icon="heroicon-o-check-circle">
                    Record &amp; Post Expense
                </x-filament::button>
            </div>
        </form>

        {{-- Recent Quick Expenses --}}
        <x-filament::section heading="Recently Recorded Expenses" icon="heroicon-o-clock">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b text-left text-gray-500 dark:text-gray-400 font-medium">
                            <th class="p-2">Date</th>
                            <th class="p-2">Voucher / Ref</th>
                            <th class="p-2">Description</th>
                            <th class="p-2">Debit Account</th>
                            <th class="p-2">Credit Account</th>
                            <th class="p-2">Project</th>
                            <th class="p-2 text-right">Amount (PKR)</th>
                            <th class="p-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->recentExpenses as $expense)
                            @php
                                $debitLine = $expense->lines->firstWhere('debit', '>', 0);
                                $creditLine = $expense->lines->firstWhere('credit', '>', 0);
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-2 text-gray-600 dark:text-gray-300">{{ $expense->transaction_date->format('Y-m-d') }}</td>
                                <td class="p-2 font-mono font-medium text-gray-800 dark:text-gray-200">
                                    {{ $expense->voucher_number ?? $expense->entry_number }}
                                </td>
                                <td class="p-2 text-gray-900 dark:text-gray-100 max-w-xs truncate" title="{{ $expense->narration }}">
                                    {{ $expense->narration }}
                                </td>
                                <td class="p-2 text-gray-600 dark:text-gray-400">
                                    {{ $debitLine?->account?->name ?? $debitLine?->account_name_snapshot ?? '-' }}
                                </td>
                                <td class="p-2 text-gray-600 dark:text-gray-400">
                                    {{ $creditLine?->account?->name ?? $creditLine?->account_name_snapshot ?? '-' }}
                                </td>
                                <td class="p-2 text-gray-600 dark:text-gray-400">
                                    {{ $debitLine?->project?->name ?? '-' }}
                                </td>
                                <td class="p-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format((float) ($debitLine?->debit ?? $expense->debit_total ?? 0), 2) }}
                                </td>
                                <td class="p-2 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                        {{ $expense->status->value === 'posted' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                        {{ $expense->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-400">
                                    No recent expenses recorded yet. Use the form above to add an expense.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
