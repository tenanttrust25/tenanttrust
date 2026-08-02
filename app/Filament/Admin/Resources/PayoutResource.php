<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PayoutResource\Pages;
use App\Models\Payout;
use App\Services\StripeConnectService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Owner Payouts';

    protected static ?string $pluralModelLabel = 'Owner Payouts';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('property.title')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rent_amount')
                    ->money('CAD')
                    ->sortable()
                    ->label('Rent Collected'),
                Tables\Columns\TextColumn::make('management_fee')
                    ->money('CAD')
                    ->sortable()
                    ->label('Mgmt Fee'),
                Tables\Columns\TextColumn::make('net_amount')
                    ->money('CAD')
                    ->sortable()
                    ->label('Owner Payout')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('stripe_account')
                    ->label('Stripe')
                    ->badge()
                    ->getStateUsing(fn (Payout $record): string => $record->owner?->stripe_onboarding_complete ? 'Connected' : '—')
                    ->color(fn (string $state): string => $state === 'Connected' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->label('Paid'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->label('Created'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('payStripe')
                    ->label('Pay via Stripe')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn (Payout $record): bool => $record->status === 'pending' && $record->owner?->stripe_onboarding_complete)
                    ->requiresConfirmation()
                    ->modalHeading('Process Stripe Payout')
                    ->modalDescription('This will transfer $' . '{{ $record->net_amount }} to the owner\'s connected bank account via Stripe.')
                    ->action(function (Payout $record) {
                        $service = app(StripeConnectService::class);
                        $transfer = $service->transferToOwner($record);
                        if ($transfer) {
                            Notification::make()
                                ->title('Transferred $' . number_format($record->net_amount, 2) . ' via Stripe')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Transfer failed. Check owner Stripe settings.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->visible(fn (Payout $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Payout $record) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Payout marked as paid')
                            ->success()
                            ->send();
                    }),
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
                Infolists\Components\Section::make('Payout Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('owner.name')->label('Owner'),
                        Infolists\Components\TextEntry::make('property.title')->label('Property'),
                        Infolists\Components\TextEntry::make('invoice.invoice_number')->label('Invoice'),
                        Infolists\Components\TextEntry::make('rent_amount')->money('CAD')->label('Rent Collected'),
                        Infolists\Components\TextEntry::make('management_fee')->money('CAD')->label('Management Fee'),
                        Infolists\Components\TextEntry::make('net_amount')->money('CAD')->label('Net to Owner'),
                        Infolists\Components\TextEntry::make('status')->badge(),
                        Infolists\Components\TextEntry::make('owner.stripe_onboarding_complete')
                            ->label('Stripe Connected')
                            ->badge()
                            ->state(fn (Payout $record): string => $record->owner?->stripe_onboarding_complete ? 'Yes' : 'No'),
                        Infolists\Components\TextEntry::make('management_fee_type')->label('Fee Type'),
                        Infolists\Components\TextEntry::make('management_fee_rate')->label('Fee Rate'),
                        Infolists\Components\TextEntry::make('paid_at')->dateTime()->label('Paid At'),
                        Infolists\Components\TextEntry::make('notes')->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'view' => Pages\ViewPayout::route('/{record}'),
        ];
    }
}
