<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use App\Models\TenantFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class TenantFilesRelationManager extends RelationManager
{
    protected static string $relationship = "tenantFiles";
    protected static ?string $title = "Tenant Files";
    protected static ?string $recordTitleAttribute = "description";

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make("description")->required(),
            Forms\Components\Select::make("file_type")->options([
                "lease" => "Lease", "N1" => "N1", "N4" => "N4", "other" => "Other",
            ]),
            Forms\Components\FileUpload::make("file_path")->directory("tenant-files"),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("description"),
                Tables\Columns\TextColumn::make("file_type"),
                Tables\Columns\TextColumn::make("created_at")->dateTime(),
            ])
            ->filters([])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\Action::make("view_file")
                    ->label("View")
                    ->icon("heroicon-o-eye")
                    ->color("primary")
                    ->url(fn (TenantFile $record): ?string => $record->file_path
                        ? Storage::disk("public")->url($record->file_path)
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (TenantFile $record): bool => (bool) $record->file_path),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}
