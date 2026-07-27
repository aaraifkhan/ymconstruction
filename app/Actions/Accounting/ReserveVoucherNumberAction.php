<?php

namespace App\Actions\Accounting;

use App\Models\VoucherSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveVoucherNumberAction
{
    public function handle(VoucherSequence $sequence): string
    {
        return DB::transaction(function () use ($sequence): string {
            $sequence = VoucherSequence::query()->with('financialYear')->lockForUpdate()->findOrFail($sequence->getKey());
            if (! $sequence->is_active) {
                throw ValidationException::withMessages(['sequence' => 'Voucher sequence is inactive.']);
            }
            $number = "{$sequence->prefix}-{$sequence->financialYear->starts_on->year}-".str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
            $sequence->increment('next_number');

            return $number;
        });
    }
}
