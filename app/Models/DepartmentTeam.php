<?php

namespace App\Models;

use App\Enums\TeamType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'department_id',
    'name',
    'code',
    'team_type',
    'team_lead_id',
    'description',
    'is_active',
])]
class DepartmentTeam extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'team_type' => TeamType::Other->value,
        'is_active' => true,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(Employment::class, 'team_lead_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(DepartmentTeamMember::class, 'department_team_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'department_team_id');
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyWorkReport::class, 'department_team_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('department_teams')
            ->logOnly(['company_id', 'department_id', 'name', 'code', 'team_type', 'team_lead_id', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'team_type' => TeamType::class,
            'is_active' => 'boolean',
        ];
    }
}
