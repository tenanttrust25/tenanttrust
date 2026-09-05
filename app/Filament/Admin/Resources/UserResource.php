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
            ->whereIn("role", ["manager", "owner", "admin", "tenant", "previous_tenant"]);
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
                        "previous_tenant" => "Previous Tenant",
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

                Forms\Components\Placeholder::make("outstanding_balance")
                    ->label("Outstanding Balance")
                    ->content(function (?User $record) {
                        if (! $record) {
                            return '$0.00';
                        }

                        $balance = $record->outstandingBalance();
                        $color = $balance > 0 ? 'text-danger-600' : 'text-success-600';

                        return new \Illuminate\Support\HtmlString(
                            "<span class=\"{$color} text-xl font-bold\">$" . number_format($balance, 2) . "</span>"
                        );
                    })
                    ->extraAttributes([
                        'class' => 'rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4',
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                Forms\Components\DatePicker::make("end_date")
                    ->label("End Date")
                    ->native(false)
                    ->displayFormat("M d, Y"),

                Forms\Components\Section::make("Private Notes")
                    ->description("Visible to admins only — tenants and managers never see this.")
                    ->icon("heroicon-o-lock-closed")
                    ->schema([
                        Forms\Components\Textarea::make("private_notes")
                            ->label("")
                            ->rows(4)
                            ->placeholder("Internal notes about this user (e.g. payment history quirks, LTB history, special arrangements)...")
                            ->columnSpanFull(),
                    ]),
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
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "admin" => "danger",
                        "manager" => "warning",
                        "owner" => "info",
                        "tenant" => "gray",
                        "previous_tenant" => "secondary",
                        default => "gray",
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        "admin" => "Admin",
                        "manager" => "Property Manager",
                        "owner" => "Client",
                        "tenant" => "Tenant",
                        "previous_tenant" => "Previous Tenant",
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
                        "previous_tenant" => "Previous Tenant",
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
            \App\Filament\Admin\Resources\UserResource\RelationManagers\FormSubmissionsRelationManager::class,
            \App\Filament\Admin\Resources\UserResource\RelationManagers\RemindersRelationManager::class,
        ];
    }
}
