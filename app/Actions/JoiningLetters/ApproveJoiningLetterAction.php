<?php

namespace App\Actions\JoiningLetters;

use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveJoiningLetterAction
{
    public function handle(JoiningLetter $letter, User $actor): JoiningLetter
    {
        return DB::transaction(function () use ($actor, $letter): JoiningLetter {
            $lockedLetter = JoiningLetter::query()->whereKey($letter)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('approve', $lockedLetter);

            if ($lockedLetter->status !== JoiningLetterStatus::PendingApproval) {
                throw ValidationException::withMessages([
                    'joining_letter' => 'Only a submitted joining letter can be approved.',
                ]);
            }

            $lockedLetter->update([
                'status' => JoiningLetterStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('joining_letters')
                ->causedBy($actor)
                ->performedOn($lockedLetter)
                ->event('approved')
                ->withProperties(['company_id' => $lockedLetter->company_id])
                ->log('approved joining letter');

            return $lockedLetter;
        });
    }
}
