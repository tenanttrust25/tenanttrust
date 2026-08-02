<?php

namespace App\Filament\Tenant\Resources;

use App\Models\TenantFile;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

class TenantFileResource extends Resource
{
    protected static ?string $model = TenantFile::class;

    protected static ?string $navigationIcon = "heroicon-o-document-text";
    protected static ?string $navigationLabel = "Your Files";
    protected static ?string $pluralModelLabel = "Files";
    protected static ?string $modelLabel = "File";
    protected static ?int $navigationSort = 4;

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
                TextColumn::make("name")
                    ->label("Name")
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make("file_type")
                    ->label("Type")
                    ->colors([
                        "blue" => "lease",
                        "orange" => "N1",
                        "red" => "N4",
                        "gray" => "other",
                    ])
                    ->formatStateUsing(fn(string $state): string => strtoupper($state)),
                TextColumn::make("created_at")
                    ->label("Uploaded")
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Action::make("download")
                    ->label("Download")
                    ->icon("heroicon-o-arrow-down-tray")
                    ->color("primary")
                    ->url(fn(TenantFile $record): string => asset("storage/" . $record->file_path))
                    ->openUrlInNewTab(),
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
            "index" => \App\Filament\Tenant\Resources\TenantFileResource\Pages\ListTenantFiles::route("/"),
        ];
    }
}
