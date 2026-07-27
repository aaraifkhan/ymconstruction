<?php

namespace App\Policies;

use App\Enums\CustomerInvoiceStatus;
use App\Models\CustomerInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CustomerInvoicePolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'CustomerInvoice';

    public function update(User $user, Model $invoice): bool
    {
        return parent::update($user, $invoice) && $invoice->isEditable();
    }

    public function delete(User $user, Model $invoice): bool
    {
        return parent::delete($user, $invoice) && $invoice->isEditable();
    }

    public function submit(User $user, CustomerInvoice $invoice): bool
    {
        return $this->canPerform($user, $invoice, 'Submit:CustomerInvoice')
            && $invoice->isEditable();
    }

    public function approve(User $user, CustomerInvoice $invoice): bool
    {
        return $this->canPerform($user, $invoice, 'Approve:CustomerInvoice')
            && $invoice->status === CustomerInvoiceStatus::Submitted;
    }

    public function reject(User $user, CustomerInvoice $invoice): bool
    {
        return $this->canPerform($user, $invoice, 'Reject:CustomerInvoice')
            && $invoice->status === CustomerInvoiceStatus::Submitted;
    }

    public function post(User $user, CustomerInvoice $invoice): bool
    {
        return $this->canPerform($user, $invoice, 'Post:CustomerInvoice')
            && $invoice->status === CustomerInvoiceStatus::Approved;
    }

    public function reverse(User $user, CustomerInvoice $invoice): bool
    {
        return $this->canPerform($user, $invoice, 'Reverse:CustomerInvoice')
            && $invoice->status === CustomerInvoiceStatus::Posted;
    }

    private function canPerform(User $user, CustomerInvoice $invoice, string $permission): bool
    {
        return $this->hasPermission($user, $permission) && $this->canAccessRecord($user, $invoice);
    }
}
