<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Filament\Resources\Accounts\AccountResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('provision')
                ->label('Provision standard accounts')
                ->authorize('Provision:Account')
                ->schema([
                    Select::make('profile')
                        ->options(collect(AccountingProfile::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                        ->default(fn (): string => Filament::getTenant()->accountingSettings()->first()?->profile?->value ?? AccountingProfile::Generic->value)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    app(ProvisionStandardAccountTemplatesAction::class)->handle();
                    app(ProvisionCompanyAccountingFoundationAction::class)->handle(Filament::getTenant(), AccountingProfile::from($data['profile']));
                    Notification::make()->success()->title('Accounting foundation provisioned')->send();
                }),
            CreateAction::make(),
        ];
    }
}
