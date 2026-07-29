<?php

namespace App\Policies;

use App\Models\HrDocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrDocumentTypePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'HrDocumentType';

    public function delete(User $user, Model $record): bool
    {
        return parent::delete($user, $record)
            && $record instanceof HrDocumentType
            && ! $record->documents()->exists();
    }
}
