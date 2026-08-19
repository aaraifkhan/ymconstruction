<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OpeningBalanceBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Opening balance source')->columns(2)->schema([
                Select::make('financial_period_id')->relationship('financialPeriod', 'name')->required()->searchable()->preload(),
                DatePicker::make('opening_date')->required()->default(today()),
                TextInput::make('source_name')->maxLength(255)->placeholder('e.g. Audited TB as on 30-Jun-2026'),
                Textarea::make('notes')->columnSpanFull(),
            ]),
            Section::make('Trial balance lines')->schema([
                Repeater::make('lines')->relationship()->orderColumn('line_number')->minItems(2)->defaultItems(2)
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'company_id' => Filament::getTenant()->getKey()])
                    ->schema([
                        Select::make('account_id')->relationship('account', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true)->whereDoesntHave('children'))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} — {$record->name}")->searchable(['code', 'name'])->preload()->required()->columnSpan(2),
                        TextInput::make('debit')->numeric()->default(0)->minValue(0)->prefix('PKR')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ((float) $state > 0) {
                                    $set('credit', 0);
                                }
                            }),
                        TextInput::make('credit')->numeric()->default(0)->minValue(0)->prefix('PKR')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ((float) $state > 0) {
                                    $set('debit', 0);
                                }
                            }),
                        Select::make('party_id')->relationship('party', 'name')->searchable()->preload(),
                        Select::make('project_id')->relationship('project', 'name')->searchable()->preload(),
                        Select::make('cost_center_id')->relationship('costCenter', 'name')->searchable()->preload(),
                        TextInput::make('description'),
                    ])->columns(4)->columnSpanFull(),

                Placeholder::make('balance_summary')
                    ->label('')
                    ->columnSpanFull()
                    ->content(function (Get $get): HtmlString {
                        $lines = $get('lines') ?? [];
                        $debitTotal = '0.0000';
                        $creditTotal = '0.0000';

                        foreach ($lines as $line) {
                            $debit = (string) ($line['debit'] ?? 0);
                            $credit = (string) ($line['credit'] ?? 0);
                            $debitTotal = bcadd($debitTotal, is_numeric($debit) && (float) $debit > 0 ? $debit : '0', 4);
                            $creditTotal = bcadd($creditTotal, is_numeric($credit) && (float) $credit > 0 ? $credit : '0', 4);
                        }

                        $diff = bcsub($debitTotal, $creditTotal, 4);
                        $isBalanced = bccomp($debitTotal, '0.0000', 4) > 0 && bccomp($diff, '0.0000', 4) === 0;

                        $debitFormatted = number_format((float) $debitTotal, 2);
                        $creditFormatted = number_format((float) $creditTotal, 2);
                        $diffFormatted = number_format(abs((float) $diff), 2);

                        if ($isBalanced) {
                            return new HtmlString("
                                <div style=\"display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-radius:8px; background-color:#ecfdf5; border:1px solid #10b981; color:#065f46; font-size:14px; margin-top:8px;\">
                                    <div><strong>Total Opening Debit:</strong> PKR {$debitFormatted}</div>
                                    <div><strong>Total Opening Credit:</strong> PKR {$creditFormatted}</div>
                                    <div style=\"font-weight:bold; color:#047857;\">✓ Balanced (PKR {$debitFormatted})</div>
                                </div>
                            ");
                        }

                        $warningText = bccomp($debitTotal, '0.0000', 4) === 0 && bccomp($creditTotal, '0.0000', 4) === 0
                            ? 'Enter opening debit and credit balances'
                            : "Difference: PKR {$diffFormatted} (Out of Balance)";

                        return new HtmlString("
                            <div style=\"display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-radius:8px; background-color:#fff1f2; border:1px solid #f43f5e; color:#9f1239; font-size:14px; margin-top:8px;\">
                                <div><strong>Total Opening Debit:</strong> PKR {$debitFormatted}</div>
                                <div><strong>Total Opening Credit:</strong> PKR {$creditFormatted}</div>
                                <div style=\"font-weight:bold; color:#e11d48;\">⚠ {$warningText}</div>
                            </div>
                        ");
                    }),
            ]),
        ]);
    }
}
