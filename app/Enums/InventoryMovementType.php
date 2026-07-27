<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InventoryMovementType: string implements HasLabel
{
    case GoodsReceipt = 'goods_receipt';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case ProjectIssue = 'project_issue';
    case ProjectReturn = 'project_return';
    case VendorReturn = 'vendor_return';
    case AdjustmentIncrease = 'adjustment_increase';
    case AdjustmentDecrease = 'adjustment_decrease';
    case TradingSale = 'trading_sale';
    case TradingSaleReturn = 'trading_sale_return';
    case TradingSaleReversal = 'trading_sale_reversal';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
