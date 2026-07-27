<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Reporting period" :description="$periodLabel ?? 'No financial year is configured.'" />

        <x-filament::section heading="Trial Balance">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Code</th><th class="p-2">Account</th><th class="p-2 text-right">Debit</th><th class="p-2 text-right">Credit</th></tr></thead>
                    <tbody>
                    @forelse ($trialBalance as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['code'] }}</td><td class="p-2">{{ $row['name'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['debit_balance'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['credit_balance'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="4">No posted journal lines.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section heading="Profit & Loss">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt>Revenue</dt><dd>{{ number_format((float) ($profitAndLoss['revenue_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between"><dt>Expenses</dt><dd>{{ number_format((float) ($profitAndLoss['expense_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between border-t pt-2 font-semibold"><dt>Profit / (Loss)</dt><dd>{{ number_format((float) ($profitAndLoss['profit_or_loss'] ?? 0), 2) }}</dd></div>
                </dl>
            </x-filament::section>

            <x-filament::section heading="Balance Sheet">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt>Assets</dt><dd>{{ number_format((float) ($balanceSheet['asset_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between"><dt>Liabilities</dt><dd>{{ number_format((float) ($balanceSheet['liability_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between"><dt>Equity + current result</dt><dd>{{ number_format((float) ($balanceSheet['equity_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between border-t pt-2 font-semibold"><dt>Reconciled</dt><dd>{{ ($balanceSheet['balances'] ?? false) ? 'Yes' : 'No' }}</dd></div>
                </dl>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
