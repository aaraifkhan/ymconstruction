<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class RequestTaskRevisionAction
{
    public function handle(Task $task, User $requester, string $revisionNotes): Task
    {
        $revisionNumber = $task->revision_count + 1;

        $task->revisions()->create([
            'revision_number' => $revisionNumber,
            'requested_by_user_id' => $requester->id,
            'requested_at' => now(),
            'revision_notes' => $revisionNotes,
        ]);

        $task->revision_count = $revisionNumber;
        $task->status = TaskStatus::RevisionRequired;

        $task->comments()->create([
            'user_id' => $requester->id,
            'comment' => "Revision #{$revisionNumber} Requested: {$revisionNotes}",
            'is_blocker' => true,
        ]);

        $task->save();

        return $task;
    }
}
