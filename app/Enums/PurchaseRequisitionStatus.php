<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PurchaseRequisitionStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case PartiallyOrdered = 'partially_ordered';
    case Ordered = 'ordered';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
