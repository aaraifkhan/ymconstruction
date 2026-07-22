<form wire:submit="submit" class="fi-profile-form">
    <x-filament::section
        :heading="__('filament-breezy::default.profile.password.heading')"
        :description="__('filament-breezy::default.profile.password.subheading')"
    >
        {{ $this->form }}

        <x-slot name="footer">
            <div class="fi-profile-actions">
                <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="submit">
                    {{ __('filament-breezy::default.profile.password.submit.label') }}
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::section>
</form>
