<?php

namespace App\Filament\Tenant\Resources;

use App\Models\MaintenanceTicket;
use App\Models\Tenancy;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;

class MaintenanceTicketResource extends Resource
{
    protected static ?string $model = MaintenanceTicket::class;

    protected static ?string $navigationIcon = "heroicon-o-wrench-screwdriver";
    protected static ?string $navigationLabel = "Maintenance";
    protected static ?string $pluralModelLabel = "Maintenance Requests";
    protected static ?string $modelLabel = "Maintenance Request";
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $tenantIds = $user->getUnitTenantIds();
        return parent::getEloquentQuery()->whereIn("tenant_id", $tenantIds);
    }

    public static function form(Form $form): Form
    {
        $tenant = Auth::user();
        $tenancy = Tenancy::where("tenant_id", $tenant->id)
            ->where("status", "active")
            ->first();

        $propertyName = $tenancy?->property?->title ?? "No property assigned";
        $unitText = $tenancy?->unit ? " (Unit " . $tenancy->unit . ")" : "";
        $propertyName .= $unitText;
        $propertyId = $tenancy?->property_id;
        $unitId = $tenancy?->property_unit_id;

        return $form
            ->schema([
                Placeholder::make("property_display")
                    ->label("Property")
                    ->content($propertyName)
                    ->extraAttributes(["class" => "text-gray-900 dark:text-white font-medium"]),

                TextInput::make("property_id")
                    ->hidden()
                    ->default($propertyId),

                TextInput::make("property_unit_id")
                    ->hidden()
                    ->default($unitId),

                TextInput::make("title")
                    ->label("Title")
                    ->required()
                    ->maxLength(255),

                Textarea::make("description")
                    ->label("Description")
                    ->required()
                    ->rows(4),

                Select::make("category")
                    ->label("Category")
                    ->options([
                        "plumbing" => "Plumbing",
                        "electrical" => "Electrical",
                        "heating_cooling" => "Heating/Cooling",
                        "appliance" => "Appliance",
                        "pest" => "Pest",
                        "structural" => "Structural",
                    ])
                    ->required()
                    ->default(null),
                Select::make("priority")
                    ->label("Priority")
                    ->options([
                        "low" => "Low",
                        "medium" => "Medium",
                        "high" => "High",
                        "emergency" => "Emergency",
                    ])
                    ->default("medium")
                    ->required(),

                FileUpload::make("photos")
                    ->label("Photos")
                    ->multiple()
                    ->image()
                    ->directory("ticket-photos")
                    ->maxSize(5120),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("ticket_number")
                    ->label("Ticket #")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("title")
                    ->label("Title")
                    ->searchable()
                    ->limit(40),
                BadgeColumn::make("status")
                    ->label("Status")
                    ->colors([
                        "gray" => "open",
                        "warning" => "in_progress",
                        "success" => "resolved",
                        "info" => "closed",
                    ])
                    ->formatStateUsing(fn(string $state): string => str_replace("_", " ", ucfirst($state))),
                BadgeColumn::make("priority")
                    ->label("Priority")
                    ->colors([
                        "gray" => "low",
                        "warning" => "medium",
                        "danger" => "high",
                        "red" => "emergency",
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                TextColumn::make("created_at")
                    ->label("Submitted")
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make("status")
                    ->options([
                        "open" => "Open",
                        "in_progress" => "In Progress",
                        "resolved" => "Resolved",
                        "closed" => "Closed",
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->label("View Details")
                    ->modalHeading(fn(MaintenanceTicket $record): string => "Ticket: " . $record->ticket_number)
                    ->modalContent(function (MaintenanceTicket $record): \Illuminate\View\View {
                        return view("filament.tenant.ticket-detail", [
                            "ticket" => $record,
                        ]);
                    }),
            ])
            ->bulkActions([])
            ->defaultSort("created_at", "desc");
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Tenant\Resources\MaintenanceTicketResource\Pages\ListMaintenanceTickets::route("/"),
            "create" => \App\Filament\Tenant\Resources\MaintenanceTicketResource\Pages\CreateMaintenanceTicket::route("/create"),
        ];
    }
}
