<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallLogEntryResource\Pages;
use App\Models\CallLogEntry;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CallLogEntryResource extends Resource
{
    protected static ?string $model = CallLogEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'Call Log';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $pluralModelLabel = 'Call Log';
    protected static ?string $modelLabel = 'Call';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tenant')
                    ->description('Pick a tenant to auto-fill their name, phone, and address below.')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship(
                                'tenant',
                                'name',
                                fn (Builder $query): Builder => $query
                                    ->whereIn('role', ['tenant', 'previous_tenant'])
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                if (! $state) {
                                    return;
                                }

                                $tenant = User::find($state);
                                if (! $tenant) {
                                    return;
                                }

                                $set('caller_name', $tenant->name);
                                $set('phone', $tenant->phone);
                                $set('address', $tenant->address);
                            })
                            ->placeholder('Select a tenant…'),
                    ]),
                Forms\Components\Section::make('Call Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('caller_name')
                            ->label('Name')
                            ->placeholder('Who called?')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->placeholder('(705) 555-0123')
                            ->maxLength(32),
                        Forms\Components\DateTimePicker::make('called_at')
                            ->label('Date / Time')
                            ->native(false)
                            ->displayFormat('M d, Y g:i A')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->label('Address')
                            ->placeholder('e.g. 137 Hillsboro Ave, Sudbury')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description of Call')
                            ->placeholder('What was the call about?')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('called_at')
                    ->label('Date / Time')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('caller_name')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Address')
                    ->placeholder('—')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn (CallLogEntry $record): ?string => $record->description)
                    ->searchable(),
            ])
            ->defaultSort('called_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('called_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->native(false),
                        Forms\Components\DatePicker::make('until')->native(false),
                    ])
                    ->query(function ($query, array $data): void {
                        $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('called_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('called_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallLogEntries::route('/'),
            'create' => Pages\CreateCallLogEntry::route('/create'),
            'edit' => Pages\EditCallLogEntry::route('/{record}/edit'),
        ];
    }
}
