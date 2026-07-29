<?php

namespace Tests\Feature;

use App\Actions\HR\ImportAttendanceCsvAction;
use App\Actions\HR\ReprocessAttendanceImportBatchAction;
use App\Actions\HR\ReprocessAttendanceRawEventAction;
use App\Actions\HR\SyncAttendanceDeviceAction;
use App\Contracts\AttendanceDeviceAdapter;
use App\Data\AttendanceDevicePullResult;
use App\Data\AttendanceEventData;
use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceImportSource;
use App\Enums\AttendancePunchDirection;
use App\Enums\AttendancePunchSource;
use App\Enums\AttendanceRawEventStatus;
use App\Filament\Resources\AttendanceDevices\Pages\ListAttendanceDevices;
use App\Filament\Resources\AttendanceImportBatches\Pages\ListAttendanceImportBatches;
use App\Filament\Resources\AttendanceRawEvents\Pages\ListAttendanceRawEvents;
use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceUserMapping;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceImportRowError;
use App\Models\AttendancePunch;
use App\Models\AttendanceRawEvent;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employment;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceMachineIngestionFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_csv_import_normalizes_mapped_events_and_is_file_idempotent(): void
    {
        [$company, $employment, $device] = $this->deviceContext();
        $actor = $this->actor($company, ['Import:AttendanceImportBatch']);
        $this->map($company, $employment, $device, '101');
        $path = $this->storeCsv([
            'DEV-01,101,2026-07-28 09:01:00,Asia/Karachi,in,evt-1',
        ]);

        $batch = app(ImportAttendanceCsvAction::class)->handle($company, $path, 'attendance.csv', $actor);
        $duplicate = app(ImportAttendanceCsvAction::class)->handle($company, $path, 'attendance.csv', $actor);

        $this->assertTrue($batch->is($duplicate));
        $this->assertSame(AttendanceImportBatchStatus::Completed, $batch->status);
        $this->assertSame(1, $batch->accepted_count);
        $this->assertDatabaseCount('attendance_import_batches', 1);
        $this->assertDatabaseCount('attendance_raw_events', 1);
        $this->assertDatabaseCount('attendance_punches', 1);
        $this->assertDatabaseCount('attendance_records', 1);

        $event = AttendanceRawEvent::query()->sole();
        $punch = AttendancePunch::query()->sole();
        $this->assertSame(AttendanceRawEventStatus::Processed, $event->processing_status);
        $this->assertSame(AttendancePunchSource::Machine, $punch->source);
        $this->assertNull($punch->created_by_id);
        $this->assertSame('2026-07-28', AttendanceRecord::query()->sole()->attendance_date->toDateString());
    }

    public function test_unknown_user_is_quarantined_then_reprocessed_after_mapping(): void
    {
        [$company, $employment, $device] = $this->deviceContext();
        $actor = $this->actor($company, [
            'Import:AttendanceImportBatch',
            'Reprocess:AttendanceRawEvent',
        ]);
        $path = $this->storeCsv([
            'DEV-01,404,2026-07-28 09:01:00,Asia/Karachi,in,evt-404',
        ]);

        $batch = app(ImportAttendanceCsvAction::class)->handle($company, $path, 'unknown.csv', $actor);
        $event = AttendanceRawEvent::query()->sole();

        $this->assertSame(AttendanceImportBatchStatus::CompletedWithErrors, $batch->status);
        $this->assertSame(AttendanceRawEventStatus::Quarantined, $event->processing_status);
        $this->assertDatabaseCount('attendance_punches', 0);

        $this->map($company, $employment, $device, '404');
        app(ReprocessAttendanceRawEventAction::class)->handle($event, $actor);

        $this->assertSame(AttendanceRawEventStatus::Processed, $event->refresh()->processing_status);
        $this->assertDatabaseCount('attendance_punches', 1);
    }

    public function test_missing_direction_requires_review_without_creating_a_punch(): void
    {
        [$company, $employment, $device] = $this->deviceContext();
        $actor = $this->actor($company, ['Import:AttendanceImportBatch']);
        $this->map($company, $employment, $device, '101');
        $path = $this->storeCsv([
            'DEV-01,101,2026-07-28 09:01:00,Asia/Karachi,,evt-review',
        ]);

        app(ImportAttendanceCsvAction::class)->handle($company, $path, 'review.csv', $actor);

        $this->assertSame(AttendanceRawEventStatus::RequiresReview, AttendanceRawEvent::query()->sole()->processing_status);
        $this->assertDatabaseCount('attendance_punches', 0);
    }

    public function test_invalid_csv_contract_and_rows_are_retained_as_auditable_errors(): void
    {
        [$company] = $this->deviceContext();
        $actor = $this->actor($company, [
            'Import:AttendanceImportBatch',
            'Reprocess:AttendanceImportBatch',
        ]);
        Storage::disk('local')->put('private/attendance-imports/bad.csv', "wrong,headers\nx,y\n");

        $batch = app(ImportAttendanceCsvAction::class)->handle(
            $company,
            'private/attendance-imports/bad.csv',
            'bad.csv',
            $actor,
        );

        $this->assertSame(AttendanceImportBatchStatus::Failed, $batch->status);
        $this->assertSame('invalid_header', AttendanceImportRowError::query()->sole()->error_code);
        $this->assertTrue(Storage::disk('local')->exists($batch->stored_file_path));

        $reprocessed = app(ReprocessAttendanceImportBatchAction::class)->handle($batch, $actor);
        $this->assertFalse($batch->is($reprocessed));
        $this->assertSame(AttendanceImportBatchStatus::Failed, $reprocessed->status);
        $this->assertDatabaseCount('attendance_import_row_errors', 2);
    }

    public function test_overnight_machine_event_is_attached_to_the_prior_attendance_day(): void
    {
        [$company, $employment, $device] = $this->deviceContext();
        $actor = $this->actor($company, ['Import:AttendanceImportBatch']);
        $this->map($company, $employment, $device, '101');
        $calendar = WorkCalendar::factory()->forCompany($company)->create([
            'effective_from' => '2026-01-01',
        ]);
        $shift = WorkShift::factory()->forCompany($company)->create([
            'starts_at' => '20:00',
            'ends_at' => '05:00',
            'is_overnight' => true,
        ]);
        ShiftAssignment::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'work_calendar_id' => $calendar->getKey(),
            'work_shift_id' => $shift->getKey(),
            'effective_from' => '2026-01-01',
        ]);
        $path = $this->storeCsv([
            'DEV-01,101,2026-07-29 05:02:00,Asia/Karachi,out,overnight-out',
        ]);

        app(ImportAttendanceCsvAction::class)->handle($company, $path, 'overnight.csv', $actor);

        $this->assertSame('2026-07-28', AttendanceRecord::query()->sole()->attendance_date->toDateString());
    }

    public function test_adapter_contract_uses_same_ingestion_pipeline_and_prevents_duplicate_evidence(): void
    {
        [$company, $employment, $device] = $this->deviceContext();
        $actor = $this->actor($company, ['Sync:AttendanceDevice']);
        $this->map($company, $employment, $device, '101');
        $adapter = new class implements AttendanceDeviceAdapter
        {
            public function pull(AttendanceDevice $device, ?string $cursor): AttendanceDevicePullResult
            {
                return new AttendanceDevicePullResult([
                    new AttendanceEventData(
                        deviceCode: $device->code,
                        externalUserId: '101',
                        punchedAtLocal: '2026-07-28 09:01:00',
                        timezone: $device->timezone,
                        direction: AttendancePunchDirection::In,
                        sourceEventId: 'adapter-evt-1',
                        source: AttendanceImportSource::DeviceAdapter,
                    ),
                ], 'cursor-1', ['adapter' => 'test-double']);
            }
        };

        app(SyncAttendanceDeviceAction::class)->handle($device, $adapter, $actor);
        $secondBatch = app(SyncAttendanceDeviceAction::class)->handle($device->refresh(), $adapter, $actor);

        $this->assertSame(1, $secondBatch->duplicate_count);
        $this->assertDatabaseCount('attendance_raw_events', 1);
        $this->assertDatabaseCount('attendance_punches', 1);
        $this->assertSame('cursor-1', $device->refresh()->last_cursor);
    }

    public function test_raw_evidence_batches_and_row_errors_are_immutable(): void
    {
        [$company] = $this->deviceContext();
        $batch = AttendanceImportBatch::factory()->create(['company_id' => $company->getKey()]);
        $event = AttendanceRawEvent::factory()->create([
            'company_id' => $company->getKey(),
            'attendance_import_batch_id' => $batch->getKey(),
        ]);
        $error = AttendanceImportRowError::factory()->create([
            'company_id' => $company->getKey(),
            'attendance_import_batch_id' => $batch->getKey(),
        ]);

        $this->expectValidationException(fn () => $batch->update(['failure_summary' => 'rewrite']));
        $this->expectValidationException(fn () => $event->update(['processing_error' => 'rewrite']));
        $this->expectValidationException(fn () => $error->delete());
    }

    public function test_company_boundaries_and_management_lists_are_enforced(): void
    {
        [$company, $employment, $device] = $this->deviceContext();
        $otherCompany = Company::factory()->create();
        $otherEmployment = Employment::factory()->forCompany($otherCompany)->create();
        $otherLocation = WorkLocation::factory()->for($otherCompany)->create();
        $actor = $this->actor($company, [
            'ViewAny:AttendanceDevice',
            'ViewAny:AttendanceImportBatch',
            'ViewAny:AttendanceRawEvent',
        ]);
        $otherDevice = AttendanceDevice::factory()->forCompany($otherCompany)->create();

        $this->expectValidationException(fn () => AttendanceDeviceUserMapping::query()->create([
            'company_id' => $company->getKey(),
            'attendance_device_id' => $device->getKey(),
            'employment_id' => $otherEmployment->getKey(),
            'external_user_id' => 'cross-company',
            'effective_from' => '2026-01-01',
        ]));
        $this->expectValidationException(fn () => $device->update([
            'work_location_id' => $otherLocation->getKey(),
        ]));

        $this->actingAs($actor);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(ListAttendanceDevices::class)
            ->assertCanSeeTableRecords([$device])
            ->assertCanNotSeeTableRecords([$otherDevice]);
        Livewire::test(ListAttendanceImportBatches::class)->assertSuccessful();
        Livewire::test(ListAttendanceRawEvents::class)->assertSuccessful();
    }

    /**
     * @return array{Company, Employment, AttendanceDevice}
     */
    private function deviceContext(): array
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
        ]);
        $device = AttendanceDevice::factory()->forCompany($company)->create([
            'code' => 'DEV-01',
            'timezone' => 'Asia/Karachi',
        ]);

        return [$company, $employment, $device];
    }

    private function map(
        Company $company,
        Employment $employment,
        AttendanceDevice $device,
        string $externalUserId,
    ): AttendanceDeviceUserMapping {
        return AttendanceDeviceUserMapping::query()->create([
            'company_id' => $company->getKey(),
            'attendance_device_id' => $device->getKey(),
            'employment_id' => $employment->getKey(),
            'external_user_id' => $externalUserId,
            'effective_from' => '2026-01-01',
        ]);
    }

    /**
     * @param  list<string>  $rows
     */
    private function storeCsv(array $rows): string
    {
        $path = 'private/attendance-imports/'.fake()->unique()->uuid().'.csv';
        $header = 'device_code,external_user_id,punched_at_local,timezone,direction,source_event_id';
        Storage::disk('local')->put($path, implode("\n", [$header, ...$rows])."\n");

        return $path;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actor(Company $company, array $permissions): User
    {
        $actor = User::factory()->create();
        $actor->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $actor->givePermissionTo(
            collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission))
                ->all(),
        );

        return $actor;
    }

    private function expectValidationException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }
}
