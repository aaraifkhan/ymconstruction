<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $company = Filament::getTenant();

        if (! $company instanceof Company) {
            throw new LogicException('A company tenant is required.');
        }

        Gate::authorize('create', Employment::class);

        $employment = [];

        foreach ([
            'joining_date',
            'department_id',
            'designation_id',
            'reporting_to_employment_id',
            'employment_category',
            'employment_type',
            'employment_status',
            'probation_start_date',
            'probation_end_date',
            'confirmation_date',
            'notice_period_days',
            'work_location_id',
            'work_start_time',
            'work_end_time',
            'working_days_per_week',
        ] as $field) {
            $employment[$field] = Arr::pull($data, "employment_{$field}");
        }

        if (! (auth()->user()?->can('ManageSensitive:Employee') ?? false)) {
            $data = Arr::except($data, [
                'father_or_husband_name',
                'cnic',
                'date_of_birth',
                'gender',
                'marital_status',
                'nationality',
                'blood_group',
                'address',
                'mobile',
                'alternate_contact',
                'email',
            ]);
        }

        return DB::transaction(function () use ($company, $data, $employment): Employee {
            $employee = Employee::query()->create($data);
            $employee->employments()->create([
                ...$employment,
                'company_id' => $company->getKey(),
            ]);

            return $employee;
        });
    }
}
