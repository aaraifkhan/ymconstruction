<?php

namespace App\Models;

use App\Enums\QaTestingStatus;
use App\Enums\WebDevStatus;
use App\Enums\WebDevTaskType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'client_project',
    'website_or_page',
    'dev_task_type',
    'development_requirement',
    'dev_status',
    'qa_testing_status',
    'bug_count',
    'staging_url',
    'live_url',
    'qa_feedback_notes',
])]
class WebDevTaskDetail extends Model
{
    use HasFactory;

    protected $attributes = [
        'dev_task_type' => WebDevTaskType::Frontend->value,
        'dev_status' => WebDevStatus::RequirementAnalysis->value,
        'qa_testing_status' => QaTestingStatus::Untested->value,
        'bug_count' => 0,
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected function casts(): array
    {
        return [
            'dev_task_type' => WebDevTaskType::class,
            'dev_status' => WebDevStatus::class,
            'qa_testing_status' => QaTestingStatus::class,
            'bug_count' => 'integer',
        ];
    }
}
