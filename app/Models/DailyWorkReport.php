<?php

namespace App\Models;

use App\Enums\DailyReportReviewStatus;
use App\Enums\DailyReportSubmissionStatus;
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
    'employment_id',
    'department_id',
    'department_team_id',
    'report_date',
    'submitted_at',
    'submission_status',
    'tasks_completed_count',
    'tasks_in_progress_count',
    'tasks_pending_count',
    'overall_progress_percentage',
    'deliverables_count',
    'blockers_summary',
    'additional_comments',
    'review_status',
    'reviewed_by_user_id',
    'reviewed_at',
    'review_notes',
])]
class DailyWorkReport extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'submission_status' => DailyReportSubmissionStatus::OnTime->value,
        'review_status' => DailyReportReviewStatus::Pending->value,
        'tasks_completed_count' => 0,
        'tasks_in_progress_count' => 0,
        'tasks_pending_count' => 0,
        'overall_progress_percentage' => 0,
        'deliverables_count' => 0,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function departmentTeam(): BelongsTo
    {
        return $this->belongsTo(DepartmentTeam::class, 'department_team_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function taskItems(): HasMany
    {
        return $this->hasMany(DailyReportTaskItem::class);
    }

    public function salesDetail(): HasOne
    {
        return $this->hasOne(DailySalesReportDetail::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('daily_work_reports')
            ->logOnly([
                'employment_id',
                'report_date',
                'submission_status',
                'submitted_at',
                'overall_progress_percentage',
                'review_status',
                'reviewed_by_user_id',
                'reviewed_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'submission_status' => DailyReportSubmissionStatus::class,
            'review_status' => DailyReportReviewStatus::class,
            'report_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'tasks_completed_count' => 'integer',
            'tasks_in_progress_count' => 'integer',
            'tasks_pending_count' => 'integer',
            'overall_progress_percentage' => 'integer',
            'deliverables_count' => 'integer',
        ];
    }
}
