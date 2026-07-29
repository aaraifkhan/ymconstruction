<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\LeaveType;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $leaveType = LeaveType::query()
            ->whereBelongsTo(Filament::getTenant())
            ->findOrFail($data['leave_type_id']);
        $data['status'] = 'draft';
        $data['is_paid_snapshot'] = $leaveType->is_paid;
        $data['payroll_impact_snapshot'] = $leaveType->payroll_impact->value;
        $data['leave_policy_id'] = null;
        $data['requested_by_id'] = null;
        $data['requested_at'] = null;

        return $data;
    }
}
