<?php

namespace App\Filament\Resources\SliderItems\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SliderItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Image')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Slider Image')
                                    ->image()
                                    ->required()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('slider')
                                    ->columnSpanFull()
                                    ->helperText('Recommended size: 1920×700px'),
                            ]),

                        Section::make('Content')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title'),
                                TextInput::make('subtitle')
                                    ->label('Subtitle'),
                                TextInput::make('alt_text')
                                    ->label('Alt Text')
                                    ->helperText('Screen reader description'),
                                TextInput::make('button_label')
                                    ->label('CTA Button Label')
                                    ->placeholder('Shop Now'),
                            ]),
                    ]),

                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Link')
                            ->schema([
                                TextInput::make('url')
                                    ->label('Target URL')
                                    ->url()
                                    ->nullable()
                                    ->helperText('Where the slide links to'),
                                Toggle::make('url_opens_in_new_tab')
                                    ->label('Open in New Tab')
                                    ->default(false),
                            ]),

                        Section::make('Visibility')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->integer()
                                    ->default(0),
                            ]),

                        Section::make('Schedule')
                            ->schema([
                                DateTimePicker::make('starts_at')
                                    ->label('Show From')
                                    ->nullable(),
                                DateTimePicker::make('ends_at')
                                    ->label('Show Until')
                                    ->nullable(),
                            ]),
                    ]),
            ]);
    }
}
