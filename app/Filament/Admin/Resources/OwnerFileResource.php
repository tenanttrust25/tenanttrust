<?php

namespace App\Filament\Admin\Resources;

use App\Models\OwnerFile;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OwnerFileResource extends Resource
{
    protected static ?string $model = OwnerFile::class;

    protected static ?string $navigationIcon = "heroicon-o-folder";
    protected static ?string $navigationLabel = "Client Documents";
    protected static ?string $navigationGroup = "People";
    protected static ?string $pluralModelLabel = "Client Documents";
    protected static ?string $modelLabel = "Client Document";
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make("owner_id")
                    ->label("Client")
                    ->relationship("owner", "name")
                    ->options(fn () => \App\Models\User::where("role", "owner")->orderBy("name")->pluck("name", "id"))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make("name")
                    ->label("Document Name")
                    ->required()
                    ->maxLength(255)
                    ->placeholder("e.g. Management Contract 2026"),
                Select::make("file_type")
                    ->label("Type")
                    ->options([
                        "contract" => "Contract",
                        "agreement" => "Agreement",
                        "invoice" => "Invoice",
                        "statement" => "Statement",
                        "correspondence" => "Correspondence",
                        "other" => "Other",
                    ])
                    ->default("other"),
                Textarea::make("description")
                    ->label("Description")
                    ->rows(2)
                    ->helperText("Optional notes to help identify this document"),
                FileUpload::make("file_path")
                    ->label("File (PDF recommended)")
                    ->directory("owner-files")
                    ->acceptedFileTypes([
                        "application/pdf",
                        "application/msword",
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    ])
                    ->maxSize(51200)
                    ->required()
                    ->helperText("PDF, DOC or DOCX — up to 50MB"),
                \Filament\Forms\Components\Hidden::make("uploaded_by")
                    ->default(fn () => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("owner.name")
                    ->label("Client")
                    ->searchable()
                    ->sortable()
                    ->weight("bold"),
                TextColumn::make("name")
                    ->label("Document")
                    ->searchable()
                    ->sortable(),
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
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make("uploader.name")
                    ->label("Uploaded By")
                    ->toggleable(),
                TextColumn::make("created_at")
                    ->label("Uploaded")
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make("owner_id")
                    ->label("Client")
                    ->options(fn () => \App\Models\User::where("role", "owner")->orderBy("name")->pluck("name", "id")),
            ])
            ->actions([
                Action::make("open")
                    ->label("Open")
                    ->icon("heroicon-o-arrow-top-right-on-square")
                    ->color("primary")
                    ->url(fn (OwnerFile $record): string => Storage::disk("public")->url($record->file_path))
                    ->openUrlInNewTab(),
                Action::make("download")
                    ->label("Download")
                    ->icon("heroicon-o-arrow-down-tray")
                    ->color("success")
                    ->action(fn (OwnerFile $record) => Storage::disk("public")->download($record->file_path)),
                EditAction::make(),
                DeleteAction::make(),
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
            "index" => \App\Filament\Admin\Resources\OwnerFileResource\Pages\ListOwnerFiles::route("/"),
            "create" => \App\Filament\Admin\Resources\OwnerFileResource\Pages\CreateOwnerFile::route("/create"),
            "edit" => \App\Filament\Admin\Resources\OwnerFileResource\Pages\EditOwnerFile::route("/{record}/edit"),
        ];
    }
}
