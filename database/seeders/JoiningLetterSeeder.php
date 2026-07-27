<?php

namespace Database\Seeders;

use App\Models\Employment;
use App\Models\JoiningLetter;
use App\Models\JoiningLetterTemplate;
use Illuminate\Database\Seeder;

class JoiningLetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employment::query()->each(function (Employment $employment): void {
            $template = JoiningLetterTemplate::query()
                ->whereBelongsTo($employment->company)
                ->where('is_default', true)
                ->first();

            if ($template === null) {
                return;
            }

            JoiningLetter::factory()->create([
                'company_id' => $employment->company_id,
                'employment_id' => $employment->getKey(),
                'joining_letter_template_id' => $template->getKey(),
            ]);
        });
    }
}
