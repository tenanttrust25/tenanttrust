<?php

namespace App\Filament\Tenant\Resources;

use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = "heroicon-o-credit-card";
    protected static ?string $navigationLabel = "Transactions";
    protected static ?string $pluralModelLabel = "Transactions";
    protected static ?string $modelLabel = "Transaction";
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $tenantIds = $user->getUnitTenantIds();
        return parent::getEloquentQuery()->whereIn("tenant_id", $tenantIds);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
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
                TextColumn::make("payment_method")
                    ->label("Method")
                    ->formatStateUsing(fn(string $state): string => $state === "interac" ? "Interac" : "Credit Card"),
                BadgeColumn::make("status")
                    ->label("Status")
                    ->colors([
                        "warning" => "pending",
                        "success" => "completed",
                        "danger" => "failed",
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                TextColumn::make("paid_at")
                    ->label("Paid At")
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort("paid_at", "desc");
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Tenant\Resources\PaymentResource\Pages\ListPayments::route("/"),
        ];
    }
}
