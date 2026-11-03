<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        Textarea::make('description')
                            ->rows(3),
                    ]),

                Section::make('Settings')
                    ->schema([
                        TextInput::make('slug')
                            ->required()
                            ->unique(Category::class, 'slug', ignoreRecord: true)
                            ->helperText('Auto-filled from name'),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->integer()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        FileUpload::make('image')
                            ->label('Category Image')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('categories')
                            ->nullable(),
                    ]),
            ]);
    }
}
