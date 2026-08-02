<?php

namespace App\Filament\Owner\Resources;

use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\TicketReply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MaintenanceTicketResource extends Resource
{
    protected static ?string $model = MaintenanceTicket::class;
    protected static ?string $navigationIcon = "heroicon-o-wrench";
    protected static ?string $navigationLabel = "Maintenance Tracker";
    protected static ?string $pluralLabel = "Maintenance Tracker";
    protected static ?string $slug = "maintenance";
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $propertyIds = Property::where("owner_id", Auth::id())->pluck("id")->toArray();
        return parent::getEloquentQuery()->whereIn("property_id", $propertyIds);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("ticket_number")
                    ->label("Ticket #")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("property.title")
                    ->label("Address")
                    ->searchable(),
                TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable(),
                TextColumn::make("tenant.phone")
                    ->label("Phone"),
                TextColumn::make("priority")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "emergency" => "danger",
                        "urgent" => "warning",
                        "normal" => "info",
                        default => "gray",
                    }),
                TextColumn::make("status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "open" => "danger",
                        "in_progress" => "warning",
                        "resolved" => "success",
                        "closed" => "gray",
                        default => "gray",
                    }),
                TextColumn::make("created_at")
                    ->label("Reported")
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort("created_at", "desc")
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Owner\Resources\MaintenanceTicketResource\Pages\ListMaintenanceTickets::route("/"),
            "view" => \App\Filament\Owner\Resources\MaintenanceTicketResource\Pages\ViewMaintenanceTicket::route("/{record}"),
        ];
    }
}
