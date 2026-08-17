<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'revision_number',
    'requested_by_user_id',
    'requested_at',
    'revision_notes',
    'submitted_response',
    'resubmitted_at',
])]
class TaskRevision extends Model
{
    use HasFactory;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'requested_at' => 'datetime',
            'resubmitted_at' => 'datetime',
        ];
    }
}
