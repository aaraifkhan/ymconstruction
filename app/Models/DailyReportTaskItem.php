<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'daily_work_report_id',
    'task_id',
    'task_title',
    'status_today',
    'hours_spent',
    'progress_percentage',
    'deliverable_summary',
    'work_links',
    'blockers',
])]
class DailyReportTaskItem extends Model
{
    use HasFactory;

    protected $attributes = [
        'status_today' => 'in_progress',
        'hours_spent' => 0,
        'progress_percentage' => 0,
    ];

    public function dailyWorkReport(): BelongsTo
    {
        return $this->belongsTo(DailyWorkReport::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected function casts(): array
    {
        return [
            'hours_spent' => 'decimal:2',
            'progress_percentage' => 'integer',
        ];
    }
}
