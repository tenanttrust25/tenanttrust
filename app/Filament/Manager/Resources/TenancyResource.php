<?php

namespace App\Filament\Manager\Resources;

use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Tenancy;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenancyResource extends Resource
{
    protected static ?string $model = Tenancy::class;

    protected static ?string $navigationIcon = "heroicon-o-document-text";
    protected static ?string $navigationLabel = "Leases";
    protected static ?string $navigationGroup = "Leasing";
    protected static ?string $slug = "leases";
    protected static ?string $modelLabel = "Lease";
    protected static ?string $pluralModelLabel = "Leases";
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Lease Details")
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make("property_id")
                        ->label("Property")
                        ->options(fn () => Property::orderBy("title")->pluck("title", "id"))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set("property_unit_id", null)),
                    Forms\Components\Select::make("property_unit_id")
                        ->label("Unit Record")
                        ->helperText("Optional — links this lease to a unit record.")
                        ->options(fn (Forms\Get $get) => PropertyUnit::where("property_id", $get("property_id"))->orderBy("unit")->pluck("unit", "id"))
                        ->searchable(),
                    Forms\Components\TextInput::make("unit")
                        ->label("Unit / Suite #")
                        ->placeholder("e.g. Apt 2B, Unit 3")
                        ->maxLength(50),
                ]),

            Forms\Components\Section::make("Tenant & Term")
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make("tenant_id")
                        ->label("Tenant")
                        ->options(fn () => User::where("role", "tenant")->orderBy("name")->pluck("name", "id"))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make("status")
                        ->options([
                            "active" => "Active",
                            "pending" => "Pending (Move-In)",
                            "expired" => "Expired",
                            "terminated" => "Terminated",
                        ])
                        ->default("active")
                        ->required(),
                    Forms\Components\DatePicker::make("lease_start")
                        ->label("Lease Start")
                        ->required(),
                    Forms\Components\DatePicker::make("lease_end")
                        ->label("Lease End")
                        ->afterOrEqual("lease_start"),
                    Forms\Components\TextInput::make("rent_amount")
                        ->label("Rent Amount")
                        ->numeric()
                        ->prefix("$")
                        ->required()
                        ->minValue(0),
                    Forms\Components\TextInput::make("security_deposit")
                        ->label("Security Deposit")
                        ->numeric()
                        ->prefix("$")
                        ->default(0),
                    Forms\Components\DatePicker::make("notice_given_at")
                        ->label("Notice Given"),
                    Forms\Components\DatePicker::make("move_out_date")
                        ->label("Move-Out Date"),
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
                Tables\Columns\TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("property.title")
                    ->label("Property")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("unit")
                    ->label("Unit")
                    ->searchable(),
                Tables\Columns\TextColumn::make("rent_amount")
                    ->label("Rent")
                    ->money("CAD")
                    ->sortable(),
                Tables\Columns\TextColumn::make("lease_start")
                    ->label("Start")
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make("lease_end")
                    ->label("End")
                    ->date()
                    ->sortable()
                    ->placeholder("—"),
                Tables\Columns\TextColumn::make("status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "active" => "success",
                        "pending" => "warning",
                        "expired" => "gray",
                        "terminated" => "danger",
                        default => "gray",
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make("status")
                    ->options([
                        "active" => "Active",
                        "pending" => "Pending",
                        "expired" => "Expired",
                        "terminated" => "Terminated",
                    ]),
                SelectFilter::make("property_id")
                    ->label("Property")
                    ->relationship("property", "title")
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort("lease_start", "desc");
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Manager\Resources\TenancyResource\Pages\ListTenancies::route("/"),
            "create" => \App\Filament\Manager\Resources\TenancyResource\Pages\CreateTenancy::route("/create"),
            "edit" => \App\Filament\Manager\Resources\TenancyResource\Pages\EditTenancy::route("/{record}/edit"),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $expiring = Tenancy::expiringSoon(60)->count();

        return $expiring > 0 ? (string) $expiring : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return "warning";
    }
}
