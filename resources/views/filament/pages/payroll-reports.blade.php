<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Payroll / GL / Settlement Reconciliation">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Payroll</th><th class="p-2">Period</th><th class="p-2 text-right">Payroll expense</th><th class="p-2 text-right">GL debit / credit</th><th class="p-2 text-right">Settled</th><th class="p-2 text-right">Open</th><th class="p-2">Result</th></tr></thead>
                    <tbody>
                    @forelse ($reconciliationRows as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['run']->reference_number }}</td><td class="p-2">{{ $row['run']->period_start->format('d M') }} – {{ $row['run']->period_end->format('d M Y') }}</td><td class="p-2 text-right">{{ number_format((float) $row['payroll_expense'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['journal_debit'], 2) }} / {{ number_format((float) $row['journal_credit'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['settled'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['open'], 2) }}</td><td class="p-2">{{ $row['reconciled'] ? 'Reconciled' : 'Mismatch' }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="7">No posted payroll runs.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Employee Advance Subledger">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Employee</th><th class="p-2">Date</th><th class="p-2">Voucher</th><th class="p-2">Description</th><th class="p-2 text-right">Advance</th><th class="p-2 text-right">Recovery</th></tr></thead>
                    <tbody>
                    @forelse ($advanceRows as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['employee'] }}</td><td class="p-2">{{ $row['date'] }}</td><td class="p-2">{{ $row['voucher'] }}</td><td class="p-2">{{ $row['description'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['advance'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['recovery'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="6">No posted employee advance or recovery activity.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
