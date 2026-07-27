<?php

namespace Tests\Feature\Filament;

use App\Enums\JoiningLetterStatus;
use App\Filament\Resources\JoiningLetters\Pages\CreateJoiningLetter;
use App\Filament\Resources\JoiningLetters\Pages\ListJoiningLetters;
use App\Filament\Resources\JoiningLetters\Pages\ViewJoiningLetter;
use App\Filament\Resources\JoiningLetterTemplates\Pages\CreateJoiningLetterTemplate;
use App\Filament\Resources\JoiningLetterTemplates\Pages\ListJoiningLetterTemplates;
use App\Models\Company;
use App\Models\Employment;
use App\Models\JoiningLetter;
use App\Models\JoiningLetterTemplate;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JoiningLetterAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_template_and_letter_lists_are_company_scoped(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $template = JoiningLetterTemplate::factory()->create(['company_id' => $company->getKey()]);
        $otherTemplate = JoiningLetterTemplate::factory()->create(['company_id' => $otherCompany->getKey()]);
        $letter = $this->letterForCompany($company, $template);
        $otherLetter = $this->letterForCompany($otherCompany, $otherTemplate);
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:JoiningLetterTemplate',
            'View:JoiningLetterTemplate',
            'Create:JoiningLetterTemplate',
            'ViewAny:JoiningLetter',
            'View:JoiningLetter',
        ]);

        Livewire::test(ListJoiningLetterTemplates::class)
            ->assertCanSeeTableRecords([$template])
            ->assertCanNotSeeTableRecords([$otherTemplate]);

        Livewire::test(ListJoiningLetters::class)
            ->assertCanSeeTableRecords([$letter])
            ->assertCanNotSeeTableRecords([$otherLetter]);
    }

    public function test_create_pages_assign_current_company_and_render_letter_snapshot(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $template = JoiningLetterTemplate::factory()->create(['company_id' => $company->getKey()]);
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:JoiningLetterTemplate',
            'Create:JoiningLetterTemplate',
            'ViewAny:JoiningLetter',
            'Create:JoiningLetter',
            'ManageCompensation:JoiningLetter',
        ]);

        Livewire::test(CreateJoiningLetterTemplate::class)
            ->fillForm([
                'name' => 'Probation Joining Letter',
                'code' => 'probation-letter',
                'subject_template' => 'Welcome {{ employee.full_name }}',
                'body_template' => 'Welcome to {{ company.name }}.',
                'is_default' => false,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateJoiningLetter::class)
            ->fillForm([
                'employment_id' => $employment->getKey(),
                'joining_letter_template_id' => $template->getKey(),
                'letter_number' => 'JL-2026-001',
                'letter_date' => '2026-07-24',
                'employment_effective_date' => '2026-08-01',
                'compensation_amount' => 150000,
                'currency_code' => 'PKR',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $createdTemplate = JoiningLetterTemplate::query()->where('code', 'probation-letter')->sole();
        $letter = JoiningLetter::query()->where('letter_number', 'JL-2026-001')->sole();

        $this->assertTrue($createdTemplate->company->is($company));
        $this->assertTrue($letter->company->is($company));
        $this->assertSame(JoiningLetterStatus::Draft, $letter->status);
        $this->assertStringContainsString($employment->employee->full_name, $letter->body);
        $this->assertStringContainsString('PKR 150,000.00', $letter->body);
    }

    public function test_workflow_actions_require_individual_permissions(): void
    {
        $company = Company::factory()->create();
        $letter = $this->letterForCompany($company);
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:JoiningLetter',
            'View:JoiningLetter',
        ]);

        Livewire::test(ViewJoiningLetter::class, ['record' => $letter->getRouteKey()])
            ->assertActionHidden('regenerate')
            ->assertActionHidden('submit')
            ->assertActionHidden('approve')
            ->assertActionHidden('reject')
            ->assertActionHidden('issue')
            ->assertActionHidden('recordAcceptance');

        $user->givePermissionTo([
            Permission::findOrCreate('Regenerate:JoiningLetter'),
            Permission::findOrCreate('Submit:JoiningLetter'),
        ]);

        Livewire::test(ViewJoiningLetter::class, ['record' => $letter->getRouteKey()])
            ->assertActionVisible('regenerate')
            ->assertActionVisible('submit')
            ->assertActionHidden('approve');

        $letter->update(['status' => JoiningLetterStatus::PendingApproval]);
        $user->givePermissionTo([
            Permission::findOrCreate('Approve:JoiningLetter'),
            Permission::findOrCreate('Reject:JoiningLetter'),
        ]);

        Livewire::test(ViewJoiningLetter::class, ['record' => $letter->getRouteKey()])
            ->assertActionVisible('approve')
            ->assertActionVisible('reject')
            ->assertActionHidden('submit');
    }

    private function letterForCompany(
        Company $company,
        ?JoiningLetterTemplate $template = null,
    ): JoiningLetter {
        $employment = Employment::factory()->forCompany($company)->create();
        $template ??= JoiningLetterTemplate::factory()->create(['company_id' => $company->getKey()]);

        return JoiningLetter::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'joining_letter_template_id' => $template->getKey(),
        ]);
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
