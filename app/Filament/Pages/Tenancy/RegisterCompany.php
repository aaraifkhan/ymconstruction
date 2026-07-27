<?php

namespace App\Filament\Pages\Tenancy;

use App\Actions\Documents\ProvisionDefaultDocumentCategoriesAction;
use App\Models\Company;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RegisterCompany extends RegisterTenant
{
    private ProvisionDefaultDocumentCategoriesAction $provisionDocumentCategories;

    public function boot(
        ProvisionDefaultDocumentCategoriesAction $provisionDocumentCategories,
    ): void {
        $this->provisionDocumentCategories = $provisionDocumentCategories;
    }

    public static function getLabel(): string
    {
        return 'Create company';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('First company')
                    ->description('Create a company and grant your user access to it.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Legal name')
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('URL key')
                            ->helperText('Use a permanent short key, for example: bunyan-construction.')
                            ->required()
                            ->alphaDash()
                            ->unique()
                            ->maxLength(100),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Company
    {
        $company = Company::query()->create($data);
        $company->members()->attach(auth()->id(), [
            'is_active' => true,
            'can_access_descendants' => true,
        ]);
        $this->provisionDocumentCategories->handle($company);

        activity('company_members')
            ->performedOn($company)
            ->causedBy(auth()->user())
            ->withProperties(['user_id' => auth()->id()])
            ->event('member_attached')
            ->log('Company creator granted company access');

        return $company;
    }
}
