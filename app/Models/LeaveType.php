<?php

namespace App\Models;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveUnit;
use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'code', 'name', 'unit', 'is_paid', 'payroll_impact',
    'requires_attachment', 'is_active',
])]
class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['unit' => 'day', 'is_paid' => true, 'payroll_impact' => 'none', 'is_active' => true];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('leave_types')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'unit' => LeaveUnit::class,
            'is_paid' => 'boolean',
            'payroll_impact' => LeavePayrollImpact::class,
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
