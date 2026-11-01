<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
            ->collapsibleNavigationGroups(true)
            ->navigationGroups([
                NavigationGroup::make('Properties'),
                NavigationGroup::make('Leasing'),
                NavigationGroup::make('Tenants'),
                NavigationGroup::make('People'),
                NavigationGroup::make('Rent & Billing'),
                NavigationGroup::make('Maintenance'),
                NavigationGroup::make('Operations'),
                NavigationGroup::make('Account'),
            ])
            ->font("Work Sans")
            ->favicon(asset("favicon.ico"))
            ->discoverResources(in: app_path("Filament/Manager/Resources"), for: "App\\Filament\\Manager\\Resources")
            ->discoverPages(in: app_path("Filament/Manager/Pages"), for: "App\\Filament\\Manager\\Pages")
            ->discoverWidgets(in: app_path("Filament/Manager/Widgets"), for: "App\\Filament\\Manager\\Widgets")
            // Tier 1: operational admin resources surfaced to the manager panel
            // (no policies / panel-specific logic in these classes — safe to share)
            ->resources([
                \App\Filament\Admin\Resources\RentalApplicationResource::class,
                \App\Filament\Admin\Resources\N9SubmissionResource::class,
                \App\Filament\Admin\Resources\FormResource::class,
                \App\Filament\Admin\Resources\PaymentResource::class,
                \App\Filament\Admin\Resources\ReminderResource::class,
                \App\Filament\Admin\Resources\CallLogEntryResource::class,
                \App\Filament\Admin\Resources\TenantFileResource::class,
            ])
            ->pages([
                \App\Filament\Admin\Pages\OutstandingInvoices::class,
                \App\Filament\Admin\Pages\OverdueInvoices::class,
            ])
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

    public function boot(): void
    {
        // PWA: manifest + installable app meta + service worker registration
        // (scoped to this panel — render hooks are global across all panels)
        FilamentView::registerRenderHook(
            'panels::head.start',
            fn (): string => \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'manager'
                ? '<link rel="manifest" href="/pwa/manager-manifest.webmanifest">' . "\n" . '<meta name="theme-color" content="#0F172A">' . "\n" . '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n" . '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n" . '<meta name="apple-mobile-web-app-title" content="Manager">' . "\n" . '<link rel="apple-touch-icon" href="/pwa/icons/apple-touch-icon.png">'
                : '',
        );
        FilamentView::registerRenderHook(
            'panels::scripts.after',
            function (): string {
                if (\Filament\Facades\Filament::getCurrentPanel()?->getId() !== 'manager') {
                    return '';
                }
                $config = json_encode([
                    'swPath' => '/manager/sw.js',
                    'subscribeUrl' => url('/push/subscribe'),
                    'vapidPublicKey' => config('services.webpush.vapid.public_key'),
                    'authenticated' => auth()->check(),
                ]);
                $installConfig = json_encode([
                    'authenticated' => auth()->check(),
                ]);
                return '<script>
if (\'serviceWorker\' in navigator) { navigator.serviceWorker.register(\'/manager/sw.js\'); }
window.TTPushConfig = ' . $config . ';
window.TTInstallConfig = ' . $installConfig . ';
</script>
<script src="/pwa/push-client.js"></script>
<script src="/pwa/install-prompt.js"></script>';
            },
        );
    }
}
