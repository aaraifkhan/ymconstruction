<?php

namespace App\Models;

use App\Enums\EmployeeClearanceStatus;
use Database\Factories\EmployeeClearanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'employment_separation_id', 'reference_number',
    'status', 'source_checksum', 'prepared_by_id', 'submitted_by_id', 'submitted_at',
    'completed_by_id', 'completed_at',
])]
class EmployeeClearance extends Model
{
    /** @use HasFactory<EmployeeClearanceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (self $clearance): void {
            if (! Employment::query()->whereKey($clearance->employment_id)
                ->where('company_id', $clearance->company_id)->exists()
                || ! EmploymentSeparation::query()->whereKey($clearance->employment_separation_id)
                    ->where('company_id', $clearance->company_id)
                    ->where('employment_id', $clearance->employment_id)->exists()) {
                throw ValidationException::withMessages(['employment_id' => 'Clearance requires a same-company Employment and separation.']);
            }
        });

        static::deleting(function (self $clearance): void {
            if ($clearance->status !== EmployeeClearanceStatus::Draft || $clearance->items()->exists()) {
                throw ValidationException::withMessages(['status' => 'Prepared clearance evidence cannot be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function separation(): BelongsTo
    {
        return $this->belongsTo(EmploymentSeparation::class, 'employment_separation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EmployeeClearanceItem::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('employee_clearances')
            ->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => EmployeeClearanceStatus::class,
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
