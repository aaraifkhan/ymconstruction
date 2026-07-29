<div class="space-y-6 text-sm">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold">{{ $letter['company_name'] }}</h2>
            <p>Final Settlement Letter</p>
        </div>
        <div class="text-right">
            <p>{{ $letter['reference_number'] }}</p>
            <p>Last working date: {{ $letter['last_working_date'] }}</p>
        </div>
    </div>

    <div>
        <p><strong>Employee:</strong> {{ $letter['employee_name'] }} ({{ $letter['employee_code'] }})</p>
        <p><strong>Separation:</strong> {{ $letter['separation_type'] }}</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b text-left">
                    <th class="p-2">Component</th>
                    <th class="p-2">Description</th>
                    <th class="p-2">Nature</th>
                    <th class="p-2 text-right">Amount (PKR)</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($letter['lines'] as $line)
                <tr class="border-b">
                    <td class="p-2">{{ $line['component'] }}</td>
                    <td class="p-2">{{ $line['description'] }}</td>
                    <td class="p-2">{{ $line['nature'] }}</td>
                    <td class="p-2 text-right">{{ number_format((float) $line['amount'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="ml-auto max-w-sm space-y-1">
        <div class="flex justify-between"><span>Total earnings</span><strong>{{ number_format((float) $letter['earning_total'], 2) }}</strong></div>
        <div class="flex justify-between"><span>Total recoveries</span><strong>{{ number_format((float) $letter['recovery_total'], 2) }}</strong></div>
        <div class="flex justify-between border-t pt-1"><span>Net {{ $letter['balance_direction'] }}</span><strong>{{ number_format((float) $letter['net_amount'], 2) }}</strong></div>
    </div>

    <p class="text-xs text-gray-500">
        Approved: {{ $letter['approved_at'] ?? 'Pending' }} · Evidence checksum: {{ $letter['source_checksum'] }}
    </p>

    <button type="button" class="fi-btn fi-btn-color-primary" onclick="window.print()">Print letter</button>
</div>
