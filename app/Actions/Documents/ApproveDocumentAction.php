<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveDocumentAction
{
    public function handle(Document $document, User $actor): Document
    {
        Gate::forUser($actor)->authorize('approve', $document);

        return DB::transaction(function () use ($actor, $document): Document {
            $lockedDocument = Document::query()
                ->with(['category', 'currentVersion'])
                ->whereKey($document)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDocument->currentVersion === null) {
                throw ValidationException::withMessages([
                    'document' => 'A document file is required before approval.',
                ]);
            }

            if ($lockedDocument->status === DocumentStatus::Approved) {
                return $lockedDocument;
            }

            if ($lockedDocument->category->requires_verification && $lockedDocument->verified_at === null) {
                throw ValidationException::withMessages([
                    'document' => 'This document category requires verification before approval.',
                ]);
            }

            $lockedDocument->update([
                'status' => DocumentStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('documents')
                ->causedBy($actor)
                ->performedOn($lockedDocument)
                ->event('approved')
                ->withProperties(['company_id' => $lockedDocument->company_id])
                ->log('approved the document');

            return $lockedDocument;
        });
    }
}
