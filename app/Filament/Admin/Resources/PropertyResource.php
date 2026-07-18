<?php

namespace App\Filament\Admin\Resources;

use App\Models\Property;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationGroup(): ?string
    {
        return 'Properties';
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('description')->required(),
            Forms\Components\TextInput::make('price')->numeric()->required()->prefix('$'),
            Forms\Components\TextInput::make('address')->required(),
            Forms\Components\TextInput::make('city')->required(),
            Forms\Components\Select::make('province')->options([
                'ON'=>'Ontario','BC'=>'British Columbia','QC'=>'Quebec','AB'=>'Alberta',
                'MB'=>'Manitoba','SK'=>'Saskatchewan','NS'=>'Nova Scotia','NB'=>'New Brunswick',
                'NL'=>'Newfoundland','PE'=>'PEI','YT'=>'Yukon','NT'=>'NWT','NU'=>'Nunavut',
            ])->required(),
            Forms\Components\TextInput::make('postal_code')->required(),
            Forms\Components\Select::make('status')->options([
                'available'=>'Available','rented'=>'Rented','maintenance'=>'Maintenance',
            ])->required(),
            Forms\Components\Select::make('type')->options([
                'apartment'=>'Apartment','house'=>'House','condo'=>'Condo','commercial'=>'Commercial',
            ])->required(),
            Forms\Components\TextInput::make('bedrooms')->numeric(),
            Forms\Components\TextInput::make('bathrooms')->numeric(),
            Forms\Components\TextInput::make('square_feet')->numeric(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('price')->money('cad'),
            Tables\Columns\TextColumn::make('city'),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'available' => 'success',
                'rented' => 'danger',
                'maintenance' => 'warning',
            }),
            Tables\Columns\TextColumn::make('type'),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options([
                'available'=>'Available','rented'=>'Rented','maintenance'=>'Maintenance',
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => PropertyResource\Pages\ListProperties::route('/')];
    }
}
