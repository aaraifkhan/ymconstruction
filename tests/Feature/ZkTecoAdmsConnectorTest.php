<?php

namespace Tests\Feature;

use App\Enums\AttendanceDeviceHealthStatus;
use App\Enums\AttendanceDeviceTransport;
use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceRawEventStatus;
use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceUserMapping;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceRawEvent;
use App\Models\Company;
use App\Models\Employment;
use Database\Seeders\CompanySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ZkTecoAdmsConnectorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_adms_get_cdata_handshake_returns_ok(): void
    {
        $this->seed(CompanySeeder::class);
        $company = Company::query()->where('slug', 'ymc-construction')->firstOrFail();

        $device = AttendanceDevice::factory()->create([
            'company_id' => $company->getKey(),
            'code' => 'DEV-MB460-01',
            'device_identifier' => 'MB460-SN-12345',
            'transport' => AttendanceDeviceTransport::ZkTecoAdms,
        ]);

        $response = $this->get('/iclock/cdata?SN=MB460-SN-12345');

        $response->assertOk();
        $response->assertSee('OK');

        $this->assertNotNull($device->fresh()->last_seen_at);
        $this->assertSame(AttendanceDeviceHealthStatus::Online, $device->fresh()->health_status);
    }

    public function test_adms_post_cdata_unregistered_device_returns_404(): void
    {
        $response = $this->post('/iclock/cdata?SN=UNKNOWN-DEVICE-SN', [], [
            'Content-Type' => 'text/plain',
        ]);

        $response->assertStatus(404);
        $response->assertSee('ERROR: Device not registered or inactive');
    }

    public function test_adms_post_cdata_attlog_pushes_valid_punches_and_ingests(): void
    {
        $this->seed(CompanySeeder::class);
        $company = Company::query()->where('slug', 'ymc-construction')->firstOrFail();

        $employment = Employment::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $device = AttendanceDevice::factory()->create([
            'company_id' => $company->getKey(),
            'code' => 'DEV-MB460-01',
            'device_identifier' => 'MB460-SN-12345',
            'transport' => AttendanceDeviceTransport::ZkTecoAdms,
            'timezone' => 'Asia/Karachi',
        ]);

        AttendanceDeviceUserMapping::factory()->create([
            'company_id' => $company->getKey(),
            'attendance_device_id' => $device->getKey(),
            'employment_id' => $employment->getKey(),
            'external_user_id' => '1001',
        ]);

        $body = "1001\t2026-08-04 09:00:00\t0\t1\t0\t0\t0\n1001\t2026-08-04 18:00:00\t1\t1\t0\t0\t0";

        $response = $this->call('POST', '/iclock/cdata?SN=MB460-SN-12345&table=ATTLOG', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], $body);

        $response->assertOk();
        $response->assertSee('OK: 2');

        $this->assertDatabaseHas('attendance_import_batches', [
            'company_id' => $company->getKey(),
            'attendance_device_id' => $device->getKey(),
            'status' => AttendanceImportBatchStatus::Completed->value,
            'accepted_count' => 2,
        ]);

        $this->assertCount(2, AttendanceRawEvent::query()->where('company_id', $company->getKey())->get());
    }

    public function test_adms_post_cdata_duplicate_punches_are_deduplicated_safely(): void
    {
        $this->seed(CompanySeeder::class);
        $company = Company::query()->where('slug', 'ymc-construction')->firstOrFail();

        $employment = Employment::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $device = AttendanceDevice::factory()->create([
            'company_id' => $company->getKey(),
            'code' => 'DEV-MB460-01',
            'device_identifier' => 'MB460-SN-12345',
            'transport' => AttendanceDeviceTransport::ZkTecoAdms,
            'timezone' => 'Asia/Karachi',
        ]);

        AttendanceDeviceUserMapping::factory()->create([
            'company_id' => $company->getKey(),
            'attendance_device_id' => $device->getKey(),
            'employment_id' => $employment->getKey(),
            'external_user_id' => '1001',
        ]);

        $body = "1001\t2026-08-04 09:00:00\t0\t1\t0\t0\t0";

        // First push
        $this->call('POST', '/iclock/cdata?SN=MB460-SN-12345&table=ATTLOG', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], $body)->assertSee('OK: 1');

        // Second push with same log
        $response = $this->call('POST', '/iclock/cdata?SN=MB460-SN-12345&table=ATTLOG', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], $body);

        $response->assertOk();
        $response->assertSee('OK: 1');

        $batches = AttendanceImportBatch::query()->where('company_id', $company->getKey())->get();
        $this->assertCount(2, $batches);
        $this->assertSame(1, $batches->last()->duplicate_count);
        $this->assertSame(0, $batches->last()->accepted_count);
    }

    public function test_adms_post_cdata_unknown_user_id_quarantines_punch(): void
    {
        $this->seed(CompanySeeder::class);
        $company = Company::query()->where('slug', 'ymc-construction')->firstOrFail();

        $device = AttendanceDevice::factory()->create([
            'company_id' => $company->getKey(),
            'code' => 'DEV-MB460-01',
            'device_identifier' => 'MB460-SN-12345',
            'transport' => AttendanceDeviceTransport::ZkTecoAdms,
        ]);

        $body = "9999\t2026-08-04 09:10:00\t0\t1";

        $response = $this->call('POST', '/iclock/cdata?SN=MB460-SN-12345&table=ATTLOG', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], $body);

        $response->assertOk();
        $response->assertSee('OK: 1');

        $rawEvent = AttendanceRawEvent::query()->where('company_id', $company->getKey())->firstOrFail();
        $this->assertSame(AttendanceRawEventStatus::Quarantined, $rawEvent->processing_status);
        $this->assertSame('9999', $rawEvent->external_user_id);
    }
}
