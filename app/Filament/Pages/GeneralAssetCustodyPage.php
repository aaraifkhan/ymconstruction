<?php

namespace App\Filament\Pages;

use App\Actions\Assets\RegisterGeneralGroupAssetAction;
use App\Actions\Assets\ReturnGeneralAssetToPoolAction;
use App\Actions\Assets\TransferGeneralAssetCustodyAction;
use App\Enums\AssetCustodyStatus;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\Employment;
use App\Models\FixedAsset;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GeneralAssetCustodyPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts Management';

    protected static ?string $navigationLabel = 'General Asset Registry & Custody';

    protected static ?string $title = 'General Group Asset Registry & Cross-Company Custody Hub';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.general-asset-custody-page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        if ($user === null) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:MasterAccountsHub')
            || $user->can('ViewAny:FixedAsset');
    }

    public function getCorporateCompany(): ?Company
    {
        return Company::withoutGlobalScopes()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->where('name', 'LIKE', '%7%Orbit%')
                    ->orWhere('slug', 'LIKE', '%7-orbit%');
            })
            ->first()
            ?? Company::withoutGlobalScopes()->where('is_active', true)->first();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'acquired_on' => today()->toDateString(),
            'condition_on_assignment' => 'Brand New',
            'acquisition_cost' => 0,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $corporateCompany = $this->getCorporateCompany();
        $user = Filament::auth()->user();
        $accessibleCompanies = $user?->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->pluck('name', 'id')
            : $user?->companies()->wherePivot('is_active', true)->pluck('companies.name', 'companies.id') ?? collect();

        return $form
            ->statePath('data')
            ->components([
                Section::make('Register & Deploy Group Asset')
                    ->description('Register central/group physical assets (laptops, furniture, ACs, tools) owned by Corporate Holding (7 Orbit) and immediately deploy them to operating companies and employee custodians.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Asset Name / Model Description')
                            ->placeholder('e.g. Dell Latitude 5440 Core i7, Executive Office Desk, Panasonic 1.5T AC')
                            ->required()
                            ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),

                        Select::make('asset_category_id')
                            ->label('Asset Category')
                            ->options(function () use ($corporateCompany) {
                                if (! $corporateCompany) {
                                    return [];
                                }

                                return AssetCategory::query()
                                    ->where('company_id', $corporateCompany->getKey())
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('Auto-assigned General Category')
                            ->helperText('Optional: links to specific asset category schedule.'),

                        TextInput::make('serial_number')
                            ->label('Serial Number / Model ID')
                            ->placeholder('e.g. CN-0K2801-48220')
                            ->maxLength(100),

                        TextInput::make('asset_number')
                            ->label('Asset Tag / Barcode')
                            ->placeholder('Leave blank to auto-generate (e.g. GRP-AST-0021)')
                            ->maxLength(80),

                        TextInput::make('acquisition_cost')
                            ->label('Acquisition Cost (PKR)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('PKR')
                            ->default(0),

                        DatePicker::make('acquired_on')
                            ->label('Acquired Date')
                            ->default(today()->toDateString())
                            ->required(),

                        Select::make('assigned_company_id')
                            ->label('Deploy to Operating Company')
                            ->options($accessibleCompanies)
                            ->searchable()
                            ->placeholder('Central Storage / Group Pool')
                            ->live()
                            ->helperText('Select which operating subsidiary will hold and use this asset.'),

                        Select::make('custodian_employment_id')
                            ->label('Custodian Employee')
                            ->options(function ($get) {
                                $companyId = $get('assigned_company_id');
                                if (! $companyId) {
                                    return [];
                                }

                                return Employment::query()
                                    ->where('company_id', $companyId)
                                    ->with(['employee', 'designation'])
                                    ->get()
                                    ->mapWithKeys(fn (Employment $emp) => [
                                        $emp->getKey() => "{$emp->employee->full_name} (".($emp->designation?->name ?? $emp->employee_code).')',
                                    ]);
                            })
                            ->searchable()
                            ->visible(fn ($get) => ! empty($get('assigned_company_id')))
                            ->placeholder('General Company / Site Use'),

                        TextInput::make('location')
                            ->label('Room / Floor / Site Location')
                            ->placeholder('e.g. Gulberg Site Office, Room 102, Desk 4')
                            ->maxLength(255),

                        Select::make('condition_on_assignment')
                            ->label('Initial Physical Condition')
                            ->options([
                                'Brand New' => 'Brand New',
                                'Good' => 'Good',
                                'Fair' => 'Fair',
                            ])
                            ->default('Brand New'),

                        Textarea::make('handover_notes')
                            ->label('Handover & Accessory Notes')
                            ->placeholder('e.g. Handed over with original 65W charger, laptop sleeve, mouse, and HDMI cable')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function submit(RegisterGeneralGroupAssetAction $action): void
    {
        $validated = $this->form->getState();
        $corporateCompany = $this->getCorporateCompany();

        if (! $corporateCompany) {
            Notification::make()->title('Corporate Holding company not configured.')->danger()->send();

            return;
        }

        try {
            $asset = $action->handle(
                ownerCompany: $corporateCompany,
                name: $validated['name'],
                assetCategoryId: ! empty($validated['asset_category_id']) ? (int) $validated['asset_category_id'] : null,
                assetNumber: ! empty($validated['asset_number']) ? $validated['asset_number'] : null,
                serialNumber: $validated['serial_number'] ?? null,
                location: $validated['location'] ?? null,
                acquiredOn: CarbonImmutable::parse($validated['acquired_on']),
                acquisitionCost: $validated['acquisition_cost'] ?? 0,
                assignedCompanyId: ! empty($validated['assigned_company_id']) ? (int) $validated['assigned_company_id'] : null,
                custodianEmploymentId: ! empty($validated['custodian_employment_id']) ? (int) $validated['custodian_employment_id'] : null,
                conditionOnAssignment: $validated['condition_on_assignment'] ?? 'Good',
                handoverNotes: $validated['handover_notes'] ?? null,
                actor: Filament::auth()->user(),
            );

            Notification::make()
                ->title('Asset Registered & Deployed')
                ->body("Asset {$asset->asset_number} ({$asset->name}) was added to the Group Register.")
                ->success()
                ->send();

            $this->form->fill([
                'acquired_on' => today()->toDateString(),
                'condition_on_assignment' => 'Brand New',
                'acquisition_cost' => 0,
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Register Asset')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();
        $accessibleCompanyIds = $user?->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->pluck('id')->all()
            : $user?->companies()->wherePivot('is_active', true)->pluck('companies.id')->all() ?? [];

        $query = FixedAsset::withoutGlobalScopes()
            ->with(['company', 'assignedCompany', 'custodianEmployment.employee', 'category'])
            ->latest('id');

        return $table
            ->query($query)
            ->heading('Group Asset Custody & Deployment Matrix')
            ->description('Live tracking of group-owned assets, deployed operating subsidiaries, and employee custodians')
            ->columns([
                TextColumn::make('asset_number')
                    ->label('Asset Tag')
                    ->badge()
                    ->color('gray')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Asset Description')
                    ->weight(FontWeight::Bold)
                    ->description(fn (FixedAsset $record) => $record->category?->name ?? 'General Equipment')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serial_number')
                    ->label('Serial #')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('N/A')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('assignedCompany.name')
                    ->label('Deployed Company')
                    ->badge()
                    ->color('info')
                    ->placeholder('Central Group Pool')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('custodianEmployment.employee.full_name')
                    ->label('Custodian Employee')
                    ->badge()
                    ->color('success')
                    ->placeholder('General / Unassigned')
                    ->searchable(),

                TextColumn::make('location')
                    ->label('Location / Room')
                    ->placeholder('-')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('acquisition_cost')
                    ->label('Cost (PKR)')
                    ->money('PKR')
                    ->alignment(Alignment::End)
                    ->sortable(),

                TextColumn::make('custody_status')
                    ->label('Custody Status')
                    ->badge()
                    ->color(fn (AssetCustodyStatus $state) => $state->getColor())
                    ->icon(fn (AssetCustodyStatus $state) => $state->getIcon()),
            ])
            ->filters([
                SelectFilter::make('custody_status')
                    ->label('Custody Status')
                    ->options(AssetCustodyStatus::class),

                SelectFilter::make('assigned_company_id')
                    ->label('Deployed Company')
                    ->options(fn () => Company::withoutGlobalScopes()->whereIn('id', $accessibleCompanyIds)->where('is_active', true)->pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('transfer')
                    ->label('Transfer / Re-assign')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->modalHeading(fn (FixedAsset $record) => "Transfer Asset: {$record->name} ({$record->asset_number})")
                    ->modalDescription('Hand over this asset to another company or employee.')
                    ->form([
                        Select::make('target_company_id')
                            ->label('Target Operating Company')
                            ->options(fn () => Company::withoutGlobalScopes()->whereIn('id', $accessibleCompanyIds)->where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->default(fn (FixedAsset $record) => $record->assigned_company_id),

                        Select::make('target_employment_id')
                            ->label('Target Custodian Employee')
                            ->options(function ($get) {
                                $targetCompId = $get('target_company_id');
                                if (! $targetCompId) {
                                    return [];
                                }

                                return Employment::query()
                                    ->where('company_id', $targetCompId)
                                    ->with(['employee', 'designation'])
                                    ->get()
                                    ->mapWithKeys(fn (Employment $emp) => [
                                        $emp->getKey() => "{$emp->employee->full_name} (".($emp->designation?->name ?? $emp->employee_code).')',
                                    ]);
                            })
                            ->searchable()
                            ->placeholder('General Company / Site Use'),

                        TextInput::make('location')
                            ->label('New Location / Room')
                            ->default(fn (FixedAsset $record) => $record->location),

                        Select::make('condition')
                            ->label('Condition at Transfer')
                            ->options([
                                'Brand New' => 'Brand New',
                                'Good' => 'Good',
                                'Fair' => 'Fair',
                                'Minor Scratches' => 'Minor Scratches',
                            ])
                            ->default('Good')
                            ->required(),

                        Textarea::make('handover_notes')
                            ->label('Transfer Notes & Accessories')
                            ->placeholder('e.g. Handed over from BMC to Medical Billing with all accessories')
                            ->columnSpanFull(),
                    ])
                    ->action(function (FixedAsset $record, array $data, TransferGeneralAssetCustodyAction $transferAction): void {
                        try {
                            $transferAction->handle(
                                asset: $record,
                                targetCompanyId: ! empty($data['target_company_id']) ? (int) $data['target_company_id'] : null,
                                targetEmploymentId: ! empty($data['target_employment_id']) ? (int) $data['target_employment_id'] : null,
                                location: $data['location'] ?? null,
                                condition: $data['condition'] ?? 'Good',
                                handoverNotes: $data['handover_notes'] ?? null,
                                actor: Filament::auth()->user(),
                            );

                            Notification::make()
                                ->title('Asset Transferred')
                                ->body("Asset {$record->asset_number} was successfully re-assigned.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Transfer Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('return_to_pool')
                    ->label('Return to Pool')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (FixedAsset $record) => $record->custody_status !== AssetCustodyStatus::InPool)
                    ->modalHeading(fn (FixedAsset $record) => "Return Asset to Central Pool: {$record->name}")
                    ->modalDescription('Return this asset from company/employee custody back to Head Office storage.')
                    ->form([
                        Select::make('return_condition')
                            ->label('Return Condition')
                            ->options([
                                'Good' => 'Good',
                                'Fair' => 'Fair',
                                'Needs Repair / Maintenance' => 'Needs Repair / Maintenance',
                                'Damaged' => 'Damaged',
                            ])
                            ->default('Good')
                            ->required(),

                        Textarea::make('return_notes')
                            ->label('Return Reason & Notes')
                            ->placeholder('e.g. Project completed, employee resigned, or returned for storage')
                            ->columnSpanFull(),
                    ])
                    ->action(function (FixedAsset $record, array $data, ReturnGeneralAssetToPoolAction $returnAction): void {
                        try {
                            $returnAction->handle(
                                asset: $record,
                                returnCondition: $data['return_condition'] ?? 'Good',
                                returnNotes: $data['return_notes'] ?? null,
                                actor: Filament::auth()->user(),
                            );

                            Notification::make()
                                ->title('Asset Returned to Central Pool')
                                ->body("Asset {$record->asset_number} has been returned to storage.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Return Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultPaginationPageOption(15)
            ->paginationPageOptions([15, 25, 50]);
    }
}
