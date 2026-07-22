<div class="fi-sidebar-footer">
    <div class="fi-sidebar-user-menu-with-logout">
        <x-filament-panels::user-menu :position="\Filament\Enums\UserMenuPosition::Sidebar" />

        <form
            action="{{ filament()->getLogoutUrl() }}"
            class="fi-sidebar-user-menu-logout"
            method="post"
        >
            @csrf

            <x-filament::icon-button
                color="gray"
                :icon="\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle"
                :label="__('filament-panels::widgets/account-widget.actions.logout.label')"
                size="xs"
                type="submit"
            />
        </form>
    </div>
</div>
