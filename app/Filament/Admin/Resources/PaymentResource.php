<?php

namespace App\Filament\Admin\Resources;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = "heroicon-o-banknotes";
    protected static ?string $slug = "transactions";
    protected static ?string $navigationLabel = "Transactions";
    protected static ?string $navigationGroup = "Rent & Billing";
    protected static ?string $pluralModelLabel = "Transactions";
    protected static ?string $modelLabel = "Transaction";
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('invoice_id')
                ->label('Invoice')
                ->relationship('invoice', 'invoice_number')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('tenant_id')
                ->label('Tenant')
                ->relationship('tenant', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->label('Amount')
                ->numeric()
                ->required()
                ->prefix('$'),
            Forms\Components\Select::make('payment_method')
                ->label('Payment Method')
                ->options([
                    'cash' => 'Cash',
                    'cheque' => 'Cheque',
                    'interac' => 'Interac',
                    'credit_card' => 'Credit Card',
                    'bank_transfer' => 'Bank Transfer',
                ])
                ->required(),
            Forms\Components\TextInput::make('transaction_id')
                ->label('Transaction / Reference #')
                ->maxLength(255)
                ->nullable(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ])
                ->default('pending')
                ->required(),
            Forms\Components\DateTimePicker::make('paid_at')
                ->label('Payment Date')
                ->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("invoice.invoice_number")
                    ->label("Invoice #")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("amount")
                    ->label("Amount")
                    ->money("CAD")
                    ->sortable(),
                Tables\Columns\TextColumn::make("payment_method")
                    ->label("Method"),
                Tables\Columns\TextColumn::make("transaction_id")
                    ->label("Reference #")
                    ->searchable(),
                Tables\Columns\BadgeColumn::make("status")
                    ->label("Status")
                    ->colors([
                        "warning" => "pending",
                        "success" => "completed",
                        "danger" => "failed",
                    ]),
                Tables\Columns\TextColumn::make("paid_at")
                    ->label("Date")
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("status")
                    ->options([
                        "pending" => "Pending",
                        "completed" => "Completed",
                        "failed" => "Failed",
                    ]),
            ])
            ->defaultSort("paid_at", "desc");
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Admin\Resources\PaymentResource\Pages\ListPayments::route("/"),
            "create" => \App\Filament\Admin\Resources\PaymentResource\Pages\CreatePayment::route("/create"),
            "edit" => \App\Filament\Admin\Resources\PaymentResource\Pages\EditPayment::route("/{record}/edit"),
        ];
    }
}
