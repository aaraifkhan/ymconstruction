<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class CompanyContextField
{
    public static function make(string $name = 'company_id', bool $allowCrossCompany = false): Select
    {
        $user = Filament::auth()->user();
        $canCrossCompany = $allowCrossCompany && $user !== null && ($user->hasRole('super_admin') || $user->can('CrossCompany:Accounts'));

        return Select::make($name)
            ->label('Company')
            ->relationship(
                name: 'company',
                titleAttribute: 'name',
                modifyQueryUsing: function (Builder $query) use ($canCrossCompany, $user): Builder {
                    if ($canCrossCompany) {
                        if ($user?->hasRole('super_admin')) {
                            return $query->where('is_active', true);
                        }

                        $companyIds = $user?->companies()->wherePivot('is_active', true)->pluck('companies.id')->all() ?? [];

                        return $query->whereIn('id', $companyIds);
                    }

                    return $query->whereKey(Filament::getTenant()?->getKey());
                },
            )
            ->default(fn (): ?int => Filament::getTenant()?->getKey())
            ->formatStateUsing(fn ($state): ?int => $state ?? Filament::getTenant()?->getKey())
            ->disabled(fn () => ! $canCrossCompany)
            ->dehydrated()
            ->required();
    }
}
