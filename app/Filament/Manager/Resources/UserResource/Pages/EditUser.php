<?php

namespace App\Filament\Manager\Resources\UserResource\Pages;

use App\Filament\Manager\Resources\UserResource;
use App\Mail\PasswordResetMail;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Tenant updated successfully!';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resetPassword')
                ->label('Send Password Reset')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Send Password Reset Email')
                ->modalDescription('This will send the tenant an email with a link to reset their password. The link expires in 60 minutes.')
                ->modalSubmitActionLabel('Send Reset Email')
                ->action(function () {
                    $user = $this->record;

                    // Generate a password reset token
                    $token = Password::broker()->createToken($user);

                    // Build the reset URL for the Filament tenant portal
                    $resetUrl = url('/tenant/password-reset/reset/' . $token);

                    // Send the email
                    Mail::to($user->email)->send(new PasswordResetMail($user, $resetUrl));

                    Notification::make()
                        ->title('Password reset email sent to ' . $user->email)
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
