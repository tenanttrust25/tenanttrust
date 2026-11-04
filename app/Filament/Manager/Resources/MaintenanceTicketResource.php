<?php

namespace App\Filament\Manager\Resources;

use App\Models\MaintenanceTicket;
use App\Models\Tenancy;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class MaintenanceTicketResource extends Resource
{
    protected static ?string $model = MaintenanceTicket::class;

    protected static ?string $navigationIcon = "heroicon-o-wrench-screwdriver";
    protected static ?string $navigationLabel = "Maintenance Tickets";
    protected static ?string $navigationGroup = "Maintenance";
    protected static ?string $pluralModelLabel = "Maintenance Tickets";
    protected static ?string $modelLabel = "Maintenance Ticket";
    protected static ?string $slug = "maintenance";
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make("Ticket Information")
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make("ticket_number")
                            ->label("Ticket #")
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make("assigned_to")
                            ->label("Assigned To")
                            ->options(User::where("role", "manager")->pluck("name", "id"))
                            ->searchable()
                            ->placeholder("Unassigned"),
                        Forms\Components\Select::make("status")
                            ->label("Status")
                            ->options([
                                "open" => "Open",
                                "in_progress" => "In Progress",
                                "resolved" => "Resolved",
                                "closed" => "Closed",
                            ])
                            ->required(),
                        Forms\Components\Select::make("priority")
                            ->label("Priority")
                            ->options([
                                "low" => "Low",
                                "medium" => "Medium",
                                "high" => "High",
                                "emergency" => "Emergency",
                            ])
                            ->required(),
                    ]),

                Forms\Components\Section::make("Tenant Information")
                    ->schema([
                        Forms\Components\Placeholder::make("tenant_details")
                            ->label("")
                            ->content(function ($record) {
                                if (!$record || !$record->tenant) return "No tenant assigned";
                                $t = $record->tenant;
                                return "Name: {$t->name}\nEmail: {$t->email}\nPhone: " . ($t->phone ?? "N/A");
                            }),
                    ]),

                Forms\Components\Section::make("Property & Issue")
                    ->schema([
                        Forms\Components\Placeholder::make("property_info")
                            ->label("Property")
                            ->content(function ($record) {
                                if (!$record || !$record->property) return "N/A";
                                $prop = $record->property;
                                $tenancy = Tenancy::where("property_id", $prop->id)
                                    ->where("tenant_id", $record->tenant_id)
                                    ->first();
                                $unitText = $tenancy?->unit ? " (Unit " . $tenancy->unit . ")" : "";
                                return "{$prop->title}{$unitText}\n{$prop->address}, {$prop->city}";
                            }),
                        Forms\Components\TextInput::make("category")
                            ->label("Category")
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace("_", " ", $state)) : "N/A"),
                        Forms\Components\TextInput::make("title")
                            ->label("Title")
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make("description")
                            ->label("Description")
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3),
                    ]),

                Forms\Components\Section::make("Conversation")
                    ->schema([
                        Forms\Components\Repeater::make("replies")
                            ->relationship("replies")
                            ->label("")
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                Forms\Components\Placeholder::make("reply_info")
                                    ->label("")
                                    ->content(function ($state, $record) {
                                        $userName = $record?->user?->name ?? "System";
                                        $time = $record?->created_at?->diffForHumans() ?? "";
                                        return "{$userName} · {$time}\n\n" . ($state["message"] ?? $record?->message ?? "");
                                    }),
                            ]),
                        Forms\Components\Textarea::make("new_reply")
                            ->label("Add a Reply")
                            ->rows(3)
                            ->placeholder("Type your reply to the tenant..."),
                    ]),

                Forms\Components\Section::make("Resolution")
                    ->columns(2)
                    ->visible(fn (Forms\Get $get): bool => in_array($get("status"), ["resolved", "closed"]))
                    ->schema([
                        Forms\Components\DateTimePicker::make("resolved_at")
                            ->label("Resolved At")
                            ->default(now()),
                        Forms\Components\Select::make("vendor_id")
                            ->label("Vendor (directory)")
                            ->options(fn () => \App\Models\Vendor::orderBy("name")->pluck("name", "id"))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set("vendor", \App\Models\Vendor::find($state)?->name);
                                }
                            }),
                        Forms\Components\TextInput::make("vendor")
                            ->label("Contractor / Vendor (name)")
                            ->maxLength(255)
                            ->placeholder("e.g. ABC Plumbing"),
                        Forms\Components\TextInput::make("cost")
                            ->label("Repair Cost ($) — passed to owner at cost")
                            ->numeric()
                            ->prefix("$")
                            ->placeholder("0.00"),
                        Forms\Components\FileUpload::make("invoice_path")
                            ->label("Contractor Invoice (optional)")
                            ->directory("ticket-invoices")
                            ->disk('documents')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->maxSize(8192)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("ticket_number")
                    ->label("Ticket #")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("property.title")
                    ->label("Property")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("title")
                    ->label("Issue")
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make("category")
                    ->label("Category")
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace("_", " ", $state)) : "—"),
                Tables\Columns\TextColumn::make("priority")
                    ->label("Priority")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "emergency" => "danger",
                        "high" => "warning",
                        "medium" => "gray",
                        "low" => "gray",
                        default => "gray",
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make("status")
                    ->label("Status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "open" => "gray",
                        "in_progress" => "warning",
                        "resolved" => "success",
                        "closed" => "info",
                        default => "gray",
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace("_", " ", ucfirst($state))),
                Tables\Columns\TextColumn::make("assignedTo.name")
                    ->label("Assigned")
                    ->placeholder("—"),
                Tables\Columns\TextColumn::make("created_at")
                    ->label("Opened")
                    ->date("M d")
                    ->sortable(),
                Tables\Columns\TextColumn::make("days_open")
                    ->label("Days")
                    ->state(fn (MaintenanceTicket $record): int => $record->created_at->diffInDays(now()))
                    ->color(fn (int $state): string => $state > 7 ? "danger" : ($state > 3 ? "warning" : "gray"))
                    ->badge()
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
                SelectFilter::make("priority")
                    ->options([
                        "low" => "Low",
                        "medium" => "Medium",
                        "high" => "High",
                        "emergency" => "Emergency",
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label("View / Update"),
            ])
            ->defaultSort("created_at", "desc");
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Manager\Resources\MaintenanceTicketResource\Pages\ListMaintenanceTickets::route("/"),
            "edit" => \App\Filament\Manager\Resources\MaintenanceTicketResource\Pages\EditMaintenanceTicket::route("/{record}/edit"),
        ];
    }
}
