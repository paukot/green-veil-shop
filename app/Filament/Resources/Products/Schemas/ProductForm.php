<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Brand;
use App\Models\Country;
use App\Models\Product;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public const CLASSIFICATIONS = [
        'drugstore' => 'Drugstore / Mass-Market',
        'dermocosmetic' => 'Dermocosmetic / Pharmacy',
        'natural_organic' => 'Natural & Organic',
        'professional' => 'Professional / Salon',
        'luxury' => 'Luxury / Selective',
        'k_beauty' => 'K-Beauty',
    ];

    public const GENDERS = [
        'unisex' => 'Unisex',
        'women' => 'Women',
        'men' => 'Men',
        'kids' => 'Kids',
    ];

    public const AGE_RANGES = [
        'all' => 'All Ages',
        'teen' => 'Teens',
        '20+' => '20+',
        '30+' => '30+',
        '40+' => '40+',
        '50+' => '50+',
        '60+' => '60+',
    ];

    public const FORMULA_TYPES = [
        'serum' => 'Serum',
        'cream' => 'Cream',
        'gel' => 'Gel',
        'liquid' => 'Liquid / Fluid',
        'oil' => 'Oil',
        'mousse' => 'Mousse / Foam',
        'powder' => 'Powder',
        'balm' => 'Balm',
        'spray' => 'Mist / Spray',
        'stick' => 'Stick',
        'scrub' => 'Exfoliating Scrub',
        'mask' => 'Treatment Mask',
    ];

    public const SPF_OPTIONS = [
        'none' => 'No SPF',
        'SPF 15' => 'SPF 15',
        'SPF 20' => 'SPF 20',
        'SPF 30' => 'SPF 30',
        'SPF 50' => 'SPF 50',
        'SPF 50+' => 'SPF 50+',
    ];

    public const UNITS = [
        'ml' => 'Milliliters (ml)',
        'g' => 'Grams (g)',
        'pcs' => 'Pieces (pcs)',
        'l' => 'Liters (l)',
        'kg' => 'Kilograms (kg)',
    ];

    public const PAO_OPTIONS = [
        3 => '3 Months (3M)',
        6 => '6 Months (6M)',
        12 => '12 Months (12M)',
        24 => '24 Months (24M)',
        36 => '36 Months (36M)',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Product Information')
                    ->tabs([
                        // Tab 1: General & Brand
                        Tab::make('General & Brand')
                            ->schema([
                                Section::make('Brand & Classification')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('brand_id')
                                                    ->label('Brand')
                                                    ->relationship('brand', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->nullable(),
                                                Select::make('category_id')
                                                    ->label('Category')
                                                    ->relationship('category', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->nullable(),
                                                TextInput::make('series')
                                                    ->label('Product Series / Line')
                                                    ->placeholder('e.g. Lash Sensational, Effaclar, Hydro Boost')
                                                    ->nullable(),
                                            ]),
                                    ]),

                                Section::make('Title & URLs')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->unique(Product::class, 'slug', ignoreRecord: true)
                                                    ->helperText('Auto-filled from name'),
                                            ]),
                                        Textarea::make('short_description')
                                            ->label('Short Description')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Full Description & Editorial Content')
                                            ->rows(5)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Media & Visibility')
                                    ->schema([
                                        FileUpload::make('main_image')
                                            ->label('Main Catalog Image')
                                            ->image()
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('products')
                                            ->nullable()
                                            ->columnSpanFull(),
                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('is_active')
                                                    ->label('Active in Catalog')
                                                    ->default(true),
                                                Toggle::make('is_featured')
                                                    ->label('Featured Product')
                                                    ->default(false),
                                                TextInput::make('sort_order')
                                                    ->label('Sort Order')
                                                    ->numeric()
                                                    ->integer()
                                                    ->default(0),
                                            ]),
                                    ]),
                            ]),

                        // Tab 2: Pricing, Volume & Inventory
                        Tab::make('Price & Inventory')
                            ->schema([
                                Section::make('Pricing & Unit Price Calculation (EU Directive 98/6/EC)')
                                    ->description('Base amount and net volume are used to display mandatory unit prices (e.g. €30.79 / 100 ml).')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('price')
                                                    ->label('Current Price')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('€')
                                                    ->minValue(0),
                                                TextInput::make('compare_at_price')
                                                    ->label('Compare at Price (Original)')
                                                    ->numeric()
                                                    ->prefix('€')
                                                    ->nullable()
                                                    ->helperText('Original strikethrough price showing discount percentage'),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('volume')
                                                    ->label('Net Quantity / Volume')
                                                    ->numeric()
                                                    ->placeholder('e.g. 7.2, 50, 250')
                                                    ->nullable(),
                                                Select::make('unit')
                                                    ->label('Unit of Measurement')
                                                    ->options(self::UNITS)
                                                    ->default('ml'),
                                                Select::make('unit_price_base_amount')
                                                    ->label('Base Amount for Unit Price')
                                                    ->options([
                                                        100 => 'Per 100 (standard for ml / g)',
                                                        1 => 'Per 1 (standard for pcs / kg / l)',
                                                        1000 => 'Per 1000',
                                                    ])
                                                    ->default(100),
                                            ]),
                                    ]),

                                Section::make('Inventory & Barcodes')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('sku')
                                                    ->label('SKU (Stock Keeping Unit)')
                                                    ->unique(Product::class, 'sku', ignoreRecord: true)
                                                    ->nullable(),
                                                TextInput::make('barcode')
                                                    ->label('Barcode / GTIN-13 / EAN')
                                                    ->placeholder('e.g. 3600531637996')
                                                    ->nullable(),
                                                TextInput::make('stock_quantity')
                                                    ->label('Stock Quantity')
                                                    ->numeric()
                                                    ->integer()
                                                    ->default(0)
                                                    ->minValue(0),
                                                Toggle::make('track_inventory')
                                                    ->label('Track Inventory')
                                                    ->default(true),
                                            ]),
                                    ]),
                            ]),

                        // Tab 3: Cosmetic Characteristics & Targeting
                        Tab::make('Characteristics')
                            ->schema([
                                Section::make('Cosmetic Classification & Target Demographics')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('classification')
                                                    ->label('Cosmetic Classification')
                                                    ->options(self::CLASSIFICATIONS)
                                                    ->nullable(),
                                                Select::make('gender')
                                                    ->label('Target Audience')
                                                    ->options(self::GENDERS)
                                                    ->default('unisex'),
                                                Select::make('age_range')
                                                    ->label('Age Recommendation')
                                                    ->options(self::AGE_RANGES)
                                                    ->nullable(),
                                                Select::make('formula_type')
                                                    ->label('Formula & Texture')
                                                    ->options(self::FORMULA_TYPES)
                                                    ->nullable(),
                                                Select::make('spf')
                                                    ->label('Sun Protection Factor (SPF)')
                                                    ->options(self::SPF_OPTIONS)
                                                    ->nullable(),
                                            ]),
                                    ]),

                                Section::make('Profiles & Actions')
                                    ->schema([
                                        Select::make('skinTypes')
                                            ->label('Target Skin Types / Concerns')
                                            ->relationship('skinTypes', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload(),
                                        Select::make('hairTypes')
                                            ->label('Target Hair Types')
                                            ->relationship('hairTypes', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload(),
                                        Select::make('effects')
                                            ->label('Cosmetic Effects & Actions')
                                            ->relationship('effects', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload(),
                                    ]),

                                Section::make('Custom Domain Characteristics')
                                    ->description('Flexible key-value pairs for niche attributes (e.g. Brush Type, Finish, Coverage level, Aroma notes).')
                                    ->schema([
                                        KeyValue::make('custom_characteristics')
                                            ->keyLabel('Property (e.g. Brush Type, Finish, Coverage)')
                                            ->valueLabel('Value (e.g. Flexible Silicone, Satin-Matte, High)')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Tab 4: Formulation & Ingredients (INCI)
                        Tab::make('Ingredients & INCI')
                            ->schema([
                                Section::make('Official INCI Formulation')
                                    ->schema([
                                        Textarea::make('inci_list')
                                            ->label('Full INCI Declaration')
                                            ->helperText('Official chemical composition list as mandated by EU Cosmetic Regulation 1223/2009 (Aqua, Glycerin, Dimethicone...).')
                                            ->rows(6)
                                            ->columnSpanFull(),
                                        Select::make('ingredients')
                                            ->label('Highlighted Key & Botanical Actives')
                                            ->relationship('ingredients', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Clean Beauty & Regulatory Claims')
                                    ->schema([
                                        CheckboxList::make('claims')
                                            ->label('Product Claims & Certifications')
                                            ->relationship('claims', 'name')
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Tab 5: Application, Safety & Regulatory
                        Tab::make('Usage & Regulatory')
                            ->schema([
                                Section::make('Directions & Safety Warnings')
                                    ->schema([
                                        Textarea::make('how_to_use')
                                            ->label('Directions for Use')
                                            ->placeholder('e.g. Apply mascara in a zig-zag motion from root to tip. Repeat for additional volume.')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                        Textarea::make('warnings')
                                            ->label('Warnings & Storage Conditions')
                                            ->placeholder('e.g. For external cosmetic use only. Keep away from heat and direct sunlight. Store between 5°C and 25°C.')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('EU Compliance & Manufacturing')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('pao_months')
                                                    ->label('Period After Opening (PAO)')
                                                    ->options(self::PAO_OPTIONS)
                                                    ->nullable(),
                                                Select::make('manufactured_in')
                                                    ->label('Country of Manufacture')
                                                    ->options(fn (): array => Country::getOptionList())
                                                    ->searchable()
                                                    ->nullable(),
                                                TextInput::make('responsible_person')
                                                    ->label('EU Responsible Person')
                                                    ->placeholder('e.g. Maybelline New York, 14 Rue Royale 75008 Paris')
                                                    ->nullable(),
                                            ]),
                                    ]),
                            ]),

                        // Tab 6: SEO & Meta
                        Tab::make('SEO & Meta')
                            ->schema([
                                Section::make('Search Engine Optimization')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->placeholder('Browser and search results title')
                                            ->columnSpanFull(),
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->placeholder('Summary snippet for search results')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
