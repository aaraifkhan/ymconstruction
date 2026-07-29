<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Payroll Summary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Payroll</th><th class="p-2">Status</th><th class="p-2 text-right">Employees</th><th class="p-2 text-right">Gross</th><th class="p-2 text-right">Bonus / Incentive</th><th class="p-2 text-right">Attendance deductions</th><th class="p-2 text-right">Loan / Advance</th><th class="p-2 text-right">Net</th></tr></thead>
                    <tbody>
                    @forelse ($summaryRows as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['run']->reference_number }}<div class="text-xs text-gray-500">{{ $row['run']->period_start->format('d M') }} – {{ $row['run']->period_end->format('d M Y') }}</div></td><td class="p-2">{{ $row['run']->status->getLabel() }}</td><td class="p-2 text-right">{{ $row['employees'] }}</td><td class="p-2 text-right">{{ number_format((float) $row['gross'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['bonus'], 2) }} / {{ number_format((float) $row['incentive'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['attendance_deductions'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['financing_recovery'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['net'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="8">No payroll runs.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Salary Register">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Period</th><th class="p-2">Employee</th><th class="p-2 text-right">Gross</th><th class="p-2 text-right">Attendance deductions</th><th class="p-2 text-right">Loan / Advance</th><th class="p-2 text-right">Other</th><th class="p-2 text-right">Net</th></tr></thead>
                    <tbody>
                    @forelse ($salaryRegisterRows as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['run']->period_end->format('M Y') }}</td><td class="p-2">{{ $row['entry']->employee_name }}<div class="text-xs text-gray-500">{{ $row['entry']->employee_code }}</div></td><td class="p-2 text-right">{{ number_format((float) $row['gross'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['attendance_deductions'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['financing_recovery'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['other_deduction'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['net'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="7">No salary register rows.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Project-wise Payroll">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Period</th><th class="p-2">Project</th><th class="p-2">Site / Cost center</th><th class="p-2">Employee</th><th class="p-2 text-right">Allocated payroll</th></tr></thead>
                    <tbody>
                    @forelse ($projectRows as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['run']->period_end->format('M Y') }}</td><td class="p-2">{{ $row['project']->name }}</td><td class="p-2">{{ $row['site']?->name ?? '—' }} / {{ $row['cost_center']?->name ?? '—' }}</td><td class="p-2">{{ $row['entry']->employee_name }}</td><td class="p-2 text-right">{{ number_format((float) $row['amount'], 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="5">No project payroll allocations.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

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
