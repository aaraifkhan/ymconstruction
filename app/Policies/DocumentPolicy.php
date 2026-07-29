<?php

namespace App\Policies;

use App\Enums\DocumentClassification;
use App\Models\Document;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Document')
            && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'View:Document')
            && $user->canAccessTenant($document->company)
            && $this->canViewClassification($user, $document)
            && $this->canViewHrDocumentType($user, $document);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Document')
            && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'Update:Document')
            && $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'Delete:Document')
            && $this->view($user, $document);
    }

    public function restore(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'Restore:Document')
            && $this->view($user, $document);
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:Document')
            && $this->canAccessCurrentCompany($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:Document')
            && $this->canAccessCurrentCompany($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function viewSensitive(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'ViewSensitive:Document')
            && $user->canAccessTenant($document->company);
    }

    public function download(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'Download:Document')
            && $this->view($user, $document);
    }

    public function preview(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'Preview:Document')
            && $this->view($user, $document);
    }

    public function uploadVersion(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'UploadVersion:Document')
            && $this->view($user, $document);
    }

    public function verify(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'Verify:Document')
            && $this->view($user, $document);
    }

    public function approve(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'Approve:Document')
            && $this->view($user, $document);
    }

    public function reject(User $user, Document $document): bool
    {
        return $this->hasPermission($user, 'Reject:Document')
            && $this->view($user, $document);
    }

    private function canViewClassification(User $user, Document $document): bool
    {
        if ($document->classification === DocumentClassification::Internal) {
            return true;
        }

        return $this->hasPermission($user, 'ViewSensitive:Document');
    }

    private function canViewHrDocumentType(User $user, Document $document): bool
    {
        $typeCode = $document->hrDocumentType?->code;

        if ($typeCode?->requiresIdentityPermission()) {
            return $this->hasPermission($user, 'ViewIdentity:EmployeeDocument');
        }

        if ($typeCode?->requiresMedicalPermission()) {
            return $this->hasPermission($user, 'ViewMedical:EmployeeDocument');
        }

        return true;
    }

    private function canAccessCurrentCompany(User $user): bool
    {
        $company = Filament::getTenant();

        return $company !== null && $user->canAccessTenant($company);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
