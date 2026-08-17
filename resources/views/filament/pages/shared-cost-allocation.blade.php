<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Multi-Company Shared Expense Split" icon="heroicon-o-arrows-right-left">
            <form wire:submit="submit" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end pt-4">
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-check-circle">
                        Post Balanced Multi-Company Allocation
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Recent Shared Cost Allocations Table --}}
        <x-filament::section heading="Recent Shared Cost Allocation Entries" icon="heroicon-o-clock">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-left border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                            <th class="p-2.5 w-24">Date</th>
                            <th class="p-2.5 w-28">Voucher / Ref</th>
                            <th class="p-2.5">Description</th>
                            <th class="p-2.5">Inter-company Splits</th>
                            <th class="p-2.5 text-right w-32 font-bold text-gray-900 dark:text-white">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->recentAllocations as $entry)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="p-2.5 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $entry->transaction_date->format('Y-m-d') }}</td>
                                <td class="p-2.5 font-mono font-medium text-gray-800 dark:text-gray-200">{{ $entry->voucher_number ?? $entry->entry_number }}</td>
                                <td class="p-2.5 text-gray-900 dark:text-gray-100">{{ $entry->description }}</td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($entry->lines->where('debit', '>', 0) as $line)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                                                {{ $line->relatedCompany?->name ?? 'Own' }}: PKR {{ number_format((float)$line->debit, 2) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-2.5 text-right font-bold text-primary-900 dark:text-primary-100">
                                    PKR {{ number_format((float) $entry->debit_total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-400">
                                    No shared cost allocation entries recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
