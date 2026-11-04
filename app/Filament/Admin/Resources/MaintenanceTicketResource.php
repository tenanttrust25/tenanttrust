<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Components\ImageUpload;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Tenancy;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class MaintenanceTicketResource extends Resource
{
    protected static ?string $model = MaintenanceTicket::class;

    protected static ?string $navigationIcon = "heroicon-o-wrench-screwdriver";
    protected static ?string $navigationLabel = "Maintenance Tickets";
    protected static ?string $navigationGroup = "Operations";
    protected static ?string $pluralModelLabel = "Maintenance Tickets";
    protected static ?string $modelLabel = "Maintenance Ticket";
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $isCreate = fn (string $operation): bool => $operation === "create";
        $isEdit = fn (string $operation): bool => $operation === "edit";

        return $form
            ->schema([
                TextInput::make("ticket_number")
                    ->label("Ticket #")
                    ->disabled()
                    ->visible($isEdit),

                Select::make("tenant_id")
                    ->label("Tenant")
                    ->options(fn () => User::where("role", "tenant")
                        ->orderBy("name")
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [
                            $user->id => $user->name . ($user->email ? " — " . $user->email : ""),
                        ]))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $tenancy = $state
                            ? Tenancy::where("tenant_id", $state)->where("status", "active")->first()
                            : null;
                        $set("property_id", $tenancy?->property_id);
                        $set("property_unit_id", $tenancy?->property_unit_id);
                    })
                    ->visible($isCreate),

                Placeholder::make("tenant")
                    ->label("Tenant")
                    ->content(fn (?MaintenanceTicket $record): string => $record?->tenant?->name ?? "Not matched")
                    ->visible($isEdit),

                Select::make("property_id")
                    ->label("Property")
                    ->options(fn () => Property::orderBy("title")->pluck("title", "id"))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set("property_unit_id", null))
                    ->visible($isCreate),

                Select::make("property_unit_id")
                    ->label("Unit")
                    ->options(fn (Get $get) => PropertyUnit::where("property_id", $get("property_id"))
                        ->orderBy("unit")
                        ->pluck("unit", "id"))
                    ->searchable()
                    ->required()
                    ->visible($isCreate),

                Placeholder::make("property")
                    ->label("Property")
                    ->content(fn (?MaintenanceTicket $record): string => $record?->property
                        ? $record->property->title
                            . ($record->propertyUnit?->unit ? " — Unit " . $record->propertyUnit->unit : "")
                            . ", " . ($record->property->city ?? "")
                            . " " . ($record->property->province ?? "")
                        : "Not matched")
                    ->visible($isEdit),

                TextInput::make("title")
                    ->label("Title")
                    ->required()
                    ->maxLength(255)
                    ->disabled($isEdit),

                Textarea::make("description")
                    ->label("Description")
                    ->required()
                    ->rows(4)
                    ->disabled($isEdit),

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
                    ->required($isCreate),

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

                ImageUpload::make("photos")
                    ->label("Photos")
                    ->multiple()
                    ->image()
                    ->directory("ticket-photos")
                    ->disk('documents')
                    ->visibility('private')
                    ->maxSize(5120)
                    ->visible($isCreate),

                Select::make("status")
                    ->label("Status")
                    ->options([
                        "open" => "Open",
                        "in_progress" => "In Progress",
                        "resolved" => "Resolved",
                        "closed" => "Closed",
                    ])
                    ->required()
                    ->visible($isEdit),

                Select::make("vendor_id")
                    ->label("Vendor (directory)")
                    ->options(fn () => \App\Models\Vendor::orderBy("name")->pluck("name", "id"))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state) {
                            $set("vendor", \App\Models\Vendor::find($state)?->name);
                        }
                    })
                    ->visible($isEdit),

                TextInput::make("vendor")
                    ->label("Contractor / Vendor (name)")
                    ->maxLength(255)
                    ->visible($isEdit),

                TextInput::make("cost")
                    ->label("Repair Cost ($) — passed to owner at cost")
                    ->numeric()
                    ->prefix("$")
                    ->visible($isEdit),

                Section::make("Conversation with Tenant")
                    ->description("Replies are shared with the tenant and shown in their portal. Sending a reply notifies the tenant (push + email).")
                    ->schema([
                        Placeholder::make("thread")
                            ->label("")
                            ->content(function (?MaintenanceTicket $record): HtmlString {
                                if (! $record) {
                                    return new HtmlString('<span class="text-sm text-gray-500">—</span>');
                                }

                                $replies = $record->replies()->with("user")->orderBy("created_at")->get();

                                if ($replies->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-500 dark:text-gray-400">No replies yet.</p>');
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($replies as $reply) {
                                    $who = e($reply->user?->name ?? "System");
                                    $when = e(optional($reply->created_at)->format("M j, Y g:ia") ?? "");
                                    $msg = nl2br(e($reply->message));
                                    $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">'
                                        . '<div class="text-xs text-gray-500 dark:text-gray-400">' . $who . ' · ' . $when . '</div>'
                                        . '<div class="text-sm mt-1 text-gray-900 dark:text-gray-100">' . $msg . '</div>'
                                        . '</div>';
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                        Textarea::make("new_reply")
                            ->label("Add a Reply to Tenant")
                            ->rows(3)
                            ->placeholder("Type your reply to the tenant..."),
                    ])
                    ->visible($isEdit),
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
                TextColumn::make("vendor")
                    ->label("Vendor")
                    ->placeholder("—"),
                TextColumn::make("cost")
                    ->label("Repair Cost")
                    ->money("CAD")
                    ->placeholder("—"),
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
                DeleteAction::make()
                    ->label("Delete")
                    ->icon("heroicon-o-trash")
                    ->color("danger")
                    ->requiresConfirmation(),
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
            "create" => \App\Filament\Admin\Resources\MaintenanceTicketResource\Pages\CreateMaintenanceTicket::route("/create"),
            "edit" => \App\Filament\Admin\Resources\MaintenanceTicketResource\Pages\EditMaintenanceTicket::route("/{record}/edit"),
        ];
    }
}
