<?php

namespace App\Filament\Manager\Resources;

use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = "heroicon-o-truck";
    protected static ?string $navigationLabel = "Vendors";
    protected static ?string $navigationGroup = "Maintenance";
    protected static ?string $slug = "vendors";
    protected static ?string $modelLabel = "Vendor";
    protected static ?string $pluralModelLabel = "Vendors";
    protected static ?int $navigationSort = 2;

    public const TRADES = [
        "plumbing" => "Plumbing",
        "electrical" => "Electrical",
        "hvac" => "HVAC",
        "appliance" => "Appliance",
        "locksmith" => "Locksmith",
        "handyman" => "General Handyman",
        "pest" => "Pest Control",
        "roofing" => "Roofing",
        "snow" => "Snow Removal",
        "flooring" => "Flooring",
        "drywall" => "Drywall / Paint",
        "other" => "Other",
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Vendor")
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make("name")
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make("trade")
                        ->options(self::TRADES)
                        ->searchable(),
                    Forms\Components\TextInput::make("contact_name")
                        ->label("Contact Person")
                        ->maxLength(255),
                    Forms\Components\TextInput::make("phone")
                        ->tel()
                        ->maxLength(50),
                    Forms\Components\TextInput::make("email")
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make("rate")
                        ->label("Rate")
                        ->maxLength(255)
                        ->placeholder("e.g. $95/hr"),
                    Forms\Components\DatePicker::make("insurance_expiry")
                        ->label("Insurance / WSIB Expiry"),
                    Forms\Components\Toggle::make("wsib")
                        ->label("WSIB on file"),
                    Forms\Components\Toggle::make("active")
                        ->default(true),
                    Forms\Components\Textarea::make("notes")
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("trade")
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::TRADES[$state] ?? ucfirst((string) $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make("phone")
                    ->searchable(),
                Tables\Columns\TextColumn::make("email")
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make("insurance_expiry")
                    ->label("Insurance")
                    ->date()
                    ->placeholder("—")
                    ->color(fn ($state): ?string => $state && \Carbon\Carbon::parse($state)->isPast() ? "danger" : null)
                    ->sortable(),
                Tables\Columns\IconColumn::make("wsib")
                    ->boolean()
                    ->label("WSIB"),
                Tables\Columns\IconColumn::make("active")
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make("trade")->options(self::TRADES),
                TernaryFilter::make("active"),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort("name");
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Manager\Resources\VendorResource\Pages\ListVendors::route("/"),
            "create" => \App\Filament\Manager\Resources\VendorResource\Pages\CreateVendor::route("/create"),
            "edit" => \App\Filament\Manager\Resources\VendorResource\Pages\EditVendor::route("/{record}/edit"),
        ];
    }
}
