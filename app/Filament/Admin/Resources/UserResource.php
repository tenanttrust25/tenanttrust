<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = "heroicon-o-users";
    protected static ?string $navigationLabel = "Users";
    protected static ?string $pluralModelLabel = "Users";
    protected static ?string $modelLabel = "User";
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereIn("role", ["manager", "owner", "admin", "tenant"]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make("name")
                    ->label("Full Name")
                    ->required(fn (string $operation): bool => $operation === "create")
                    ->maxLength(255),

                Forms\Components\TextInput::make("email")
                    ->label("Email Address")
                    ->email()
                    ->required(fn (string $operation): bool => $operation === "create")
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make("phone")
                    ->label("Phone Number")
                    ->tel()
                    ->required(fn (string $operation): bool => $operation === "create")
                    ->maxLength(20),

                Forms\Components\Select::make("role")
                    ->label("Role")
                    ->options([
                        "manager" => "Property Manager",
                        "owner" => "Client (Owner)",
                        "tenant" => "Tenant",
                    ])
                    ->required(fn (string $operation): bool => $operation === "create")
                    ->default("owner"),

                Forms\Components\Textarea::make("address")
                    ->label("Address")
                    ->required(fn (string $operation): bool => $operation === "create")
                    ->rows(2)
                    ->maxLength(500),

                Forms\Components\DatePicker::make("start_date")
                    ->label("Start Date")
                    ->native(false)
                    ->displayFormat("M d, Y"),

                Forms\Components\DatePicker::make("end_date")
                    ->label("End Date")
                    ->native(false)
                    ->displayFormat("M d, Y"),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->label("Name")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("email")
                    ->label("Email")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make("phone")
                    ->label("Phone")
                    ->searchable(),
                Tables\Columns\TextColumn::make("role")
                    ->label("Role")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "admin" => "danger",
                        "manager" => "warning",
                        "owner" => "info",
                        default => "gray",
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        "admin" => "Admin",
                        "manager" => "Property Manager",
                        "owner" => "Client",
                        "tenant" => "Tenant",
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make("created_at")
                    ->label("Created")
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make("start_date")
                    ->label("Start Date")
                    ->date("M d, Y")
                    ->placeholder("—")
                    ->sortable(),
                Tables\Columns\TextColumn::make("end_date")
                    ->label("End Date")
                    ->date("M d, Y")
                    ->placeholder("—")
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make("role")
                    ->options([
                        "manager" => "Property Manager",
                        "owner" => "Client",
                        "tenant" => "Tenant",
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort("name", "asc");
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Admin\Resources\UserResource\Pages\ListUsers::route("/"),
            "create" => \App\Filament\Admin\Resources\UserResource\Pages\CreateUser::route("/create"),
            "edit" => \App\Filament\Admin\Resources\UserResource\Pages\EditUser::route("/{record}/edit"),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\UserResource\RelationManagers\InvoicesRelationManager::class,
            \App\Filament\Admin\Resources\UserResource\RelationManagers\PaymentsRelationManager::class,
            \App\Filament\Admin\Resources\UserResource\RelationManagers\TenantFilesRelationManager::class,
        ];
    }
}
