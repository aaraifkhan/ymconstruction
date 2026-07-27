<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Asset Register">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Asset</th><th class="p-2">Category</th><th class="p-2">Location / Project</th><th class="p-2">Status</th><th class="p-2 text-right">Cost</th><th class="p-2 text-right">Accumulated depreciation</th><th class="p-2 text-right">Carrying amount</th></tr></thead>
                    <tbody>
                    @forelse ($assets as $asset)
                        <tr class="border-b"><td class="p-2">{{ $asset->asset_number }} — {{ $asset->name }}</td><td class="p-2">{{ $asset->category->name }}</td><td class="p-2">{{ $asset->location ?: ($asset->project?->name ?? '—') }}</td><td class="p-2">{{ str($asset->status->value)->headline() }}</td><td class="p-2 text-right">{{ number_format((float) $asset->acquisition_cost, 2) }}</td><td class="p-2 text-right">{{ number_format((float) $asset->accumulated_depreciation, 2) }}</td><td class="p-2 text-right">{{ number_format((float) $asset->carryingAmount(), 2) }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="7">No fixed assets registered.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Asset Register / GL Reconciliation">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="p-2">Categories</th><th class="p-2">Control accounts</th><th class="p-2 text-right">Register cost / GL</th><th class="p-2 text-right">Register accumulated / GL</th><th class="p-2">Result</th></tr></thead>
                    <tbody>
                    @forelse ($reconciliationRows as $row)
                        <tr class="border-b"><td class="p-2">{{ $row['categories'] }}</td><td class="p-2">{{ $row['cost_account']->code }} / {{ $row['accumulated_account']?->code ?? '—' }}</td><td class="p-2 text-right">{{ number_format((float) $row['register_cost'], 2) }} / {{ number_format((float) $row['gl_cost'], 2) }}</td><td class="p-2 text-right">{{ number_format((float) $row['register_accumulated'], 2) }} / {{ number_format((float) $row['gl_accumulated'], 2) }}</td><td class="p-2">{{ $row['reconciled'] ? 'Reconciled' : 'Mismatch' }}</td></tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="5">No asset categories configured.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
