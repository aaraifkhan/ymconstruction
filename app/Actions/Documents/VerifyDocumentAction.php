<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VerifyDocumentAction
{
    public function handle(Document $document, User $actor): Document
    {
        Gate::forUser($actor)->authorize('verify', $document);

        return DB::transaction(function () use ($actor, $document): Document {
            $lockedDocument = Document::query()
                ->with('currentVersion')
                ->whereKey($document)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDocument->currentVersion === null) {
                throw ValidationException::withMessages([
                    'document' => 'A document file is required before verification.',
                ]);
            }

            if ($lockedDocument->status === DocumentStatus::Approved) {
                throw ValidationException::withMessages([
                    'document' => 'An approved document cannot be verified again.',
                ]);
            }

            if ($lockedDocument->status === DocumentStatus::Verified) {
                return $lockedDocument;
            }

            $lockedDocument->update([
                'status' => DocumentStatus::Verified,
                'verified_by_id' => $actor->getKey(),
                'verified_at' => now(),
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('documents')
                ->causedBy($actor)
                ->performedOn($lockedDocument)
                ->event('verified')
                ->withProperties(['company_id' => $lockedDocument->company_id])
                ->log('verified the document');

            return $lockedDocument;
        });
    }
}
