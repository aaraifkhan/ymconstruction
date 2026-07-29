<?php

namespace App\Models;

use Database\Factories\WorkLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'project_site_id', 'code', 'name', 'address', 'is_active'])]
class WorkLocation extends Model
{
    /** @use HasFactory<WorkLocationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (WorkLocation $workLocation): void {
            if ($workLocation->project_site_id !== null
                && ! ProjectSite::query()
                    ->whereKey($workLocation->project_site_id)
                    ->where('company_id', $workLocation->company_id)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'project_site_id' => 'The selected project site must belong to the same company.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    public function attendanceDevices(): HasMany
    {
        return $this->hasMany(AttendanceDevice::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('work_locations')
            ->logOnly(['company_id', 'project_site_id', 'code', 'name', 'address', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
