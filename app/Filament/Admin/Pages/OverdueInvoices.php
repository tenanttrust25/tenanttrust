<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class OverdueInvoices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Overdue';
    protected static ?string $title = 'Overdue Invoices';
    protected static ?string $slug = 'overdue-invoices';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query()->overdue())
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.title')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'danger' => 'overdue',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->actions([
                Action::make('edit')
                    ->label('View / Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Invoice $record) => InvoiceResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public function getView(): string
    {
        return 'filament.admin.pages.overdue-invoices';
    }
}
