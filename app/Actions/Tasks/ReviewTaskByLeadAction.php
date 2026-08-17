<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;

class ReviewTaskByLeadAction
{
    public function handle(Task $task, User $lead, ?string $notes = null): Task
    {
        $task->lead_reviewed_by_id = $lead->id;
        $task->lead_reviewed_at = now();
        $task->lead_review_notes = $notes;

        if ($notes) {
            $task->comments()->create([
                'user_id' => $lead->id,
                'comment' => "Team Lead Review: {$notes}",
                'is_blocker' => false,
            ]);
        }

        $task->save();

        return $task;
    }
}
