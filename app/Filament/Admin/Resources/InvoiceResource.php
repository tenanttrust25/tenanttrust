<?php

namespace App\Filament\Admin\Resources;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Services\InvoiceCombineService;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = "heroicon-o-currency-dollar";
    protected static ?string $navigationLabel = "Invoices";
    protected static ?string $navigationGroup = "Rent & Billing";
    protected static ?string $pluralModelLabel = "Invoices";
    protected static ?string $modelLabel = "Invoice";
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make("property_unit_id")
                    ->label("Property Unit")
                    ->relationship("propertyUnit", "unit", fn($query) => $query->whereNotNull('id'))
                    ->getOptionLabelFromRecordUsing(fn(PropertyUnit $record) =>
                        $record->property?->title . ' — Unit ' . $record->unit
                    )
                    ->searchable()
                    ->preload()
                    ->optionsLimit(500)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $unit = PropertyUnit::with('property', 'tenants.tenant')->find($state);
                            if ($unit) {
                                $set('property_id', $unit->property_id);
                                $firstTenant = $unit->tenants()->where('status', 'active')->first()?->tenant;
                                if ($firstTenant) {
                                    $set('tenant_id', $firstTenant->id);
                                }
                            }
                        }
                    }),
                Select::make("tenant_id")
                    ->label("Primary Tenant")
                    ->relationship("tenant", "name")
                    ->searchable()
                    ->preload()
                    ->optionsLimit(500)
                    ->required(),
                Select::make("property_id")
                    ->label("Property")
                    ->relationship("property", "title")
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make("invoice_number")
                    ->label("Invoice #")
                    ->default("INV-" . date("Y") . "-" . strtoupper(Str::random(6)))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make("type")
                    ->label("Type")
                    ->options([
                        "rent" => "Rent",
                        "parking" => "Parking",
                        "other" => "Other",
                    ])
                    ->default("rent")
                    ->required(),
                TextInput::make("amount")
                    ->label("Amount")
                    ->numeric()
                    ->prefix("$")
                    ->required()
                    ->minValue(0),
                Textarea::make("description")
                    ->label("Description")
                    ->rows(3),
                DatePicker::make("due_date")
                    ->label("Due Date")
                    ->required(),
                Select::make("status")
                    ->label("Status")
                    ->options([
                        "pending" => "Pending",
                        "paid" => "Paid",
                        "overdue" => "Overdue",
                        "cancelled" => "Cancelled",
                    ])
                    ->default("pending")
                    ->required(),
                FileUpload::make("file_path")
                    ->label("PDF File")
                    ->directory("invoices")
                    ->acceptedFileTypes(["application/pdf"])
                    ->maxSize(10240),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("invoice_number")
                    ->label("Invoice #")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("type")
                    ->label("Type")
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'rent'))
                    ->color(fn ($state) => match ($state) {
                        'rent' => 'success',
                        'parking' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("property.title")
                    ->label("Property")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("amount")
                    ->label("Amount")
                    ->money("CAD")
                    ->sortable(),
                BadgeColumn::make("status")
                    ->label("Status")
                    ->sortable()
                    ->colors([
                        "warning" => "pending",
                        "success" => "paid",
                        "danger" => "overdue",
                        "gray" => "cancelled",
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                TextColumn::make("due_date")
                    ->label("Due Date")
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make("status")
                    ->options([
                        "pending" => "Pending",
                        "paid" => "Paid",
                        "overdue" => "Overdue",
                        "cancelled" => "Cancelled",
                    ]),
            ])
            ->bulkActions([
                InvoiceCombineService::bulkAction(),
            ])
            ->defaultSort("created_at", "desc")
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\InvoiceResource\RelationManagers\InvoicesRelationManager::class,
            \App\Filament\Admin\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Admin\Resources\InvoiceResource\Pages\ListInvoices::route("/"),
            "create" => \App\Filament\Admin\Resources\InvoiceResource\Pages\CreateInvoice::route("/create"),
            "edit" => \App\Filament\Admin\Resources\InvoiceResource\Pages\EditInvoice::route("/{record}/edit"),
        ];
    }
}
