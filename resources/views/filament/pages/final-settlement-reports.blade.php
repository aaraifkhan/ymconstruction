<x-filament-panels::page>
    <x-filament::section heading="Final Settlement / GL / Treasury Reconciliation">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="p-2">Settlement</th>
                        <th class="p-2">Employee</th>
                        <th class="p-2">Cutoff / Status</th>
                        <th class="p-2 text-right">Earnings</th>
                        <th class="p-2 text-right">Recoveries</th>
                        <th class="p-2 text-right">Net</th>
                        <th class="p-2">GL voucher</th>
                        <th class="p-2 text-right">Treasury / Open</th>
                        <th class="p-2">Result</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b">
                        <td class="p-2">{{ $row['reference_number'] }}</td>
                        <td class="p-2">{{ $row['employee_name'] }}<div class="text-xs text-gray-500">{{ $row['employee_code'] }}</div></td>
                        <td class="p-2">{{ $row['cutoff_date'] }}<div class="text-xs text-gray-500">{{ str($row['status'])->headline() }}</div></td>
                        <td class="p-2 text-right">{{ number_format((float) $row['earning_total'], 2) }}</td>
                        <td class="p-2 text-right">{{ number_format((float) $row['recovery_total'], 2) }}</td>
                        <td class="p-2 text-right">{{ str($row['balance_direction'])->headline() }} {{ number_format((float) $row['net_amount'], 2) }}</td>
                        <td class="p-2">{{ $row['gl_voucher'] ?? '—' }}<div class="text-xs text-gray-500">{{ number_format((float) $row['gl_amount'], 2) }}</div></td>
                        <td class="p-2 text-right">{{ number_format((float) $row['treasury_settled'], 2) }} / {{ number_format((float) $row['open_amount'], 2) }}</td>
                        <td class="p-2">{{ $row['operational_gl_reconciled'] && $row['treasury_reconciled'] ? 'Reconciled' : 'Mismatch' }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-center" colspan="9">No Final Settlements.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
