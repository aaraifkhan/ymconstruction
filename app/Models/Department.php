<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'parent_department_id', 'name', 'code', 'description', 'is_active'])]
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (Department $department): void {
            $department->validateParentDepartment();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    public function parentDepartment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_department_id');
    }

    public function childDepartments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_department_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('departments')
            ->logOnly(['company_id', 'parent_department_id', 'name', 'code', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    private function validateParentDepartment(): void
    {
        if ($this->parent_department_id === null) {
            return;
        }

        $parent = self::withTrashed()->find($this->parent_department_id);

        if ($parent === null || $parent->company_id !== $this->company_id) {
            throw ValidationException::withMessages([
                'parent_department_id' => 'The parent department must belong to the same company.',
            ]);
        }

        $visitedDepartmentIds = [];

        while ($parent !== null) {
            if ($this->exists && $parent->is($this)) {
                throw ValidationException::withMessages([
                    'parent_department_id' => 'A department cannot be its own parent or a descendant of itself.',
                ]);
            }

            if (in_array($parent->getKey(), $visitedDepartmentIds, true)) {
                throw ValidationException::withMessages([
                    'parent_department_id' => 'The selected department hierarchy already contains a cycle.',
                ]);
            }

            $visitedDepartmentIds[] = $parent->getKey();
            $parent = $parent->parentDepartment()->withTrashed()->first();
        }
    }
}
