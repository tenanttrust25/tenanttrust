<?php

namespace App\Filament\Owner\Resources;

use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantFile;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = TenantFile::class;
    protected static ?string $navigationIcon = "heroicon-o-document";
    protected static ?string $navigationLabel = "Documents";
    protected static ?string $pluralLabel = "Documents";
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = "documents";

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $propertyIds = Property::where("owner_id", Auth::id())->pluck("id")->toArray();
        $tenantIds = Tenancy::whereIn("property_id", $propertyIds)->pluck("tenant_id")->toArray();
        return parent::getEloquentQuery()->whereIn("tenant_id", $tenantIds);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->label("Document Name")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("description")
                    ->label("Description")
                    ->limit(60),
                TextColumn::make("file_type")
                    ->label("Type")
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "lease" => "success",
                        "N1" => "warning",
                        "N4" => "danger",
                        default => "gray",
                    }),
                TextColumn::make("updated_at")
                    ->label("Uploaded")
                    ->date(),
            ])
            ->actions([
                Tables\Actions\Action::make("download")
                    ->label("Download")
                    ->icon("heroicon-o-arrow-down-tray")
                    ->color("primary")
                    ->url(fn (TenantFile $record): string => asset("storage/" . $record->file_path))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort("created_at", "desc");
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Owner\Resources\DocumentResource\Pages\ListDocuments::route("/"),
        ];
    }
}
