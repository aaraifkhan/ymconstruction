<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Cash and Bank Position">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Account</th><th class="p-2">Bank</th><th class="p-2 text-right">Balance</th></tr></thead>
                    <tbody>
                    @forelse ($positions as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['account'] }}</td><td class="p-2">{{ $row['bank'] ?? 'Cash' }}</td><td class="p-2 text-right">PKR {{ number_format((float) $row['balance'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="3">No active cash or bank mappings.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Current Month Cash Book">
            <div class="grid gap-4 md:grid-cols-4">
                @foreach (['opening_balance' => 'Opening', 'debit_total' => 'Receipts / Debits', 'credit_total' => 'Payments / Credits', 'closing_balance' => 'Closing'] as $key => $label)
                    <div class="rounded-lg border p-4">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-lg font-semibold">PKR {{ number_format((float) ($cashBook[$key] ?? 0), 2) }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section heading="Unreconciled Bank Items">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Bank</th><th class="p-2">Account</th><th class="p-2 text-right">Statement items</th><th class="p-2 text-right">Book items</th></tr></thead>
                    <tbody>
                    @forelse ($unreconciled as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['bank'] }}</td><td class="p-2">{{ $row['account'] }}</td><td class="p-2 text-right">{{ $row['statement_count'] }}</td><td class="p-2 text-right">{{ $row['book_count'] }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="4">No active company bank accounts.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
