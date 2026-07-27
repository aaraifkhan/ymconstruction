<?php

namespace App\Actions\AccountsReceivable;

use App\Enums\CustomerInvoiceType;
use App\Enums\SalesDocumentType;
use App\Models\CustomerInvoice;
use App\Models\SalesSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveCustomerInvoiceNumberAction
{
    public function handle(CustomerInvoice $invoice): string
    {
        $documentType = $invoice->type === CustomerInvoiceType::CreditNote
            ? SalesDocumentType::CustomerCreditNote
            : SalesDocumentType::from($invoice->category->value);

        return DB::transaction(function () use ($documentType, $invoice): string {
            $sequence = SalesSequence::query()->firstOrCreate(
                [
                    'company_id' => $invoice->company_id,
                    'document_type' => $documentType,
                    'calendar_year' => $invoice->invoice_date->year,
                ],
                ['prefix' => $documentType->prefix()],
            );
            $sequence = SalesSequence::query()->whereKey($sequence)->lockForUpdate()->firstOrFail();
            if (! $sequence->is_active) {
                throw ValidationException::withMessages(['invoice_number' => 'The Sales document sequence is inactive.']);
            }
            $number = sprintf(
                '%s-%d-%s',
                $sequence->prefix,
                $sequence->calendar_year,
                str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT),
            );
            $sequence->increment('next_number');

            return $number;
        });
    }
}
