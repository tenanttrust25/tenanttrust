<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Mail\UserInvitationMail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->tempPassword = Str::random(12);
        $data["password"] = Hash::make($this->tempPassword);
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $loginUrl = match ($user->role) {
            "manager" => url("/manager/login"),
            "owner" => url("/owner/login"),
            "tenant", "previous_tenant" => url("/tenant/login"),
            default => url("/admin/login"),
        };

        $role = match ($user->role) {
            "manager" => "Property Manager",
            "owner" => "Client",
            "tenant" => "Tenant",
            "previous_tenant" => "Previous Tenant",
            default => "User",
        };

        // Try to send email
        $emailSent = false;
        try {
            Mail::mailer("smtp")
                ->to($user->email)
                ->send(new UserInvitationMail($user, $this->tempPassword, $loginUrl));
            $emailSent = true;
        } catch (\Exception $e) {
            // Email unavailable - show password in notification below
        }

        if ($emailSent) {
            Notification::make()
                ->success()
                ->title("{$role} created & invitation emailed!")
                ->body("Sent to {$user->email}")
                ->send();
        } else {
            Notification::make()
                ->warning()
                ->title("{$role} created — email unavailable")
                ->body("Login: {$loginUrl}\nEmail: {$user->email}\nTemporary Password: {$this->tempPassword}")
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected ?string $tempPassword = null;
}
