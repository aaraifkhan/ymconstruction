<?php

namespace App\Models;

use Database\Factories\EmployeeQualificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'employee_id',
    'qualification',
    'institution',
    'field_of_study',
    'completion_year',
    'grade',
    'notes',
])]
class EmployeeQualification extends Model
{
    /** @use HasFactory<EmployeeQualificationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_qualifications')
            ->logOnly([
                'employee_id',
                'qualification',
                'institution',
                'field_of_study',
                'completion_year',
                'grade',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['completion_year' => 'integer'];
    }
}
