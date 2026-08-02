<?php

namespace App\Filament\Admin\Resources\InvoiceResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = "payments";
    protected static ?string $title = "Transactions";
    protected static ?string $recordTitleAttribute = "transaction_id";

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("transaction_id")
                    ->label("Reference #")
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
                        "etransfer", "interac" => "🏦 E-Transfer",
                        "cash" => "💵 Cash",
                        "cheque" => "📝 Cheque",
                        "debit" => "💳 Debit",
                        "bank_transfer" => "🏦 Bank Transfer",
                        "credit_note" => "📄 Credit Note",
                        "debit_note" => "📝 Debit Note",
                        default => ucfirst($state ?? "N/A"),
                    }),
                TextColumn::make("paid_at")
                    ->label("Date")
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
