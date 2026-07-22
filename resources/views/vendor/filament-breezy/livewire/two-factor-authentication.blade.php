<x-filament::section
    :heading="__('filament-breezy::default.profile.2fa.title')"
    :description="__('filament-breezy::default.profile.2fa.description')"
>
    <div class="fi-profile-security-stack">
        @if ($this->showRequiresTwoFactorAlert())
            <x-filament::callout
                color="danger"
                icon="heroicon-s-shield-exclamation"
                icon-size="sm"
                :heading="__('filament-breezy::default.profile.2fa.must_enable')"
            />
        @endif

        @unless ($user->hasEnabledTwoFactor())
            <x-filament::callout
                color="warning"
                icon="heroicon-o-exclamation-circle"
                icon-size="sm"
                :heading="__('filament-breezy::default.profile.2fa.not_enabled.title')"
                :description="__('filament-breezy::default.profile.2fa.not_enabled.description')"
            >
                <x-slot name="footer">
                    <div class="fi-profile-actions">
                        {{ $this->enableAction }}
                    </div>
                </x-slot>
            </x-filament::callout>
        @else
            @if ($user->hasConfirmedTwoFactor())
                <x-filament::callout
                    color="success"
                    icon="heroicon-o-shield-check"
                    icon-size="sm"
                    :heading="__('filament-breezy::default.profile.2fa.enabled.title')"
                    :description="__('filament-breezy::default.profile.2fa.enabled.description')"
                >
                    <x-slot name="footer">
                        <div class="fi-profile-actions fi-profile-actions-between">
                            {{ $this->regenerateCodesAction }}
                            {{ $this->disableAction }}
                        </div>
                    </x-slot>
                </x-filament::callout>

                @if ($showRecoveryCodes)
                    <x-filament::section
                        compact
                        secondary
                        :heading="__('filament-breezy::default.profile.2fa.enabled.store_codes')"
                    >
                        <div class="fi-profile-recovery-codes">
                            @foreach ($this->recoveryCodes->toArray() as $code)
                                <x-filament::badge color="gray">
                                    {{ $code }}
                                </x-filament::badge>
                            @endforeach
                        </div>

                        <x-slot name="footer">
                            <x-filament-breezy::clipboard-link :data="$this->recoveryCodes->join(',')" />
                        </x-slot>
                    </x-filament::section>
                @endif
            @else
                <x-filament::callout
                    color="info"
                    icon="heroicon-o-question-mark-circle"
                    icon-size="sm"
                    :heading="__('filament-breezy::default.profile.2fa.finish_enabling.title')"
                    :description="__('filament-breezy::default.profile.2fa.finish_enabling.description')"
                >
                    <x-slot name="footer">
                        <div class="fi-profile-actions fi-profile-actions-between">
                            {{ $this->confirmAction }}
                            {{ $this->disableAction }}
                        </div>
                    </x-slot>
                </x-filament::callout>

                <div class="fi-profile-two-factor-setup">
                    <div class="fi-profile-qr-code">
                        {!! $this->getTwoFactorQrCode() !!}

                        <p class="fi-profile-setup-key">
                            <span>{{ __('filament-breezy::default.profile.2fa.setup_key') }}</span>
                            <code>{{ $this->two_factor_secret }}</code>
                        </p>
                    </div>

                    <x-filament::section
                        compact
                        secondary
                        :heading="__('filament-breezy::default.profile.2fa.enabled.store_codes')"
                    >
                        <div class="fi-profile-recovery-codes">
                            @foreach ($this->recoveryCodes->toArray() as $code)
                                <x-filament::badge color="gray">
                                    {{ $code }}
                                </x-filament::badge>
                            @endforeach
                        </div>

                        <x-slot name="footer">
                            <x-filament-breezy::clipboard-link :data="$this->recoveryCodes->join(',')" />
                        </x-slot>
                    </x-filament::section>
                </div>
            @endif
        @endunless
    </div>

    <x-filament-actions::modals />
</x-filament::section>
