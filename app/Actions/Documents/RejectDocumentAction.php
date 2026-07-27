<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class RejectDocumentAction
{
    public function handle(Document $document, User $actor, string $reason): Document
    {
        Gate::forUser($actor)->authorize('reject', $document);

        $validatedReason = Validator::make(
            ['reason' => $reason],
            ['reason' => ['required', 'string', 'max:2000']],
        )->validate();
        $reason = $validatedReason['reason'];

        return DB::transaction(function () use ($actor, $document, $reason): Document {
            $lockedDocument = Document::query()
                ->whereKey($document)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDocument->status === DocumentStatus::Rejected) {
                return $lockedDocument;
            }

            $lockedDocument->update([
                'status' => DocumentStatus::Rejected,
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            activity('documents')
                ->causedBy($actor)
                ->performedOn($lockedDocument)
                ->event('rejected')
                ->withProperties([
                    'company_id' => $lockedDocument->company_id,
                    'reason' => $reason,
                ])
                ->log('rejected the document');

            return $lockedDocument;
        });
    }
}
