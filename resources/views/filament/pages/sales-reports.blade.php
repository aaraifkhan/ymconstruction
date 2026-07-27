<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Accounts Receivable Aging">
            <div class="grid gap-4 md:grid-cols-5">
                @foreach (['current' => 'Current', '1_30' => '1–30 days', '31_60' => '31–60 days', '61_90' => '61–90 days', 'over_90' => 'Over 90 days'] as $key => $label)
                    <div class="rounded-lg border p-4">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-lg font-semibold">PKR {{ number_format((float) ($agingBuckets[$key] ?? 0), 2) }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section heading="Unpaid Customer Invoices">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Invoice</th><th class="p-2">Customer</th><th class="p-2">Due</th><th class="p-2 text-right">Open amount</th></tr></thead>
                    <tbody>
                    @forelse ($unpaidInvoices as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['number'] }}</td><td class="p-2">{{ $row['customer'] }}</td><td class="p-2">{{ $row['due_date'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['open_amount'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="4">No unpaid posted Customer Invoices.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Project Budget, Revenue, Cost & Profit">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Project</th><th class="p-2 text-right">Budget</th><th class="p-2 text-right">Revenue</th><th class="p-2 text-right">Cost</th><th class="p-2 text-right">Profit</th><th class="p-2 text-right">Budget variance</th></tr></thead>
                    <tbody>
                    @forelse ($projects as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['code'] }} — {{ $row['name'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['budget'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['revenue'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['cost'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['profit'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['variance'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="6">No Projects available for this company.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
