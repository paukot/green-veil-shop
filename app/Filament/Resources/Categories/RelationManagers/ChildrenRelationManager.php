<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Models\Category;
use App\Services\StorageImageService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Subcategories';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')
                    ->required()
                    ->unique(Category::class, 'slug', ignoreRecord: true)
                    ->helperText('Auto-filled from name'),
                Textarea::make('description')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('Category Image')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('categories')
                    ->nullable()
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->integer()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->square()
                    ->imageSize(40)
                    ->getStateUsing(fn (Category $record): ?string => app(StorageImageService::class)->getImage($record->image))
                    ->defaultImageUrl(asset('images/placeholder.png')),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Subcategory')
                    ->modalHeading('New Subcategory'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Subcategory'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
