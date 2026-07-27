<?php

namespace App\Actions\JoiningLetters;

use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class IssueJoiningLetterAction
{
    public function handle(JoiningLetter $letter, User $actor): JoiningLetter
    {
        return DB::transaction(function () use ($actor, $letter): JoiningLetter {
            $lockedLetter = JoiningLetter::query()->whereKey($letter)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('issue', $lockedLetter);

            if ($lockedLetter->status !== JoiningLetterStatus::Approved) {
                throw ValidationException::withMessages([
                    'joining_letter' => 'Only an approved joining letter can be issued.',
                ]);
            }

            $checksum = hash('sha256', $lockedLetter->subject."\n".$lockedLetter->body);

            $lockedLetter->update([
                'status' => JoiningLetterStatus::Issued,
                'issued_by_id' => $actor->getKey(),
                'issued_at' => now(),
                'content_checksum' => $checksum,
            ]);

            activity('joining_letters')
                ->causedBy($actor)
                ->performedOn($lockedLetter)
                ->event('issued')
                ->withProperties([
                    'company_id' => $lockedLetter->company_id,
                    'content_checksum' => $checksum,
                ])
                ->log('issued joining letter');

            return $lockedLetter;
        });
    }
}
