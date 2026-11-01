<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PropertyResource\Pages;
use App\Filament\Components\ImageUpload;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    // Tenant Trust standard management fee (percent of rent). When a rent amount
    // is entered for a unit, the per-unit management fee is auto-filled from it.
    public const MANAGEMENT_FEE_PERCENT = 6.1;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Properties';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Building Details')
                    ->description('Main property / building information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->label('Building / Property Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Forms\Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('owner_id')
                            ->label('Client / Owner')
                            ->relationship('owner', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                                return $query->where('role', 'owner');
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('province')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('postal_code')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('unit_count')
                            ->label('Total Units')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->helperText('Number of units in this building'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'rented' => 'Rented',
                                'sold' => 'Sold',
                                'inactive' => 'Inactive (not managed)',
                            ])
                            ->default('active')
                            ->helperText('Building-level status shown on the properties list'),
                    ]),

                Forms\Components\Section::make('Management Fee')
                    ->description('How Tenant Trust charges this property. The property-level fee overrides per-unit fees.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('management_fee_type')
                            ->label('Fee Type')
                            ->options([
                                'percentage' => 'Percentage of rent (per unit invoice)',
                                'flat' => 'Flat $ per unit (per unit invoice)',
                                'flat_monthly' => 'Flat $ per property, once per month',
                            ])
                            ->native(false)
                            ->nullable()
                            ->helperText('Percentage / Flat = charged on every unit rent invoice. "Flat monthly" = one charge per property per month.'),
                        Forms\Components\TextInput::make('management_fee_value')
                            ->label('Fee Value (% or $)')
                            ->numeric()
                            ->nullable()
                            ->helperText('Percentage: 6.1 means 6.1%. Flat: dollars (e.g. 60). Flat monthly: dollars per property per month.'),
                    ]),


                Forms\Components\Section::make('Units')
                    ->description('Add individual units within this building.')
                    ->schema([
                        Forms\Components\Repeater::make('units')
                            ->relationship('units')
                            ->label('')
                            ->addActionLabel('Add Unit')
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['unit'] ?? 'New Unit')
                            ->schema([
                                Forms\Components\RichEditor::make('description')
                                    ->label('Unit Description')
                                    ->columnSpanFull(),
                                Forms\Components\TagsInput::make('amenities')
                                    ->label('Amenities')
                                    ->placeholder('Type an amenity and press Enter — e.g. In-suite laundry, Parking, Balcony')
                                    ->suggestions([
                                        'In-suite laundry',
                                        'Laundry in building',
                                        'Parking',
                                        'Balcony',
                                        'Dishwasher',
                                        'Air conditioning',
                                        'Storage locker',
                                        'Fridge & stove',
                                        'Microwave',
                                        'Gym access',
                                        'Pet friendly',
                                        'Fully Furnished',
                                        'Utilities included',
                                        'Internet included',
                                        'Elevator access',
                                        'Wheelchair accessible',
                                        'Furnished',
                                        'Patio / yard',
                                        'On-site laundry',
                                        'Heating included',
                                        'Water included',
                                    ])
                                    ->columnSpanFull()
                                    ->helperText('List everything this unit comes with. Shown on the public listing.'),
                                Forms\Components\TextInput::make('unit')
                                    ->label('Unit #')
                                    ->required()
                                    ->placeholder('e.g. Unit 1, Apt 2B')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('slug')
                                    ->label('URL Slug')
                                    ->maxLength(255)
                                    ->helperText('Auto-generated if left empty'),
                                Forms\Components\TextInput::make('rent_amount')
                                    ->label('Rent Amount ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, $state, $record = null) {
                                        if ($state === null || $state === '') {
                                            return;
                                        }

                                        // Flat-fee buildings (e.g. 137 Hillsboro / 178 Dell,
                                        // $60/unit) keep their fixed per-unit charge.
                                        $property = $record?->property;
                                        if ($property && in_array($property->management_fee_type, ['flat', 'flat_monthly'], true)) {
                                            return;
                                        }

                                        $rate = self::MANAGEMENT_FEE_PERCENT;
                                        if ($property && $property->management_fee_type === 'percentage'
                                            && $property->management_fee_value !== null) {
                                            $rate = (float) $property->management_fee_value;
                                        }

                                        $set('monthly_management_fee', round(((float) $state) * $rate / 100, 2));
                                    }),

                                Forms\Components\Select::make('tenant_id')
                                    ->label('Tenant')
                                    ->relationship('tenant', 'name', function ($query) {
                                        return $query->where('role', 'tenant');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),

                                Forms\Components\TextInput::make('monthly_management_fee')
                                    ->label('Monthly Mgmt Fee ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->nullable()
                                    ->helperText('Per-unit monthly management charge (used if no property-level fee is set)'),

                                Forms\Components\Repeater::make('tenants')
                                    ->label('Tenants')
                                    ->relationship('tenants')
                                    ->addActionLabel('Add Tenant')
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['tenant_id'] ?? 'New Tenant')
                                    ->schema([
                                        Forms\Components\Select::make('tenant_id')
                                            ->label('Tenant')
                                            ->relationship('tenant', 'name', function ($query) {
                                                return $query->where('role', 'tenant');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ])
                                    ->columns(1),
                                Forms\Components\TextInput::make('bedrooms')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('bathrooms')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Select::make('status')
                                    ->label('Listing Type')
                                    ->options([
                                        'active' => 'For Rent',
                                        'sold' => 'Sold',
                                        'rented' => 'Rented',
                                        'vacant' => 'Vacant',
                                        'occupied' => 'Occupied',
                                    ])
                                    ->default('vacant'),
                                ImageUpload::make('photo')
                                    ->label('Photos')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(30)
                                    ->directory('properties'),
                                Forms\Components\FileUpload::make('videos')
                                    ->label('Videos')
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->directory('properties/videos')
                                    ->maxSize(512000)
                                    ->mimeTypeMap([
                                        'mp4' => 'video/mp4',
                                        'webm' => 'video/webm',
                                        'ogg' => 'video/ogg',
                                        'mov' => 'video/quicktime',
                                        'avi' => 'video/x-msvideo',
                                        'mkv' => 'video/x-matroska',
                                        'flv' => 'video/x-flv',
                                        'wmv' => 'video/x-ms-wmv',
                                    ]),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_count')
                    ->label('Units')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'rented',
                        'gray' => 'sold',
                        'warning' => 'inactive',
                    ]),
                Tables\Columns\IconColumn::make('featured')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'rented' => 'Rented',
                        'sold' => 'Sold',
                        'inactive' => 'Inactive (not managed)',
                    ]),
                Tables\Filters\TernaryFilter::make('featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
