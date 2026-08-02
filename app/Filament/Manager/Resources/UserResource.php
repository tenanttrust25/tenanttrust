<?php

namespace App\Filament\Manager\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = "heroicon-o-user-plus";

    protected static ?string $navigationLabel = "Add Tenant";

    protected static ?string $modelLabel = "Tenant";

    protected static ?string $pluralModelLabel = "Tenants";

    protected static ?string $slug = "tenants";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make("Tenant Information")
                    ->schema([
                        Forms\Components\TextInput::make("name")
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make("email")
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make("phone")
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make("address")
                            ->maxLength(255)
                            ->label("Property Address"),
                        Forms\Components\TextInput::make("password")
                            ->password()
                            ->required(fn (string $operation): bool => $operation === "create")
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        Forms\Components\Hidden::make("role")
                            ->default("tenant"),
                    ]),

                Forms\Components\Section::make("Lease Documents")
                    ->description("Upload lease agreements, N1/N4 forms, or other tenant documents.")
                    ->visible(fn (string $operation): bool => $operation === "edit")
                    ->schema([
                        Forms\Components\Repeater::make("tenantFiles")
                            ->relationship("tenantFiles")
                            ->label("")
                            ->addActionLabel("Add Document")
                            ->cloneable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state["name"] ?? "New Document")
                            ->schema([
                                Forms\Components\FileUpload::make("file_path")
                                    ->label("File")
                                    ->directory("tenant-files")
                                    ->preserveFilenames()
                                    ->required()
                                    ->openable()
                                    ->downloadable()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make("name")
                                    ->label("Document Name")
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder("e.g. Signed Lease - June 2026"),
                                Forms\Components\Select::make("file_type")
                                    ->label("Document Type")
                                    ->options([
                                        "lease" => "Lease Agreement",
                                        "N1" => "N1 Form",
                                        "N4" => "N4 Form",
                                        "other" => "Other",
                                    ])
                                    ->default("lease")
                                    ->required(),
                                Forms\Components\Textarea::make("description")
                                    ->label("Notes (optional)")
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Hidden::make("uploaded_by")
                                    ->default(fn () => auth()->id()),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where("role", "tenant"))
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("email")
                    ->searchable(),
                Tables\Columns\TextColumn::make("phone")
                    ->searchable(),
                Tables\Columns\TextColumn::make("tenantFiles_count")
                    ->counts("tenantFiles")
                    ->label("Documents")
                    ->badge()
                    ->color("info"),
                Tables\Columns\TextColumn::make("created_at")
                    ->dateTime("M d, Y")
                    ->sortable()
                    ->label("Added"),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Manager\Resources\UserResource\Pages\ListUsers::route("/"),
            "create" => \App\Filament\Manager\Resources\UserResource\Pages\CreateUser::route("/create"),
            "edit" => \App\Filament\Manager\Resources\UserResource\Pages\EditUser::route("/{record}/edit"),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = User::where("role", "tenant")->count();
        return $count > 0 ? (string) $count : null;
    }
}
