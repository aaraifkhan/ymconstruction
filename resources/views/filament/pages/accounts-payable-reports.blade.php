<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="AP Aging">
            <div class="grid gap-4 md:grid-cols-5">
                @foreach (['current' => 'Current', '1_30' => '1–30 days', '31_60' => '31–60 days', '61_90' => '61–90 days', 'over_90' => 'Over 90 days'] as $key => $label)
                    <div class="rounded-lg border p-4">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-lg font-semibold">PKR {{ number_format((float) ($agingBuckets[$key] ?? 0), 2) }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section heading="Unpaid Vendor Bills">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Bill</th><th class="p-2">Vendor</th><th class="p-2">Due</th><th class="p-2 text-right">Open amount</th></tr></thead>
                    <tbody>
                    @forelse ($unpaidBills as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['number'] }}</td><td class="p-2">{{ $row['vendor'] }}</td><td class="p-2">{{ $row['due_date'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['open_amount'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="4">No unpaid posted Vendor Bills.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Handed-over Receipts Awaiting Invoice">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">GRN</th><th class="p-2">Item</th><th class="p-2 text-right">Quantity</th><th class="p-2 text-right">GRNI value</th></tr></thead>
                    <tbody>
                    @forelse ($unmatchedReceipts as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['grn'] }}</td><td class="p-2">{{ $row['item'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['quantity'], 4) }}</td><td class="p-2 text-right">{{ number_format((float) $row['value'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="4">No unmatched handed-over receipt quantities.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
