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
use Filament\Actions\Action;
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
use Filament\Schemas\Components\Utilities\Set;
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

    protected static bool $isScopedToTenant = false;

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
                    'manage_members', 'ManageMembers' => 'Manage Company Users',
                    'view_sensitive', 'ViewSensitive' => 'View Sensitive Details',
                    'view_identity', 'ViewIdentity' => 'View Identity Details',
                    'view_contact', 'ViewContact' => 'View Private Contact Details',
                    'view_medical', 'ViewMedical' => 'View Medical Details',
                    'manage_sensitive', 'ManageSensitive' => 'Edit Sensitive Employee Details',
                    'view_hr_notes', 'ViewHrNotes' => 'View Private HR Notes',
                    'manage_hr_verification', 'ManageHrVerification' => 'Manage HR Verification',
                    'view_compensation', 'ViewCompensation' => 'View Compensation',
                    'manage_compensation', 'ManageCompensation' => 'Manage Compensation Snapshot',
                    'view_amounts', 'ViewAmounts' => 'View Salary Amounts',
                    'manage_amounts', 'ManageAmounts' => 'Manage Salary Amounts',
                    'generate_entries', 'GenerateEntries' => 'Generate Payroll Entries',
                    'mark_paid', 'MarkPaid' => 'Mark Payroll Paid',
                    'lock', 'Lock' => 'Lock Final Payroll',
                    'regenerate', 'Regenerate' => 'Regenerate from Template',
                    'submit', 'Submit' => 'Submit for Approval',
                    'issue', 'Issue' => 'Issue Approved Record',
                    'cancel', 'Cancel' => 'Cancel Record',
                    'receive', 'Receive' => 'Record Material Receipt',
                    'inspect', 'Inspect' => 'Inspect Received Material',
                    'handover', 'Handover' => 'Handover to Accounts',
                    'review_match', 'ReviewMatch' => 'Review Three-Way Match',
                    'override_match', 'OverrideMatch' => 'Override Match Exception',
                    'import', 'Import' => 'Import Bank Statement',
                    'match', 'Match' => 'Match Bank Activity',
                    'unmatch', 'Unmatch' => 'Remove Bank Match',
                    'adjust', 'Adjust' => 'Post Reconciliation Adjustment',
                    'close', 'Close' => 'Close / Lock Record',
                    'reopen', 'Reopen' => 'Reopen Closed Record',
                    'return_rejected', 'ReturnRejected' => 'Return Rejected Material',
                    'record_acceptance', 'RecordAcceptance' => 'Record Employee Acceptance',
                    'download', 'Download' => 'Download Files',
                    'preview', 'Preview' => 'Preview Files',
                    'upload_version', 'UploadVersion' => 'Upload New Version',
                    'verify', 'Verify' => 'Verify Documents',
                    'approve', 'Approve' => 'Approve',
                    'reject', 'Reject' => 'Reject',
                    'post', 'Post' => 'Post to General Ledger',
                    'reverse', 'Reverse' => 'Reverse Posted Entry',
                    'validate', 'Validate' => 'Validate Opening Balances',
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
                    'manage_members', 'ManageMembers' => 'Can grant, change, and remove user access for a company.',
                    'view_sensitive', 'ViewSensitive' => 'Can view protected bank details or confidential and restricted documents.',
                    'view_identity', 'ViewIdentity' => 'Can view CNIC, date of birth, and other protected identity details.',
                    'view_contact', 'ViewContact' => 'Can view private employee address and contact details.',
                    'view_medical', 'ViewMedical' => 'Can view protected employee medical details.',
                    'manage_sensitive', 'ManageSensitive' => 'Can create or edit protected employee identity, contact, and medical details.',
                    'view_hr_notes', 'ViewHrNotes' => 'Can view private notes maintained by HR.',
                    'manage_hr_verification', 'ManageHrVerification' => 'Can record interview, document verification, and appointment-letter status.',
                    'view_compensation', 'ViewCompensation' => 'Can view compensation included in a joining letter.',
                    'manage_compensation', 'ManageCompensation' => 'Can enter or update the compensation snapshot before approval.',
                    'view_amounts', 'ViewAmounts' => 'Can view protected salary and allowance amounts.',
                    'manage_amounts', 'ManageAmounts' => 'Can enter or update salary and allowance amounts before submission.',
                    'generate_entries', 'GenerateEntries' => 'Can generate or refresh employee payroll snapshots.',
                    'mark_paid', 'MarkPaid' => 'Can confirm that an approved payroll run has been paid.',
                    'lock', 'Lock' => 'Can permanently lock a paid payroll run against further changes.',
                    'regenerate', 'Regenerate' => 'Can rebuild a draft or rejected letter from its selected template.',
                    'submit', 'Submit' => 'Can submit a draft joining letter for approval.',
                    'issue', 'Issue' => 'Can issue an approved record into its operational workflow.',
                    'cancel', 'Cancel' => 'Can cancel an eligible record with a recorded reason.',
                    'record_acceptance', 'RecordAcceptance' => 'Can record an employee’s acceptance of an issued letter.',
                    'import', 'Import' => 'Can import a validated private bank statement file.',
                    'match', 'Match' => 'Can match bank statement activity to posted bank journal lines.',
                    'unmatch', 'Unmatch' => 'Can remove an incorrect match while reconciliation remains open.',
                    'adjust', 'Adjust' => 'Can post an authorized bank reconciliation adjustment.',
                    'close', 'Close' => 'Can close and lock a fully balanced reconciliation.',
                    'reopen', 'Reopen' => 'Can reopen a closed reconciliation with a required reason.',
                    'download', 'Download' => 'Can download private document files.',
                    'preview', 'Preview' => 'Can open a short-lived preview of a private document.',
                    'upload_version', 'UploadVersion' => 'Can add a new immutable file version without replacing history.',
                    'verify', 'Verify' => 'Can confirm that a document and its metadata were checked.',
                    'approve', 'Approve' => 'Can approve the current workflow step.',
                    'reject', 'Reject' => 'Can reject the current workflow step and record a reason.',
                    'post', 'Post' => 'Can create the immutable financial posting in an open period.',
                    'reverse', 'Reverse' => 'Can post a linked opposite entry instead of editing financial history.',
                    'validate', 'Validate' => 'Can verify that an opening Trial Balance is complete and balanced.',
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
            ->afterStateHydrated(function (\Filament\Schemas\Components\Component $component, string $operation, ?Model $record, Set $set) use ($options): void {
                static::setPermissionStateForRecordPermissions(
                    component: $component,
                    operation: $operation,
                    permissions: $options,
                    record: $record
                );

                static::toggleSelectAllViaEntities($component->getLivewire(), $set);
            })
            ->afterStateUpdated(function (Component $livewire, Set $set): void {
                static::toggleSelectAllViaEntities($livewire, $set);
            })
            ->selectAllAction(fn (
                Action $action,
                \Filament\Schemas\Components\Component $component,
                Component $livewire,
                Set $set
            ) => static::bulkToggleableAction(
                action: $action,
                component: $component,
                livewire: $livewire,
                set: $set
            ))
            ->deselectAllAction(fn (
                Action $action,
                \Filament\Schemas\Components\Component $component,
                Component $livewire,
                Set $set
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
