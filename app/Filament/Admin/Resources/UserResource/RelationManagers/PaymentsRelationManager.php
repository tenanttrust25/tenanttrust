<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = "payments";
    protected static ?string $title = "Payments / Transactions";
    protected static ?string $recordTitleAttribute = "transaction_id";

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
                        "etransfer" => "🏦 E-Transfer",
                        "cash" => "💵 Cash",
                        "cheque" => "📝 Cheque",
                        "debit" => "💳 Debit",
                        default => ucfirst($state ?? "N/A"),
                    }),
                TextColumn::make("transaction_id")
                    ->label("Transaction ID")
                    ->searchable(),
                TextColumn::make("paid_at")
                    ->label("Paid At")
                    ->dateTime()
                    ->sortable(),

            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort("paid_at", "desc");
    }
}
