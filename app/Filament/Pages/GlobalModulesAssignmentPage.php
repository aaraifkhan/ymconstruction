<?php

namespace App\Filament\Pages;

use App\Enums\CompanyModuleState;
use App\Filament\Widgets\GlobalCompanyModulesStatsWidget;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class GlobalModulesAssignmentPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static \UnitEnum|string|null $navigationGroup = 'Company Management';

    protected static ?string $navigationLabel = 'Company Modules Matrix';

    protected static ?string $title = 'Global Company Modules & Capability Matrix';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.global-modules-assignment-page';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && $user->hasRole('super_admin');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GlobalCompanyModulesStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enableAllGlobally')
                ->label('Enable All Modules Globally')
                ->icon('heroicon-m-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Enable all modules for all companies?')
                ->modalDescription('This will set every catalog module to explicitly Enabled across all registered companies.')
                ->action(function (): void {
                    $companies = Company::all();
                    $modules = Module::query()->where('is_active', true)->get();

                    foreach ($companies as $comp) {
                        foreach ($modules as $mod) {
                            CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                                ['company_id' => $comp->id, 'module_id' => $mod->id],
                                ['state' => CompanyModuleState::Enabled],
                            );
                        }
                    }

                    Notification::make()
                        ->title('All Modules Enabled Globally')
                        ->body('Every system module is now active for all companies.')
                        ->success()
                        ->send();
                }),

            Action::make('resetAllGlobally')
                ->label('Reset All to Inherit')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Reset all company modules to Inherit?')
                ->modalDescription('This will reset all company module assignments back to default hierarchy inheritance.')
                ->action(function (): void {
                    $companies = Company::all();
                    $modules = Module::query()->where('is_active', true)->get();

                    foreach ($companies as $comp) {
                        foreach ($modules as $mod) {
                            CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                                ['company_id' => $comp->id, 'module_id' => $mod->id],
                                ['state' => CompanyModuleState::Inherit],
                            );
                        }
                    }

                    Notification::make()
                        ->title('All Modules Reset to Inherit')
                        ->body('Company module states have been reset to hierarchy inheritance.')
                        ->info()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $companies = Company::query()->with('parentCompany')->where('is_active', true)->orderBy('name')->get();

        $columns = [
            TextColumn::make('name')
                ->label('Business Module & Capabilities')
                ->searchable(['name', 'key', 'description', 'navigation_group'])
                ->sortable()
                ->weight(FontWeight::Bold)
                ->icon(fn (Module $record): string => $record->icon ?? 'heroicon-o-cube')
                ->description(fn (Module $record): string => ($record->key ? "[{$record->key}] " : '').($record->description ?? ''))
                ->wrap(),

            TextColumn::make('navigation_group')
                ->label('Domain / Group')
                ->badge()
                ->color('primary')
                ->sortable(),
        ];

        foreach ($companies as $comp) {
            $companyName = $comp->name.($comp->parentCompany ? ' (↳ '.$comp->parentCompany->name.')' : '');

            $columns[] = SelectColumn::make('company_'.$comp->id)
                ->label($companyName)
                ->options([
                    'enabled' => '🟢 Enabled',
                    'disabled' => '🔴 Disabled',
                    'inherit' => '⚪ Inherit',
                ])
                ->selectablePlaceholder(false)
                ->state(function (Module $record) use ($comp): string {
                    $cm = $record->companyModules->firstWhere('company_id', $comp->id);

                    return $cm?->state?->value ?? 'inherit';
                })
                ->updateStateUsing(function (Module $record, string $state) use ($comp): string {
                    $stateEnum = CompanyModuleState::tryFrom($state) ?? CompanyModuleState::Inherit;

                    CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                        ['company_id' => $comp->id, 'module_id' => $record->id],
                        ['state' => $stateEnum],
                    );

                    Notification::make()
                        ->title('Module Status Updated')
                        ->body("{$record->name} is now {$stateEnum->label()} for {$comp->name}.")
                        ->success()
                        ->send();

                    return $state;
                })
                ->alignCenter();
        }

        return $table
            ->query(
                Module::query()
                    ->with(['companyModules' => fn ($query) => $query->withoutGlobalScopes()])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
            )
            ->columns($columns)
            ->filters([
                SelectFilter::make('navigation_group')
                    ->label('Domain / Group')
                    ->options(fn (): array => Module::query()
                        ->where('is_active', true)
                        ->whereNotNull('navigation_group')
                        ->distinct()
                        ->pluck('navigation_group', 'navigation_group')
                        ->toArray()
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('enableOnAllCompanies')
                        ->label('Enable on All Companies')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $companies = Company::all();
                            foreach ($records as $record) {
                                foreach ($companies as $comp) {
                                    CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                                        ['company_id' => $comp->id, 'module_id' => $record->id],
                                        ['state' => CompanyModuleState::Enabled],
                                    );
                                }
                            }
                            Notification::make()->title('Selected modules enabled across all companies')->success()->send();
                        }),

                    BulkAction::make('disableOnAllCompanies')
                        ->label('Disable on All Companies')
                        ->icon('heroicon-m-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $companies = Company::all();
                            foreach ($records as $record) {
                                foreach ($companies as $comp) {
                                    CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                                        ['company_id' => $comp->id, 'module_id' => $record->id],
                                        ['state' => CompanyModuleState::Disabled],
                                    );
                                }
                            }
                            Notification::make()->title('Selected modules disabled across all companies')->warning()->send();
                        }),

                    BulkAction::make('resetToInheritOnAllCompanies')
                        ->label('Reset to Inherit on All Companies')
                        ->icon('heroicon-m-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $companies = Company::all();
                            foreach ($records as $record) {
                                foreach ($companies as $comp) {
                                    CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                                        ['company_id' => $comp->id, 'module_id' => $record->id],
                                        ['state' => CompanyModuleState::Inherit],
                                    );
                                }
                            }
                            Notification::make()->title('Selected modules reset to inherit across all companies')->info()->send();
                        }),
                ]),
            ])
            ->paginated(false);
    }

    public function toggleModuleState(int $companyId, int $moduleId, string $targetState): void
    {
        $company = Company::findOrFail($companyId);
        $module = Module::findOrFail($moduleId);

        $stateEnum = CompanyModuleState::tryFrom($targetState) ?? CompanyModuleState::Inherit;

        CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'module_id' => $moduleId],
            ['state' => $stateEnum],
        );

        Notification::make()
            ->title('Module Status Updated')
            ->body("{$module->name} is now {$stateEnum->label()} for {$company->name}.")
            ->success()
            ->send();
    }

    public function enableAllForCompany(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        $modules = Module::query()->where('is_active', true)->get();

        foreach ($modules as $mod) {
            CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $companyId, 'module_id' => $mod->id],
                ['state' => CompanyModuleState::Enabled],
            );
        }

        Notification::make()
            ->title('All Modules Enabled')
            ->body("All system modules have been enabled for {$company->name}.")
            ->success()
            ->send();
    }

    public function disableAllForCompany(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        $modules = Module::query()->where('is_active', true)->get();

        foreach ($modules as $mod) {
            CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $companyId, 'module_id' => $mod->id],
                ['state' => CompanyModuleState::Disabled],
            );
        }

        Notification::make()
            ->title('All Modules Disabled')
            ->body("All system modules have been disabled for {$company->name}.")
            ->warning()
            ->send();
    }

    public function resetToInheritForCompany(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        $modules = Module::query()->where('is_active', true)->get();

        foreach ($modules as $mod) {
            CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $companyId, 'module_id' => $mod->id],
                ['state' => CompanyModuleState::Inherit],
            );
        }

        Notification::make()
            ->title('Reset to Inherit')
            ->body("All modules for {$company->name} are now set to Inherit.")
            ->info()
            ->send();
    }

    public function enableModuleForAllCompanies(int $moduleId): void
    {
        $module = Module::findOrFail($moduleId);
        $companies = Company::all();

        foreach ($companies as $company) {
            CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'module_id' => $moduleId],
                ['state' => CompanyModuleState::Enabled],
            );
        }

        Notification::make()
            ->title('Module Enabled Globally')
            ->body("{$module->name} has been enabled for all companies.")
            ->success()
            ->send();
    }

    public function disableModuleForAllCompanies(int $moduleId): void
    {
        $module = Module::findOrFail($moduleId);
        $companies = Company::all();

        foreach ($companies as $company) {
            CompanyModule::query()->withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->id, 'module_id' => $moduleId],
                ['state' => CompanyModuleState::Disabled],
            );
        }

        Notification::make()
            ->title('Module Disabled Globally')
            ->body("{$module->name} has been disabled for all companies.")
            ->warning()
            ->send();
    }
}
