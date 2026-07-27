<?php

namespace Database\Seeders;

use App\Actions\JoiningLetters\ProvisionDefaultJoiningLetterTemplateAction;
use App\Models\Company;
use Illuminate\Database\Seeder;

class JoiningLetterTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProvisionDefaultJoiningLetterTemplateAction $provisionTemplate): void
    {
        Company::query()
            ->active()
            ->each(fn (Company $company) => $provisionTemplate->handle($company));
    }
}
