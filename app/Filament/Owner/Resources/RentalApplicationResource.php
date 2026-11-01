<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\RentalApplicationResource\Pages;
use App\Models\RentalApplication;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RentalApplicationResource extends Resource
{
    protected static ?string $model = RentalApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Applications';
    protected static ?string $pluralModelLabel = 'Applications';
    protected static ?string $modelLabel = 'Application';
    protected static ?string $navigationGroup = 'Leasing';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Clients only ever see applications for their own properties
        // (as owner or co-owner).
        $me = Auth::id();

        return parent::getEloquentQuery()
            ->whereHas('propertyUnit.property', function ($q) use ($me) {
                $q->where('owner_id', $me)->orWhere('co_owner_id', $me);
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Applicant')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('address_applying_for')
                    ->label('Applying For')
                    ->limit(34)
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'approved' => 'success',
                        'declined' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
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
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $statusColor = fn (string $state): string => match ($state) {
            'pending' => 'warning',
            'reviewed' => 'info',
            'approved' => 'success',
            'declined' => 'danger',
            default => 'gray',
        };

        return $infolist
            ->schema([
                Infolists\Components\Section::make('Applicant')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')->label('Name'),
                        Infolists\Components\TextEntry::make('status')->badge()->color($statusColor),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone')->label('Home Phone')->placeholder('—'),
                        Infolists\Components\TextEntry::make('alternative_phone')->label('Alternative Phone')->placeholder('—'),
                        Infolists\Components\TextEntry::make('date_of_birth')->label('Date of Birth')->date()->placeholder('—'),
                        Infolists\Components\TextEntry::make('desired_move_in_date')->label('Desired Move-In Date')->date()->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')->label('Submitted')->dateTime(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Applying For')
                    ->schema([
                        Infolists\Components\TextEntry::make('address_applying_for')->label('Property')->placeholder('—')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('propertyUnit.unit')->label('Unit #')->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Rental History — Current')
                    ->schema([
                        Infolists\Components\TextEntry::make('current_address')->label('Current Address')->placeholder('—')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('current_duration')->label('Resided Here For')->placeholder('—'),
                        Infolists\Components\TextEntry::make('current_monthly_rent')->label('Monthly Rent')->placeholder('—'),
                        Infolists\Components\TextEntry::make('current_landlord_name')->label("Landlord's Name")->placeholder('—'),
                        Infolists\Components\TextEntry::make('current_landlord_contact')->label("Landlord's Contact")->placeholder('—'),
                        Infolists\Components\TextEntry::make('current_reason_leaving')->label('Reason(s) for Leaving')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Rental History — Previous')
                    ->schema([
                        Infolists\Components\TextEntry::make('previous_address')->label('Previous Address')->placeholder('—')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('previous_duration')->label('Stayed For')->placeholder('—'),
                        Infolists\Components\TextEntry::make('previous_monthly_rent')->label('Monthly Rent')->placeholder('—'),
                        Infolists\Components\TextEntry::make('landlord_name')->label("Landlord's Name")->placeholder('—'),
                        Infolists\Components\TextEntry::make('landlord_contact')->label("Landlord's Contact")->placeholder('—'),
                        Infolists\Components\TextEntry::make('previous_reason_leaving')->label('Reason(s) for Leaving')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Screening Questions')
                    ->schema([
                        Infolists\Components\IconEntry::make('ever_evicted')->label('Ever been evicted?')->boolean(),
                        Infolists\Components\IconEntry::make('missed_payments')->label('Missed 2+ payments in 12 months?')->boolean(),
                        Infolists\Components\IconEntry::make('refused_rent')->label('Ever refused to pay rent when due?')->boolean(),
                        Infolists\Components\TextEntry::make('rental_issues_explanation')->label('Explanation')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Employment')
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
                        Infolists\Components\TextEntry::make('employer_name')->label('Employer')->placeholder('—'),
                        Infolists\Components\TextEntry::make('job_title')->label('Job Title')->placeholder('—'),
                        Infolists\Components\TextEntry::make('supervisor_name')->label("Supervisor's Name")->placeholder('—'),
                        Infolists\Components\TextEntry::make('supervisor_phone')->label("Supervisor's Phone")->placeholder('—'),
                        Infolists\Components\TextEntry::make('date_hired')->label('Date Hired')->date()->placeholder('—'),
                        Infolists\Components\TextEntry::make('monthly_income')->label('Monthly Income')->placeholder('—'),
                        Infolists\Components\TextEntry::make('other_income')->label('Other Sources of Income')->placeholder('—'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Household')
                    ->schema([
                        Infolists\Components\IconEntry::make('has_pets')->label('Has Pets')->boolean(),
                        Infolists\Components\IconEntry::make('is_smoker')->label('Smoker')->boolean(),
                        Infolists\Components\TextEntry::make('pets_details')->label('Pet Details')->placeholder('—')->columnSpanFull(),
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
