<?php

namespace App\Models;

use App\Enums\VideoType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'video_project_name',
    'client_brand',
    'video_type',
    'target_duration_seconds',
    'raw_footage_url',
    'script_text',
    'voiceover_url',
    'reference_video_url',
    'editing_instructions',
    'draft_video_url',
    'final_video_url',
    'published_link',
])]
class VideoTaskDetail extends Model
{
    use HasFactory;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected function casts(): array
    {
        return [
            'video_type' => VideoType::class,
            'target_duration_seconds' => 'integer',
        ];
    }
}
