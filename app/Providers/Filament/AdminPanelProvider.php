<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\AdminDashboard;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id("admin")
            ->path("admin")
            ->login()
            ->passwordReset()
            ->brandName("Admin Login")
            ->colors([
                "primary" => Color::Amber,
                "gray" => Color::Slate,
            ])
            ->darkMode(true)
            ->collapsibleNavigationGroups(true)
            ->navigationGroups([
                NavigationGroup::make('Properties'),
                NavigationGroup::make('People'),
                NavigationGroup::make('Leasing'),
                NavigationGroup::make('Rent & Billing'),
                NavigationGroup::make('Owner Finance'),
                NavigationGroup::make('Operations'),
                NavigationGroup::make('Mail'),
                NavigationGroup::make('Website'),
            ])
            ->discoverResources(in: app_path("Filament/Admin/Resources"), for: "App\\Filament\\Admin\\Resources")
            ->discoverPages(in: app_path("Filament/Admin/Pages"), for: "App\\Filament\\Admin\\Pages")
            ->pages([
                AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path("Filament/Admin/Widgets"), for: "App\\Filament\\Admin\\Widgets")
            ->widgets([])
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
                "role.admin",
            ]);
    }

    public function boot(): void
    {
        // Red "reminders due" bar at the top of every admin page (hook is global
        // across panels, so guard by panel id + authentication).
        FilamentView::registerRenderHook(
            'panels::content.start',
            function (): string {
                if (Filament::getCurrentPanel()?->getId() !== 'admin') {
                    return '';
                }

                if (! auth()->check()) {
                    return '';
                }

                return view('filament.admin.reminders-due-bar')->render();
            },
        );

        // PWA: manifest + installable app meta + service worker registration
        // (scoped to this panel — render hooks are global across all panels)
        FilamentView::registerRenderHook(
            'panels::head.start',
            fn (): string => Filament::getCurrentPanel()?->getId() === 'admin'
                ? '<link rel="manifest" href="/pwa/admin-manifest.webmanifest">' . "\n" . '<meta name="theme-color" content="#0F172A">' . "\n" . '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n" . '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n" . '<meta name="apple-mobile-web-app-title" content="Admin">' . "\n" . '<link rel="apple-touch-icon" href="/pwa/icons/apple-touch-icon.png">'
                : '',
        );
        FilamentView::registerRenderHook(
            'panels::scripts.after',
            function (): string {
                if (Filament::getCurrentPanel()?->getId() !== 'admin') {
                    return '';
                }
                $config = json_encode([
                    'swPath' => '/admin/sw.js',
                    'subscribeUrl' => url('/push/subscribe'),
                    'vapidPublicKey' => config('services.webpush.vapid.public_key'),
                    'authenticated' => auth()->check(),
                ]);
                $installConfig = json_encode([
                    'authenticated' => auth()->check(),
                ]);
                return '<script>
if (\'serviceWorker\' in navigator) { navigator.serviceWorker.register(\'/admin/sw.js\'); }
window.TTPushConfig = ' . $config . ';
window.TTInstallConfig = ' . $installConfig . ';
</script>
<script src="/pwa/push-client.js"></script>
<script src="/pwa/install-prompt.js"></script>';
            },
        );
    }
}
