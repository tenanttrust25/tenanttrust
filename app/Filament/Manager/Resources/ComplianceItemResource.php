<?php

namespace App\Filament\Manager\Resources;

use App\Models\ComplianceItem;
use App\Models\Property;
use App\Models\PropertyUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ComplianceItemResource extends Resource
{
    protected static ?string $model = ComplianceItem::class;

    protected static ?string $navigationIcon = "heroicon-o-clipboard-document-check";
    protected static ?string $navigationLabel = "Inspections & Compliance";
    protected static ?string $navigationGroup = "Operations";
    protected static ?string $slug = "compliance";
    protected static ?string $modelLabel = "Compliance Item";
    protected static ?string $pluralModelLabel = "Inspections & Compliance";
    protected static ?int $navigationSort = 1;

    public const TYPES = [
        "smoke_alarm_test" => "Smoke Alarm Test",
        "co_alarm_test" => "CO Alarm Test",
        "furnace_service" => "Furnace / HVAC Service",
        "move_in_inspection" => "Move-In Inspection",
        "move_out_inspection" => "Move-Out Inspection",
        "insurance_renewal" => "Insurance Renewal",
        "fire_safety" => "Fire Safety Review",
        "backflow_test" => "Backflow Test",
        "seasonal" => "Seasonal Task",
        "other" => "Other",
    ];

    public const RECURRENCE = [
        "none" => "One-time",
        "annual" => "Annual",
        "semiannual" => "Every 6 months",
        "quarterly" => "Quarterly",
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make("Item")
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make("title")
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Select::make("type")
                        ->options(self::TYPES)
                        ->default("other")
                        ->required(),
                    Forms\Components\Select::make("recurrence")
                        ->options(self::RECURRENCE)
                        ->default("none"),
                    Forms\Components\DatePicker::make("due_date")
                        ->label("Due Date")
                        ->required(),
                    Forms\Components\DatePicker::make("completed_at")
                        ->label("Completed On"),
                    Forms\Components\Select::make("property_id")
                        ->label("Property")
                        ->options(fn () => Property::orderBy("title")->pluck("title", "id"))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set("property_unit_id", null)),
                    Forms\Components\Select::make("property_unit_id")
                        ->label("Unit")
                        ->options(fn (Forms\Get $get) => PropertyUnit::where("property_id", $get("property_id"))->orderBy("unit")->pluck("unit", "id"))
                        ->searchable(),
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
                Tables\Columns\TextColumn::make("title")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("type")
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::TYPES[$state] ?? ucfirst((string) $state)),
                Tables\Columns\TextColumn::make("property.title")
                    ->label("Property")
                    ->placeholder("Portfolio-wide")
                    ->searchable(),
                Tables\Columns\TextColumn::make("due_date")
                    ->label("Due")
                    ->date()
                    ->sortable()
                    ->color(fn (ComplianceItem $record): ?string => $record->completed_at === null && $record->due_date?->isPast() ? "danger" : null),
                Tables\Columns\TextColumn::make("completed_at")
                    ->label("Completed")
                    ->date()
                    ->placeholder("—")
                    ->sortable(),
                Tables\Columns\TextColumn::make("status")
                    ->badge()
                    ->state(function (ComplianceItem $record): string {
                        if ($record->completed_at) {
                            return "Completed";
                        }
                        if ($record->due_date?->isPast()) {
                            return "Overdue";
                        }
                        if ($record->due_date && $record->due_date->diffInDays(now()) <= 30) {
                            return "Due soon";
                        }

                        return "Upcoming";
                    })
                    ->color(fn (string $state): string => match ($state) {
                        "Completed" => "success",
                        "Overdue" => "danger",
                        "Due soon" => "warning",
                        default => "gray",
                    }),
            ])
            ->filters([
                SelectFilter::make("type")->options(self::TYPES),
                SelectFilter::make("property_id")
                    ->label("Property")
                    ->relationship("property", "title")
                    ->searchable()
                    ->preload(),
                SelectFilter::make("state")
                    ->label("Status")
                    ->options([
                        "due" => "Overdue",
                        "open" => "Not completed",
                        "completed" => "Completed",
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data["value"] ?? null) {
                            "due" => $query->whereNull("completed_at")->whereDate("due_date", "<", now()->toDateString()),
                            "open" => $query->whereNull("completed_at"),
                            "completed" => $query->whereNotNull("completed_at"),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make("complete")
                    ->label("Mark complete")
                    ->icon("heroicon-o-check-circle")
                    ->color("success")
                    ->requiresConfirmation()
                    ->visible(fn (ComplianceItem $record): bool => $record->completed_at === null)
                    ->action(fn (ComplianceItem $record) => $record->update(["completed_at" => now()->toDateString()])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort("due_date");
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Manager\Resources\ComplianceItemResource\Pages\ListComplianceItems::route("/"),
            "create" => \App\Filament\Manager\Resources\ComplianceItemResource\Pages\CreateComplianceItem::route("/create"),
            "edit" => \App\Filament\Manager\Resources\ComplianceItemResource\Pages\EditComplianceItem::route("/{record}/edit"),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $due = ComplianceItem::query()->due()->count();

        return $due > 0 ? (string) $due : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return "danger";
    }
}
