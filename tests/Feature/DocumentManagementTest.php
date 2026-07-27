<?php

namespace Tests\Feature;

use App\Actions\Documents\ApproveDocumentAction;
use App\Actions\Documents\CreateDocumentAction;
use App\Actions\Documents\ProvisionDefaultDocumentCategoriesAction;
use App\Actions\Documents\UploadDocumentVersionAction;
use App\Actions\Documents\VerifyDocumentAction;
use App\Enums\DocumentClassification;
use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Employment;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_document_creation_records_a_private_immutable_version_and_checksum(): void
    {
        $company = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, ['Create:Document']);
        $path = $this->storePdf($company);

        $document = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Company Registration Certificate',
                'classification' => DocumentClassification::Confidential,
            ],
            uploadedFilePath: $path,
            originalFileName: 'registration-certificate.pdf',
            actor: $user,
        );

        $version = $document->currentVersion()->firstOrFail();

        $this->assertTrue($document->company->is($company));
        $this->assertTrue($document->category->is($category));
        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertSame(1, $version->version);
        $this->assertSame('registration-certificate.pdf', $version->original_file_name);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($path)), $version->checksum);
        $this->assertSame($user->getKey(), $version->uploaded_by_id);
        Storage::disk('local')->assertExists($path);
    }

    public function test_documents_can_be_linked_to_employee_and_employment_records_in_current_company(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $otherEmployment = Employment::factory()->forCompany($otherCompany)->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, ['Create:Document']);

        $employeeDocument = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Employee CNIC',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'employee',
                'related_record_id' => $employment->employee_id,
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'employee-cnic.pdf',
            actor: $user,
        );
        $employmentDocument = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Employment Record',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'employment',
                'related_record_id' => $employment->getKey(),
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'employment-record.pdf',
            actor: $user,
        );

        $this->assertTrue($employeeDocument->documentable->is($employment->employee));
        $this->assertTrue($employmentDocument->documentable->is($employment));

        $this->expectException(ValidationException::class);

        app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Cross-company record',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'employment',
                'related_record_id' => $otherEmployment->getKey(),
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'invalid.pdf',
            actor: $user,
        );
    }

    public function test_documents_can_be_linked_to_projects_in_current_company_only(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $project = Project::factory()->create(['company_id' => $company->getKey()]);
        $otherProject = Project::factory()->create(['company_id' => $otherCompany->getKey()]);
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, ['Create:Document']);

        $document = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Project Contract',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'project',
                'related_record_id' => $project->getKey(),
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'project-contract.pdf',
            actor: $user,
        );

        $this->assertTrue($document->documentable->is($project));

        $this->expectException(ValidationException::class);

        app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Cross-company Project Contract',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'project',
                'related_record_id' => $otherProject->getKey(),
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'invalid-project-contract.pdf',
            actor: $user,
        );
    }

    public function test_uploading_a_new_version_preserves_history_and_resets_review_state(): void
    {
        $company = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'Create:Document',
            'View:Document',
            'UploadVersion:Document',
        ]);

        $document = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Contract',
                'classification' => DocumentClassification::Internal,
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'contract-v1.pdf',
            actor: $user,
        );
        $document->update([
            'status' => DocumentStatus::Approved,
            'verified_by_id' => $user->getKey(),
            'verified_at' => now(),
            'approved_by_id' => $user->getKey(),
            'approved_at' => now(),
        ]);

        $version = app(UploadDocumentVersionAction::class)->handle(
            document: $document,
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'contract-v2.pdf',
            actor: $user,
            notes: 'Updated commercial terms.',
        );

        $document->refresh();

        $this->assertSame(2, $document->versions()->count());
        $this->assertSame(2, $version->version);
        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertNull($document->verified_at);
        $this->assertNull($document->approved_at);

        $this->expectException(LogicException::class);

        $version->update(['notes' => 'History must not change.']);
    }

    public function test_required_verification_must_happen_before_approval(): void
    {
        $company = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create([
            'requires_verification' => true,
            'requires_approval' => true,
        ]);
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'Create:Document',
            'View:Document',
            'Verify:Document',
            'Approve:Document',
        ]);

        $document = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Reviewed Contract',
                'classification' => DocumentClassification::Internal,
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'reviewed-contract.pdf',
            actor: $user,
        );

        try {
            app(ApproveDocumentAction::class)->handle($document, $user);
            $this->fail('Approval should require verification.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('document', $exception->errors());
        }

        app(VerifyDocumentAction::class)->handle($document, $user);
        app(ApproveDocumentAction::class)->handle($document, $user);

        $document->refresh();

        $this->assertSame(DocumentStatus::Approved, $document->status);
        $this->assertSame($user->getKey(), $document->verified_by_id);
        $this->assertSame($user->getKey(), $document->approved_by_id);
    }

    public function test_sensitive_documents_are_hidden_without_sensitive_permission(): void
    {
        $company = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $internalDocument = Document::factory()
            ->for($company)
            ->for($category, 'category')
            ->create(['classification' => DocumentClassification::Internal]);
        $confidentialDocument = Document::factory()
            ->for($company)
            ->for($category, 'category')
            ->confidential()
            ->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:Document',
            'View:Document',
        ]);

        $documentIds = DocumentResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($internalDocument->getKey(), $documentIds);
        $this->assertNotContains($confidentialDocument->getKey(), $documentIds);
        $this->assertFalse(Gate::allows('view', $confidentialDocument));

        $user->givePermissionTo(Permission::findOrCreate('ViewSensitive:Document'));

        $this->assertTrue(Gate::allows('view', $confidentialDocument));
        $this->assertContains(
            $confidentialDocument->getKey(),
            DocumentResource::getEloquentQuery()->pluck('id')->all(),
        );
    }

    public function test_a_document_cannot_use_another_company_category_or_file_path(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $otherCategory = DocumentCategory::factory()->for($otherCompany)->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, ['Create:Document']);
        $otherPath = $this->storePdf($otherCompany);

        try {
            app(CreateDocumentAction::class)->handle(
                company: $company,
                attributes: [
                    'document_category_id' => $otherCategory->getKey(),
                    'title' => 'Invalid cross-company document',
                    'classification' => DocumentClassification::Internal,
                ],
                uploadedFilePath: $otherPath,
                originalFileName: 'invalid.pdf',
                actor: $user,
            );
            $this->fail('A category from another company should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('document_category_id', $exception->errors());
        }

        $this->assertSame(0, Document::query()->count());
        Storage::disk('local')->assertMissing($otherPath);
    }

    public function test_category_with_documents_cannot_be_deleted(): void
    {
        $company = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        Document::factory()
            ->for($company)
            ->for($category, 'category')
            ->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, ['Delete:DocumentCategory']);

        $this->assertFalse(Gate::allows('delete', $category));
    }

    public function test_default_category_provisioning_is_repeatable_without_duplicates(): void
    {
        $company = Company::factory()->create();
        $provisionCategories = app(ProvisionDefaultDocumentCategoriesAction::class);

        $provisionCategories->handle($company);
        $provisionCategories->handle($company);

        $this->assertSame(5, $company->documentCategories()->count());
        $this->assertSame(
            [
                'company-registration',
                'contract',
                'employee-document',
                'financial-document',
                'general-document',
            ],
            $company->documentCategories()->orderBy('slug')->pluck('slug')->all(),
        );
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function authenticateCompanyUser(User $user, Company $company, array $permissions): void
    {
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }

    private function storePdf(Company $company): string
    {
        $path = "documents/{$company->getKey()}/incoming/".Str::uuid().'.pdf';

        Storage::disk('local')->put($path, "%PDF-1.4\nTest document content\n%%EOF");

        return $path;
    }
}
