<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MailCampaignResource\Pages;
use App\Filament\Admin\Resources\MailCampaignResource\Widgets\CampaignStatsOverview;
use App\Models\MailCampaign;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MailCampaignResource extends Resource
{
    protected static ?string $model = MailCampaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Mail Campaigns';
    protected static ?string $navigationLabel = 'Campaigns';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Campaign Details')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Campaign Name')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('Description (internal notes)')
                    ->columnSpanFull(),
                Forms\Components\Select::make('template_id')
                    ->label('Email Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Template Name')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\TextInput::make('subject')
                            ->label('Subject')
                            ->maxLength(200),
                        Forms\Components\RichEditor::make('body')
                            ->label('Body')
                            ->required(),
                    ]),
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Schedule Delivery')
                    ->nullable()
                    ->helperText('Leave blank to send immediately after creating.'),
            ])->columns(2),

            Forms\Components\Section::make('Audience')
                ->description('Select which tenants will receive this campaign.')
                ->schema([
                    Forms\Components\Select::make('audience_filter')
                        ->label('Filter By Property')
                        ->multiple()
                        ->options(\App\Models\Property::pluck('title', 'id'))
                        ->helperText('Leave empty to send to ALL tenants. Select specific properties to filter.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'sending' => 'info',
                        'sent' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('template.name')
                    ->label('Template')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recipient_count')
                    ->label('Recipients')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('open_count')
                    ->label('Opens')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (MailCampaign $record): bool => $record->status === 'draft'),

                Tables\Actions\Action::make('send')
                    ->label('Send Campaign')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (MailCampaign $record): bool => in_array($record->status, ['draft']))
                    ->action(function (MailCampaign $record) {
                        $record->update(['status' => 'sending']);
                        $job = new \App\Jobs\SendMailCampaign($record); $job->handle();
                        Notification::make()
                            ->title('Campaign queued for delivery')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_recipients')
                    ->label('View Recipients')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->url(fn (MailCampaign $record): string => 
                        MailCampaignResource::getUrl('recipients', ['record' => $record])),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (MailCampaign $record): bool => $record->status !== 'sent'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [
            CampaignStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMailCampaigns::route('/'),
            'create' => Pages\CreateMailCampaign::route('/create'),
            'edit' => Pages\EditMailCampaign::route('/{record}/edit'),
            'recipients' => Pages\ViewCampaignRecipients::route('/{record}/recipients'),
        ];
    }
}
