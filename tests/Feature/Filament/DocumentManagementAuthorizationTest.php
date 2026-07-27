<?php

namespace Tests\Feature\Filament;

use App\Enums\DocumentClassification;
use App\Filament\Resources\DocumentCategories\Pages\CreateDocumentCategory;
use App\Filament\Resources\DocumentCategories\Pages\ListDocumentCategories;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentManagementAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_document_category_and_document_lists_are_company_scoped(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $otherCategory = DocumentCategory::factory()->for($otherCompany)->create();
        $document = Document::factory()
            ->for($company)
            ->for($category, 'category')
            ->create();
        $otherDocument = Document::factory()
            ->for($otherCompany)
            ->for($otherCategory, 'category')
            ->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:DocumentCategory',
            'View:DocumentCategory',
            'Create:DocumentCategory',
            'ViewAny:Document',
            'View:Document',
        ]);

        Livewire::test(ListDocumentCategories::class)
            ->assertCanSeeTableRecords([$category])
            ->assertCanNotSeeTableRecords([$otherCategory]);

        Livewire::test(CreateDocumentCategory::class)
            ->fillForm([
                'name' => 'Board Resolution',
                'slug' => 'board-resolution',
                'default_classification' => DocumentClassification::Confidential->value,
                'requires_expiry' => false,
                'requires_verification' => true,
                'requires_approval' => true,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $createdCategory = DocumentCategory::query()
            ->where('slug', 'board-resolution')
            ->firstOrFail();

        $this->assertTrue($createdCategory->company->is($company));

        Livewire::test(ListDocuments::class)
            ->assertCanSeeTableRecords([$document])
            ->assertCanNotSeeTableRecords([$otherDocument]);
    }

    public function test_document_create_page_uploads_initial_version_into_current_company(): void
    {
        $company = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:Document',
            'View:Document',
            'Create:Document',
        ]);

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'title' => 'Tax Registration Certificate',
                'reference_number' => 'TAX-001',
                'document_category_id' => $category->getKey(),
                'classification' => DocumentClassification::Internal->value,
                'uploaded_file_path' => UploadedFile::fake()->createWithContent(
                    'tax-certificate.pdf',
                    "%PDF-1.4\nCertificate\n%%EOF",
                ),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = Document::query()
            ->where('title', 'Tax Registration Certificate')
            ->firstOrFail();

        $this->assertTrue($document->company->is($company));
        $this->assertTrue($document->category->is($category));
        $this->assertSame(1, $document->versions()->count());
        Storage::disk('local')->assertExists($document->currentVersion()->firstOrFail()->path);
    }

    public function test_confidential_document_is_not_visible_in_filament_without_permission(): void
    {
        $company = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create();
        $internalDocument = Document::factory()
            ->for($company)
            ->for($category, 'category')
            ->create();
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

        Livewire::test(ListDocuments::class)
            ->assertCanSeeTableRecords([$internalDocument])
            ->assertCanNotSeeTableRecords([$confidentialDocument]);
    }

    public function test_document_file_and_workflow_actions_require_separate_permissions(): void
    {
        $company = Company::factory()->create();
        $category = DocumentCategory::factory()->for($company)->create([
            'requires_verification' => true,
            'requires_approval' => true,
        ]);
        $document = Document::factory()
            ->for($company)
            ->for($category, 'category')
            ->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:Document',
            'View:Document',
        ]);

        Livewire::test(ViewDocument::class, ['record' => $document->getRouteKey()])
            ->assertActionHidden('download')
            ->assertActionHidden('preview')
            ->assertActionHidden('uploadVersion')
            ->assertActionHidden('verify')
            ->assertActionHidden('approve')
            ->assertActionHidden('reject');

        $user->givePermissionTo([
            Permission::findOrCreate('Download:Document'),
            Permission::findOrCreate('Preview:Document'),
            Permission::findOrCreate('UploadVersion:Document'),
            Permission::findOrCreate('Verify:Document'),
            Permission::findOrCreate('Approve:Document'),
            Permission::findOrCreate('Reject:Document'),
        ]);

        Livewire::test(ViewDocument::class, ['record' => $document->getRouteKey()])
            ->assertActionVisible('download')
            ->assertActionVisible('preview')
            ->assertActionVisible('uploadVersion')
            ->assertActionVisible('verify')
            ->assertActionVisible('approve')
            ->assertActionVisible('reject');
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
}
