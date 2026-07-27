<?php

namespace App\Models;

use Database\Factories\EmployeeExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'employee_id',
    'company_name',
    'designation',
    'start_date',
    'end_date',
    'duration_text',
    'reason_for_leaving',
    'notes',
])]
class EmployeeExperience extends Model
{
    /** @use HasFactory<EmployeeExperienceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (EmployeeExperience $experience): void {
            if ($experience->start_date !== null && $experience->end_date?->lt($experience->start_date)) {
                throw ValidationException::withMessages([
                    'end_date' => 'The ending date must be on or after the starting date.',
                ]);
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_experiences')
            ->logOnly([
                'employee_id',
                'company_name',
                'designation',
                'start_date',
                'end_date',
                'duration_text',
                'reason_for_leaving',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
