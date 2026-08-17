<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\DesignType;
use App\Enums\QaTestingStatus;
use App\Enums\SocialContentType;
use App\Enums\SocialPlatform;
use App\Enums\SocialWorkflowStage;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamType;
use App\Enums\VideoType;
use App\Enums\WebDevStatus;
use App\Enums\WebDevTaskType;
use App\Models\DepartmentTeam;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Core Task Information')
                    ->schema([
                        TextInput::make('title')
                            ->label('Task Title')
                            ->placeholder('e.g. Design 3 Instagram Carousels for Summer Campaign')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Select::make('department_id')
                            ->label('Department')
                            ->relationship(
                                'department',
                                'name',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant())
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Select::make('department_team_id')
                            ->label('Team / Unit')
                            ->relationship(
                                'departmentTeam',
                                'name',
                                fn (Builder $query, callable $get): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->when($get('department_id'), fn ($q, $deptId) => $q->where('department_id', $deptId))
                            )
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('assigned_to_employment_id')
                            ->label('Assigned Employee')
                            ->relationship(
                                'assignee',
                                'employee_code',
                                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant())
                            )
                            ->getOptionLabelFromRecordUsing(fn (Employment $record) => "{$record->employee?->full_name} ({$record->employee_code}) - {$record->designation?->name}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('priority')
                            ->label('Priority')
                            ->options(collect(TaskPriority::cases())->mapWithKeys(fn (TaskPriority $p) => [$p->value => $p->label()]))
                            ->default(TaskPriority::Medium->value)
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $s) => [$s->value => $s->label()]))
                            ->default(TaskStatus::NotStarted->value)
                            ->required(),
                        TextInput::make('progress_percentage')
                            ->label('Progress (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->suffix('%'),
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()),
                        DatePicker::make('deadline_date')
                            ->label('Deadline')
                            ->required(),
                        Textarea::make('description')
                            ->label('Task Description & Instructions')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('blocker_note')
                            ->label('Blockers / Issues (if any)')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Mention any client dependency or blocker...'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                // Specialized: Social Media Team Details
                Section::make('📱 Social Media Workflow Details')
                    ->relationship('socialMediaDetail')
                    ->visible(function (callable $get) {
                        $teamId = $get('department_team_id');
                        if (! $teamId) {
                            return false;
                        }
                        $team = DepartmentTeam::find($teamId);

                        return $team?->team_type === TeamType::SocialMedia;
                    })
                    ->schema([
                        TextInput::make('client_brand')->label('Client / Brand Name'),
                        Select::make('platforms')
                            ->label('Target Platforms')
                            ->multiple()
                            ->options(collect(SocialPlatform::cases())->mapWithKeys(fn (SocialPlatform $p) => [$p->value => $p->label()])),
                        Select::make('content_type')
                            ->label('Content Type')
                            ->options(collect(SocialContentType::cases())->mapWithKeys(fn (SocialContentType $c) => [$c->value => $c->label()])),
                        Select::make('workflow_stage')
                            ->label('Social Workflow Stage')
                            ->options(collect(SocialWorkflowStage::cases())->mapWithKeys(fn (SocialWorkflowStage $w) => [$w->value => $w->label()]))
                            ->default(SocialWorkflowStage::Idea->value),
                        DateTimePicker::make('publishing_datetime')->label('Scheduled Publish Date & Time'),
                        TextInput::make('published_link')->label('Published Post URL')->url(),
                        Textarea::make('caption')->label('Post Caption / Copy')->rows(3)->columnSpanFull(),
                        Textarea::make('creative_requirement')->label('Creative Brief (for Designer/Editor)')->rows(2)->columnSpanFull(),
                        Textarea::make('hashtags_keywords')->label('Hashtags & SEO Keywords')->rows(2)->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->columnSpanFull(),

                // Specialized: Graphic Design Details
                Section::make('🎨 Graphic Design Workflow Details')
                    ->relationship('designDetail')
                    ->visible(function (callable $get) {
                        $teamId = $get('department_team_id');
                        if (! $teamId) {
                            return false;
                        }
                        $team = DepartmentTeam::find($teamId);

                        return $team?->team_type === TeamType::GraphicDesign;
                    })
                    ->schema([
                        Select::make('design_type')
                            ->label('Design Type')
                            ->options(collect(DesignType::cases())->mapWithKeys(fn (DesignType $d) => [$d->value => $d->label()])),
                        TextInput::make('dimensions')->label('Dimensions Spec (e.g. 1080x1080, 1920x1080)'),
                        TextInput::make('target_platform')->label('Target Platform / Medium'),
                        TextInput::make('brand_client')->label('Brand / Client'),
                        Textarea::make('reference_links')->label('Inspiration / Reference Links')->rows(2)->columnSpan(2),
                        Textarea::make('copy_content')->label('Design Copy / Text to Include')->rows(3)->columnSpanFull(),
                        TextInput::make('source_file_path')->label('Source File Link (PSD/AI/Figma)'),
                        TextInput::make('preview_file_path')->label('Preview Image / PDF Link'),
                        TextInput::make('final_file_path')->label('Final Export Asset Link'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->columnSpanFull(),

                // Specialized: Video Production Details
                Section::make('🎬 Video Production Workflow Details')
                    ->relationship('videoDetail')
                    ->visible(function (callable $get) {
                        $teamId = $get('department_team_id');
                        if (! $teamId) {
                            return false;
                        }
                        $team = DepartmentTeam::find($teamId);

                        return $team?->team_type === TeamType::VideoProduction;
                    })
                    ->schema([
                        TextInput::make('video_project_name')->label('Video / Project Name'),
                        TextInput::make('client_brand')->label('Client / Brand'),
                        Select::make('video_type')
                            ->label('Video Type')
                            ->options(collect(VideoType::cases())->mapWithKeys(fn (VideoType $v) => [$v->value => $v->label()])),
                        TextInput::make('target_duration_seconds')->label('Target Duration (Seconds)')->numeric(),
                        TextInput::make('raw_footage_url')->label('Raw Footage Drive/Cloud Link')->url(),
                        TextInput::make('voiceover_url')->label('Voiceover Audio Link')->url(),
                        TextInput::make('reference_video_url')->label('Reference Video Link')->url(),
                        TextInput::make('draft_video_url')->label('Draft Preview Link (v1/v2)')->url(),
                        TextInput::make('final_video_url')->label('Final Rendered Video URL')->url(),
                        TextInput::make('published_link')->label('Published Link (YouTube/Reel)')->url(),
                        Textarea::make('script_text')->label('Script & Storyline')->rows(3)->columnSpanFull(),
                        Textarea::make('editing_instructions')->label('Editing Instructions & Style')->rows(2)->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->columnSpanFull(),

                // Specialized: Website Development Details
                Section::make('💻 Website Development & QA Details')
                    ->relationship('webDevDetail')
                    ->visible(function (callable $get) {
                        $teamId = $get('department_team_id');
                        if (! $teamId) {
                            return false;
                        }
                        $team = DepartmentTeam::find($teamId);

                        return $team?->team_type === TeamType::WebDevelopment;
                    })
                    ->schema([
                        TextInput::make('client_project')->label('Client / Project Name'),
                        TextInput::make('website_or_page')->label('Website URL / Page Name'),
                        Select::make('dev_task_type')
                            ->label('Dev Task Type')
                            ->options(collect(WebDevTaskType::cases())->mapWithKeys(fn (WebDevTaskType $w) => [$w->value => $w->label()])),
                        Select::make('dev_status')
                            ->label('Development Status')
                            ->options(collect(WebDevStatus::cases())->mapWithKeys(fn (WebDevStatus $s) => [$s->value => $s->label()]))
                            ->default(WebDevStatus::RequirementAnalysis->value),
                        Select::make('qa_testing_status')
                            ->label('QA / Testing Status')
                            ->options(collect(QaTestingStatus::cases())->mapWithKeys(fn (QaTestingStatus $q) => [$q->value => $q->label()]))
                            ->default(QaTestingStatus::Untested->value),
                        TextInput::make('bug_count')->label('Open Bug Count')->numeric()->default(0),
                        TextInput::make('staging_url')->label('Staging / Preview URL')->url(),
                        TextInput::make('live_url')->label('Production / Live URL')->url(),
                        Textarea::make('development_requirement')->label('Technical Specifications & Requirements')->rows(4)->columnSpanFull(),
                        Textarea::make('qa_feedback_notes')->label('QA Feedback & Bugs Notes')->rows(2)->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
