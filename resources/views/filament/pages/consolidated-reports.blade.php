<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Authorized consolidation scope" :description="$periodLabel ?? 'No financial year configured.'">
            <p class="text-sm">{{ $companyNames ? implode(', ', $companyNames) : 'No companies available.' }}</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border p-3"><span class="text-sm">Ledger and elimination reconciliation</span><div class="font-semibold">{{ $reconciles ? 'Passed' : 'Review required' }}</div></div>
                <div class="rounded-lg border p-3"><span class="text-sm">Accounting integrity audit</span><div class="font-semibold">{{ $integrityPasses ? 'Passed' : 'Review required' }}</div></div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Consolidated Trial Balance">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Code</th><th class="p-2">Mapped account</th><th class="p-2 text-right">Debit</th><th class="p-2 text-right">Credit</th></tr></thead>
                    <tbody>
                    @forelse ($trialBalance as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['code'] }}</td><td class="p-2">{{ $row['name'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['debit_balance'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['credit_balance'], 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="p-4 text-center">No posted group balances.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section heading="Consolidated P&L">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt>Revenue</dt><dd>{{ number_format((float) ($profitAndLoss['revenue_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between"><dt>Expenses</dt><dd>{{ number_format((float) ($profitAndLoss['expense_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between border-t pt-2 font-semibold"><dt>Profit / (Loss)</dt><dd>{{ number_format((float) ($profitAndLoss['profit_or_loss'] ?? 0), 2) }}</dd></div>
                </dl>
            </x-filament::section>
            <x-filament::section heading="Consolidated Balance Sheet">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt>Assets</dt><dd>{{ number_format((float) ($balanceSheet['asset_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between"><dt>Liabilities</dt><dd>{{ number_format((float) ($balanceSheet['liability_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between"><dt>Equity + current result</dt><dd>{{ number_format((float) ($balanceSheet['equity_total'] ?? 0), 2) }}</dd></div>
                    <div class="flex justify-between border-t pt-2 font-semibold"><dt>Balances</dt><dd>{{ ($balanceSheet['balances'] ?? false) ? 'Yes' : 'No' }}</dd></div>
                </dl>
            </x-filament::section>
        </div>

        <x-filament::section heading="Inter-company Reconciliation">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Company pair</th><th class="p-2 text-right">Due from</th><th class="p-2 text-right">Due to</th><th class="p-2 text-right">Difference</th><th class="p-2">Status</th></tr></thead>
                    <tbody>
                    @forelse ($reconciliation as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['first_company'] }} / {{ $row['second_company'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['due_from'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['due_to'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['difference'], 2) }}</td><td class="p-2">{{ $row['reconciles'] ? 'Reconciled' : 'Mismatch' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-center">No inter-company control balances.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
