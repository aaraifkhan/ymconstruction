<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingProfile;
use App\Models\Account;
use App\Models\AccountTemplate;
use App\Models\Company;

class PreviewCompanyAccountProvisioningAction
{
    /** @return array{create:int, exists:int, conflicts:array<int, string>} */
    public function handle(Company $company, AccountingProfile $profile): array
    {
        $create = 0;
        $exists = 0;
        $conflicts = [];

        foreach (AccountTemplate::query()->orderBy('sort_order')->get() as $template) {
            $account = Account::withTrashed()->whereBelongsTo($company)->where('code', $template->code)->first();
            if ($account === null) {
                $create++;
            } elseif ($account->account_template_id !== null && (int) $account->account_template_id !== (int) $template->getKey()) {
                $conflicts[] = "Code {$template->code} is linked to another template.";
            } else {
                $exists++;
            }
        }

        return compact('create', 'exists', 'conflicts');
    }
}
