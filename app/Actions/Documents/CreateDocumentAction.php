<?php

namespace App\Actions\Documents;

use App\Enums\DocumentClassification;
use App\Enums\DocumentStatus;
use App\Models\BankReconciliation;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\CustomerInvoice;
use App\Models\Document;
use App\Models\DocumentCategory;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateDocumentAction
{
    public function __construct(
        private UploadDocumentVersionAction $uploadDocumentVersion,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        Company $company,
        array $attributes,
        string $uploadedFilePath,
        string $originalFileName,
        User $actor,
    ): Document {
        Gate::forUser($actor)->authorize('create', Document::class);

        try {
            $attributes = Validator::make($attributes, [
                'document_category_id' => ['required', 'integer'],
                'title' => ['required', 'string', 'max:255'],
                'reference_number' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique((new Document)->getTable())
                        ->where('company_id', $company->getKey()),
                ],
                'classification' => ['required', Rule::enum(DocumentClassification::class)],
                'issue_date' => ['nullable', 'date'],
                'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
                'description' => ['nullable', 'string'],
                'metadata' => ['nullable', 'array'],
                'document_scope' => ['nullable', Rule::in([
                    'company', 'employee', 'employment', 'project', 'journal',
                    'purchase_requisition', 'purchase_order', 'goods_receipt',
                    'inventory_transaction', 'vendor_bill', 'customer_invoice',
                    'treasury_transaction', 'bank_statement', 'bank_reconciliation',
                    'fixed_asset',
                ])],
                'related_record_id' => ['nullable', 'integer'],
            ])->validate();

            $category = DocumentCategory::query()
                ->whereBelongsTo($company)
                ->where('is_active', true)
                ->find($attributes['document_category_id'] ?? null);

            if ($category === null) {
                throw ValidationException::withMessages([
                    'document_category_id' => 'Select a document category belonging to the current company.',
                ]);
            }

            if ($category->requires_expiry && blank($attributes['expiry_date'] ?? null)) {
                throw ValidationException::withMessages([
                    'expiry_date' => 'An expiry date is required for this document category.',
                ]);
            }

            $documentable = $this->resolveDocumentable($company, $attributes);

            return DB::transaction(function () use (
                $actor,
                $attributes,
                $company,
                $documentable,
                $originalFileName,
                $uploadedFilePath,
            ): Document {
                $document = Document::query()->create([
                    ...Arr::only($attributes, [
                        'document_category_id',
                        'title',
                        'reference_number',
                        'classification',
                        'issue_date',
                        'expiry_date',
                        'description',
                        'metadata',
                    ]),
                    'company_id' => $company->getKey(),
                    'documentable_type' => $documentable::class,
                    'documentable_id' => $documentable->getKey(),
                    'status' => DocumentStatus::Draft,
                ]);

                $this->uploadDocumentVersion->handle(
                    document: $document,
                    uploadedFilePath: $uploadedFilePath,
                    originalFileName: $originalFileName,
                    actor: $actor,
                    authorize: false,
                );

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($uploadedFilePath);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveDocumentable(Company $company, array $attributes): Model
    {
        $scope = $attributes['document_scope'] ?? 'company';
        $relatedRecordId = $attributes['related_record_id'] ?? null;

        $documentable = match ($scope) {
            'employee' => Employee::query()
                ->whereHas(
                    'employments',
                    fn ($query) => $query->whereBelongsTo($company),
                )
                ->find($relatedRecordId),
            'employment' => Employment::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'project' => Project::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'journal' => JournalEntry::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'purchase_requisition' => PurchaseRequisition::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'purchase_order' => PurchaseOrder::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'goods_receipt' => GoodsReceipt::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'inventory_transaction' => InventoryTransaction::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'vendor_bill' => VendorBill::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'customer_invoice' => CustomerInvoice::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'treasury_transaction' => TreasuryTransaction::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'bank_statement' => BankStatement::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'bank_reconciliation' => BankReconciliation::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            'fixed_asset' => FixedAsset::query()
                ->whereBelongsTo($company)
                ->find($relatedRecordId),
            default => $company,
        };

        if (! $documentable instanceof Model) {
            throw ValidationException::withMessages([
                'related_record_id' => 'Select a related record belonging to the current company.',
            ]);
        }

        return $documentable;
    }
}
