<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use BezhanSalleh\PluginEssentials\Concerns\Resource as Essentials;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Override;

class RoleResource extends Resource
{
    use Essentials\BelongsToParent;
    use Essentials\BelongsToTenant;
    use Essentials\HasGlobalSearch;
    use Essentials\HasLabels;
    use Essentials\HasNavigation;
    use HasShieldFormComponents;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(
                                        ignoreRecord: true, /** @phpstan-ignore-next-line */
                                        modifyRuleUsing: fn (Unique $rule): Unique => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id) : $rule
                                    )
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    /** @phpstan-ignore-next-line */
                                    ->default(Filament::getTenant()?->id)
                                    ->options(fn (): array => in_array(Utils::getTenantModel(), [null, '', '0'], true) ? [] : Utils::getTenantModel()::pluck('name', 'id')->toArray())
                                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled())
                                    ->dehydrated(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                                static::getSelectAllFormComponent(),

                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                static::getShieldFormComponents(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight(FontWeight::Medium)
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('team.name')
                    ->default('Global')
                    ->badge()
                    ->color(fn (mixed $state): string => str($state)->contains('Global') ? 'gray' : 'primary')
                    ->label(__('filament-shield::filament-shield.column.team'))
                    ->searchable()
                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->color('primary'),
                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster();
    }

    public static function getEssentialsPlugin(): ?FilamentShieldPlugin
    {
        return FilamentShieldPlugin::get();
    }

    public static function getCheckboxListFormComponent(string $name, array $options, bool $searchable = true, array|int|string|null $columns = null, array|int|string|null $columnSpan = null): \Filament\Schemas\Components\Component
    {
        $isResource = ! in_array($name, ['pages_tab', 'widgets_tab', 'custom_permissions']);

        $friendlyOptions = [];
        $descriptions = [];
        foreach ($options as $key => $label) {
            $parts = explode(':', $key);
            $action = $parts[0];

            if ($isResource) {
                $isRolePerm = str_ends_with($key, '_role') || str_ends_with($key, 'Role');

                $friendlyOptions[$key] = match ($action) {
                    'view_any', 'ViewAny' => 'View All (List)',
                    'view', 'View' => 'View Details',
                    'create', 'Create' => 'Create New',
                    'update', 'Update' => 'Edit / Update',
                    'delete', 'Delete' => $isRolePerm ? 'Delete' : 'Delete (Soft)',
                    'delete_any', 'DeleteAny' => $isRolePerm ? 'Delete Multiple' : 'Delete Multiple (Soft)',
                    'force_delete', 'ForceDelete' => 'Permanent Delete',
                    'force_delete_any', 'ForceDeleteAny' => 'Permanent Delete Multiple',
                    'restore', 'Restore' => 'Restore',
                    'restore_any', 'RestoreAny' => 'Restore Multiple',
                    'reset_password', 'ResetPassword' => 'Reset Password',
                    default => $label,
                };
    
                $desc = match ($action) {
                    'view_any', 'ViewAny' => 'Can see the list of records.',
                    'view', 'View' => 'Can see the details of a single record.',
                    'create', 'Create' => 'Can create a new record.',
                    'update', 'Update' => 'Can modify an existing record.',
                    'delete', 'Delete' => $isRolePerm ? 'Can delete a record.' : 'Can move a record to trash (soft delete).',
                    'delete_any', 'DeleteAny' => $isRolePerm ? 'Can delete multiple records.' : 'Can move multiple records to trash at once.',
                    'force_delete', 'ForceDelete' => 'Can permanently erase a record from the database.',
                    'force_delete_any', 'ForceDeleteAny' => 'Can permanently erase multiple records at once.',
                    'restore', 'Restore' => 'Can recover a record from trash.',
                    'restore_any', 'RestoreAny' => 'Can recover multiple records from trash at once.',
                    default => null,
                };
    
                if ($desc) {
                    $descriptions[$key] = $desc;
                }
            } else {
                $friendlyOptions[$key] = $label;
                
                // Provide a generic description for pages and widgets
                if (in_array($name, ['pages_tab', 'widgets_tab'])) {
                    $type = $name === 'pages_tab' ? 'page' : 'widget';
                    $descriptions[$key] = "Can access and view this {$type}.";
                }
            }
        }

        return CheckboxList::make($name)
            ->hiddenLabel()
            ->options(fn (): array => $friendlyOptions)
            ->descriptions($descriptions)
            ->searchable($searchable)
            ->live()
            ->afterStateHydrated(function (\Filament\Schemas\Components\Component $component, string $operation, ?Model $record, \Filament\Schemas\Components\Utilities\Set $set) use ($options): void {
                static::setPermissionStateForRecordPermissions(
                    component: $component,
                    operation: $operation,
                    permissions: $options,
                    record: $record
                );

                static::toggleSelectAllViaEntities($component->getLivewire(), $set);
            })
            ->afterStateUpdated(function (Component $livewire, \Filament\Schemas\Components\Utilities\Set $set): void {
                static::toggleSelectAllViaEntities($livewire, $set);
            })
            ->selectAllAction(fn (
                \Filament\Actions\Action $action,
                \Filament\Schemas\Components\Component $component,
                Component $livewire,
                \Filament\Schemas\Components\Utilities\Set $set
            ) => static::bulkToggleableAction(
                action: $action,
                component: $component,
                livewire: $livewire,
                set: $set
            ))
            ->deselectAllAction(fn (
                \Filament\Actions\Action $action,
                \Filament\Schemas\Components\Component $component,
                Component $livewire,
                \Filament\Schemas\Components\Utilities\Set $set
            ) => static::bulkToggleableAction(
                action: $action,
                component: $component,
                livewire: $livewire,
                set: $set,
                resetState: true
            ))
            ->dehydrated(fn ($state): bool => ! blank($state))
            ->bulkToggleable()
            ->gridDirection('row')
            ->columns($columns ?? static::shield()->getCheckboxListColumns())
            ->columnSpan($columnSpan ?? static::shield()->getCheckboxListColumnSpan());
    }
}
