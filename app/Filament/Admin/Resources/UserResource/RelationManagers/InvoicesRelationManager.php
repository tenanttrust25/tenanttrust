<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = "invoices";
    protected static ?string $title = "Invoices";
    protected static ?string $recordTitleAttribute = "invoice_number";

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("invoice_number")
                    ->label("Invoice #")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("propertyUnit.property.title")
                    ->label("Property")
                    ->sortable(),
                TextColumn::make("propertyUnit.unit")
                    ->label("Unit")
                    ->sortable(),
                TextColumn::make("amount")
                    ->label("Amount")
                    ->money("CAD")
                    ->sortable(),
                BadgeColumn::make("status")
                    ->label("Status")
                    ->colors([
                        "warning" => "pending",
                        "success" => "paid",
                        "danger" => "overdue",
                        "gray" => "cancelled",
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make("due_date")
                    ->label("Due Date")
                    ->date()
                    ->sortable(),
                TextColumn::make("created_at")
                    ->label("Created")
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn ($record): string => route("filament.admin.resources.invoices.edit", $record)),
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => route("filament.admin.resources.invoices.edit", $record)),
            ])
            ->bulkActions([])
            ->defaultSort("created_at", "desc");
    }
}
