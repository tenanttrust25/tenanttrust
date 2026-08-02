<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Field;

class FilamentFixServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Add the missing areHtmlValidationMessagesAllowed method to Field
        Field::macro('areHtmlValidationMessagesAllowed', function () {
            return true;
        });
    }
}
