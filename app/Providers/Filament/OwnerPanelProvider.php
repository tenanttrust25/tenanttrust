<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class OwnerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id("owner")
            ->path("owner")
            ->login()
            ->passwordReset()
            ->brandName("Client Portal")
            ->colors([
                "primary" => Color::Amber,
                "gray" => Color::Slate,
            ])
            ->font("Work Sans")
            ->darkMode(true)
            ->favicon(asset("favicon.ico"))
            ->discoverResources(in: app_path("Filament/Owner/Resources"), for: "App\\Filament\\Owner\\Resources")
            ->discoverPages(in: app_path("Filament/Owner/Pages"), for: "App\\Filament\\Owner\\Pages")
            ->discoverWidgets(in: app_path("Filament/Owner/Widgets"), for: "App\\Filament\\Owner\\Widgets")
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
                "role.owner",
            ]);
    }

    public function boot(): void
    {
        // PWA: manifest + installable app meta + service worker registration
        // (scoped to this panel — render hooks are global across all panels)
        FilamentView::registerRenderHook(
            'panels::head.start',
            fn (): string => \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'owner'
                ? '<link rel="manifest" href="/pwa/owner-manifest.webmanifest">
<meta name="theme-color" content="#0F172A">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Client Portal">
<link rel="apple-touch-icon" href="/pwa/icons/apple-touch-icon.png">'
                : '',
        );
        FilamentView::registerRenderHook(
            'panels::scripts.after',
            function (): string {
                if (\Filament\Facades\Filament::getCurrentPanel()?->getId() !== 'owner') {
                    return '';
                }
                $config = json_encode([
                    'swPath' => '/owner/sw.js',
                    'subscribeUrl' => url('/push/subscribe'),
                    'vapidPublicKey' => config('services.webpush.vapid.public_key'),
                    'authenticated' => auth()->check(),
                ]);
                $installConfig = json_encode([
                    'authenticated' => auth()->check(),
                ]);
                return '<script>
if (\'serviceWorker\' in navigator) { navigator.serviceWorker.register(\'/owner/sw.js\'); }
window.TTPushConfig = ' . $config . ';
window.TTInstallConfig = ' . $installConfig . ';
</script>
<script src="/pwa/push-client.js"></script>
<script src="/pwa/install-prompt.js"></script>';
            },
        );
    }
}
