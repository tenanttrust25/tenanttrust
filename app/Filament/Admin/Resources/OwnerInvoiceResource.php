<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OwnerInvoiceResource\Pages;
use App\Mail\OwnerInvoiceMail;
use App\Models\OwnerInvoice;
use App\Models\Property;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OwnerInvoiceResource extends Resource
{
    protected static ?string $model = OwnerInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Owner Finance';

    protected static ?string $navigationLabel = 'Client Invoices';

    protected static ?string $pluralModelLabel = 'Client Invoices';

    protected static ?string $modelLabel = 'Client Invoice';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('owner_id')
                    ->label('Client (Owner)')
                    ->options(fn () => User::where('role', 'owner')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('property_id', null);
                    }),

                Select::make('property_id')
                    ->label('Property')
                    ->options(function (callable $get) {
                        $ownerId = $get('owner_id');
                        $query = Property::query();
                        if ($ownerId) {
                            $query->where('owner_id', $ownerId);
                        }
                        return $query->orderBy('title')->pluck('title', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state) {
                            $set('amount', null);
                            return;
                        }

                        $property = Property::find($state);
                        $fee = $property?->managementFeeTotalWithTax();
                        if ($fee !== null) {
                            $set('amount', number_format($fee, 2, '.', ''));
                        }
                    }),

                TextInput::make('invoice_number')
                    ->label('Invoice #')
                    ->default(fn () => 'OWN-' . date('Y') . '-' . strtoupper(Str::random(6)))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->minValue(0)
                    ->step(0.01),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),

                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->native(false)
                    ->displayFormat('M d, Y')
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),

                FileUpload::make('file_path')
                    ->label('PDF File')
                    ->directory('owner-invoices')
                    ->disk('documents')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.title')
                    ->label('Property')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('CAD')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('owner_id')
                    ->label('Client')
                    ->options(fn () => User::where('role', 'owner')->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('email')
                    ->label('Email Invoice')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice to Client')
                    ->modalContent(function (OwnerInvoice $record) {
                        return view('filament.admin.owner-invoice-email-confirm', [
                            'invoice' => $record,
                        ]);
                    })
                    ->action(function (OwnerInvoice $record) {
                        $owner = $record->owner;

                        if (!$owner?->email) {
                            Notification::make()
                                ->danger()
                                ->title('Client has no email address')
                                ->body("Invoice #{$record->invoice_number} can't be emailed — the client has no email on file.")
                                ->send();
                            return;
                        }

                        try {
                            Mail::to($owner->email)
                                ->send(new OwnerInvoiceMail($record));

                            Notification::make()
                                ->success()
                                ->title('Invoice emailed!')
                                ->body("Invoice #{$record->invoice_number} sent to {$owner->email}")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to send email')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwnerInvoices::route('/'),
            'create' => Pages\CreateOwnerInvoice::route('/create'),
            'edit' => Pages\EditOwnerInvoice::route('/{record}/edit'),
        ];
    }
}
