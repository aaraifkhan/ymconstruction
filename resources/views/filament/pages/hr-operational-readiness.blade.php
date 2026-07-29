<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3">
        <x-filament::section>
            <x-slot name="heading">Configuration</x-slot>
            <div class="space-y-2">
                @foreach ($report['configuration'] as $name => $passes)
                    <div class="flex items-center justify-between gap-3">
                        <span>{{ str($name)->headline() }}</span>
                        <x-filament::badge :color="$passes ? 'success' : 'danger'">
                            {{ $passes ? 'Ready' : 'Required' }}
                        </x-filament::badge>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Reconciliation</x-slot>
            <div class="space-y-2">
                @foreach ($report['reconciliation'] as $name => $value)
                    <div class="flex items-center justify-between gap-3">
                        <span>{{ str($name)->headline() }}</span>
                        @if (is_bool($value))
                            <x-filament::badge :color="$value ? 'success' : 'danger'">
                                {{ $value ? 'Pass' : 'Fail' }}
                            </x-filament::badge>
                        @else
                            <span>{{ $value }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Attendance device continuity</x-slot>
            <div class="space-y-2">
                <p>Normalized private CSV fallback:
                    <strong>{{ $report['device_offline_continuity']['normalized_csv_fallback_available'] ? 'Available' : 'Unavailable' }}</strong>
                </p>
                <p>Configured devices: {{ $report['device_offline_continuity']['configured_devices'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $report['device_offline_continuity']['blocker'] }}
                </p>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Pilot rollout gates</x-slot>
        <div class="mb-4">
            <x-filament::badge :color="$report['pilot_ready_except_device_connector'] ? 'success' : 'warning'">
                {{ $report['pilot_ready_except_device_connector'] ? 'Application ready; device connector pending' : 'Action required' }}
            </x-filament::badge>
        </div>
        <ul class="list-disc space-y-1 ps-5">
            @foreach ($report['rollout_blockers'] as $blocker)
                <li>{{ $blocker }}</li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-panels::page>
