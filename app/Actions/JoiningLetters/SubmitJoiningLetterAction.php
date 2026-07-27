<?php

namespace App\Actions\JoiningLetters;

use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitJoiningLetterAction
{
    public function handle(JoiningLetter $letter, User $actor): JoiningLetter
    {
        return DB::transaction(function () use ($actor, $letter): JoiningLetter {
            $lockedLetter = JoiningLetter::query()->whereKey($letter)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('submit', $lockedLetter);

            if ($lockedLetter->status !== JoiningLetterStatus::Draft) {
                throw ValidationException::withMessages([
                    'joining_letter' => 'Only a draft joining letter can be submitted.',
                ]);
            }

            $lockedLetter->update([
                'status' => JoiningLetterStatus::PendingApproval,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
            ]);

            $this->log($lockedLetter, $actor, 'submitted');

            return $lockedLetter;
        });
    }

    private function log(JoiningLetter $letter, User $actor, string $event): void
    {
        activity('joining_letters')
            ->causedBy($actor)
            ->performedOn($letter)
            ->event($event)
            ->withProperties(['company_id' => $letter->company_id])
            ->log("{$event} joining letter");
    }
}
