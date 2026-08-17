<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmitTaskAction
{
    public function handle(Task $task, User $actor, ?string $submissionNote = null): Task
    {
        if (in_array($task->status, [TaskStatus::Approved, TaskStatus::Completed, TaskStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'status' => 'This task is already approved or completed.',
            ]);
        }

        $task->status = TaskStatus::Submitted;
        $task->progress_percentage = 100;
        $task->submitted_at = now();

        if ($submissionNote) {
            $task->comments()->create([
                'user_id' => $actor->id,
                'comment' => "Submitted for review: {$submissionNote}",
                'is_blocker' => false,
            ]);
        }

        $task->save();

        return $task;
    }
}
