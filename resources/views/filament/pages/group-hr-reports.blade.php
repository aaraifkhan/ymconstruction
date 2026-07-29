<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Authorized Group Scope" :description="$period">
            <p class="text-sm">{{ implode(', ', $companies) }}</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border p-3"><div class="text-sm text-gray-500">Unique people across group</div><div class="text-2xl font-semibold">{{ number_format($uniquePeople) }}</div></div>
                <div class="rounded-lg border p-3"><div class="text-sm text-gray-500">Company Employment records</div><div class="text-2xl font-semibold">{{ number_format($employmentCount) }}</div></div>
            </div>
            <p class="mt-3 text-xs text-gray-500">A person employed by multiple group companies counts once in Unique people and once per company in Employment records.</p>
        </x-filament::section>

        <x-filament::section heading="Company Comparison">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Company</th><th class="p-2 text-right">People / Employments</th><th class="p-2 text-right">Active / Leave</th><th class="p-2 text-right">Joiners / Exits</th><th class="p-2 text-right">Present / Absent / Half</th><th class="p-2 text-right">Late Min / Leave Units</th>@if ($payrollVisible)<th class="p-2 text-right">Payroll Cost</th>@endif @if ($financingVisible)<th class="p-2 text-right">Loan / Advance Outstanding</th>@endif</tr></thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['company'] }}</td><td class="p-2 text-right">{{ $row['unique_people'] }} / {{ $row['employment_count'] }}</td><td class="p-2 text-right">{{ $row['active'] }} / {{ $row['on_leave'] }}</td><td class="p-2 text-right">{{ $row['joiners'] }} / {{ $row['exits'] }}</td><td class="p-2 text-right">{{ $row['present_days'] }} / {{ $row['absent_days'] }} / {{ $row['half_days'] }}</td><td class="p-2 text-right">{{ $row['late_minutes'] }} / {{ $row['leave_units'] }}</td>@if ($payrollVisible)<td class="p-2 text-right">{{ number_format((float) $row['payroll_cost'], 2) }}</td>@endif @if ($financingVisible)<td class="p-2 text-right">{{ number_format((float) $row['loan_outstanding'], 2) }} / {{ number_format((float) $row['advance_outstanding'], 2) }}</td>@endif</tr>
                    @empty
                        <tr><td colspan="8" class="p-4 text-center">No companies in this authorized hierarchy.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
