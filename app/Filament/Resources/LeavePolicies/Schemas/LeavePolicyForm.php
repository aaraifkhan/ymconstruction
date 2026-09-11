<?php

namespace App\Filament\Resources\LeavePolicies\Schemas;

use App\Filament\Support\CompanyContextField;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class LeavePolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Leave Policy Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('leave_type_id')
                            ->relationship('leaveType', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->required(),
                        TextInput::make('name')
                            ->required(),
                        DatePicker::make('effective_from')
                            ->required(),
                        DatePicker::make('effective_to'),
                        TextInput::make('annual_units')
                            ->numeric(),
                        TextInput::make('maximum_carry_forward_units')
                            ->numeric(),
                        Toggle::make('allow_negative_balance')
                            ->required(),
                        Toggle::make('allow_encashment')
                            ->required(),
                        Toggle::make('is_active')
                            ->required(),
                    ]),
            ]);
    }
}
