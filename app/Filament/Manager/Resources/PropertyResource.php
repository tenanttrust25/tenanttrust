<?php

namespace App\Filament\Manager\Resources;

use App\Models\Property;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = "heroicon-o-building-office-2";
    protected static ?string $navigationLabel = "Properties";
    protected static ?string $navigationGroup = "Properties";
    protected static ?string $pluralModelLabel = "Properties";
    protected static ?string $modelLabel = "Property";
    protected static ?string $slug = "properties";
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make("Property Info")
                    ->description("Read-only property details")
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make("title")
                            
                            ,
                        Forms\Components\TextInput::make("address")
                            
                            ,
                        Forms\Components\TextInput::make("city")
                            
                            ,
                        Forms\Components\TextInput::make("province")
                            
                            ,
                        Forms\Components\TextInput::make("unit_count")
                            
                            
                            ->label("Units"),
                        Forms\Components\Placeholder::make("total_rent")
                            
                            
                            ->label("Monthly Rent (Total)")
                            ->content(function ($record) {
                                return "$" . number_format(\App\Models\Tenancy::where("property_id", $record->id)->where("status", "active")->sum("rent_amount") ?: $record->price, 2);
                            }),
                    ]),

                Forms\Components\Section::make("Tenant Assignments")
                    ->description("Manage tenants, lease terms, and documents.")
                    ->schema([
                        Forms\Components\Repeater::make("tenancies")
                            ->relationship("tenancies")
                            ->label("")
                            ->addActionLabel("Add Tenant to This Property")
                            ->collapsible()
                            ->cloneable(false)
                            ->itemLabel(fn (array $state): ?string => "Tenant: " . (\App\Models\User::find($state["tenant_id"] ?? 0)?->name ?? "New Assignment"))
                            ->schema([
                                Forms\Components\Select::make("tenant_id")
                                    ->label("Tenant")
                                    ->options(fn () => \App\Models\User::where("role", "tenant")->pluck("name", "id"))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make("unit")
                                    ->label("Unit / Suite #")
                                    ->placeholder("e.g. Apt 2B, Unit 3")
                                    ->maxLength(50),
                                Forms\Components\TextInput::make("rent_amount")
                                    ->label("Rent Amount ($)")
                                    ->numeric()
                                    ->prefix("$")
                                    ->required()
                                    ->minValue(0),
                                Forms\Components\DatePicker::make("lease_start")
                                    ->label("Lease Start")
                                    ->required(),
                                Forms\Components\DatePicker::make("lease_end")
                                    ->label("Lease End")
                                    ->nullable(),
                                Forms\Components\Select::make("status")
                                    ->label("Status")
                                    ->options([
                                        "active" => "Active",
                                        "pending" => "Pending (Move-In)",
                                        "expired" => "Expired",
                                        "terminated" => "Terminated",
                                    ])
                                    ->default("active")
                                    ->required(),
                                Forms\Components\TextInput::make("security_deposit")
                                    ->label("Security Deposit ($)")
                                    ->numeric()
                                    ->prefix("$")
                                    ->default(0),
                                Forms\Components\DatePicker::make("move_out_date")
                                    ->label("Move-Out Date")
                                    ->nullable(),
                                Forms\Components\Textarea::make("notes")
                                    ->label("Notes")
                                    ->rows(2),
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make("Tenant Documents")
                    ->description("Upload lease agreements, N forms, or other files.")
                    ->schema([
                        Forms\Components\Repeater::make("tenantFilesUpload")
                            ->label("")
                            ->addActionLabel("Upload Document")
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state["name"] ?? "New Document")
                            ->schema([
                                Forms\Components\Select::make("tenant_id")
                                    ->label("Tenant")
                                    ->options(function () {
                                        return User::where("role", "tenant")->pluck("name", "id");
                                    })
                                    ->searchable()
                                    ->required(),
                                Forms\Components\FileUpload::make("file_path")
                                    ->label("File")
                                    ->directory("tenant-files")
                                    ->preserveFilenames()
                                    ->required()
                                    ->openable()
                                    ->downloadable()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make("name")
                                    ->label("Document Name")
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder("e.g. Signed Lease"),
                                Forms\Components\Select::make("file_type")
                                    ->label("Type")
                                    ->options([
                                        "lease" => "Lease Agreement",
                                        "N1" => "N1 Form",
                                        "N4" => "N4 Form",
                                        "N5" => "N5 Form",
                                        "other" => "Other",
                                    ])
                                    ->default("lease")
                                    ->required(),
                                Forms\Components\Hidden::make("uploaded_by")
                                    ->default(fn () => auth()->id()),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("title")
                    ->label("Property")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("city")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("status")
                    ->label("Status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "occupied" => "success",
                        "vacant" => "danger",
                        "active" => "info",
                        "sold" => "gray",
                        default => "gray",
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make("unit_count")
                    ->label("Units")
                    ->sortable(),
                Tables\Columns\TextColumn::make("total_rent")
                    ->label("Rent")
                    ->money("CAD")
                    ->state(fn (\App\Models\Property $record) => $record->tenancies()->where("status", "active")->sum("rent_amount") ?: $record->price ?? 0),
                Tables\Columns\TextColumn::make("current_tenant")
                    ->label("Tenant")
                    ->state(function (Property $record) {
                        $active = $record->tenancies()->where("status", "active")->first();
                        return $active ? ($active->tenant?->name ?? "Vacant") : "Vacant";
                    }),
            ])
            ->filters([
                SelectFilter::make("status")
                    ->options([
                        "occupied" => "Occupied",
                        "vacant" => "Vacant",
                        "active" => "Active",
                    ]),
                SelectFilter::make("property_type")
                    ->options([
                        "for-rent" => "For Rent",
                        "for-sale" => "For Sale",
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label("Manage Tenants"),
            ])
            ->bulkActions([])
            ->defaultSort("title");
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Manager\Resources\PropertyResource\Pages\ListProperties::route("/"),
            "edit" => \App\Filament\Manager\Resources\PropertyResource\Pages\EditProperty::route("/{record}/edit"),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $vacant = Property::where("status", "vacant")->count();
        return $vacant > 0 ? (string) $vacant : null;
    }
}
