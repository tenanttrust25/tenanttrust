<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RentalApplicationResource\Pages;
use App\Models\RentalApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RentalApplicationResource extends Resource
{
    protected static ?string $model = RentalApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Rental Applications';

    protected static ?string $pluralModelLabel = 'Rental Applications';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')->disabled(),
                Forms\Components\TextInput::make('last_name')->disabled(),
                Forms\Components\TextInput::make('phone')->disabled(),
                Forms\Components\TextInput::make('email')->disabled(),
                Forms\Components\DatePicker::make('date_of_birth')->disabled(),
                Forms\Components\TextInput::make('address_applying_for')->disabled(),
                Forms\Components\TextInput::make('previous_address')->disabled(),
                Forms\Components\TextInput::make('landlord_name')->disabled(),
                Forms\Components\TextInput::make('landlord_contact')->disabled()->columnSpanFull(),
                Forms\Components\Toggle::make('has_pets')->disabled(),
                Forms\Components\TextInput::make('pets_details')->disabled(),
                Forms\Components\Toggle::make('is_smoker')->disabled(),
                Forms\Components\Toggle::make('consent_credit_check')->disabled(),
                Forms\Components\Toggle::make('consent_background_check')->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'approved' => 'Approved',
                        'declined' => 'Declined',
                    ])
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_applying_for')
                    ->label('Applying For')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\IconColumn::make('has_pets')
                    ->label('Pets')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_smoker')
                    ->label('Smoker')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'approved' => 'success',
                        'declined' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'approved' => 'Approved',
                        'declined' => 'Declined',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Applicant')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('date_of_birth')
                            ->label('Date of Birth')
                            ->date(),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Application Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('address_applying_for')
                            ->label('Address Applying For'),
                        Infolists\Components\TextEntry::make('propertyUnit.unit')
                            ->label('Unit #'),
                        Infolists\Components\TextEntry::make('previous_address')
                            ->label('Previous Address')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('landlord_name')
                            ->label('Landlord Name')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('landlord_contact')
                            ->label('Landlord Contact')
                            ->placeholder('—'),
                    ]),
                Infolists\Components\Section::make('Documents')
                    ->schema([
                        Infolists\Components\TextEntry::make('gov_id_path')
                            ->label('Government Issued Photo ID')
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : 'No file uploaded')
                            ->url(fn (?string $state): ?string => $state ? \Illuminate\Support\Facades\Storage::disk('public')->url($state) : null)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-arrow-down-tray'),
                        Infolists\Components\TextEntry::make('proof_of_employment_path')
                            ->label('Proof of Employment')
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : 'No file uploaded')
                            ->url(fn (?string $state): ?string => $state ? \Illuminate\Support\Facades\Storage::disk('public')->url($state) : null)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-arrow-down-tray'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Household')
                    ->schema([
                        Infolists\Components\IconEntry::make('has_pets')
                            ->label('Has Pets')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('pets_details')
                            ->label('Pet Details')
                            ->placeholder('—'),
                        Infolists\Components\IconEntry::make('is_smoker')
                            ->label('Smoker')
                            ->boolean(),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Consent')
                    ->schema([
                        Infolists\Components\IconEntry::make('consent_credit_check')
                            ->label('Soft Credit Check Consent')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('consent_background_check')
                            ->label('Criminal Background Check Consent')
                            ->boolean(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalApplications::route('/'),
            'view' => Pages\ViewRentalApplication::route('/{record}'),
        ];
    }
}
