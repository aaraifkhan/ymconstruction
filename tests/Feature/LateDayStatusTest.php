<?php

namespace Tests\Feature;

use App\Actions\HR\ApproveAttendancePunchAction;
use App\Actions\HR\FinalizeAttendanceRecordAction;
use App\Enums\AttendanceDayStatus;
use App\Enums\AttendancePunchDirection;
use App\Enums\MissingPunchTreatment;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRule;
use App\Models\Company;
use App\Models\Employment;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkShift;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LateDayStatusTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_attendance_record_resolves_to_late_when_arrived_after_grace_but_before_half_day_threshold(): void
    {
        [$company, $employment, $rule] = $this->attendanceContext();
        $creator = $this->actor($company, []);
        $approver = $this->actor($company, ['Approve:AttendancePunch', 'Finalize:AttendanceRecord']);

        // Shift: 09:00. Grace: 10 min. Half-day: 120 min. Arrived 09:25 => 15 min late (after rounding to 5).
        $inPunch = $this->punch($company, $employment, $creator, '2026-07-07 09:25:00', AttendancePunchDirection::In);
        $outPunch = $this->punch($company, $employment, $creator, '2026-07-07 18:00:00', AttendancePunchDirection::Out);

        app(ApproveAttendancePunchAction::class)->handle($inPunch, $approver);
        app(ApproveAttendancePunchAction::class)->handle($outPunch, $approver);

        $record = AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => '2026-07-07',
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);

        $finalized = app(FinalizeAttendanceRecordAction::class)->handle($record, $approver);

        $this->assertSame(AttendanceDayStatus::Late, $finalized->day_status);
        $this->assertGreaterThan(0, $finalized->late_minutes);
    }

    public function test_attendance_record_resolves_to_present_when_within_grace_period(): void
    {
        [$company, $employment, $rule] = $this->attendanceContext();
        $creator = $this->actor($company, []);
        $approver = $this->actor($company, ['Approve:AttendancePunch', 'Finalize:AttendanceRecord']);

        // Arrived 09:08 — within 10-minute grace.
        $inPunch = $this->punch($company, $employment, $creator, '2026-07-07 09:08:00', AttendancePunchDirection::In);
        $outPunch = $this->punch($company, $employment, $creator, '2026-07-07 18:00:00', AttendancePunchDirection::Out);

        app(ApproveAttendancePunchAction::class)->handle($inPunch, $approver);
        app(ApproveAttendancePunchAction::class)->handle($outPunch, $approver);

        $record = AttendanceRecord::query()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'attendance_date' => '2026-07-07',
            'day_status' => AttendanceDayStatus::MissingPunch,
        ]);

        $finalized = app(FinalizeAttendanceRecordAction::class)->handle($record, $approver);

        $this->assertSame(AttendanceDayStatus::Present, $finalized->day_status);
        $this->assertSame(0, $finalized->late_minutes);
    }

    public function test_late_case_exists_in_enum(): void
    {
        $this->assertSame('late', AttendanceDayStatus::Late->value);
        $this->assertSame('Late', AttendanceDayStatus::Late->label());
    }

    private function attendanceContext(): array
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        WorkCalendar::query()->create([
            'company_id' => $company->getKey(),
            'code' => 'STD',
            'name' => 'Synthetic standard calendar',
            'working_weekdays' => [1, 2, 3, 4, 5, 6],
            'effective_from' => '2026-01-01',
        ]);
        $calendar = WorkCalendar::query()->where('company_id', $company->getKey())->first();
        WorkShift::query()->create([
            'company_id' => $company->getKey(),
            'code' => 'SHIFT',
            'name' => 'Synthetic shift',
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'break_minutes' => 60,
            'is_overnight' => false,
        ]);
        $shift = WorkShift::query()->where('company_id', $company->getKey())->first();
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

        return [$company, $employment, $rule];
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

    private function actor(Company $company, array $permissions = ['Approve:AttendancePunch', 'Finalize:AttendanceRecord']): User
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
}
