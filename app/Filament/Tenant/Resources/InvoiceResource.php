<?php

namespace App\Filament\Tenant\Resources;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = "heroicon-o-currency-dollar";
    protected static ?string $navigationLabel = "Invoices";
    protected static ?string $pluralModelLabel = "Invoices";
    protected static ?string $modelLabel = "Invoice";
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $tenantIds = $user->getUnitTenantIds();
        return parent::getEloquentQuery()->whereIn("tenant_id", $tenantIds);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("invoice_number")
                    ->label("Invoice #")
                    ->disabled(),
                TextInput::make("amount")
                    ->label("Amount")
                    ->prefix("$")
                    ->disabled(),
                TextInput::make("description")
                    ->label("Description")
                    ->disabled(),
                DatePicker::make("due_date")
                    ->label("Due Date")
                    ->disabled(),
                Select::make("status")
                    ->label("Status")
                    ->options([
                        "pending" => "Pending",
                        "paid" => "Paid",
                        "overdue" => "Overdue",
                        "cancelled" => "Cancelled",
                    ])
                    ->disabled(),
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
                TextColumn::make("property.title")
                    ->label("Property")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("amount")
                    ->label("Amount")
                    ->money("CAD")
                    ->sortable(),
                TextColumn::make("due_date")
                    ->label("Due Date")
                    ->date()
                    ->sortable(),
                TextColumn::make("status")
                    ->label("Status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "paid" => "success",
                        "pending" => "warning",
                        "overdue" => "danger",
                        "cancelled" => "gray",
                        default => "gray",
                    })
                    ->formatStateUsing(fn(string $state): string => $state === "pending" ? "Unpaid" : ucfirst($state)),
                TextColumn::make("created_at")
                    ->label("Created")
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make("status")
                    ->options([
                        "pending" => "Unpaid",
                        "paid" => "Paid",
                        "overdue" => "Overdue",
                        "cancelled" => "Cancelled",
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->label("View Invoice"),

                Action::make("pay")
                    ->label("Pay Now")
                    ->icon("heroicon-o-credit-card")
                    ->color("success")
                    ->visible(fn(Invoice $record): bool => in_array($record->status, ["pending", "overdue"]))
                    ->url(fn(Invoice $record): string => self::getUrl("pay", ["record" => $record])),
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
            "index" => \App\Filament\Tenant\Resources\InvoiceResource\Pages\ListInvoices::route("/"),
            "view" => \App\Filament\Tenant\Resources\InvoiceResource\Pages\ViewInvoice::route("/{record}"),
            "pay" => \App\Filament\Tenant\Resources\InvoiceResource\Pages\PayInvoice::route("/{record}/pay"),
        ];
    }
}
