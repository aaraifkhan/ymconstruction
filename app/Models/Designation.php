<?php

namespace App\Models;

use Database\Factories\DesignationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'department_id', 'name', 'code', 'description', 'is_active'])]
class Designation extends Model
{
    /** @use HasFactory<DesignationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (Designation $designation): void {
            $designation->validateDepartment();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('designations')
            ->logOnly(['company_id', 'department_id', 'name', 'code', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    private function validateDepartment(): void
    {
        if ($this->department_id === null) {
            return;
        }

        $department = Department::withTrashed()->find($this->department_id);

        if ($department === null || $department->company_id !== $this->company_id) {
            throw ValidationException::withMessages([
                'department_id' => 'The selected department must belong to the same company.',
            ]);
        }
    }
}
