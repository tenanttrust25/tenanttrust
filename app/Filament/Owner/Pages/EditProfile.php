<?php

namespace App\Filament\Owner\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditProfile extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-user-circle";
    protected static ?string $navigationLabel = "My Profile";
    protected static ?string $title = "My Profile";
    protected static ?string $slug = "profile";
    protected static ?int $navigationSort = 1;

    protected static string $view = "filament.owner.pages.edit-profile";

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            "name" => $user->name,
            "email" => $user->email,
            "phone" => $user->phone,
            "address" => $user->address,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make("name")
                    ->label("Full Name")
                    ->required(),

                Forms\Components\TextInput::make("email")
                    ->label("Email Address")
                    ->email()
                    ->required(),

                Forms\Components\TextInput::make("phone")
                    ->label("Phone Number")
                    ->tel(),

                Forms\Components\Textarea::make("address")
                    ->label("Address")
                    ->rows(2),

                Forms\Components\Section::make("Change Password")
                    ->description("Leave blank to keep current password")
                    ->schema([
                        Forms\Components\TextInput::make("current_password")
                            ->label("Current Password")
                            ->password()
                            ->revealable(),
                        Forms\Components\TextInput::make("new_password")
                            ->label("New Password")
                            ->password()
                            ->revealable()
                            ->minLength(8),
                        Forms\Components\TextInput::make("new_password_confirmation")
                            ->label("Confirm New Password")
                            ->password()
                            ->revealable()
                            ->same("new_password"),
                    ]),
            ])
            ->statePath("data");
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        $user->update([
            "name" => $data["name"],
            "email" => $data["email"],
            "phone" => $data["phone"],
            "address" => $data["address"],
        ]);

        if (!empty($data["new_password"])) {
            if (empty($data["current_password"]) || !Hash::check($data["current_password"], $user->password)) {
                Notification::make()
                    ->danger()
                    ->title("Current password is incorrect.")
                    ->send();
                return;
            }

            $user->update(["password" => Hash::make($data["new_password"])]);

            Notification::make()
                ->success()
                ->title("Profile updated & password changed!")
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title("Profile updated!")
                ->send();
        }

        $this->form->fill([
            "name" => $user->name,
            "email" => $user->email,
            "phone" => $user->phone,
            "address" => $user->address,
        ]);
    }
}
