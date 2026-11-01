<?php

namespace App\Filament\Admin\Pages;

use App\Models\PropertyUnit;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManagementFees extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Management Fees';
    protected static ?string $title = 'Management Fees';
    protected static ?string $slug = 'management-fees';
    protected static ?string $navigationGroup = 'Owner Finance';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'fees' => PropertyUnit::with('property')
                ->orderBy('property_id')
                ->orderBy('unit')
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'property' => $u->property?->title ?? 'N/A',
                    'unit_label' => $u->unit ?? 'N/A',
                    'rent' => number_format($u->rent_amount, 2),
                    'status' => $u->status,
                    'monthly_management_fee' => $u->monthly_management_fee,
                ])
                ->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('fees')
                    ->label('')
                    ->columnSpanFull()
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('property')
                            ->label('Building')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('unit_label')
                            ->label('Unit')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('rent')
                            ->label('Rent')
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('monthly_management_fee')
                            ->label('Mgmt Fee ($)')
                            ->numeric()
                            ->prefix('$')
                            ->nullable(),
                        TextInput::make('status')
                            ->label('Status')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(6)
                    ->collapsible()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): string => ($state['property'] ?? '') . ' - ' . ($state['unit_label'] ?? '')),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $fees = $this->form->getState()['fees'] ?? [];

        foreach ($fees as $item) {
            PropertyUnit::where('id', $item['id'])
                ->update([
                    'monthly_management_fee' => $item['monthly_management_fee'] !== '' ? $item['monthly_management_fee'] : null,
                ]);
        }

        Notification::make()
            ->title('Management fees updated successfully')
            ->success()
            ->send();

        $this->mount();
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save All Fees')
                ->action('save'),
        ];
    }

    public function getView(): string
    {
        return 'filament.admin.pages.management-fees';
    }
}
