<?php

namespace App\Filament\Owner\Resources;

use App\Models\OwnerFile;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = OwnerFile::class;
    protected static ?string $navigationIcon = "heroicon-o-document";
    protected static ?string $navigationLabel = "Documents";
    protected static ?string $pluralLabel = "Documents";
    protected static ?string $navigationGroup = 'Documents';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = "documents";

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Clients only see documents the admin has on them (contracts, agreements, etc.).
        return parent::getEloquentQuery()->where("owner_id", Auth::id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->label("Document Name")
                    ->searchable()
                    ->sortable()
                    ->weight("bold"),
                BadgeColumn::make("file_type")
                    ->label("Type")
                    ->colors([
                        "blue" => "contract",
                        "purple" => "agreement",
                        "green" => "invoice",
                        "orange" => "statement",
                        "gray" => ["correspondence", "other"],
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : "—"),
                TextColumn::make("description")
                    ->label("Description")
                    ->limit(60),
                TextColumn::make("created_at")
                    ->label("Uploaded")
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make("open")
                    ->label("Open")
                    ->icon("heroicon-o-arrow-top-right-on-square")
                    ->color("primary")
                    ->url(fn (OwnerFile $record): string => Storage::disk("public")->url($record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make("download")
                    ->label("Download")
                    ->icon("heroicon-o-arrow-down-tray")
                    ->color("success")
                    ->action(fn (OwnerFile $record) => Storage::disk("public")->download($record->file_path)),
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
            "index" => \App\Filament\Owner\Resources\DocumentResource\Pages\ListDocuments::route("/"),
        ];
    }
}
