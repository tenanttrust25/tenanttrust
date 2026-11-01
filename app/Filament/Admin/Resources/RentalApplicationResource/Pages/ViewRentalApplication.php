<?php

namespace App\Filament\Admin\Resources\RentalApplicationResource\Pages;

use App\Filament\Admin\Resources\RentalApplicationResource;
use App\Mail\RentalApplicationApprovedMail;
use App\Mail\RentalApplicationDeclinedMail;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
                    ->requiresConfirmation()
                    ->modalHeading('Approve this application?')
                    ->modalDescription('This marks the application approved and emails the applicant the approval + next-steps email.')
                    ->modalSubmitActionLabel('Approve & email applicant')
                    ->action(function (): void {
                        $this->record->update(['status' => 'approved']);

                        $this->notifyApplicantOfApproval();

                        Notification::make()
                            ->title('Application approved')
                            ->body($this->record->full_name . ' — approval email sent.')
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('markDeclined')
                    ->label('Decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn () => in_array($this->record->status, ['pending', 'reviewed', 'approved']))
                    ->modalHeading('Why was this application declined?')
                    ->modalDescription('Pick the reason(s) and add any notes. This is saved on the application.')
                    ->modalSubmitActionLabel('Decline application')
                    ->form([
                        Forms\Components\ToggleButtons::make('reasons')
                            ->label('Reason(s)')
                            ->multiple()
                            ->options(RentalApplicationResource::declineReasons())
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Additional notes')
                            ->placeholder('Optional — anything else worth recording')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data): void {
                        $this->record->update([
                            'status' => 'declined',
                            'decline_reasons' => $data['reasons'] ?? [],
                            'decline_notes' => $data['notes'] ?? null,
                        ]);

                        $this->notifyApplicantOfDecline();

                        Notification::make()
                            ->title('Application declined')
                            ->body($this->record->full_name . ' — reason recorded.')
                            ->danger()
                            ->send();
                    }),
            ]),
            Actions\DeleteAction::make(),
        ];
    }

    private function updateStatus(string $status): void
    {
        $this->record->update(['status' => $status]);

        Notification::make()
            ->title('Application marked as ' . ucfirst($status))
            ->success()
            ->send();
    }

    /**
     * Email the applicant a neutral notice. Sensitive reasons (credit,
     * evictions, rental history, income) are never spelled out — see
     * RentalApplicationDeclinedMail::INTERNAL_ONLY_REASONS. Failure to send
     * must not block the decline itself.
     */
    private function notifyApplicantOfDecline(): void
    {
        if (empty($this->record->email)) {
            return;
        }

        try {
            Mail::mailer('smtp')
                ->to($this->record->email)
                ->cc('info@tenanttrust.ca')
                ->send(new RentalApplicationDeclinedMail($this->record));
        } catch (\Throwable $e) {
            Log::error('Rental application decline email failed: ' . $e->getMessage(), [
                'application_id' => $this->record->id,
                'email' => $this->record->email,
            ]);
        }
    }

    /**
     * Email the applicant the approval + next-steps notice. A send failure
     * must not block the approval itself.
     */
    private function notifyApplicantOfApproval(): void
    {
        if (empty($this->record->email)) {
            return;
        }

        try {
            Mail::mailer('smtp')
                ->to($this->record->email)
                ->cc('info@tenanttrust.ca')
                ->send(new RentalApplicationApprovedMail($this->record));
        } catch (\Throwable $e) {
            Log::error('Rental application approval email failed: ' . $e->getMessage(), [
                'application_id' => $this->record->id,
                'email' => $this->record->email,
            ]);
        }
    }
}
