<?php

namespace App\Filament\Resources\AssetDisposals\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetDisposalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asset disposal')->schema([
                TextEntry::make('fixedAsset.asset_number')->label('Asset'),
                TextEntry::make('fixedAsset.name')->label('Asset name'),
                TextEntry::make('status')->badge(),
                TextEntry::make('disposal_date')->date(),
                TextEntry::make('reason')->columnSpanFull(),
                TextEntry::make('proceeds_amount')->money('PKR'),
                TextEntry::make('carrying_amount')->money('PKR'),
                TextEntry::make('gain_amount')->money('PKR'),
                TextEntry::make('loss_amount')->money('PKR'),
                TextEntry::make('journalEntry.voucher_number')->label('Journal')->placeholder('—'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
