<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Actions\Documents\ProvisionDefaultDocumentCategoriesAction;
use App\Actions\JoiningLetters\ProvisionDefaultJoiningLetterTemplateAction;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    private ProvisionDefaultDocumentCategoriesAction $provisionDocumentCategories;

    private ProvisionDefaultJoiningLetterTemplateAction $provisionJoiningLetterTemplate;

    public function boot(
        ProvisionDefaultDocumentCategoriesAction $provisionDocumentCategories,
        ProvisionDefaultJoiningLetterTemplateAction $provisionJoiningLetterTemplate,
    ): void {
        $this->provisionDocumentCategories = $provisionDocumentCategories;
        $this->provisionJoiningLetterTemplate = $provisionJoiningLetterTemplate;
    }

    protected function afterCreate(): void
    {
        if ($this->record instanceof Company) {
            $this->provisionDocumentCategories->handle($this->record);
            $this->provisionJoiningLetterTemplate->handle($this->record);

            $user = auth()->user();

            if ($user instanceof User) {
                $this->record->members()->syncWithoutDetaching([
                    $user->getKey() => [
                        'is_active' => true,
                        'can_access_descendants' => true,
                    ],
                ]);

                activity('company_members')
                    ->performedOn($this->record)
                    ->causedBy($user)
                    ->withProperties(['user_id' => $user->getKey()])
                    ->event('member_attached')
                    ->log('Company creator granted company access');
            }
        }
    }
}
