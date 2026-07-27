<?php

namespace App\Actions\JoiningLetters;

use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecordJoiningLetterAcceptanceAction
{
    public function handle(
        JoiningLetter $letter,
        User $actor,
        string $acceptedByName,
        ?string $notes = null,
    ): JoiningLetter {
        return DB::transaction(function () use ($acceptedByName, $actor, $letter, $notes): JoiningLetter {
            $lockedLetter = JoiningLetter::query()->whereKey($letter)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('recordAcceptance', $lockedLetter);

            if ($lockedLetter->status !== JoiningLetterStatus::Issued) {
                throw ValidationException::withMessages([
                    'joining_letter' => 'Acceptance can only be recorded for an issued joining letter.',
                ]);
            }

            if (blank($acceptedByName)) {
                throw ValidationException::withMessages([
                    'accepted_by_name' => 'The accepting employee name is required.',
                ]);
            }

            $lockedLetter->update([
                'status' => JoiningLetterStatus::Accepted,
                'accepted_by_name' => $acceptedByName,
                'accepted_at' => now(),
                'acceptance_notes' => $notes,
            ]);

            activity('joining_letters')
                ->causedBy($actor)
                ->performedOn($lockedLetter)
                ->event('accepted')
                ->withProperties([
                    'company_id' => $lockedLetter->company_id,
                    'accepted_by_name' => $acceptedByName,
                ])
                ->log('recorded joining-letter acceptance');

            return $lockedLetter;
        });
    }
}
