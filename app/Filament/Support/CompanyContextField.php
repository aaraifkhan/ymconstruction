<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class CompanyContextField
{
    public static function make(string $name = 'company_id'): Select
    {
        return Select::make($name)
            ->label('Company')
            ->relationship(
                name: 'company',
                titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query): Builder => $query->whereKey(Filament::getTenant()?->getKey()),
            )
            ->default(fn (): ?int => Filament::getTenant()?->getKey())
            ->formatStateUsing(fn ($state): ?int => $state ?? Filament::getTenant()?->getKey())
            ->disabled()
            ->dehydrated()
            ->required();
    }
}
