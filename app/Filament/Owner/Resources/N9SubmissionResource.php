<?php

namespace App\Filament\Owner\Resources;

use App\Models\N9Submission;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class N9SubmissionResource extends Resource
{
    protected static ?string $model = N9Submission::class;

    protected static ?string $navigationIcon = "heroicon-o-paper-airplane";
    protected static ?string $navigationLabel = "Move-Out Notices";
    protected static ?string $pluralModelLabel = "Move-Out Notices";
    protected static ?string $modelLabel = "Move-Out Notice";
    protected static ?string $navigationGroup = 'Properties';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Clients only see N9 notices for their own buildings.
        return parent::getEloquentQuery()->where("owner_id", Auth::id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("unit_address")
                    ->label("Rental Unit")
                    ->searchable()
                    ->limit(30)
                    ->sortable(),
                TextColumn::make("termination_date")
                    ->label("Tenancy Ends")
                    ->date("M d, Y")
                    ->sortable(),
                TextColumn::make("tenant.phone")
                    ->label("Tenant Phone")
                    ->searchable(),
                TextColumn::make("created_at")
                    ->label("Submitted")
                    ->dateTime("M d, Y g:i A")
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make("open_pdf")
                    ->label("View Notice (PDF)")
                    ->icon("heroicon-o-document-arrow-down")
                    ->color("success")
                    ->url(fn (N9Submission $record): ?string => $record->file_path ? route('files.n9', $record) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (N9Submission $record): bool => (bool) $record->file_path),
                Tables\Actions\Action::make("download_pdf")
                    ->label("Download")
                    ->icon("heroicon-o-arrow-down-tray")
                    ->color("primary")
                    ->action(fn (N9Submission $record) => $record->file_path ? Storage::disk('documents')->download($record->file_path) : null)
                    ->visible(fn (N9Submission $record): bool => (bool) $record->file_path),
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
            'index' => \App\Filament\Owner\Resources\N9SubmissionResource\Pages\ListN9Submissions::route('/'),
        ];
    }
}
