<x-filament-panels::page>
    <div class="flex flex-col gap-y-8">
        {{-- Universal Multi-Company Transaction Form --}}
        <form wire:submit.prevent="submit">
            <div class="space-y-6">
                {{ $this->form }}

                <div class="flex items-center justify-end pt-6 pb-4" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-paper-airplane">
                        Submit &amp; Post into Selected Company Ledger
                    </x-filament::button>
                </div>
            </div>
        </form>

        {{-- Visual Divider --}}
        <div class="border-t border-gray-200 dark:border-gray-800 my-2"></div>

        {{-- Recent Cross-Company Transactions via Filament Table --}}
        <div style="margin-top: 1.5rem;">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
