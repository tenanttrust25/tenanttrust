<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReminderResource\Pages;
use App\Models\Reminder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReminderResource extends Resource
{
    protected static ?string $model = Reminder::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Reminders';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $pluralModelLabel = 'Reminders';
    protected static ?string $modelLabel = 'Reminder';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant (optional)')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('No tenant — general reminder'),
                Forms\Components\TextInput::make('title')
                    ->label('Reminder')
                    ->placeholder('e.g. Fill out N1 for rent increase')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('Details (optional)')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('remind_date')
                    ->label('Remind me on (email + red bar)')
                    ->native(false)
                    ->displayFormat('M d, Y')
                    ->required()
                    ->helperText('The date the reminder becomes due — you get an email and it shows in the red bar.'),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Due date (optional)')
                    ->native(false)
                    ->displayFormat('M d, Y')
                    ->helperText('Actual deadline of the task, e.g. the N1 effective date.'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Reminder')
                    ->searchable()
                    ->weight('bold')
                    ->limit(50),
                Tables\Columns\TextColumn::make('remind_date')
                    ->label('Remind On')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Completed',
                        'danger' => 'Due',
                        'gray' => 'Upcoming',
                    ])
                    ->formatStateUsing(fn (Reminder $record): string => $record->completed_at
                        ? 'Completed'
                        : ($record->remind_date?->isPast() || $record->remind_date?->isToday() ? 'Due' : 'Upcoming')),
                Tables\Columns\TextColumn::make('notified_at')
                    ->label('Emailed')
                    ->dateTime('M d, Y g:i A')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('remind_date')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'due' => 'Due',
                        'upcoming' => 'Upcoming',
                        'completed' => 'Completed',
                    ])
                    ->query(function ($query, array $data) {
                        if (blank($data['value'])) {
                            return;
                        }

                        match ($data['value']) {
                            'due' => $query->whereNull('completed_at')->where('remind_date', '<=', now()->toDateString()),
                            'upcoming' => $query->whereNull('completed_at')->where('remind_date', '>', now()->toDateString()),
                            'completed' => $query->whereNotNull('completed_at'),
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Reminder $record): bool => $record->completed_at === null)
                    ->action(function (Reminder $record): void {
                        $record->update(['completed_at' => now()]);
                        Notification::make()->success()->title('Reminder completed')->send();
                    }),
                Tables\Actions\Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (Reminder $record): bool => $record->completed_at !== null)
                    ->action(function (Reminder $record): void {
                        $record->update(['completed_at' => null]);
                        Notification::make()->success()->title('Reminder reopened')->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_complete_bulk')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        $records->each->update(['completed_at' => now()]);
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $due = Reminder::query()->due()->count();

        return $due > 0 ? (string) $due : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Reminder::query()->due()->exists() ? 'danger' : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReminders::route('/'),
            'create' => Pages\CreateReminder::route('/create'),
            'edit' => Pages\EditReminder::route('/{record}/edit'),
        ];
    }
}
