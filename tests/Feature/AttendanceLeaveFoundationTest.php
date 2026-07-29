<?php

namespace Tests\Feature;

use App\Actions\Documents\CreateDocumentAction;
use App\Actions\HR\AdjustLeaveBalanceAction;
use App\Actions\HR\ApproveAttendanceCorrectionAction;
use App\Actions\HR\ApproveAttendancePunchAction;
use App\Actions\HR\ApproveLeaveRequestAction;
use App\Actions\HR\BuildAttendanceMonthlySummaryAction;
use App\Actions\HR\CancelLeaveRequestAction;
use App\Actions\HR\FinalizeAttendanceMonthlySummaryAction;
use App\Actions\HR\FinalizeAttendanceRecordAction;
use App\Actions\HR\ManagerApproveLeaveRequestAction;
use App\Actions\HR\SubmitLeaveRequestAction;
use App\Enums\AttendanceDayStatus;
use App\Enums\AttendancePunchDirection;
use App\Enums\AttendancePunchStatus;
use App\Enums\AttendanceRecordState;
use App\Enums\AttendanceSummaryStatus;
use App\Enums\DocumentClassification;
use App\Enums\EmploymentStatus;
use App\Enums\LeaveLedgerEntryType;
use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveUnit;
use App\Enums\MissingPunchTreatment;
use App\Filament\Resources\AttendanceCorrections\Pages\ListAttendanceCorrections;
use App\Filament\Resources\AttendanceMonthlySummaries\Pages\ListAttendanceMonthlySummaries;
use App\Filament\Resources\AttendancePunches\Pages\ListAttendancePunches;
use App\Filament\Resources\AttendanceRecords\Pages\ListAttendanceRecords;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceMonthlySummary;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRule;
use App\Models\Company;
use App\Models\CompanyHoliday;
use App\Models\DocumentCategory;
use App\Models\Employment;
use App\Models\LeaveLedgerEntry;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkShift;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceLeaveFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_overnight_shift_uses_approved_manual_evidence_and_preserves_it(): void
    {
        [$company, $employment, $calendar, $shift, $rule] = $this->attendanceContext(
            shiftStart: '20:00',
            shiftEnd: '05:00',
            overnight: true,
        );
        $assignment = ShiftAssignment::query()->where('employment_id', $employment->getKey())->sole();
        $creator = $this->actor($company, []);
        $approver = $this->actor($company, ['Approve:AttendancePunch', 'Finalize:AttendanceRecord']);
        $firstPunch = $this->punch($company, $employment, $creator, '2026-07-06 19:56:00', AttendancePunchDirection::In);
        $lastPunch = $this->punch($company, $employment, $creator, '2026-07-07 05:08:00', AttendancePunchDirection::Out);

        app(ApproveAttendancePunchAction::class)->handle($firstPunch, $approver);
        app(ApproveAttendancePunchAction::class)->handle($lastPunch, $approver);

        $record = AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => '2026-07-06',
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);
        app(FinalizeAttendanceRecordAction::class)->handle($record, $approver);

        $record->refresh();
        $this->assertSame(AttendanceRecordState::Finalized, $record->state);
        $this->assertSame(AttendanceDayStatus::Present, $record->day_status);
        $this->assertSame($assignment->getKey(), $record->shift_assignment_id);
        $this->assertSame($rule->getKey(), $record->attendance_rule_id);
        $this->assertSame('2026-07-06 19:56:00', $record->first_in_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-07 05:08:00', $record->last_out_at->format('Y-m-d H:i:s'));
        $this->assertSame(492, $record->worked_minutes);
        $this->assertSame(0, $record->late_minutes);

        $this->expectException(ValidationException::class);
        $firstPunch->refresh()->update(['reason' => 'Evidence cannot be rewritten']);
    }

    public function test_hr3_factories_create_valid_isolated_records(): void
    {
        $this->assertTrue(WorkCalendar::factory()->create()->exists);
        $this->assertTrue(CompanyHoliday::factory()->create()->exists);
        $this->assertTrue(WorkShift::factory()->create()->exists);
        $this->assertTrue(ShiftAssignment::factory()->create()->exists);
        $this->assertTrue(AttendanceRule::factory()->create()->exists);
        $this->assertTrue(AttendanceRecord::factory()->create()->exists);
        $this->assertTrue(AttendancePunch::factory()->create()->exists);
        $this->assertTrue(AttendanceCorrection::factory()->create()->exists);
        $this->assertTrue(AttendanceMonthlySummary::factory()->create()->exists);
        $this->assertTrue(LeaveType::factory()->create()->exists);
        $this->assertTrue(LeavePolicy::factory()->create()->exists);
        $this->assertTrue(LeaveLedgerEntry::factory()->create()->exists);
        $this->assertTrue(LeaveRequest::factory()->create()->exists);
    }

    public function test_hr3_workflow_lists_render_for_authorized_company_user(): void
    {
        $company = Company::factory()->create();
        $this->actor($company, [
            'ViewAny:AttendancePunch',
            'ViewAny:AttendanceCorrection',
            'ViewAny:AttendanceRecord',
            'ViewAny:AttendanceMonthlySummary',
            'ViewAny:LeaveRequest',
        ]);

        Livewire::test(ListAttendancePunches::class)->assertSuccessful();
        Livewire::test(ListAttendanceCorrections::class)->assertSuccessful();
        Livewire::test(ListAttendanceRecords::class)->assertSuccessful();
        Livewire::test(ListAttendanceMonthlySummaries::class)->assertSuccessful();
        Livewire::test(ListLeaveRequests::class)->assertSuccessful();
    }

    public function test_holidays_rest_days_missing_punch_rules_and_employment_dates_are_enforced(): void
    {
        [$company, $employment, $calendar] = $this->attendanceContext();
        CompanyHoliday::query()->create([
            'company_id' => $company->getKey(),
            'work_calendar_id' => $calendar->getKey(),
            'name' => 'Synthetic holiday',
            'holiday_date' => '2026-07-07',
        ]);
        $actor = $this->actor($company, ['Finalize:AttendanceRecord']);

        $holiday = $this->finalizeEmptyDay($company, $employment, $actor, '2026-07-07');
        $restDay = $this->finalizeEmptyDay($company, $employment, $actor, '2026-07-12');
        $missingPunch = $this->finalizeEmptyDay($company, $employment, $actor, '2026-07-08');

        $this->assertSame(AttendanceDayStatus::Holiday, $holiday->day_status);
        $this->assertSame(AttendanceDayStatus::RestDay, $restDay->day_status);
        $this->assertSame(AttendanceDayStatus::MissingPunch, $missingPunch->day_status);

        $outsideEmployment = AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => '2025-12-31',
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);

        $this->expectException(ValidationException::class);
        app(FinalizeAttendanceRecordAction::class)->handle($outsideEmployment, $actor);
    }

    public function test_on_leave_employment_requires_approved_leave_evidence_before_finalization(): void
    {
        [$company, $employment] = $this->attendanceContext();
        $employment->update(['employment_status' => EmploymentStatus::OnLeave]);
        $actor = $this->actor($company, ['Finalize:AttendanceRecord']);
        $record = AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => '2026-07-08',
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);

        $this->expectValidationException(
            fn () => app(FinalizeAttendanceRecordAction::class)->handle($record, $actor),
        );
    }

    public function test_manual_punch_and_attendance_correction_enforce_maker_checker(): void
    {
        [$company, $employment] = $this->attendanceContext();
        $maker = $this->actor($company, ['Approve:AttendancePunch', 'Approve:AttendanceCorrection']);
        $checker = $this->actor($company, ['Approve:AttendancePunch', 'Approve:AttendanceCorrection']);
        $punch = $this->punch($company, $employment, $maker, '2026-07-08 09:00:00', AttendancePunchDirection::In);

        $this->expectValidationException(fn () => app(ApproveAttendancePunchAction::class)->handle($punch, $maker));
        app(ApproveAttendancePunchAction::class)->handle($punch, $checker);
        $this->assertSame(AttendancePunchStatus::Approved, $punch->refresh()->status);

        $record = AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => '2026-07-08',
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);
        $correction = AttendanceCorrection::query()->create([
            'company_id' => $company->getKey(),
            'attendance_record_id' => $record->getKey(),
            'before_snapshot' => ['day_status' => AttendanceDayStatus::MissingPunch->value],
            'proposed_snapshot' => ['day_status' => AttendanceDayStatus::Present->value, 'worked_minutes' => 480],
            'reason' => 'Approved source evidence',
            'requested_by_id' => $maker->getKey(),
        ]);

        $this->expectValidationException(fn () => app(ApproveAttendanceCorrectionAction::class)->handle($correction, $maker));
        app(ApproveAttendanceCorrectionAction::class)->handle($correction, $checker);

        $this->assertSame(AttendanceDayStatus::Present, $record->refresh()->day_status);
        $this->assertSame(480, $record->worked_minutes);
        $this->assertSame('approved', $correction->refresh()->status->value);
    }

    public function test_effective_rules_schedules_and_records_are_company_isolated(): void
    {
        [$company, $employment, $calendar, $shift] = $this->attendanceContext();
        [$otherCompany, $otherEmployment, $otherCalendar] = $this->attendanceContext();
        $companyRecord = AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => '2026-07-15',
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);
        $otherRecord = AttendanceRecord::query()->create([
            'company_id' => $otherCompany->getKey(),
            'employment_id' => $otherEmployment->getKey(),
            'attendance_date' => '2026-07-15',
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);
        $user = $this->actor($company, ['View:AttendanceRecord']);

        $this->assertTrue($user->can('view', $companyRecord));
        $this->assertFalse($user->can('view', $otherRecord));

        $this->expectValidationException(fn () => ShiftAssignment::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'work_calendar_id' => $otherCalendar->getKey(),
            'work_shift_id' => $shift->getKey(),
            'effective_from' => '2027-01-01',
        ]));

        $this->expectValidationException(fn () => AttendanceRule::query()->create([
            'company_id' => $company->getKey(),
            'name' => 'Overlapping active rule',
            'effective_from' => '2026-06-01',
            'grace_minutes' => 0,
            'late_rounding_minutes' => 1,
            'half_day_after_minutes' => 60,
            'absence_after_minutes' => 120,
            'minimum_overtime_minutes' => 0,
            'missing_punch_treatment' => MissingPunchTreatment::Absent,
        ]));

        $this->assertTrue($calendar->isWorkingDay(CarbonImmutable::parse('2026-07-15')));
    }

    public function test_leave_approval_consumes_balance_and_cancellation_writes_reversal(): void
    {
        [$company, $employment] = $this->attendanceContext();
        [$leaveType, $policy] = $this->leaveConfiguration($company, allowNegative: false);
        $requester = $this->actor($company, ['Submit:LeaveRequest']);
        $manager = $this->actor($company, ['ManagerApprove:LeaveRequest']);
        $hrApprover = $this->actor($company, ['Approve:LeaveRequest', 'Cancel:LeaveRequest', 'Adjust:LeaveLedgerEntry']);
        app(AdjustLeaveBalanceAction::class)->handle(
            $employment,
            $leaveType,
            2,
            CarbonImmutable::parse('2026-07-01'),
            'Approved opening balance',
            $hrApprover,
            LeaveLedgerEntryType::Opening,
        );

        $request = $this->leaveRequest($company, $employment, $leaveType, units: 2);
        app(SubmitLeaveRequestAction::class)->handle($request, $requester);
        app(ManagerApproveLeaveRequestAction::class)->handle($request, $manager);
        app(ApproveLeaveRequestAction::class)->handle($request, $hrApprover);

        $this->assertSame(LeaveRequestStatus::Approved, $request->refresh()->status);
        $this->assertSame($policy->getKey(), $request->leave_policy_id);
        $this->assertSame(0.0, (float) LeaveLedgerEntry::query()->where('employment_id', $employment->getKey())->sum('units'));

        app(CancelLeaveRequestAction::class)->handle($request, $hrApprover, 'Approved cancellation');

        $this->assertSame(LeaveRequestStatus::Cancelled, $request->refresh()->status);
        $this->assertSame(2.0, (float) LeaveLedgerEntry::query()->where('employment_id', $employment->getKey())->sum('units'));
        $this->assertSame(
            [LeaveLedgerEntryType::Opening->value, LeaveLedgerEntryType::Consumption->value, LeaveLedgerEntryType::Reversal->value],
            LeaveLedgerEntry::query()
                ->where('employment_id', $employment->getKey())
                ->orderBy('id')
                ->get()
                ->map(fn (LeaveLedgerEntry $entry): string => $entry->entry_type->value)
                ->all(),
        );
    }

    public function test_required_leave_attachment_uses_private_document_scope_before_submission(): void
    {
        [$company, $employment] = $this->attendanceContext();
        [$leaveType] = $this->leaveConfiguration($company, allowNegative: false);
        $leaveType->update(['requires_attachment' => true]);
        $request = $this->leaveRequest($company, $employment, $leaveType, units: 1);
        $requester = $this->actor($company, ['Submit:LeaveRequest', 'Create:Document']);

        $this->expectValidationException(
            fn () => app(SubmitLeaveRequestAction::class)->handle($request, $requester),
        );

        $category = DocumentCategory::factory()->for($company)->create();
        $path = "documents/{$company->getKey()}/incoming/leave-evidence.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 synthetic leave evidence');
        $document = app(CreateDocumentAction::class)->handle(
            company: $company,
            attributes: [
                'document_category_id' => $category->getKey(),
                'title' => 'Synthetic leave evidence',
                'classification' => DocumentClassification::Restricted,
                'document_scope' => 'leave_request',
                'related_record_id' => $request->getKey(),
            ],
            uploadedFilePath: $path,
            originalFileName: 'leave-evidence.pdf',
            actor: $requester,
        );
        app(SubmitLeaveRequestAction::class)->handle($request, $requester);

        $this->assertTrue($document->documentable->is($request));
        $this->assertSame(LeaveRequestStatus::Requested, $request->fresh()->status);
        Storage::disk('local')->assertExists($path);
    }

    public function test_leave_cannot_overconsume_unless_effective_policy_allows_negative_balance(): void
    {
        [$company, $employment] = $this->attendanceContext();
        [$leaveType, $policy] = $this->leaveConfiguration($company, allowNegative: false);
        $requester = $this->actor($company, ['Submit:LeaveRequest']);
        $manager = $this->actor($company, ['ManagerApprove:LeaveRequest']);
        $hrApprover = $this->actor($company, ['Approve:LeaveRequest']);
        $request = $this->leaveRequest($company, $employment, $leaveType, units: 1);
        app(SubmitLeaveRequestAction::class)->handle($request, $requester);
        app(ManagerApproveLeaveRequestAction::class)->handle($request, $manager);

        $this->expectValidationException(fn () => app(ApproveLeaveRequestAction::class)->handle($request, $hrApprover));

        $policy->update(['allow_negative_balance' => true]);
        app(ApproveLeaveRequestAction::class)->handle($request, $hrApprover);

        $this->assertSame(-1.0, (float) LeaveLedgerEntry::query()->where('employment_id', $employment->getKey())->sum('units'));
    }

    public function test_monthly_summary_snapshots_unpaid_leave_and_is_immutable_after_finalization(): void
    {
        [$company, $employment] = $this->attendanceContext();
        [$leaveType] = $this->leaveConfiguration($company, allowNegative: true, paid: false);
        $requester = $this->actor($company, ['Submit:LeaveRequest']);
        $manager = $this->actor($company, ['ManagerApprove:LeaveRequest']);
        $approver = $this->actor($company, [
            'Approve:LeaveRequest',
            'Generate:AttendanceMonthlySummary',
            'Finalize:AttendanceMonthlySummary',
            'Finalize:AttendanceRecord',
        ]);
        $leave = $this->leaveRequest($company, $employment, $leaveType, units: 1);
        app(SubmitLeaveRequestAction::class)->handle($leave, $requester);
        app(ManagerApproveLeaveRequestAction::class)->handle($leave, $manager);
        app(ApproveLeaveRequestAction::class)->handle($leave, $approver);
        $leaveDay = $this->finalizeEmptyDay($company, $employment, $approver, '2026-07-10');
        $this->assertSame(AttendanceDayStatus::UnpaidLeave, $leaveDay->day_status);

        AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => '2026-07-09',
            'day_status' => AttendanceDayStatus::Present,
            'state' => AttendanceRecordState::Finalized,
            'scheduled_minutes' => 480,
            'worked_minutes' => 480,
            'source_checksum' => hash('sha256', 'synthetic-finalized-record'),
            'finalized_by_id' => $approver->getKey(),
            'finalized_at' => now(),
        ]);

        $summary = app(BuildAttendanceMonthlySummaryAction::class)->handle(
            $employment,
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-31'),
            $approver,
        );
        app(FinalizeAttendanceMonthlySummaryAction::class)->handle($summary, $approver);

        $this->assertSame(AttendanceSummaryStatus::Finalized, $summary->refresh()->status);
        $this->assertSame('1.00', $summary->unpaid_leave_units);
        $this->assertSame(1, $summary->present_days);
        $this->assertSame(1, $summary->leave_days);
        $this->assertNotNull($summary->source_checksum);

        $this->expectException(ValidationException::class);
        $summary->update(['late_minutes' => 5]);
    }

    /**
     * @return array{Company, Employment, WorkCalendar, WorkShift, AttendanceRule}
     */
    private function attendanceContext(
        string $shiftStart = '09:00',
        string $shiftEnd = '18:00',
        bool $overnight = false,
    ): array {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        $calendar = WorkCalendar::query()->create([
            'company_id' => $company->getKey(),
            'code' => 'STD',
            'name' => 'Synthetic standard calendar',
            'working_weekdays' => [1, 2, 3, 4, 5, 6],
            'effective_from' => '2026-01-01',
        ]);
        $shift = WorkShift::query()->create([
            'company_id' => $company->getKey(),
            'code' => 'SHIFT',
            'name' => 'Synthetic shift',
            'starts_at' => $shiftStart,
            'ends_at' => $shiftEnd,
            'break_minutes' => 60,
            'is_overnight' => $overnight,
        ]);
        $rule = AttendanceRule::query()->create([
            'company_id' => $company->getKey(),
            'name' => 'Synthetic attendance rule',
            'effective_from' => '2026-01-01',
            'grace_minutes' => 10,
            'late_rounding_minutes' => 5,
            'half_day_after_minutes' => 120,
            'absence_after_minutes' => 240,
            'minimum_overtime_minutes' => 30,
            'missing_punch_treatment' => MissingPunchTreatment::Flag,
        ]);
        ShiftAssignment::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'work_calendar_id' => $calendar->getKey(),
            'work_shift_id' => $shift->getKey(),
            'effective_from' => '2026-01-01',
        ]);

        return [$company, $employment, $calendar, $shift, $rule];
    }

    /**
     * @return array{LeaveType, LeavePolicy}
     */
    private function leaveConfiguration(Company $company, bool $allowNegative, bool $paid = true): array
    {
        $leaveType = LeaveType::query()->create([
            'company_id' => $company->getKey(),
            'code' => $paid ? 'PAID' : 'UNPAID',
            'name' => $paid ? 'Synthetic paid leave' : 'Synthetic unpaid leave',
            'unit' => LeaveUnit::Day,
            'is_paid' => $paid,
            'payroll_impact' => $paid ? LeavePayrollImpact::None : LeavePayrollImpact::UnpaidDeduction,
        ]);
        $policy = LeavePolicy::query()->create([
            'company_id' => $company->getKey(),
            'leave_type_id' => $leaveType->getKey(),
            'name' => 'Synthetic effective policy',
            'effective_from' => '2026-01-01',
            'annual_units' => 12,
            'allow_negative_balance' => $allowNegative,
        ]);

        return [$leaveType, $policy];
    }

    private function leaveRequest(
        Company $company,
        Employment $employment,
        LeaveType $leaveType,
        float $units,
    ): LeaveRequest {
        return LeaveRequest::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'leave_type_id' => $leaveType->getKey(),
            'starts_on' => '2026-07-10',
            'ends_on' => '2026-07-10',
            'requested_units' => $units,
            'reason' => 'Synthetic leave request',
            'is_paid_snapshot' => $leaveType->is_paid,
            'payroll_impact_snapshot' => $leaveType->payroll_impact,
        ]);
    }

    private function punch(
        Company $company,
        Employment $employment,
        User $creator,
        string $punchedAt,
        AttendancePunchDirection $direction,
    ): AttendancePunch {
        return AttendancePunch::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'punched_at' => $punchedAt,
            'direction' => $direction,
            'reason' => 'Authorized manual evidence',
            'created_by_id' => $creator->getKey(),
        ]);
    }

    private function finalizeEmptyDay(
        Company $company,
        Employment $employment,
        User $actor,
        string $date,
    ): AttendanceRecord {
        $record = AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => $date,
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);

        return app(FinalizeAttendanceRecordAction::class)->handle($record, $actor);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(Company $company, array $permissions): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $user->givePermissionTo(collect($permissions)->map(
            fn (string $permission): Permission => Permission::findOrCreate($permission),
        ));
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        return $user;
    }

    private function expectValidationException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
