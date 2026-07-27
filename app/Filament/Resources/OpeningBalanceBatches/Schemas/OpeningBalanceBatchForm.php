<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OpeningBalanceBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Opening balance source')->columns(2)->schema([
                Select::make('financial_period_id')->relationship('financialPeriod', 'name')->required()->searchable()->preload(),
                DatePicker::make('opening_date')->required(),
                TextInput::make('source_name')->maxLength(255),
                Textarea::make('notes')->columnSpanFull(),
            ]),
            Section::make('Trial balance lines')->schema([
                Repeater::make('lines')->relationship()->orderColumn('line_number')->minItems(2)->defaultItems(2)
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'company_id' => Filament::getTenant()->getKey()])
                    ->schema([
                        Select::make('account_id')->relationship('account', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true)->whereDoesntHave('children'))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} — {$record->name}")->searchable(['code', 'name'])->preload()->required()->columnSpan(2),
                        TextInput::make('debit')->numeric()->default(0)->minValue(0),
                        TextInput::make('credit')->numeric()->default(0)->minValue(0),
                        Select::make('party_id')->relationship('party', 'name')->searchable()->preload(),
                        Select::make('project_id')->relationship('project', 'name')->searchable()->preload(),
                        Select::make('cost_center_id')->relationship('costCenter', 'name')->searchable()->preload(),
                        TextInput::make('description'),
                    ])->columns(4)->columnSpanFull(),
            ]),
        ]);
    }
}
