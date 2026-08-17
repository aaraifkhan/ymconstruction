<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Tasks'),
            'in_progress' => Tab::make('In Progress')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TaskStatus::InProgress)),
            'submitted' => Tab::make('Awaiting Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TaskStatus::Submitted)),
            'revision_required' => Tab::make('Revision Required')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TaskStatus::RevisionRequired)),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TaskStatus::Completed)),
            'overdue' => Tab::make('⚠️ Overdue')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', '!=', TaskStatus::Completed)
                    ->whereNotNull('deadline_date')
                    ->where('deadline_date', '<', now()->toDateString())),
        ];
    }
}
