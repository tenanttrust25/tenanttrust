<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use App\Models\Reminder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'reminders';

    protected static ?string $title = 'Reminders';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->helperText('When this becomes due — you get an email and it shows in the red bar.'),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Due date (optional)')
                    ->native(false)
                    ->displayFormat('M d, Y'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
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
            ])
            ->defaultSort('remind_date')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Reminder')
                    ->icon('heroicon-o-bell-alert')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = $this->getOwnerRecord()->id;

                        return $data;
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
}
