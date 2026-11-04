<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MailItemResource\Pages;
use App\Models\MailItem;
use App\Services\PostGridService;
use App\Support\TenantTrustSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Throwable;

class MailItemResource extends Resource
{
    protected static ?string $model = MailItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'Outgoing Mail';

    protected static ?string $navigationGroup = 'Mail';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Letter';

    protected static ?string $pluralModelLabel = 'Outgoing Mail';

    public static function getNavigationBadge(): ?string
    {
        $count = MailItem::where('status', 'draft')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Recipient')
                ->description('The address PostGrid prints on the letter and envelope.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->label('Tenant')
                        ->relationship('tenant', 'name', modifyQueryUsing: fn ($query) => $query->where('role', 'tenant'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) {
                                return;
                            }
                            $tenant = \App\Models\User::find($state);
                            if (! $tenant) {
                                return;
                            }
                            [$l1, $l2, $city, $prov, $postal] = \App\Support\AddressParser::parse(
                                $tenant->mailing_address ?: $tenant->address
                            );
                            $set('recipient_name', $tenant->name);
                            $set('address_line1', $l1);
                            $set('address_line2', $l2);
                            $set('city', $city);
                            $set('province', $prov ?: 'ON');
                            $set('postal_code', $postal);
                        })
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Full name')->required()->maxLength(255),
                            Forms\Components\TextInput::make('email')->email()->required()->maxLength(255),
                            Forms\Components\TextInput::make('address')
                                ->label('Address')
                                ->placeholder('123 Main St, Unit 2, Sudbury, P3A 1A1')
                                ->helperText('Street, unit, city, postal.'),
                        ])
                        ->createOptionUsing(function (array $data) {
                            return \App\Models\User::create([
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'address' => $data['address'] ?? null,
                                'role' => 'tenant',
                                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                            ])->getKey();
                        })
                        ->helperText('Pick a tenant to auto-fill the address below, or “+ Create” a new one.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('recipient_name')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Auto-filled from the tenant — edit for a one-off recipient.'),
                    Forms\Components\TextInput::make('form_label')
                        ->label('Type / label')
                        ->placeholder('Letter, N1, N4, N9…')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('address_line1')
                        ->label('Address line 1')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('address_line2')
                        ->label('Address line 2 (unit)')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('city')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('province')
                        ->default('ON')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('postal_code')
                        ->label('Postal code')
                        ->maxLength(20),
                    Forms\Components\Hidden::make('country')->default('CA'),
                ]),
            Forms\Components\Section::make('Letter PDF')
                ->schema([
                    Forms\Components\FileUpload::make('pdf_path')
                        ->label('PDF file')
                        ->disk('documents')
                        ->directory('mail-items')
                        ->disk('documents')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])
                        ->preserveFilenames()
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('single_sheet')
                        ->label('1-sheet layout')
                        ->helperText('On: the letter leaves the top 3.25" blank so PostGrid prints the return + destination addresses there (1 sheet). Leave OFF for any PDF that already has a letterhead at the top — then addresses go on a separate page (2 sheets).')
                        ->default(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('form_label')
                    ->label('Type')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => MailItem::STATUSES[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('mode')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'live' ? 'danger' : 'gray')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Queued')
                    ->dateTime('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('M d, Y g:i A')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('postgrid_id')
                    ->label('PostGrid ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(MailItem::STATUSES),
                Tables\Filters\SelectFilter::make('form_label')
                    ->label('Type')
                    ->options(fn () => MailItem::query()
                        ->whereNotNull('form_label')
                        ->distinct()
                        ->orderBy('form_label')
                        ->pluck('form_label', 'form_label')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Send Letter Mail')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(function (MailItem $record): string {
                        return TenantTrustSettings::postgridMode() === 'live'
                            ? 'Mail this letter for real?'
                            : 'Test-send this letter?';
                    })
                    ->modalDescription(function (MailItem $record): string {
                        if (TenantTrustSettings::postgridMode() === 'live') {
                            return 'LIVE MODE — PostGrid will print and mail this letter to '
                                . $record->recipient_name . ' and charge real postage. This cannot be undone.';
                        }

                        return 'TEST MODE — nothing is mailed. PostGrid generates a PDF proof only.';
                    })
                    ->action(fn (MailItem $record) => self::sendItem($record))
                    ->visible(fn (MailItem $record): bool => $record->isSendable()),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn (MailItem $record): ?string => $record->pdfUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (MailItem $record): bool => (bool) $record->pdf_path),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('send')
                    ->label('Send Letter Mail')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(fn () => TenantTrustSettings::postgridMode() === 'live'
                        ? 'LIVE MODE — every selected letter will be printed, mailed and charged.'
                        : 'TEST MODE — nothing is mailed; PostGrid proofs only.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        $sent = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            if (! $record->isSendable()) {
                                continue;
                            }
                            try {
                                self::sendItem($record, notify: false);
                                $sent++;
                            } catch (Throwable $e) {
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->title("Outgoing mail: {$sent} sent" . ($failed ? ", {$failed} failed" : ''))
                            ->{$failed ? 'warning' : 'success'}()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function sendItem(MailItem $record, bool $notify = true): void
    {
        $mode = TenantTrustSettings::postgridMode();

        try {
            app(PostGridService::class)->send($record, $mode);
            if ($notify) {
                Notification::make()
                    ->title($mode === 'live' ? 'Letter mailed via PostGrid' : 'Test send complete (nothing mailed)')
                    ->body('PostGrid ID: ' . ($record->fresh()->postgrid_id ?: '—'))
                    ->success()
                    ->send();
            }
        } catch (Throwable $e) {
            if ($notify) {
                Notification::make()
                    ->title('Send failed')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }

            throw $e;
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMailItems::route('/'),
            'create' => Pages\CreateMailItem::route('/create'),
            'edit' => Pages\EditMailItem::route('/{record}/edit'),
        ];
    }
}
