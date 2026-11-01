<?php

namespace App\Filament\Admin\Resources;

use App\Models\TenantFile;
use App\Models\PropertyUnit;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class TenantFileResource extends Resource
{
    protected static ?string $model = TenantFile::class;

    protected static ?string $navigationIcon = "heroicon-o-document-text";
    protected static ?string $navigationLabel = "Tenant Files";
    protected static ?string $navigationGroup = "People";
    protected static ?string $pluralModelLabel = "Tenant Files";
    protected static ?string $modelLabel = "Tenant File";
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make("property_unit_id")
                    ->label("Property Unit")
                    ->relationship("propertyUnit", "unit")
                    ->getOptionLabelFromRecordUsing(fn(PropertyUnit $record) =>
                        $record->property?->title . ' — Unit ' . $record->unit
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $unit = PropertyUnit::with('property', 'tenants.tenant')->find($state);
                            if ($unit) {
                                $firstTenant = $unit->tenants()->where('status', 'active')->first()?->tenant;
                                if ($firstTenant) {
                                    $set('tenant_id', $firstTenant->id);
                                }
                            }
                        }
                    }),
                Select::make("tenant_id")
                    ->label("Tenant")
                    ->relationship("tenant", "name")
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make("name")
                    ->label("File Name")
                    ->required()
                    ->maxLength(255),
                Textarea::make("description")
                    ->label("Description")
                    ->rows(3),
                Select::make("file_type")
                    ->label("File Type")
                    ->options([
                        "lease" => "Lease",
                        "N1" => "N1",
                        "N4" => "N4",
                        "N9" => "N9",
                        "other" => "Other",
                    ])
                    ->default("other")
                    ->required(),
                FileUpload::make("file_path")
                    ->label("File")
                    ->directory("tenant-files")
                    ->required()
                    ->maxSize(20480),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->label("Name")
                    ->searchable()
                    ->sortable(),
                TextColumn::make("tenant.name")
                    ->label("Tenant")
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make("file_type")
                    ->label("Type")
                    ->colors([
                        "blue" => "lease",
                        "orange" => "N1",
                        "red" => "N4",
                        "purple" => "N9",
                        "gray" => "other",
                    ])
                    ->formatStateUsing(fn(string $state): string => strtoupper($state)),
                TextColumn::make("created_at")
                    ->label("Uploaded")
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make("file_type")
                    ->options([
                        "lease" => "Lease",
                        "N1" => "N1",
                        "N4" => "N4",
                        "N9" => "N9",
                        "other" => "Other",
                    ]),
            ])
            ->defaultSort("created_at", "desc");
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => \App\Filament\Admin\Resources\TenantFileResource\Pages\ListTenantFiles::route("/"),
            "create" => \App\Filament\Admin\Resources\TenantFileResource\Pages\CreateTenantFile::route("/create"),
            "edit" => \App\Filament\Admin\Resources\TenantFileResource\Pages\EditTenantFile::route("/{record}/edit"),
        ];
    }
}
