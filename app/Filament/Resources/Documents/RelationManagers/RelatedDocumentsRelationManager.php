<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Actions\Documents\CreateDocumentAction;
use App\Enums\DocumentClassification;
use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\Actions\DocumentFileActions;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\Schemas\DocumentForm;
use App\Models\BankReconciliation;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\CustomerInvoice;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\FixedAsset;
use App\Models\GoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Models\VendorBill;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use LogicException;

class RelatedDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    private CreateDocumentAction $createDocument;

    public function boot(CreateDocumentAction $createDocument): void
    {
        $this->createDocument = $createDocument;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord)
            && Gate::allows('viewAny', Document::class)
            && static::ownerCompanyId($ownerRecord) === (int) Filament::getTenant()?->getKey();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = auth()->user();

                return $user instanceof User
                    ? $query->visibleTo($user)->with(['category', 'currentVersion'])
                    : $query->whereRaw('1 = 0');
            })
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->badge(),
                TextColumn::make('classification')
                    ->label('Sensitivity')
                    ->formatStateUsing(fn (DocumentClassification $state): string => $state->label())
                    ->badge(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (DocumentStatus $state): string => $state->color()),
                TextColumn::make('currentVersion.version')->label('Version')->prefix('v'),
                TextColumn::make('expiry_date')->label('Expires')->date()->placeholder('—'),
            ])
            ->headerActions([
                Action::make('uploadDocument')
                    ->label('Upload document')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->authorize(fn (): bool => Gate::allows('create', Document::class))
                    ->schema([
                        TextInput::make('title')->required()->maxLength(255),
                        Select::make('document_category_id')
                            ->label('Category')
                            ->options(fn (): array => Filament::getTenant()?->documentCategories()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all() ?? [])
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('classification')
                            ->label('Sensitivity')
                            ->options(collect(DocumentClassification::cases())->mapWithKeys(
                                fn (DocumentClassification $classification): array => [
                                    $classification->value => $classification->label(),
                                ],
                            )->all())
                            ->default(DocumentClassification::Restricted->value)
                            ->required(),
                        TextInput::make('reference_number')->label('Reference number')->maxLength(255),
                        DatePicker::make('issue_date')->label('Issue date'),
                        DatePicker::make('expiry_date')->label('Expiry date')->afterOrEqual('issue_date'),
                        Textarea::make('description')->rows(3)->columnSpanFull(),
                        FileUpload::make('uploaded_file_path')
                            ->label('Document file')
                            ->disk('local')
                            ->directory(fn (): string => 'documents/'.Filament::getTenant()?->getKey().'/incoming')
                            ->visibility('private')
                            ->storeFileNamesIn('original_file_name')
                            ->acceptedFileTypes(DocumentForm::acceptedFileTypes())
                            ->rules(['extensions:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt'])
                            ->maxSize(10240)
                            ->downloadable(false)
                            ->openable(false)
                            ->previewable(false)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data): void {
                        $company = Filament::getTenant();
                        $actor = auth()->user();
                        $ownerRecord = $this->getOwnerRecord();

                        if (
                            ! $company instanceof Company
                            || ! $actor instanceof User
                            || static::ownerCompanyId($ownerRecord) !== (int) $company->getKey()
                        ) {
                            throw new LogicException('A company, authenticated user, and same-company owner record are required.');
                        }

                        $uploadedFilePath = Arr::pull($data, 'uploaded_file_path');
                        $originalFileName = Arr::pull($data, 'original_file_name');

                        if (! is_string($uploadedFilePath) || ! is_string($originalFileName)) {
                            throw new LogicException('The uploaded document file is missing.');
                        }

                        $this->createDocument->handle(
                            company: $company,
                            attributes: [
                                ...$data,
                                'document_scope' => static::documentScope($ownerRecord),
                                'related_record_id' => $ownerRecord->getKey(),
                            ],
                            uploadedFilePath: $uploadedFilePath,
                            originalFileName: $originalFileName,
                            actor: $actor,
                        );
                    }),
            ])
            ->recordActions([
                Action::make('openDocument')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->authorize('view')
                    ->url(fn (Document $record): string => DocumentResource::getUrl('view', ['record' => $record])),
                DocumentFileActions::previewCurrent(),
                DocumentFileActions::downloadCurrent(),
            ]);
    }

    private static function ownerCompanyId(Model $ownerRecord): ?int
    {
        if ($ownerRecord instanceof Company) {
            return (int) $ownerRecord->getKey();
        }
        if ($ownerRecord instanceof Employee) {
            return (int) $ownerRecord->employments()
                ->where('company_id', Filament::getTenant()?->getKey())
                ->value('company_id');
        }

        return isset($ownerRecord->company_id) ? (int) $ownerRecord->company_id : null;
    }

    private static function documentScope(Model $ownerRecord): string
    {
        return match (true) {
            $ownerRecord instanceof Employee => 'employee',
            $ownerRecord instanceof Employment => 'employment',
            $ownerRecord instanceof Project => 'project',
            $ownerRecord instanceof JournalEntry => 'journal',
            $ownerRecord instanceof PurchaseRequisition => 'purchase_requisition',
            $ownerRecord instanceof PurchaseOrder => 'purchase_order',
            $ownerRecord instanceof GoodsReceipt => 'goods_receipt',
            $ownerRecord instanceof InventoryTransaction => 'inventory_transaction',
            $ownerRecord instanceof VendorBill => 'vendor_bill',
            $ownerRecord instanceof CustomerInvoice => 'customer_invoice',
            $ownerRecord instanceof TreasuryTransaction => 'treasury_transaction',
            $ownerRecord instanceof BankStatement => 'bank_statement',
            $ownerRecord instanceof BankReconciliation => 'bank_reconciliation',
            $ownerRecord instanceof FixedAsset => 'fixed_asset',
            default => 'company',
        };
    }
}
