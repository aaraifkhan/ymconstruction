<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InventoryTransactionType: string implements HasLabel
{
    case Transfer = 'transfer';
    case ProjectIssue = 'project_issue';
    case ProjectReturn = 'project_return';
    case VendorReturn = 'vendor_return';
    case AdjustmentIncrease = 'adjustment_increase';
    case AdjustmentDecrease = 'adjustment_decrease';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function isInbound(): bool
    {
        return in_array($this, [self::ProjectReturn, self::AdjustmentIncrease], true);
    }

    public function isOutbound(): bool
    {
        return in_array($this, [self::ProjectIssue, self::VendorReturn, self::AdjustmentDecrease], true);
    }
}
