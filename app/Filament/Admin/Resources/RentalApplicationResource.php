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

    protected static ?string $navigationGroup = 'Leasing';

    protected static ?string $navigationLabel = 'Rental Applications';
    protected static ?int $navigationSort = 1;

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

    /**
     * Quick-pick reasons shown as buttons when declining an application.
     */
    public static function declineReasons(): array
    {
        return [
            'Missing address' => 'Missing address',
            'Missing landlord name' => 'Missing landlord name',
            'Missing landlord contact' => 'Missing landlord contact',
            'No photo ID' => 'No photo ID',
            'No proof of employment' => 'No proof of employment',
            'Insufficient income' => 'Insufficient income',
            'Incomplete application' => 'Incomplete application',
            'Rental history concerns' => 'Rental history concerns',
            'Credit concerns' => 'Credit concerns',
            'Eviction history' => 'Eviction history',
            'Property no longer available' => 'Property no longer available',
            'Other' => 'Other',
        ];
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
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'reviewed' => 'info',
                                'approved' => 'success',
                                'declined' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('date_of_birth')
                            ->label('Date of Birth')
                            ->date()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Home Phone')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('alternative_phone')
                            ->label('Alternative Phone')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('desired_move_in_date')
                            ->label('Desired Move-In Date')
                            ->date()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Applying For')
                    ->schema([
                        Infolists\Components\TextEntry::make('address_applying_for')
                            ->label('Property')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('propertyUnit.unit')
                            ->label('Unit #')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Co-Applicant')
                    ->schema([
                        Infolists\Components\TextEntry::make('co_applicant_name')
                            ->label('Full Name')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('co_applicant_dob')
                            ->label('Date of Birth')
                            ->date()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('co_applicant_email')
                            ->label('Email')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('co_applicant_phone')
                            ->label('Home Phone')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('co_applicant_alt_phone')
                            ->label('Alternative Phone')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Other Occupants')
                    ->schema([
                        Infolists\Components\TextEntry::make('occupant_1_name')
                            ->label('Occupant 1 — Name')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('occupant_1_relation')
                            ->label('Occupant 1 — Relation')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('occupant_1_dob')
                            ->label('Occupant 1 — Date of Birth')
                            ->date()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('occupant_2_name')
                            ->label('Occupant 2 — Name')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('occupant_2_relation')
                            ->label('Occupant 2 — Relation')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('occupant_2_dob')
                            ->label('Occupant 2 — Date of Birth')
                            ->date()
                            ->placeholder('—'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Rental History — Current Residence')
                    ->schema([
                        Infolists\Components\TextEntry::make('current_address')
                            ->label('Current Address')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('current_duration')
                            ->label('Resided Here For')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('current_monthly_rent')
                            ->label('Monthly Rent')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('current_landlord_name')
                            ->label("Landlord's Name")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('current_landlord_contact')
                            ->label("Landlord's Contact")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('current_reason_leaving')
                            ->label('Reason(s) for Leaving')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Rental History — Previous Residence')
                    ->schema([
                        Infolists\Components\TextEntry::make('previous_address')
                            ->label('Previous Address')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('previous_duration')
                            ->label('Stayed For')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('previous_monthly_rent')
                            ->label('Monthly Rent')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('landlord_name')
                            ->label("Landlord's Name")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('landlord_contact')
                            ->label("Landlord's Contact")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('previous_reason_leaving')
                            ->label('Reason(s) for Leaving')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Screening Questions')
                    ->schema([
                        Infolists\Components\IconEntry::make('ever_evicted')
                            ->label('Ever been evicted?')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('missed_payments')
                            ->label('Missed 2+ payments in 12 months?')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('refused_rent')
                            ->label('Ever refused to pay rent when due?')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('rental_issues_explanation')
                            ->label('Explanation')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Employment — Current')
                    ->schema([
                        Infolists\Components\TextEntry::make('employment_status')
                            ->label('Employment Status')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'full_time' => 'Full-time',
                                'part_time' => 'Part-time',
                                'student' => 'Student',
                                'unemployed' => 'Unemployed',
                                'retired' => 'Retired',
                                default => $state ? ucfirst(str_replace('_', ' ', $state)) : '—',
                            }),
                        Infolists\Components\TextEntry::make('employer_name')
                            ->label('Employer')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('job_title')
                            ->label('Job Title')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('supervisor_name')
                            ->label("Supervisor's Name")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('supervisor_phone')
                            ->label("Supervisor's Phone")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('date_hired')
                            ->label('Date Hired')
                            ->date()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('monthly_income')
                            ->label('Monthly Income')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('other_income')
                            ->label('Other Sources of Income')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Employment — Previous')
                    ->schema([
                        Infolists\Components\TextEntry::make('previous_employer')
                            ->label('Previous Employer')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('previous_job_title')
                            ->label('Job Title')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('previous_supervisor')
                            ->label("Supervisor's Name")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('previous_supervisor_phone')
                            ->label("Supervisor's Phone")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('employment_period')
                            ->label('Period of Employment')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Documents')
                    ->schema([
                        Infolists\Components\TextEntry::make('gov_id_path')
                            ->label('Government Issued Photo ID')
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : 'No file uploaded')
                            ->url(fn (?string $state, RentalApplication $record): ?string => $state ? route('files.application', [$record, 'gov_id_path']) : null)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-arrow-down-tray'),
                        Infolists\Components\TextEntry::make('proof_of_employment_path')
                            ->label('Proof of Income / Employment')
                            ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : 'No file uploaded')
                            ->url(fn (?string $state, RentalApplication $record): ?string => $state ? route('files.application', [$record, 'proof_of_employment_path']) : null)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-arrow-down-tray'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Household')
                    ->schema([
                        Infolists\Components\IconEntry::make('has_pets')
                            ->label('Has Pets')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_smoker')
                            ->label('Smoker')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('pets_details')
                            ->label('Pet Details')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Credit & Background Check Authorization')
                    ->schema([
                        Infolists\Components\IconEntry::make('consent_credit_check')
                            ->label('Soft Credit Check Consent')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('consent_background_check')
                            ->label('Criminal Background Check Consent')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('credit_check_comments')
                            ->label('Credit Check Comments')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('criminal_check_comments')
                            ->label('Criminal Check Comments')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Declaration & Signature')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant_signature')
                            ->label("Applicant's Signature")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('co_applicant_signature')
                            ->label("Co-Applicant's Signature")
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('signature_date')
                            ->label('Date')
                            ->date()
                            ->placeholder('—'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Decline')
                    ->visible(fn (?RentalApplication $record): bool => $record?->status === 'declined')
                    ->schema([
                        Infolists\Components\TextEntry::make('decline_reasons')
                            ->label('Reason(s)')
                            ->badge()
                            ->color('danger')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('decline_notes')
                            ->label('Notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
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
