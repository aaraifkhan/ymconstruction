<?php

namespace App\Actions\JoiningLetters;

use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectJoiningLetterAction
{
    public function handle(JoiningLetter $letter, User $actor, string $reason): JoiningLetter
    {
        return DB::transaction(function () use ($actor, $letter, $reason): JoiningLetter {
            $lockedLetter = JoiningLetter::query()->whereKey($letter)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('reject', $lockedLetter);

            if ($lockedLetter->status !== JoiningLetterStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'joining_letter' => 'Only a submitted joining letter can be rejected.',
                ]);
            }

            if (blank($reason)) {
                throw ValidationException::withMessages([
                    'reason' => 'A rejection reason is required.',
                ]);
            }

            $lockedLetter->update([
                'status' => JoiningLetterStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'approved_by_id' => null,
                'approved_at' => null,
            ]);

            activity('joining_letters')
                ->causedBy($actor)
                ->performedOn($lockedLetter)
                ->event('rejected')
                ->withProperties([
                    'company_id' => $lockedLetter->company_id,
                    'reason' => $reason,
                ])
                ->log('rejected joining letter');

            return $lockedLetter;
        });
    }
}
