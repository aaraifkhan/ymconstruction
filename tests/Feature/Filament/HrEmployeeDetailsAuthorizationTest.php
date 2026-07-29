<?php

namespace Tests\Feature\Filament;

use App\Enums\DocumentClassification;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\RelationManagers\BankAccountsRelationManager;
use App\Filament\Resources\Employees\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Employees\RelationManagers\EmergencyContactsRelationManager;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmergencyContact;
use App\Models\Employment;
use App\Models\HrDocumentType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HrEmployeeDetailsAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_repeatable_employee_records_are_denied_across_companies(): void
    {
        $currentCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $currentEmployee = Employment::factory()->forCompany($currentCompany)->create()->employee;
        $otherEmployee = Employment::factory()->forCompany($otherCompany)->create()->employee;
        $currentContact = EmployeeEmergencyContact::factory()->for($currentEmployee)->create();
        $otherContact = EmployeeEmergencyContact::factory()->for($otherEmployee)->create();
        $user = $this->authenticateCompanyUser($currentCompany, [
            'View:Employee',
            'ViewAny:EmployeeEmergencyContact',
            'View:EmployeeEmergencyContact',
            'Update:EmployeeEmergencyContact',
        ]);

        $this->assertTrue(Gate::allows('view', $currentContact));
        $this->assertTrue(Gate::allows('update', $currentContact));
        $this->assertFalse(Gate::allows('view', $otherContact));
        $this->assertFalse(Gate::allows('update', $otherContact));
        $this->assertTrue(EmergencyContactsRelationManager::canViewForRecord($currentEmployee, EditEmployee::class));
        $this->assertFalse(EmergencyContactsRelationManager::canViewForRecord($otherEmployee, EditEmployee::class));
        $this->assertTrue($user->canAccessTenant($currentCompany));
    }

    public function test_employee_bank_numbers_require_their_own_sensitive_permission(): void
    {
        $company = Company::factory()->create();
        $employee = Employment::factory()->forCompany($company)->create()->employee;
        $bankAccount = EmployeeBankAccount::factory()->for($employee)->create();
        $user = $this->authenticateCompanyUser($company, [
            'View:Employee',
            'ViewAny:EmployeeBankAccount',
            'View:EmployeeBankAccount',
        ]);

        $this->assertTrue(Gate::allows('view', $bankAccount));
        $this->assertFalse(Gate::allows('viewSensitive', $bankAccount));
        $this->assertTrue(BankAccountsRelationManager::canViewForRecord($employee, EditEmployee::class));

        $user->givePermissionTo(Permission::findOrCreate('ViewSensitive:EmployeeBankAccount'));

        $this->assertTrue(Gate::allows('viewSensitive', $bankAccount));
    }

    public function test_emergency_contact_relation_manager_create_action_uses_its_own_permission(): void
    {
        $company = Company::factory()->create();
        $employee = Employment::factory()->forCompany($company)->create()->employee;
        $this->authenticateCompanyUser($company, [
            'View:Employee',
            'ViewAny:EmployeeEmergencyContact',
            'View:EmployeeEmergencyContact',
            'Create:EmployeeEmergencyContact',
        ]);

        Livewire::test(EmergencyContactsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ])
            ->callTableAction('create', data: [
                'name' => 'Ahmed Raza',
                'relationship' => 'Brother',
                'mobile' => '03001234567',
                'is_primary' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertModelExists(
            $employee->emergencyContacts()->where('name', 'Ahmed Raza')->firstOrFail(),
        );
    }

    public function test_employee_document_manager_hides_restricted_records_without_permission(): void
    {
        $company = Company::factory()->create();
        $employee = Employment::factory()->forCompany($company)->create()->employee;
        $category = DocumentCategory::factory()->for($company)->create();
        $internalDocument = Document::factory()
            ->for($company)
            ->for($category, 'category')
            ->create([
                'documentable_type' => $employee::class,
                'documentable_id' => $employee->getKey(),
                'classification' => DocumentClassification::Internal,
            ]);
        $restrictedDocument = Document::factory()
            ->for($company)
            ->for($category, 'category')
            ->create([
                'documentable_type' => $employee::class,
                'documentable_id' => $employee->getKey(),
                'classification' => DocumentClassification::Restricted,
            ]);
        $this->authenticateCompanyUser($company, [
            'View:Employee',
            'ViewAny:Document',
            'View:Document',
        ]);

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ])
            ->assertCanSeeTableRecords([$internalDocument])
            ->assertCanNotSeeTableRecords([$restrictedDocument]);
    }

    public function test_employee_document_manager_uploads_private_version_to_employee(): void
    {
        $company = Company::factory()->create();
        $employee = Employment::factory()->forCompany($company)->create()->employee;
        $category = DocumentCategory::factory()->for($company)->create();
        $documentType = HrDocumentType::factory()->for($company)->create([
            'code' => 'cnic',
            'name' => 'CNIC',
        ]);
        $this->authenticateCompanyUser($company, [
            'View:Employee',
            'ViewAny:Document',
            'View:Document',
            'Create:Document',
        ]);

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass' => EditEmployee::class,
        ])
            ->callTableAction('uploadDocument', data: [
                'title' => 'Employee CNIC Copy',
                'document_category_id' => $category->getKey(),
                'hr_document_type_id' => $documentType->getKey(),
                'classification' => DocumentClassification::Restricted->value,
                'uploaded_file_path' => UploadedFile::fake()->createWithContent(
                    'cnic.pdf',
                    "%PDF-1.4\nEmployee CNIC\n%%EOF",
                ),
            ])
            ->assertHasNoTableActionErrors();

        $document = $employee->documents()->where('title', 'Employee CNIC Copy')->firstOrFail();

        $this->assertTrue($document->company->is($company));
        $this->assertTrue($document->hrDocumentType->is($documentType));
        $this->assertSame(1, $document->versions()->count());
        Storage::disk('local')->assertExists($document->currentVersion()->firstOrFail()->path);
    }

    /**
     * @param  array<int, string>  $permissions
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
}
