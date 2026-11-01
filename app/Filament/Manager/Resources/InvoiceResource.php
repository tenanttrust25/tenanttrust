<?php

namespace App\Filament\Manager\Resources;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Property;
use App\Models\Tenancy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = "heroicon-o-currency-dollar";
    protected static ?string $navigationLabel = "Invoices / Payments";
    protected static ?string $navigationGroup = "Rent & Billing";
    protected static ?string $pluralModelLabel = "Invoices";
    protected static ?string $modelLabel = "Invoice";
    protected static ?string $slug = "invoices";
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === Row 1: Company Info (left) + Status/Dates (right) ===
                Forms\Components\Grid::make(2)
                    ->schema([
                        // LEFT: Company logo + business info
                        Forms\Components\View::make("filament.manager.invoice.invoice-header-left"),

                        // RIGHT: Status box + dates
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make("status")
                                    ->label("Status")
                                    ->options([
                                        "pending" => "Unpaid",
                                        "paid" => "Paid",
                                        "overdue" => "Overdue",
                                        "cancelled" => "Cancelled",
                                    ])
                                    ->default("pending")
                                    ->required(),

                                Forms\Components\Placeholder::make("invoice_date_display")
                                    ->label("Invoice Date")
                                    ->content(fn () => now()->format("F j, Y")),

                                Forms\Components\TextInput::make("invoice_number")
                                    ->label("Invoice #")
                                    ->default(fn () => "INV-" . date("Y") . "-" . strtoupper(Str::random(8)))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\DatePicker::make("due_date")
                                    ->label("Due Date")
                                    ->default(fn () => now()->addMonth()->startOfMonth())
                                    ->required(),
                            ]),
                    ]),

                // === Row 2: Tenant, Property, Amount, Description ===
                Forms\Components\Section::make("Tenant & Property")
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make("tenant_id")
                            ->label("Tenant")
                            ->options(fn () => User::where("role", "tenant")->pluck("name", "id"))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if (!$state) {
                                    $set("property_id", null);
                                    $set("amount", null);
                                    $set("description", null);
                                    return;
                                }

                                // Try finding an active tenancy first
                                $tenancy = Tenancy::where("tenant_id", $state)
                                    ->where("status", "active")
                                    ->first();

                                if ($tenancy) {
                                    $set("property_id", $tenancy->property_id);
                                    $set("amount", $tenancy->rent_amount);
                                    $prop = $tenancy->property;
                                    $unitText = $tenancy->unit ? "Unit {$tenancy->unit} - " : "";
                                    if ($prop) {
                                        $set("description", "Monthly Rent for {$unitText}{$prop->title}, {$prop->address}, {$prop->city}");
                                    }
                                } else {
                                    // No active tenancy - try any tenancy for property reference
                                    $anyTenancy = Tenancy::where("tenant_id", $state)->first();
                                    if ($anyTenancy && $anyTenancy->property) {
                                        $prop = $anyTenancy->property;
                                        $set("property_id", $anyTenancy->property_id);
                                        $set("amount", $anyTenancy->rent_amount);
                                        $unitText = $anyTenancy->unit ? "Unit {$anyTenancy->unit} - " : "";
                                        $set("description", "Monthly Rent for {$unitText}{$prop->title}, {$prop->address}, {$prop->city}");
                                    }
                                    // If no tenancy at all, fields stay blank for manual entry
                                }
                            })
                            ->required(),

                        Forms\Components\Select::make("property_id")
                            ->label("Property / Unit")
                            ->options(fn () => Property::pluck("title", "id"))
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\TextInput::make("amount")
                            ->label("Amount ($)")
                            ->numeric()
                            ->prefix("$")
                            ->required()
                            ->minValue(0),

                        Forms\Components\Textarea::make("description")
                            ->label("Description")
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // === Payments Section (edit only) ===
                Forms\Components\Section::make("Payments Received")
                    ->description("Record or view payments for this invoice.")
                    ->visible(fn (string $operation): bool => $operation === "edit")
                    ->schema([
                        Forms\Components\Repeater::make("payments")
                            ->relationship("payments")
                            ->label("")
                            ->addActionLabel("Record Payment")
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => "Payment $" . number_format($state["amount"] ?? 0, 2) . " — " . ($state["payment_method"] ?? ""))
                            ->schema([
                                Forms\Components\Select::make("tenant_id")
                                    ->label("Paying Tenant")
                                    ->relationship("tenant", "name")
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make("amount")
                                    ->label("Amount ($)")
                                    ->numeric()
                                    ->prefix("$")
                                    ->required()
                                    ->minValue(0),
                                Forms\Components\Select::make("payment_method")
                                    ->label("Payment Method")
                                    ->options([
                                        "interac" => "Interac E-Transfer",
                                        "credit" => "Credit Card",
                                        "cash" => "Cash",
                                        "cheque" => "Cheque",
                                        "other" => "Other",
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make("transaction_id")
                                    ->label("Transaction ID / Reference")
                                    ->maxLength(255),
                                Forms\Components\DateTimePicker::make("paid_at")
                                    ->label("Payment Date")
                                    ->default(now()),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("invoice_number")
                    ->label("Invoice #")
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
                Tables\Columns\TextColumn::make("amount")
                    ->label("Amount")
                    ->money("CAD")
                    ->sortable(),
                Tables\Columns\TextColumn::make("status")
                    ->label("Status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "paid" => "success",
                        "pending" => "warning",
                        "overdue" => "danger",
                        "cancelled" => "gray",
                        default => "gray",
                    })
                    ->formatStateUsing(fn (string $state): string => $state === "pending" ? "Unpaid" : ucfirst($state)),
                Tables\Columns\TextColumn::make("payments_count")
                    ->label("Payments")
                    ->counts("payments")
                    ->badge()
                    ->color("info"),
                Tables\Columns\TextColumn::make("due_date")
                    ->label("Due Date")
                    ->date("M d, Y")
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make("status")
                    ->options([
                        "pending" => "Unpaid",
                        "paid" => "Paid",
                        "overdue" => "Overdue",
                        "cancelled" => "Cancelled",
                    ]),
                SelectFilter::make("tenant_id")
                    ->label("Tenant")
                    ->relationship("tenant", "name")
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label("View / Edit"),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort("created_at", "desc");
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Manager\Resources\InvoiceResource\Pages\ListInvoices::route("/"),
            "create" => \App\Filament\Manager\Resources\InvoiceResource\Pages\CreateInvoice::route("/create"),
            "edit" => \App\Filament\Manager\Resources\InvoiceResource\Pages\EditInvoice::route("/{record}/edit"),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $overdue = Invoice::where("status", "overdue")->count();
        return $overdue > 0 ? (string) $overdue : null;
    }
}
