<?php

namespace App\Filament\Resources\JoiningLetters\Pages;

use App\Actions\JoiningLetters\RenderJoiningLetterAction;
use App\Filament\Resources\JoiningLetters\JoiningLetterResource;
use App\Models\Company;
use App\Models\JoiningLetter;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;

class CreateJoiningLetter extends CreateRecord
{
    protected static string $resource = JoiningLetterResource::class;

    private RenderJoiningLetterAction $renderLetter;

    public function boot(RenderJoiningLetterAction $renderLetter): void
    {
        $this->renderLetter = $renderLetter;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $company = Filament::getTenant();
        $actor = auth()->user();

        if (! $company instanceof Company || ! $actor instanceof User) {
            throw new LogicException('A company and authenticated user are required.');
        }

        if (! $actor->can('ManageCompensation:JoiningLetter')) {
            $data = Arr::except($data, ['compensation_amount', 'currency_code']);
        }

        return DB::transaction(function () use ($actor, $company, $data): JoiningLetter {
            $letter = JoiningLetter::query()->create([
                ...$data,
                'company_id' => $company->getKey(),
                'subject' => 'Pending template rendering',
                'body' => 'Pending template rendering',
                'created_by_id' => $actor->getKey(),
            ]);

            return $this->renderLetter->handle($letter, $actor, authorize: false);
        });
    }
}
