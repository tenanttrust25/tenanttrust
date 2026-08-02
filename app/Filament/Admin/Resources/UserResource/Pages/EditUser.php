<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return "User updated!";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("reset_password")
                ->label("Reset Password")
                ->icon("heroicon-o-key")
                ->color("warning")
                ->requiresConfirmation()
                ->modalHeading("Reset Password")
                ->modalDescription("This will generate a new password and notify the user.")
                ->action(function () {
                    $user = $this->record;
                    $newPassword = Str::random(12);
                    $user->update(["password" => Hash::make($newPassword)]);

                    $role = $user->role === "manager" ? "Property Manager" : ($user->role === "owner" ? "Client" : ucfirst($user->role));
                    $loginUrl = match ($user->role) {
                        "manager" => url("/manager/login"),
                        "owner" => url("/owner/login"),
                        "tenant" => url("/tenant/login"),
                        default => url("/admin/login"),
                    };

                    $emailSent = false;
                    try {
                        Mail::mailer("sendmail")
                            ->to($user->email)
                            ->send(new \App\Mail\UserInvitationMail($user, $newPassword, $loginUrl));
                        $emailSent = true;
                    } catch (\Exception $e) {
                        // email failed - show in notification
                    }

                    if ($emailSent) {
                        Notification::make()
                            ->success()
                            ->title("Password reset!")
                            ->body("New password sent to {$user->email}")
                            ->send();
                    } else {
                        Notification::make()
                            ->warning()
                            ->title("Password reset - email unavailable")
                            ->body("Login: {$loginUrl}\nEmail: {$user->email}\nNew Password: {$newPassword}")
                            ->persistent()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
