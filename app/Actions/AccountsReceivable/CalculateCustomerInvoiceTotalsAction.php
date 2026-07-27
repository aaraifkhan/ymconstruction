<?php

namespace App\Actions\AccountsReceivable;

use App\Enums\CustomerInvoiceAdjustmentType;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\TaxCalculationMethod;
use App\Enums\TaxCodeType;
use App\Models\CustomerInvoice;
use App\Models\TaxCode;
use Illuminate\Validation\ValidationException;

class CalculateCustomerInvoiceTotalsAction
{
    public function handle(CustomerInvoice $invoice): CustomerInvoice
    {
        $subtotal = '0.0000';
        $taxTotal = '0.0000';
        foreach ($invoice->lines()->with(['item', 'unitOfMeasure'])->get() as $line) {
            $taxCode = $this->effectiveTaxCode($invoice, $line->tax_code_id, TaxCodeType::SalesTax);
            $extended = bcmul((string) $line->quantity, (string) $line->unit_rate, 4);
            if ($taxCode?->calculation_method === TaxCalculationMethod::Inclusive) {
                $tax = bcdiv(
                    bcmul($extended, (string) $taxCode->rate, 4),
                    bcadd('100.0000', (string) $taxCode->rate, 4),
                    4,
                );
                $lineSubtotal = bcsub($extended, $tax, 4);
                $lineTotal = $extended;
            } else {
                $lineSubtotal = $extended;
                $tax = bcdiv(bcmul($lineSubtotal, (string) ($taxCode?->rate ?? 0), 4), '100.0000', 4);
                $lineTotal = bcadd($lineSubtotal, $tax, 4);
            }
            $line->update([
                'tax_rate_snapshot' => $taxCode?->rate ?? '0.0000',
                'tax_method_snapshot' => $taxCode?->calculation_method->value,
                'line_subtotal' => $lineSubtotal,
                'tax_amount' => $tax,
                'line_total' => $lineTotal,
            ]);
            $subtotal = bcadd($subtotal, $lineSubtotal, 4);
            $taxTotal = bcadd($taxTotal, $tax, 4);
        }

        $adjustments = [
            CustomerInvoiceAdjustmentType::Retention->value => '0.0000',
            CustomerInvoiceAdjustmentType::WithholdingTax->value => '0.0000',
            CustomerInvoiceAdjustmentType::MobilizationRecovery->value => '0.0000',
        ];
        foreach ($invoice->adjustments()->with('taxCode')->get() as $adjustment) {
            if ($adjustment->type === CustomerInvoiceAdjustmentType::WithholdingTax) {
                $taxCode = $this->effectiveTaxCode($invoice, $adjustment->tax_code_id, TaxCodeType::WithholdingTax);
                $adjustment->update([
                    'amount' => bcdiv(bcmul($subtotal, (string) $taxCode->rate, 4), '100.0000', 4),
                ]);
            }
            $adjustments[$adjustment->type->value] = (string) $adjustment->fresh()->amount;
        }

        if ($invoice->category === CustomerInvoiceCategory::RunningBill) {
            if (bccomp(bcadd((string) $invoice->work_value, (string) $invoice->variation_amount, 4), $subtotal, 4) !== 0) {
                throw ValidationException::withMessages(['work_value' => 'Running Bill work plus variation must equal the line subtotal.']);
            }
            if ($invoice->type === CustomerInvoiceType::Invoice) {
                $previousCertified = (string) CustomerInvoice::query()
                    ->where('company_id', $invoice->company_id)->where('project_id', $invoice->project_id)
                    ->where('type', CustomerInvoiceType::Invoice)->where('category', CustomerInvoiceCategory::RunningBill)
                    ->where('status', CustomerInvoiceStatus::Posted)->whereKeyNot($invoice)->sum('work_value');
                $contractValue = (string) $invoice->project()->value('contract_value');
                if (bccomp(bcadd($previousCertified, (string) $invoice->work_value, 4), $contractValue, 4) === 1) {
                    throw ValidationException::withMessages(['work_value' => 'Certified contract work cannot exceed the Project contract value; use the explicit variation amount separately.']);
                }
                $invoice->contract_value_snapshot = $contractValue;
                $invoice->previous_certified_amount = $previousCertified;
            } else {
                $invoice->contract_value_snapshot = $invoice->originalCustomerInvoice?->contract_value_snapshot ?? '0.0000';
                $invoice->previous_certified_amount = $invoice->originalCustomerInvoice?->previous_certified_amount ?? '0.0000';
            }
        } elseif ($invoice->adjustments()->exists()) {
            throw ValidationException::withMessages(['adjustments' => 'Retention, WHT, and mobilization recovery are supported on Running Bills.']);
        }

        $grossTotal = bcadd($subtotal, $taxTotal, 4);
        $deductions = bcadd(
            bcadd($adjustments[CustomerInvoiceAdjustmentType::Retention->value], $adjustments[CustomerInvoiceAdjustmentType::WithholdingTax->value], 4),
            $adjustments[CustomerInvoiceAdjustmentType::MobilizationRecovery->value],
            4,
        );
        $receivable = bcsub($grossTotal, $deductions, 4);
        if (bccomp($receivable, '0', 4) === -1) {
            throw ValidationException::withMessages(['adjustments' => 'Retention, WHT, and mobilization recovery cannot exceed the gross bill.']);
        }
        $invoice->update([
            'contract_value_snapshot' => $invoice->contract_value_snapshot,
            'previous_certified_amount' => $invoice->previous_certified_amount,
            'subtotal' => $subtotal, 'tax_total' => $taxTotal, 'gross_total' => $grossTotal,
            'retention_amount' => $adjustments[CustomerInvoiceAdjustmentType::Retention->value],
            'wht_amount' => $adjustments[CustomerInvoiceAdjustmentType::WithholdingTax->value],
            'mobilization_recovery_amount' => $adjustments[CustomerInvoiceAdjustmentType::MobilizationRecovery->value],
            'receivable_amount' => $receivable,
        ]);

        return $invoice->refresh();
    }

    private function effectiveTaxCode(CustomerInvoice $invoice, ?int $taxCodeId, TaxCodeType $type): ?TaxCode
    {
        if ($taxCodeId === null) {
            return null;
        }
        $taxCode = TaxCode::query()->whereKey($taxCodeId)->where('company_id', $invoice->company_id)
            ->where('type', $type)->activeOn($invoice->invoice_date->toDateString())->first();
        if ($taxCode === null) {
            throw ValidationException::withMessages(['tax_code_id' => 'Tax requires an effective active same-company Tax Code of the correct type.']);
        }

        return $taxCode;
    }
}
