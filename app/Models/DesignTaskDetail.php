<?php

namespace App\Models;

use App\Enums\DesignType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'design_type',
    'dimensions',
    'target_platform',
    'brand_client',
    'reference_links',
    'copy_content',
    'source_file_path',
    'preview_file_path',
    'final_file_path',
])]
class DesignTaskDetail extends Model
{
    use HasFactory;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected function casts(): array
    {
        return [
            'design_type' => DesignType::class,
        ];
    }
}
