<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = "payments";
    protected static ?string $title = "Payments / Transactions";
    protected static ?string $recordTitleAttribute = "transaction_id";

    /**
     * Payment methods seen in the live data plus the common options.
     * Keeps historical values selectable so editing never blanks a method.
     */
    public static function paymentMethodOptions(): array
    {
        return [
            "cash" => "💵 Cash",
            "cheque" => "📝 Cheque",
            "interac" => "🏦 Interac",
            "etransfer" => "🏦 E-Transfer",
            "bank_transfer" => "🏦 Bank Transfer",
            "credit_card" => "💳 Credit Card",
            "card" => "💳 Card",
            "debit" => "💳 Debit",
            "pre_authorized_debit" => "🔁 Pre-Authorized Debit",
            "deposit" => "🔒 Deposit",
            "credit" => "➕ Credit",
            "credit_note" => "🧾 Credit Note",
            "debit_note" => "🧾 Debit Note",
        ];
    }

    public function form(Form $form): Form
    {
        $ownerId = $this->getOwnerRecord()->getKey();

        return $form->schema([
            Forms\Components\Select::make("invoice_id")
                ->label("Invoice")
                ->relationship(
                    "invoice",
                    "invoice_number",
                    fn (Builder $query) => $query->where("tenant_id", $ownerId)
                )
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make("amount")
                ->label("Amount")
                ->numeric()
                ->required()
                ->prefix("$"),

            Forms\Components\Select::make("payment_method")
                ->label("Payment Method")
                ->options(self::paymentMethodOptions())
                ->required(),

            Forms\Components\TextInput::make("transaction_id")
                ->label("Transaction / Reference #")
                ->maxLength(255)
                ->nullable(),

            Forms\Components\Select::make("status")
                ->label("Status")
                ->options([
                    "pending" => "Pending",
                    "completed" => "Completed",
                    "failed" => "Failed",
                    "refunded" => "Refunded",
                ])
                ->default("completed")
                ->required(),

            Forms\Components\DateTimePicker::make("paid_at")
                ->label("Payment Date")
                ->default(now())
                ->seconds(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("invoice.invoice_number")
                    ->label("Invoice #")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("amount")
                    ->label("Amount")
                    ->money("CAD")
                    ->sortable(),
                BadgeColumn::make("status")
                    ->label("Status")
                    ->colors([
                        "success" => "completed",
                        "warning" => "pending",
                        "danger" => "failed",
                        "gray" => "refunded",
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make("payment_method")
                    ->label("Method")
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        "credit_card" => "💳 Credit Card",
                        "card" => "💳 Card",
                        "etransfer" => "🏦 E-Transfer",
                        "interac" => "🏦 Interac",
                        "bank_transfer" => "🏦 Bank Transfer",
                        "cash" => "💵 Cash",
                        "cheque" => "📝 Cheque",
                        "debit" => "💳 Debit",
                        "pre_authorized_debit" => "🔁 Pre-Authorized Debit",
                        default => ucfirst(str_replace("_", " ", $state ?? "N/A")),
                    }),
                TextColumn::make("transaction_id")
                    ->label("Transaction ID")
                    ->searchable()
                    ->placeholder("—"),
                TextColumn::make("paid_at")
                    ->label("Paid At")
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("status")
                    ->options([
                        "pending" => "Pending",
                        "completed" => "Completed",
                        "failed" => "Failed",
                        "refunded" => "Refunded",
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label("Add Payment")
                    ->icon("heroicon-o-plus")
                    ->mutateFormDataUsing(function (array $data): array {
                        $data["tenant_id"] = $this->getOwnerRecord()->getKey();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort("paid_at", "desc");
    }
}
