<?php

namespace App\Filament\Admin\Resources;

use App\Models\Form;
use Filament\Forms\Form as FilamentForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Illuminate\Support\Facades\Storage;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static ?string $navigationIcon = "heroicon-o-clipboard-document-list";
    protected static ?string $navigationLabel = "Forms";
    protected static ?string $pluralModelLabel = "Forms";
    protected static ?string $modelLabel = "Form";
    protected static ?int $navigationSort = 3;

    public static function form(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->label("Form Name")
                    ->required()
                    ->maxLength(255),
                Select::make("category")
                    ->label("Category")
                    ->options([
                        "lease" => "Lease",
                        "n1" => "N1",
                        "n4" => "N4",
                        "n5" => "N5",
                        "ltb" => "LTB",
                        "application" => "Application",
                        "inspection" => "Inspection",
                        "receipt" => "Receipt",
                        "notice" => "Notice",
                        "other" => "Other",
                    ])
                    ->searchable()
                    ->default("other"),
                Textarea::make("description")
                    ->label("Description")
                    ->rows(3)
                    ->helperText("Optional notes to help you identify this form"),
                FileUpload::make("file_path")
                    ->label("File (PDF recommended)")
                    ->directory("forms")
                    ->acceptedFileTypes([
                        "application/pdf",
                        "application/msword",
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    ])
                    ->maxSize(51200)
                    ->required()
                    ->helperText("PDF, DOC or DOCX — up to 50MB"),
                Toggle::make("visible_to_tenants")
                    ->label("Visible to Tenants")
                    ->helperText("When ON, tenants can open/download this form from their portal. When OFF, admin-only.")
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->label("Name")
                    ->searchable()
                    ->sortable()
                    ->weight("bold"),
                BadgeColumn::make("category")
                    ->label("Category")
                    ->colors([
                        "blue" => "lease",
                        "orange" => ["n1", "n4", "n5", "ltb", "notice"],
                        "green" => ["application", "inspection"],
                        "gray" => ["receipt", "other"],
                    ])
                    ->formatStateUsing(fn(?string $state): string => $state ? strtoupper($state) : "—"),
                TextColumn::make("description")
                    ->label("Description")
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make("created_at")
                    ->label("Uploaded")
                    ->date()
                    ->sortable(),
                BadgeColumn::make("visible_to_tenants")
                    ->label("Tenant Access")
                    ->colors([
                        "success" => true,
                        "gray" => false,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? "Visible" : "Admin Only"),
            ])
            ->filters([
                SelectFilter::make("category")
                    ->options([
                        "lease" => "Lease",
                        "n1" => "N1",
                        "n4" => "N4",
                        "n5" => "N5",
                        "ltb" => "LTB",
                        "application" => "Application",
                        "inspection" => "Inspection",
                        "receipt" => "Receipt",
                        "notice" => "Notice",
                        "other" => "Other",
                    ]),
            ])
            ->defaultSort("created_at", "desc")
            ->actions([
                Action::make("open")
                    ->label("Open")
                    ->icon("heroicon-o-arrow-top-right-on-square")
                    ->color("primary")
                    ->url(fn(Form $record): string => $record->url())
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->label("Edit")
                    ->icon("heroicon-o-pencil-square")
                    ->color("gray"),
                Action::make("download")
                    ->label("Adobe")
                    ->icon("heroicon-o-arrow-down-tray")
                    ->color("success")
                    ->action(function (Form $record) {
                        return Storage::disk("public")->download($record->file_path);
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Admin\Resources\FormResource\Pages\ListForms::route("/"),
            "create" => \App\Filament\Admin\Resources\FormResource\Pages\CreateForm::route("/create"),
            "edit" => \App\Filament\Admin\Resources\FormResource\Pages\EditForm::route("/{record}/edit"),
        ];
    }
}
