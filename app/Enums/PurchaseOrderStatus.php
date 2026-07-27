<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
