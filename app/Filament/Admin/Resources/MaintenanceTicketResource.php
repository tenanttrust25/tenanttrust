<?php

namespace App\Filament\Admin\Resources;

use App\Models\MaintenanceTicket;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;

class MaintenanceTicketResource extends Resource
{
    protected static ?string $model = MaintenanceTicket::class;

    protected static ?string $navigationIcon = "heroicon-o-wrench-screwdriver";
    protected static ?string $navigationLabel = "Maintenance Tickets";
    protected static ?string $pluralModelLabel = "Maintenance Tickets";
    protected static ?string $modelLabel = "Maintenance Ticket";
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("ticket_number")->label("Ticket #")->disabled(),
                TextInput::make("tenant.name")->label("Tenant")->disabled(),
                TextInput::make("property.title")->label("Property")->disabled(),
                TextInput::make("title")->label("Title")->disabled(),
                Textarea::make("description")->label("Description")->disabled(),
                Select::make("status")
                    ->label("Status")
                    ->options([
                        "open" => "Open",
                        "in_progress" => "In Progress",
                        "resolved" => "Resolved",
                        "closed" => "Closed",
                    ])
                    ->required(),
                Textarea::make("admin_notes")
                    ->label("Admin Notes")
                    ->rows(3),
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
                TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("property.title")
                    ->label("Property")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("title")
                    ->label("Title")
                    ->limit(30),
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
                EditAction::make(),
            ])
            ->defaultSort("created_at", "desc");
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Admin\Resources\MaintenanceTicketResource\Pages\ListMaintenanceTickets::route("/"),
            "edit" => \App\Filament\Admin\Resources\MaintenanceTicketResource\Pages\EditMaintenanceTicket::route("/{record}/edit"),
        ];
    }
}
