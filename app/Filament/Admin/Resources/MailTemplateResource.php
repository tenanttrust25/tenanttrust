<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MailTemplateResource\Pages;
use App\Models\MailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MailTemplateResource extends Resource
{
    protected static ?string $model = MailTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Mail Campaigns';
    protected static ?string $navigationLabel = 'Templates';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Template Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Template Name')
                    ->required()
                    ->maxLength(120),
                Forms\Components\TextInput::make('subject')
                    ->label('Default Subject')
                    ->maxLength(200)
                    ->columnSpanFull(),
                Forms\Components\Select::make('category')
                    ->label('Category')
                    ->options([
                        'general' => 'General',
                        'invoice' => 'Invoice / Rent',
                        'announcement' => 'Announcement',
                        'maintenance' => 'Maintenance',
                        'holiday' => 'Holiday / Seasonal',
                    ]),
                Forms\Components\RichEditor::make('body')
                    ->label('Email Body (HTML)')
                    ->required()
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('email-assets'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('campaigns_count')
                    ->label('Used In')
                    ->counts('campaigns')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'general' => 'General',
                        'invoice' => 'Invoice / Rent',
                        'announcement' => 'Announcement',
                        'maintenance' => 'Maintenance',
                        'holiday' => 'Holiday / Seasonal',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMailTemplates::route('/'),
            'create' => Pages\CreateMailTemplate::route('/create'),
            'edit' => Pages\EditMailTemplate::route('/{record}/edit'),
        ];
    }
}
