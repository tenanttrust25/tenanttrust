<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditProfile extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = "heroicon-o-user";
    protected static ?string $title = "My Profile";
    protected static ?string $navigationLabel = "My Profile";
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = "profile";

    protected static string $view = "filament.tenant.profile";

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            "name" => $user->name,
            "email" => $user->email,
            "phone" => $user->phone,
            "mailing_address" => $user->mailing_address,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make("Your Details")
                    ->description("View and update your personal information.")
                    ->schema([
                        TextInput::make("name")
                            ->label("Name")
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make("email")
                            ->label("Email")
                            ->email()
                            ->required(),
                        TextInput::make("phone")
                            ->label("Phone")
                            ->tel()
                            ->maxLength(20),
                        TextInput::make("mailing_address")
                            ->label("Mailing Address")
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make("Change Password")
                    ->description("Leave blank to keep current password.")
                    ->schema([
                        TextInput::make("current_password")
                            ->label("Current Password")
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                        TextInput::make("new_password")
                            ->label("New Password")
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                        TextInput::make("new_password_confirmation")
                            ->label("Confirm New Password")
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                    ])
                    ->columns(3),
            ])
            ->statePath("data");
    }

    public function save(): void
    {
        $user = Auth::user();
        $data = $this->form->getState();

        $user->email = $data["email"];
        $user->phone = $data["phone"];

        // Handle password change
        if (!empty($data["current_password"]) && !empty($data["new_password"])) {
            if (!Hash::check($data["current_password"], $user->password)) {
                Notification::make()
                    ->title("Current password is incorrect.")
                    ->danger()
                    ->send();
                return;
            }

            if ($data["new_password"] !== $data["new_password_confirmation"]) {
                Notification::make()
                    ->title("New passwords do not match.")
                    ->danger()
                    ->send();
                return;
            }

            $user->password = Hash::make($data["new_password"]);
        }

        $user->save();

        Notification::make()
            ->title("Profile updated successfully.")
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make("save")
                ->label("Save Changes")
                ->submit("save")
                ->keyBindings(["mod+s"]),
        ];
    }
}
