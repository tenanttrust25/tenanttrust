<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ManagerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id("manager")
            ->path("manager")
            ->login()
            ->passwordReset()
            ->brandName("Property Manager Portal")
            ->colors([
                "primary" => Color::Blue,
                "gray" => Color::Slate,
            ])
            ->darkMode(true)
            ->font("Work Sans")
            ->favicon(asset("favicon.ico"))
            ->discoverResources(in: app_path("Filament/Manager/Resources"), for: "App\\Filament\\Manager\\Resources")
            ->discoverPages(in: app_path("Filament/Manager/Pages"), for: "App\\Filament\\Manager\\Pages")
            ->discoverWidgets(in: app_path("Filament/Manager/Widgets"), for: "App\\Filament\\Manager\\Widgets")
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                "role.manager",
            ]);
    }
}
