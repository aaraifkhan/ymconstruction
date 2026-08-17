<?php

namespace App\Models;

use App\Enums\SocialContentType;
use App\Enums\SocialWorkflowStage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'client_brand',
    'platforms',
    'content_type',
    'caption',
    'creative_requirement',
    'hashtags_keywords',
    'publishing_datetime',
    'assigned_designer_id',
    'assigned_video_editor_id',
    'workflow_stage',
    'published_link',
])]
class SocialMediaTaskDetail extends Model
{
    use HasFactory;

    protected $attributes = [
        'workflow_stage' => SocialWorkflowStage::Idea->value,
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignedDesigner(): BelongsTo
    {
        return $this->belongsTo(Employment::class, 'assigned_designer_id');
    }

    public function assignedVideoEditor(): BelongsTo
    {
        return $this->belongsTo(Employment::class, 'assigned_video_editor_id');
    }

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'content_type' => SocialContentType::class,
            'workflow_stage' => SocialWorkflowStage::class,
            'publishing_datetime' => 'datetime',
        ];
    }
}
