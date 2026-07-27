<?php

namespace Tests\Feature;

use App\Actions\JoiningLetters\ApproveJoiningLetterAction;
use App\Actions\JoiningLetters\IssueJoiningLetterAction;
use App\Actions\JoiningLetters\ProvisionDefaultJoiningLetterTemplateAction;
use App\Actions\JoiningLetters\RecordJoiningLetterAcceptanceAction;
use App\Actions\JoiningLetters\RejectJoiningLetterAction;
use App\Actions\JoiningLetters\RenderJoiningLetterAction;
use App\Actions\JoiningLetters\SubmitJoiningLetterAction;
use App\Enums\JoiningLetterStatus;
use App\Models\Company;
use App\Models\Employment;
use App\Models\JoiningLetter;
use App\Models\JoiningLetterTemplate;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JoiningLetterWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_default_template_provisioning_is_repeatable(): void
    {
        $company = Company::factory()->create();
        $provision = app(ProvisionDefaultJoiningLetterTemplateAction::class);

        $provision->handle($company);
        $provision->handle($company);

        $template = $company->joiningLetterTemplates()->sole();

        $this->assertSame('standard-joining-letter', $template->code);
        $this->assertTrue($template->is_default);
        $this->assertStringContainsString('{{ employee.full_name }}', $template->body_template);
        $this->assertStringNotContainsString('Signature', $template->body_template);
    }

    public function test_draft_is_rendered_from_safe_company_and_employment_placeholders(): void
    {
        [$letter, $user] = $this->draftLetter([
            'compensation_amount' => '125000.00',
        ]);

        $rendered = app(RenderJoiningLetterAction::class)->handle($letter, $user);
        $rawBody = DB::table('joining_letters')->where('id', $letter->getKey())->value('body');
        $rawCompensation = DB::table('joining_letters')->where('id', $letter->getKey())->value('compensation_amount');

        $this->assertStringContainsString($letter->employment->employee->full_name, $rendered->body);
        $this->assertStringContainsString($letter->company->legal_name, $rendered->body);
        $this->assertStringContainsString('PKR 125,000.00', $rendered->body);
        $this->assertStringNotContainsString('{{', $rendered->body);
        $this->assertNotSame($rendered->body, $rawBody);
        $this->assertNotSame('125000.00', $rawCompensation);
    }

    public function test_unknown_template_placeholder_is_rejected(): void
    {
        [$letter, $user] = $this->draftLetter();
        $letter->template->update([
            'body_template' => 'Welcome {{ employee.full_name }} {{ unsafe.value }}',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown placeholders');

        app(RenderJoiningLetterAction::class)->handle($letter, $user);
    }

    public function test_letter_follows_approval_issue_and_acceptance_workflow(): void
    {
        [$letter, $user] = $this->draftLetter();
        app(RenderJoiningLetterAction::class)->handle($letter, $user);

        app(SubmitJoiningLetterAction::class)->handle($letter, $user);
        $this->assertSame(JoiningLetterStatus::PendingApproval, $letter->refresh()->status);

        app(ApproveJoiningLetterAction::class)->handle($letter, $user);
        $this->assertSame(JoiningLetterStatus::Approved, $letter->refresh()->status);

        app(IssueJoiningLetterAction::class)->handle($letter, $user);
        $letter->refresh();

        $this->assertSame(JoiningLetterStatus::Issued, $letter->status);
        $this->assertSame(hash('sha256', $letter->subject."\n".$letter->body), $letter->content_checksum);
        $this->assertNotNull($letter->issued_at);

        app(RecordJoiningLetterAcceptanceAction::class)->handle(
            $letter,
            $user,
            'Muhammad Ahmed',
            'Accepted in person.',
        );
        $letter->refresh();

        $this->assertSame(JoiningLetterStatus::Accepted, $letter->status);
        $this->assertSame('Muhammad Ahmed', $letter->accepted_by_name);
        $this->assertNotNull($letter->accepted_at);
    }

    public function test_rejected_letter_can_be_regenerated_and_resubmitted(): void
    {
        [$letter, $user] = $this->draftLetter();
        app(RenderJoiningLetterAction::class)->handle($letter, $user);
        app(SubmitJoiningLetterAction::class)->handle($letter, $user);
        app(RejectJoiningLetterAction::class)->handle($letter, $user, 'Correct the effective date.');

        $this->assertSame(JoiningLetterStatus::Rejected, $letter->refresh()->status);
        $this->assertSame('Correct the effective date.', $letter->rejection_reason);

        app(RenderJoiningLetterAction::class)->handle($letter, $user);
        $letter->refresh();

        $this->assertSame(JoiningLetterStatus::Draft, $letter->status);
        $this->assertNull($letter->rejection_reason);
        $this->assertNull($letter->rejected_at);
    }

    public function test_issued_letter_content_and_accepted_letter_are_immutable(): void
    {
        [$letter, $user] = $this->draftLetter();
        app(RenderJoiningLetterAction::class)->handle($letter, $user);
        app(SubmitJoiningLetterAction::class)->handle($letter, $user);
        app(ApproveJoiningLetterAction::class)->handle($letter, $user);
        app(IssueJoiningLetterAction::class)->handle($letter, $user);

        try {
            $letter->refresh()->update(['subject' => 'Changed']);
            $this->fail('Issued content should be immutable.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('joining_letter', $exception->errors());
        }

        app(RecordJoiningLetterAcceptanceAction::class)->handle($letter, $user, 'Muhammad Ahmed');

        $this->expectException(ValidationException::class);
        $letter->refresh()->update(['acceptance_notes' => 'Changed after acceptance']);
    }

    public function test_cross_company_employment_and_template_are_rejected_at_model_boundary(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $otherTemplate = JoiningLetterTemplate::factory()->create(['company_id' => $otherCompany->getKey()]);

        $this->expectException(ValidationException::class);

        JoiningLetter::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'joining_letter_template_id' => $otherTemplate->getKey(),
        ]);
    }

    public function test_sensitive_body_and_compensation_are_not_written_to_activity_log(): void
    {
        [$letter, $user] = $this->draftLetter();
        app(RenderJoiningLetterAction::class)->handle($letter, $user);

        $activityPayload = Activity::query()
            ->where('subject_type', JoiningLetter::class)
            ->get()
            ->pluck('properties')
            ->map(fn ($properties): string => json_encode($properties))
            ->implode(' ');

        $this->assertStringNotContainsString($letter->body, $activityPayload);
        $this->assertStringNotContainsString('100000', $activityPayload);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{JoiningLetter, User}
     */
    private function draftLetter(array $overrides = []): array
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $template = JoiningLetterTemplate::factory()->create(['company_id' => $company->getKey()]);
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'Regenerate:JoiningLetter',
            'Submit:JoiningLetter',
            'Approve:JoiningLetter',
            'Reject:JoiningLetter',
            'Issue:JoiningLetter',
            'RecordAcceptance:JoiningLetter',
        ]);

        $letter = JoiningLetter::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'joining_letter_template_id' => $template->getKey(),
            'created_by_id' => $user->getKey(),
            ...$overrides,
        ]);

        return [$letter, $user];
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
