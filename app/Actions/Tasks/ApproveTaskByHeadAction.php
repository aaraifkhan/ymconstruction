<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class ApproveTaskByHeadAction
{
    public function handle(Task $task, User $head, ?string $notes = null): Task
    {
        $task->head_approved_by_id = $head->id;
        $task->head_approved_at = now();
        $task->head_approval_notes = $notes;
        $task->status = TaskStatus::Completed;
        $task->completed_at = now();
        $task->progress_percentage = 100;

        if ($notes) {
            $task->comments()->create([
                'user_id' => $head->id,
                'comment' => "Department Head Final Approval: {$notes}",
                'is_blocker' => false,
            ]);
        }

        $task->save();

        return $task;
    }
}
