<?php

namespace App\Models;

use App\Enums\AttachmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'uploaded_by_user_id',
    'attachment_type',
    'title',
    'file_name',
    'file_path',
    'file_type',
    'file_size_bytes',
    'external_url',
    'version_number',
    'is_final_deliverable',
    'notes',
])]
class TaskAttachment extends Model
{
    use HasFactory;

    protected $attributes = [
        'attachment_type' => AttachmentType::FileUpload->value,
        'version_number' => 1,
        'is_final_deliverable' => false,
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'attachment_type' => AttachmentType::class,
            'version_number' => 'integer',
            'is_final_deliverable' => 'boolean',
        ];
    }
}
