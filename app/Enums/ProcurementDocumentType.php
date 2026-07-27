<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProcurementDocumentType: string implements HasLabel
{
    case PurchaseRequisition = 'purchase_requisition';
    case PurchaseOrder = 'purchase_order';
    case GoodsReceipt = 'goods_receipt';
    case InventoryTransaction = 'inventory_transaction';
    case VendorBill = 'vendor_bill';
    case VendorCreditNote = 'vendor_credit_note';

    public function prefix(): string
    {
        return match ($this) {
            self::PurchaseRequisition => 'PR',
            self::PurchaseOrder => 'PO',
            self::GoodsReceipt => 'GRN',
            self::InventoryTransaction => 'INV',
            self::VendorBill => 'VB',
            self::VendorCreditNote => 'VCN',
        };
    }

    public function approvalPermission(): string
    {
        return match ($this) {
            self::PurchaseRequisition => 'Approve:PurchaseRequisition',
            self::PurchaseOrder => 'Approve:PurchaseOrder',
            self::GoodsReceipt => 'Handover:GoodsReceipt',
            self::InventoryTransaction => 'Post:InventoryTransaction',
            self::VendorBill, self::VendorCreditNote => 'Approve:VendorBill',
        };
    }

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
