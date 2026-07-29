<?php

namespace Tests\Feature;

use App\Actions\Documents\CreateDocumentAction;
use App\Actions\Documents\ProvisionDefaultHrDocumentTypesAction;
use App\Actions\Documents\UploadDocumentVersionAction;
use App\Enums\DocumentClassification;
use App\Enums\HrDocumentApplicability;
use App\Enums\HrDocumentTypeCode;
use App\Filament\Resources\HrDocumentTypes\HrDocumentTypeResource;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Employment;
use App\Models\HrDocumentType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HrTypedDocumentTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_default_hr_document_types_are_idempotent_optional_and_company_specific(): void
    {
        $firstCompany = Company::factory()->create();
        $secondCompany = Company::factory()->create();
        $action = app(ProvisionDefaultHrDocumentTypesAction::class);

        $action->handle($firstCompany);
        $action->handle($firstCompany);
        $action->handle($secondCompany);

        $this->assertSame(6, $firstCompany->hrDocumentTypes()->count());
        $this->assertSame(6, $secondCompany->hrDocumentTypes()->count());
        $this->assertFalse($firstCompany->hrDocumentTypes()->where('is_required', true)->exists());

        $appointmentLetter = $firstCompany->hrDocumentTypes()
            ->where('code', HrDocumentTypeCode::AppointmentLetter)
            ->firstOrFail();

        $this->assertSame(HrDocumentApplicability::Employment, $appointmentLetter->applicability);
        $this->assertTrue($appointmentLetter->requires_verification);
        $this->assertTrue($appointmentLetter->requires_approval);
    }

    public function test_typed_employee_document_uses_controlled_sensitivity_and_company_scope(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $cnicType = $this->provisionedType($company, HrDocumentTypeCode::Cnic);
        $otherType = $this->provisionedType($otherCompany, HrDocumentTypeCode::Cnic);
        $user = $this->authenticateCompanyUser($company, ['Create:Document']);

        $document = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'hr_document_type_id' => $cnicType->getKey(),
                'title' => 'Employee CNIC',
                'classification' => DocumentClassification::Internal,
                'document_scope' => 'employee',
                'related_record_id' => $employment->employee_id,
                'reference_number' => '35202-1234567-1',
                'issue_date' => '2026-01-01',
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'cnic.pdf',
            actor: $user,
        );

        $this->assertSame(HrDocumentTypeCode::Cnic, $document->hrDocumentType->code);
        $this->assertSame(DocumentClassification::Restricted, $document->classification);
        $this->assertSame('35202-1234567-1', $document->reference_number);

        $this->expectException(ValidationException::class);

        app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'hr_document_type_id' => $otherType->getKey(),
                'title' => 'Cross-company type',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'employee',
                'related_record_id' => $employment->employee_id,
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'invalid.pdf',
            actor: $user,
        );
    }

    public function test_document_type_applicability_and_configured_dates_are_enforced(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $appointmentType = $this->provisionedType($company, HrDocumentTypeCode::AppointmentLetter);
        $appointmentType->update([
            'requires_issue_date' => true,
            'requires_expiry' => true,
        ]);
        $user = $this->authenticateCompanyUser($company, ['Create:Document']);

        try {
            app(CreateDocumentAction::class)->handle(
                company: $company,
                attributes: [
                    'document_category_id' => $category->getKey(),
                    'hr_document_type_id' => $appointmentType->getKey(),
                    'title' => 'Appointment Letter',
                    'classification' => DocumentClassification::Confidential,
                    'document_scope' => 'employment',
                    'related_record_id' => $employment->getKey(),
                ],
                uploadedFilePath: $this->storePdf($company),
                originalFileName: 'appointment.pdf',
                actor: $user,
            );
            $this->fail('Configured issue and expiry dates should be required.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('issue_date', $exception->errors());
        }

        $this->expectException(ValidationException::class);

        app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'hr_document_type_id' => $appointmentType->getKey(),
                'title' => 'Wrong owner',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'employee',
                'related_record_id' => $employment->employee_id,
                'issue_date' => '2026-01-01',
                'expiry_date' => '2027-01-01',
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'wrong-owner.pdf',
            actor: $user,
        );
    }

    public function test_identity_and_medical_documents_require_separate_view_permissions(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $cnic = Document::factory()->for($company)->for($category, 'category')->create([
            'hr_document_type_id' => $this->provisionedType($company, HrDocumentTypeCode::Cnic)->getKey(),
            'documentable_type' => $employment->employee::class,
            'documentable_id' => $employment->employee_id,
            'classification' => DocumentClassification::Restricted,
        ]);
        $medical = Document::factory()->for($company)->for($category, 'category')->create([
            'hr_document_type_id' => $this->provisionedType($company, HrDocumentTypeCode::MedicalCertificate)->getKey(),
            'documentable_type' => $employment->employee::class,
            'documentable_id' => $employment->employee_id,
            'classification' => DocumentClassification::Restricted,
        ]);
        $user = $this->authenticateCompanyUser($company, [
            'ViewAny:Document',
            'View:Document',
            'ViewSensitive:Document',
        ]);

        $this->assertFalse(Gate::allows('view', $cnic));
        $this->assertFalse(Gate::allows('view', $medical));
        $this->assertEmpty(Document::query()->visibleTo($user)->pluck('id')->all());

        $user->givePermissionTo(Permission::findOrCreate('ViewIdentity:EmployeeDocument'));
        $this->assertTrue(Gate::allows('view', $cnic));
        $this->assertFalse(Gate::allows('view', $medical));

        $user->givePermissionTo(Permission::findOrCreate('ViewMedical:EmployeeDocument'));
        $this->assertTrue(Gate::allows('view', $medical));
        $this->assertEqualsCanonicalizing(
            [$cnic->getKey(), $medical->getKey()],
            Document::query()->visibleTo($user)->pluck('id')->all(),
        );
    }

    public function test_legacy_hr_document_can_be_mapped_without_replacing_its_versions(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $user = $this->authenticateCompanyUser($company, [
            'Create:Document',
            'View:Document',
            'ViewSensitive:Document',
            'UploadVersion:Document',
        ]);
        $legacy = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Old education file',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'employee',
                'related_record_id' => $employment->employee_id,
            ],
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'legacy.pdf',
            actor: $user,
        );
        $type = $this->provisionedType($company, HrDocumentTypeCode::EducationalDocument);

        $legacy->update(['hr_document_type_id' => $type->getKey()]);
        app(UploadDocumentVersionAction::class)->handle(
            document: $legacy,
            uploadedFilePath: $this->storePdf($company),
            originalFileName: 'education-v2.pdf',
            actor: $user,
        );

        $this->assertSame($type->getKey(), $legacy->fresh()->hr_document_type_id);
        $this->assertSame(2, $legacy->versions()->count());
        $this->assertSame([1, 2], $legacy->versions()->orderBy('version')->pluck('version')->all());
    }

    public function test_required_configuration_reports_missing_compliance_without_fabricating_documents(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $cnicType = $this->provisionedType($company, HrDocumentTypeCode::Cnic);
        $appointmentType = $this->provisionedType($company, HrDocumentTypeCode::AppointmentLetter);
        $cnicType->update(['is_required' => true]);
        $appointmentType->update(['is_required' => true]);

        $this->assertSame(
            [$cnicType->getKey()],
            $employment->employee->missingRequiredHrDocumentTypes($company)->pluck('id')->all(),
        );
        $this->assertSame(
            [$appointmentType->getKey()],
            $employment->missingRequiredHrDocumentTypes()->pluck('id')->all(),
        );

        Document::factory()->for($company)->for($category, 'category')->create([
            'hr_document_type_id' => $cnicType->getKey(),
            'documentable_type' => $employment->employee::class,
            'documentable_id' => $employment->employee_id,
            'classification' => DocumentClassification::Restricted,
        ]);

        $this->assertTrue($employment->employee->missingRequiredHrDocumentTypes($company)->isEmpty());
        $this->assertSame(
            [$appointmentType->getKey()],
            $employment->missingRequiredHrDocumentTypes()->pluck('id')->all(),
        );
    }

    public function test_hr_document_type_configuration_is_tenant_scoped_and_used_types_cannot_be_deleted(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $currentType = $this->provisionedType($company, HrDocumentTypeCode::Cnic);
        $otherType = $this->provisionedType($otherCompany, HrDocumentTypeCode::Cnic);
        $user = $this->authenticateCompanyUser($company, [
            'ViewAny:HrDocumentType',
            'View:HrDocumentType',
            'Delete:HrDocumentType',
        ]);

        $this->assertContains($currentType->getKey(), HrDocumentTypeResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($otherType->getKey(), HrDocumentTypeResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue(Gate::allows('delete', $currentType));

        Document::factory()->for($company)->for($category, 'category')->create([
            'hr_document_type_id' => $currentType->getKey(),
            'documentable_type' => $employment->employee::class,
            'documentable_id' => $employment->employee_id,
            'classification' => DocumentClassification::Restricted,
        ]);

        $this->assertFalse(Gate::allows('delete', $currentType));
        $this->assertTrue($user->canAccessTenant($company));
    }

    private function provisionedType(Company $company, HrDocumentTypeCode $code): HrDocumentType
    {
        app(ProvisionDefaultHrDocumentTypesAction::class)->handle($company);

        return $company->hrDocumentTypes()->where('code', $code)->firstOrFail();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authenticateCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $user->givePermissionTo(
            collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission))
                ->all(),
        );

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        return $user;
    }

    private function storePdf(Company $company): string
    {
        $path = "documents/{$company->getKey()}/incoming/".fake()->uuid().'.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\nHR document\n%%EOF");

        return $path;
    }
}
