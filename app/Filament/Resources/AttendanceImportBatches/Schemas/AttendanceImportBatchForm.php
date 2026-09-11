<?php

namespace App\Filament\Resources\AttendanceImportBatches\Schemas;

use App\Enums\AttendanceImportBatchStatus;
use App\Enums\AttendanceImportSource;
use App\Filament\Support\CompanyContextField;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AttendanceImportBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Attendance Import Batch Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('attendance_device_id')
                            ->label('Attendance Device')
                            ->relationship('attendanceDevice', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant())),
                        Select::make('source')
                            ->options(AttendanceImportSource::class)
                            ->required(),
                        Select::make('status')
                            ->options(AttendanceImportBatchStatus::class)
                            ->default('pending')
                            ->required(),
                        TextInput::make('original_filename')->label('Original File Name'),
                        TextInput::make('stored_file_path')->label('Storage Path'),
                        TextInput::make('batch_checksum')->label('Batch Checksum')->required(),
                        TextInput::make('row_count')->label('Total Rows')->required()->numeric()->default(0),
                        TextInput::make('accepted_count')->label('Accepted Rows')->required()->numeric()->default(0),
                        TextInput::make('duplicate_count')->label('Duplicate Rows')->required()->numeric()->default(0),
                        TextInput::make('quarantined_count')->label('Quarantined Rows')->required()->numeric()->default(0),
                        TextInput::make('error_count')->label('Error Count')->required()->numeric()->default(0),
                        Select::make('initiated_by_id')->label('Initiated By')->relationship('initiatedBy', 'name'),
                        DateTimePicker::make('started_at')->label('Started At'),
                        DateTimePicker::make('completed_at')->label('Completed At'),
                        Textarea::make('cursor_before')->rows(2)->columnSpanFull(),
                        Textarea::make('cursor_after')->rows(2)->columnSpanFull(),
                        Textarea::make('source_metadata')->rows(2)->columnSpanFull(),
                        Textarea::make('failure_summary')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
