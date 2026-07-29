<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="HR Dashboard" description="Company-scoped workforce position and current-month movements">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'Unique people' => $dashboard['unique_people'] ?? 0,
                    'Employment records' => $dashboard['employment_count'] ?? 0,
                    'Active / probation' => $dashboard['active_count'] ?? 0,
                    'On leave' => $dashboard['on_leave_count'] ?? 0,
                    'Joiners this month' => $dashboard['joiners_this_month'] ?? 0,
                    'Exits this month' => $dashboard['exits_this_month'] ?? 0,
                    'Pending leave' => $dashboard['pending_leave_requests'] ?? 0,
                    'Attendance exceptions' => $dashboard['attendance_exceptions'] ?? 0,
                ] as $label => $value)
                    <div class="rounded-lg border p-3">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-semibold">{{ number_format($value) }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section heading="Employee List">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Employee</th><th class="p-2">Department / Designation</th><th class="p-2">Type / Status</th><th class="p-2">Work Location</th><th class="p-2">Joining / Ending</th></tr></thead>
                    <tbody>
                    @forelse ($employees as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['employee_name'] }}<div class="text-xs text-gray-500">{{ $row['employee_code'] }}</div></td><td class="p-2">{{ $row['department'] }}<div class="text-xs text-gray-500">{{ $row['designation'] }}</div></td><td class="p-2">{{ $row['employment_type'] }}<div class="text-xs text-gray-500">{{ $row['employment_status'] }}</div></td><td class="p-2">{{ $row['work_location'] }}</td><td class="p-2">{{ $row['joining_date'] }}<div class="text-xs text-gray-500">{{ $row['ending_date'] ?? 'Current' }}</div></td></tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-center">No Employments.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section heading="Department-wise Employees">
                <div class="space-y-2">
                    @forelse (collect($departments)->groupBy('department') as $department => $rows)
                        <div class="flex justify-between rounded-lg border p-3"><span>{{ $department }}</span><span class="font-semibold">{{ $rows->count() }}</span></div>
                    @empty
                        <p class="text-sm text-gray-500">No Department assignments.</p>
                    @endforelse
                </div>
            </x-filament::section>
            <x-filament::section heading="Designation-wise Employees">
                <div class="space-y-2">
                    @forelse (collect($designations)->groupBy('designation') as $designation => $rows)
                        <div class="flex justify-between rounded-lg border p-3"><span>{{ $designation }}</span><span class="font-semibold">{{ $rows->count() }}</span></div>
                    @empty
                        <p class="text-sm text-gray-500">No Designation assignments.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        @if ($canViewFinancing)
            <x-filament::section heading="Employee Loan & Advance Report">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b text-left"><th class="p-2">Type / Reference</th><th class="p-2">Employee</th><th class="p-2">Date / Status</th><th class="p-2 text-right">Principal</th><th class="p-2 text-right">Repayable</th><th class="p-2 text-right">Outstanding</th></tr></thead>
                        <tbody>
                        @forelse ($financing as $row)
                            <tr class="border-b"><td class="p-2">{{ $row['type'] }}<div class="text-xs text-gray-500">{{ $row['reference_number'] }}</div></td><td class="p-2">{{ $row['employee_name'] }}<div class="text-xs text-gray-500">{{ $row['employee_code'] }}</div></td><td class="p-2">{{ $row['request_date'] }}<div class="text-xs text-gray-500">{{ $row['status'] }}</div></td><td class="p-2 text-right">{{ number_format((float) $row['principal'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['total_repayable'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['outstanding'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="p-4 text-center">No Employee Loans or Advances.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if ($canViewIncrements)
            <x-filament::section heading="Increment History">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b text-left"><th class="p-2">Employee</th><th class="p-2">Effective Period</th><th class="p-2 text-right">Previous Gross</th><th class="p-2 text-right">New Gross</th><th class="p-2 text-right">Increment</th></tr></thead>
                        <tbody>
                        @forelse ($increments as $row)
                            <tr class="border-b"><td class="p-2">{{ $row['employee_name'] }}<div class="text-xs text-gray-500">{{ $row['employee_code'] }}</div></td><td class="p-2">{{ $row['effective_from'] }}<div class="text-xs text-gray-500">{{ $row['effective_to'] ?? 'Current' }}</div></td><td class="p-2 text-right">{{ $row['previous_gross'] === null ? '—' : number_format((float) $row['previous_gross'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['new_gross'], 2) }}</td><td class="p-2 text-right">{{ $row['increment'] === null ? '—' : number_format((float) $row['increment'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center">No approved Compensation history.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if ($canViewAttendance)
            <x-filament::section heading="Attendance Summary">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b text-left"><th class="p-2">Period / Employee</th><th class="p-2 text-right">Scheduled</th><th class="p-2 text-right">Present</th><th class="p-2 text-right">Absent</th><th class="p-2 text-right">Half / Leave</th><th class="p-2 text-right">Late / OT minutes</th><th class="p-2">State</th></tr></thead>
                        <tbody>
                        @forelse ($attendance as $row)
                            <tr class="border-b"><td class="p-2">{{ $row['period'] }}<div class="text-xs text-gray-500">{{ $row['employee_name'] }} · {{ $row['employee_code'] }}</div></td><td class="p-2 text-right">{{ $row['scheduled_days'] }}</td><td class="p-2 text-right">{{ $row['present_days'] }}</td><td class="p-2 text-right">{{ $row['absent_days'] }}</td><td class="p-2 text-right">{{ $row['half_days'] }} / {{ $row['leave_days'] }}</td><td class="p-2 text-right">{{ $row['late_minutes'] }} / {{ $row['overtime_minutes'] }}</td><td class="p-2">{{ $row['status'] }}</td></tr>
                        @empty
                            <tr><td colspan="7" class="p-4 text-center">No Attendance summaries.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if ($canViewLeave)
            <x-filament::section heading="Leave Summary">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b text-left"><th class="p-2">Employee</th><th class="p-2">Leave Type / Dates</th><th class="p-2 text-right">Requested</th><th class="p-2 text-right">Balance</th><th class="p-2">Paid / Status</th></tr></thead>
                        <tbody>
                        @forelse ($leave as $row)
                            <tr class="border-b"><td class="p-2">{{ $row['employee_name'] }}<div class="text-xs text-gray-500">{{ $row['employee_code'] }}</div></td><td class="p-2">{{ $row['leave_type'] }}<div class="text-xs text-gray-500">{{ $row['dates'] }}</div></td><td class="p-2 text-right">{{ $row['requested_units'] }}</td><td class="p-2 text-right">{{ $row['current_balance'] }}</td><td class="p-2">{{ $row['paid'] }}<div class="text-xs text-gray-500">{{ $row['status'] }}</div></td></tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center">No Leave requests.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
