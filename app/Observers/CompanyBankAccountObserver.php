<?php

namespace App\Observers;

use App\Actions\Accounting\SyncCompanyBankAccountGlAction;
use App\Models\CompanyBankAccount;

class CompanyBankAccountObserver
{
    public function __construct(private SyncCompanyBankAccountGlAction $sync) {}

    public function saved(CompanyBankAccount $bankAccount): void
    {
        $this->sync->handle($bankAccount);
    }

    public function deleted(CompanyBankAccount $bankAccount): void
    {
        $this->sync->handle($bankAccount);
    }

    public function restored(CompanyBankAccount $bankAccount): void
    {
        $this->sync->handle($bankAccount);
    }
}
