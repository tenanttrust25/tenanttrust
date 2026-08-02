<?php

namespace App\Filament\Admin\Resources\ContactSubmissionResource\Pages;

use App\Filament\Admin\Resources\ContactSubmissionResource;
use App\Mail\ContactReplyMail;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewContactSubmission extends ViewRecord
{
    protected static string $resource = ContactSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('primary')
                ->form([
                    TextInput::make('subject')
                        ->label('Subject')
                        ->default('Re: Your inquiry to Tenant Trust')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(8)
                        ->placeholder('Type your reply here...')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $submission = $this->record;

                    Mail::to($submission->email)->send(
                        new ContactReplyMail(
                            $submission,
                            $data['subject'],
                            $data['message'],
                        )
                    );

                    Notification::make()
                        ->title('Reply sent to ' . $submission->email)
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
