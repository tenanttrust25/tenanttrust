<?php

namespace App\Filament\Admin\Resources\RentalApplicationResource\Pages;

use App\Filament\Admin\Resources\RentalApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRentalApplication extends ViewRecord
{
    protected static string $resource = RentalApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('markReviewed')
                    ->label('Mark Reviewed')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn () => $this->record->status === 'pending')
                    ->action(fn () => $this->updateStatus('reviewed')),
                Actions\Action::make('markApproved')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn () => in_array($this->record->status, ['pending', 'reviewed']))
                    ->action(fn () => $this->updateStatus('approved')),
                Actions\Action::make('markDeclined')
                    ->label('Decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn () => in_array($this->record->status, ['pending', 'reviewed', 'approved']))
                    ->action(fn () => $this->updateStatus('declined')),
            ]),
            Actions\DeleteAction::make(),
        ];
    }

    private function updateStatus(string $status): void
    {
        $this->record->update(['status' => $status]);

        \Filament\Notifications\Notification::make()
            ->title('Application marked as ' . ucfirst($status))
            ->success()
            ->send();
    }
}
