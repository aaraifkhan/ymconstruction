<?php

namespace App\Http\Controllers\Api;

use App\Actions\HR\ProcessZkTecoAdmsPushAction;
use App\Enums\AttendanceDeviceHealthStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ZkTecoAdmsController extends Controller
{
    /**
     * Handle ZKTeco ADMS GET handshake, options, and heartbeat requests.
     */
    public function cdataGet(Request $request): Response
    {
        $sn = $this->resolveDeviceSn($request);
        if (filled($sn)) {
            $device = $this->findDevice($sn);
            if ($device !== null) {
                AttendanceDevice::query()->whereKey($device)->update([
                    'last_seen_at' => now(),
                    'health_status' => AttendanceDeviceHealthStatus::Online->value,
                ]);
            }
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle ZKTeco ADMS POST attendance log pushes (table=ATTLOG).
     */
    public function cdataPost(Request $request, ProcessZkTecoAdmsPushAction $processAdms): Response
    {
        $sn = $this->resolveDeviceSn($request);
        if (blank($sn)) {
            return response('ERROR: Missing Device Serial Number (SN)', 400)->header('Content-Type', 'text/plain');
        }

        $device = $this->findDevice($sn);
        if ($device === null) {
            return response('ERROR: Device not registered or inactive', 404)->header('Content-Type', 'text/plain');
        }

        $rawPayload = (string) $request->getContent();
        $processedCount = $processAdms->handle($device, $rawPayload);

        return response("OK: {$processedCount}", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle ZKTeco ADMS GET command polling requests.
     */
    public function getrequest(Request $request): Response
    {
        $sn = $this->resolveDeviceSn($request);
        if (filled($sn)) {
            $device = $this->findDevice($sn);
            if ($device !== null) {
                AttendanceDevice::query()->whereKey($device)->update([
                    'last_seen_at' => now(),
                ]);
            }
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle ZKTeco ADMS POST command execution confirmation.
     */
    public function devicecmd(Request $request): Response
    {
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    private function resolveDeviceSn(Request $request): ?string
    {
        $sn = $request->query('SN')
            ?? $request->query('sn')
            ?? $request->query('device_sn')
            ?? $request->header('X-Device-SN');

        return filled($sn) ? trim((string) $sn) : null;
    }

    private function findDevice(string $sn): ?AttendanceDevice
    {
        return AttendanceDevice::query()
            ->where('is_active', true)
            ->where(function ($query) use ($sn): void {
                $query->where('device_identifier', $sn)
                    ->orWhere('code', $sn);
            })
            ->first();
    }
}
