<?php

namespace App\Models;

use App\Actions\Tasks\AllocateTaskCodeAction;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'task_code',
    'title',
    'description',
    'department_id',
    'department_team_id',
    'assigned_to_employment_id',
    'assigned_by_user_id',
    'priority',
    'status',
    'progress_percentage',
    'start_date',
    'deadline_date',
    'submitted_at',
    'completed_at',
    'revision_count',
    'blocker_note',
    'lead_reviewed_by_id',
    'lead_reviewed_at',
    'lead_review_notes',
    'head_approved_by_id',
    'head_approved_at',
    'head_approval_notes',
])]
class Task extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'priority' => TaskPriority::Medium->value,
        'status' => TaskStatus::NotStarted->value,
        'progress_percentage' => 0,
        'revision_count' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Task $task): void {
            if (blank($task->task_code)) {
                $task->task_code = app(AllocateTaskCodeAction::class)->handle((int) $task->company_id);
            }
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

    public function departmentTeam(): BelongsTo
    {
        return $this->belongsTo(DepartmentTeam::class, 'department_team_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employment::class, 'assigned_to_employment_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function leadReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_reviewed_by_id');
    }

    public function headApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_approved_by_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(TaskRevision::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function socialMediaDetail(): HasOne
    {
        return $this->hasOne(SocialMediaTaskDetail::class);
    }

    public function designDetail(): HasOne
    {
        return $this->hasOne(DesignTaskDetail::class);
    }

    public function videoDetail(): HasOne
    {
        return $this->hasOne(VideoTaskDetail::class);
    }

    public function webDevDetail(): HasOne
    {
        return $this->hasOne(WebDevTaskDetail::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tasks')
            ->logOnly([
                'task_code',
                'title',
                'department_id',
                'department_team_id',
                'assigned_to_employment_id',
                'priority',
                'status',
                'progress_percentage',
                'deadline_date',
                'revision_count',
                'lead_reviewed_by_id',
                'lead_reviewed_at',
                'head_approved_by_id',
                'head_approved_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'progress_percentage' => 'integer',
            'revision_count' => 'integer',
            'start_date' => 'date',
            'deadline_date' => 'date',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'lead_reviewed_at' => 'datetime',
            'head_approved_at' => 'datetime',
        ];
    }
}
