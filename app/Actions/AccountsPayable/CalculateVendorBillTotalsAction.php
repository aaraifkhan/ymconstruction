<?php

namespace App\Actions\AccountsPayable;

use App\Enums\TaxCalculationMethod;
use App\Enums\TaxCodeType;
use App\Enums\VendorBillDeductionType;
use App\Models\TaxCode;
use App\Models\VendorBill;
use Illuminate\Validation\ValidationException;

class CalculateVendorBillTotalsAction
{
    public function handle(VendorBill $bill): VendorBill
    {
        $subtotal = '0.0000';
        $taxTotal = '0.0000';

        foreach ($bill->lines()->with(['purchaseOrderLine', 'item', 'unitOfMeasure'])->get() as $line) {
            $orderLine = $line->purchaseOrderLine;
            $taxCode = $this->effectiveTaxCode($bill, $line->tax_code_id);
            $extendedAmount = bcmul((string) $line->quantity, (string) $line->unit_rate, 4);
            $taxAmount = '0.0000';

            if ($taxCode?->calculation_method === TaxCalculationMethod::Inclusive) {
                $taxAmount = bcdiv(
                    bcmul($extendedAmount, (string) $taxCode->rate, 4),
                    bcadd('100.0000', (string) $taxCode->rate, 4),
                    4,
                );
                $lineSubtotal = bcsub($extendedAmount, $taxAmount, 4);
                $lineTotal = $extendedAmount;
            } else {
                $lineSubtotal = $extendedAmount;
                $taxAmount = bcdiv(
                    bcmul($lineSubtotal, (string) ($taxCode?->rate ?? '0.0000'), 4),
                    '100.0000',
                    4,
                );
                $lineTotal = bcadd($lineSubtotal, $taxAmount, 4);
            }

            $line->update([
                'item_code_snapshot' => $orderLine?->item_code_snapshot ?? $line->item?->code,
                'item_name_snapshot' => $orderLine?->item_name_snapshot ?? $line->item?->name ?? $line->item_name_snapshot,
                'uom_snapshot' => $orderLine?->uom_snapshot ?? $line->unitOfMeasure?->symbol,
                'tax_code_snapshot' => $taxCode?->code,
                'tax_rate_snapshot' => $taxCode?->rate ?? '0.0000',
                'tax_calculation_method_snapshot' => $taxCode?->calculation_method->value,
                'line_subtotal' => $lineSubtotal,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
            ]);

            $subtotal = bcadd($subtotal, $lineSubtotal, 4);
            $taxTotal = bcadd($taxTotal, $taxAmount, 4);
        }

        $deductionTotal = '0.0000';
        foreach ($bill->deductions()->with('taxCode')->get() as $deduction) {
            if ($deduction->type === VendorBillDeductionType::WithholdingTax) {
                $taxCode = $this->effectiveTaxCode($bill, $deduction->tax_code_id, TaxCodeType::WithholdingTax);
                $expected = bcdiv(bcmul($subtotal, (string) $taxCode->rate, 4), '100.0000', 4);
                $deduction->update([
                    'rate_snapshot' => $taxCode->rate,
                    'amount' => $expected,
                ]);
            }
            $deductionTotal = bcadd($deductionTotal, (string) $deduction->fresh()->amount, 4);
        }

        $grossTotal = bcadd($subtotal, $taxTotal, 4);
        $netPayable = bcsub($grossTotal, $deductionTotal, 4);
        if (bccomp($netPayable, '0', 4) === -1) {
            throw ValidationException::withMessages(['deductions' => 'Deductions cannot exceed the Vendor Bill gross total.']);
        }

        $bill->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'gross_total' => $grossTotal,
            'deduction_total' => $deductionTotal,
            'net_payable' => $netPayable,
        ]);

        return $bill->refresh();
    }

    private function effectiveTaxCode(
        VendorBill $bill,
        ?int $taxCodeId,
        TaxCodeType $requiredType = TaxCodeType::SalesTax,
    ): ?TaxCode {
        if ($taxCodeId === null) {
            return null;
        }

        $taxCode = TaxCode::query()
            ->whereKey($taxCodeId)
            ->where('company_id', $bill->company_id)
            ->where('type', $requiredType)
            ->activeOn($bill->invoice_date->toDateString())
            ->first();

        if ($taxCode === null || ($requiredType === TaxCodeType::SalesTax && ! $taxCode->is_recoverable)) {
            throw ValidationException::withMessages([
                'tax_code_id' => 'Tax requires an effective active same-company recoverable tax code of the correct type.',
            ]);
        }

        return $taxCode;
    }
}
