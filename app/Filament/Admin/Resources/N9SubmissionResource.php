<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\N9SubmissionResource\Pages;
use App\Models\N9Submission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;

class N9SubmissionResource extends Resource
{
    protected static ?string $model = N9Submission::class;

    protected static ?string $navigationIcon = "heroicon-o-paper-airplane";
    protected static ?string $navigationLabel = "N9 Submissions";
    protected static ?string $navigationGroup = "Leasing";
    protected static ?string $pluralModelLabel = "N9 Submissions";
    protected static ?string $modelLabel = "N9 Submission";
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\TextInput::make("tenant.name")->label("Tenant")->disabled()->dehydrated(false),
                \Filament\Forms\Components\TextInput::make("landlord_name")->label("Landlord")->disabled()->dehydrated(false),
                \Filament\Forms\Components\TextInput::make("tenant_names")->label("Tenant Name(s) on Form")->disabled()->dehydrated(false),
                \Filament\Forms\Components\TextInput::make("unit_address")->label("Rental Unit")->disabled()->dehydrated(false),
                \Filament\Forms\Components\DatePicker::make("termination_date")->label("Tenancy Ends")->disabled()->dehydrated(false),
                \Filament\Forms\Components\TextInput::make("phone")->label("Phone")->disabled()->dehydrated(false),
                \Filament\Forms\Components\TextInput::make("signature")->label("Signature")->disabled()->dehydrated(false),
                \Filament\Forms\Components\TextInput::make("created_at")->label("Submitted")->disabled()->dehydrated(false),
                \Filament\Forms\Components\Placeholder::make("pdf_link")
                    ->label("Filled N9 PDF")
                    ->content(function (?N9Submission $record) {
                        if (! $record?->file_path) {
                            return 'Not generated.';
                        }
                        $url = Storage::disk('public')->url($record->file_path);
                        return new \Illuminate\Support\HtmlString(
                            "<a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 underline\">Open filled N9 PDF</a>"
                        );
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("unit_address")
                    ->label("Rental Unit")
                    ->searchable()
                    ->limit(30)
                    ->sortable(),
                TextColumn::make("termination_date")
                    ->label("Tenancy Ends")
                    ->date("M d, Y")
                    ->sortable(),
                TextColumn::make("created_at")
                    ->label("Submitted")
                    ->dateTime("M d, Y g:i A")
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Action::make("open_pdf")
                    ->label("Open PDF")
                    ->icon("heroicon-o-document-arrow-down")
                    ->color("success")
                    ->url(fn (N9Submission $record): ?string => $record->file_path ? Storage::disk('public')->url($record->file_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (N9Submission $record): bool => (bool) $record->file_path),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort("created_at", "desc");
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListN9Submissions::route('/'),
            'view' => Pages\ViewN9Submission::route('/{record}'),
        ];
    }
}
