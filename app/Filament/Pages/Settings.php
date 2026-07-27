<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class Settings extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $title = 'App Settings';

    protected static ?string $navigationLabel = 'App Settings';

    public ?array $data = [];

    public function mount(GeneralSettings $settings): void
    {
        $this->form->fill($settings->toArray());
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString('settings-tab')
                    ->tabs([
                        Tab::make('General')
                            ->icon('heroicon-o-computer-desktop')
                            ->schema([
                                Section::make('Brand identity')
                                    ->description('Control how your application is presented across the admin panel.')
                                    ->icon('heroicon-o-swatch')
                                    ->schema([
                                        Forms\Components\TextInput::make('brand_name')
                                            ->label('Brand name')
                                            ->placeholder('YM Construction')
                                            ->prefixIcon('heroicon-o-building-office-2')
                                            ->helperText('Displayed in the sidebar and browser title.')
                                            ->maxLength(100)
                                            ->required(),
                                        Forms\Components\ColorPicker::make('primary_color')
                                            ->label('Primary colour')
                                            ->helperText('Used for buttons, links, and active states throughout the panel.')
                                            ->required(),
                                    ])
                                    ->columns([
                                        'default' => 1,
                                        'md' => 2,
                                    ]),
                                Section::make('Brand logo')
                                    ->description('Upload a clear logo that works well against the sidebar background.')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        Forms\Components\FileUpload::make('brand_logo')
                                            ->label('Logo image')
                                            ->image()
                                            ->imageEditor()
                                            ->imagePreviewHeight('160')
                                            ->acceptedFileTypes([
                                                'image/jpeg',
                                                'image/png',
                                                'image/webp',
                                                'image/svg+xml',
                                            ])
                                            ->maxSize(2048)
                                            ->helperText('PNG, JPG, WebP, or SVG up to 2 MB.'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save Settings')
                                ->authorize('Update:Settings')
                                ->submit('save'),
                        ])->key('form-actions'),
                    ]),
            ]);
    }

    public function save(GeneralSettings $settings): void
    {
        Gate::authorize('Update:Settings');

        $oldData = $settings->toArray();
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $settings->{$key} = $value;
        }

        $settings->save();
        $newData = $settings->toArray();

        activity()
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'old' => $oldData,
                'attributes' => $newData,
            ])
            ->log('updated the general application settings');

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();

        $this->redirect(static::getUrl(), navigate: true);
    }
}
