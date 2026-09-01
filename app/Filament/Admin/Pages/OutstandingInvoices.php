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

class OutstandingInvoices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Outstanding';
    protected static ?string $title = 'Outstanding Invoices';
    protected static ?string $slug = 'outstanding-invoices';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query()->whereIn('status', ['pending', 'overdue'])
                ->withSum(['payments as paid_sum' => fn ($q) => $q->where('status', 'completed')], 'amount'))
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
                    ->label('Invoice Amount')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('paid_sum')
                    ->label('Paid')
                    ->money('CAD')
                    ->sortable(),
                TextColumn::make('outstanding')
                    ->label('Outstanding')
                    ->money('CAD')
                    ->state(fn ($record) => max(0, (float) $record->amount - (float) ($record->paid_sum ?? 0)))
                    ->color(fn ($record) => ((float) $record->amount - (float) ($record->paid_sum ?? 0)) > 0 ? 'danger' : 'success'),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
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
        return 'filament.admin.pages.outstanding-invoices';
    }
}
