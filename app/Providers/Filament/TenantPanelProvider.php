<?php

namespace App\Providers\Filament;

use App\Filament\Tenant\Pages\Dashboard;
use App\Filament\Tenant\Pages\EditProfile;
use App\Filament\Tenant\Pages\YearEnd;
use App\Services\CloverService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('tenant')
            ->login()
            ->passwordReset()
            ->brandName('Tenant Portal')
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->darkMode(true)
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->pages([
                Dashboard::class,
                EditProfile::class,
                \App\Filament\Tenant\Pages\YearEnd::class
            ])
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
                'role.tenant',
            ]);
    }

    public function boot(): void
    {
        // Load Clover SDK globally on all tenant pages
        FilamentView::registerRenderHook(
            'panels::scripts.after',
            fn (): string => '<script src="' . CloverService::getSdkUrl() . '"></script>',
        );

        // PWA: manifest + installable app meta + service worker registration
        // (scoped to this panel — render hooks are global across all panels)
        FilamentView::registerRenderHook(
            'panels::head.start',
            fn (): string => \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'tenant'
                ? '<link rel="manifest" href="/pwa/tenant-manifest.webmanifest">\n<meta name="theme-color" content="#0F172A">\n<meta name="apple-mobile-web-app-capable" content="yes">\n<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">\n<meta name="apple-mobile-web-app-title" content="Tenant Portal">\n<link rel="apple-touch-icon" href="/pwa/icons/apple-touch-icon.png">'
                : '',
        );
        FilamentView::registerRenderHook(
            'panels::scripts.after',
            function (): string {
                if (\Filament\Facades\Filament::getCurrentPanel()?->getId() !== 'tenant') {
                    return '';
                }
                $config = json_encode([
                    'swPath' => '/tenant/sw.js',
                    'subscribeUrl' => url('/push/subscribe'),
                    'vapidPublicKey' => config('services.webpush.vapid.public_key'),
                    'authenticated' => auth()->check(),
                ]);
                $installConfig = json_encode([
                    'authenticated' => auth()->check(),
                ]);
                return '<script>
if (\'serviceWorker\' in navigator) { navigator.serviceWorker.register(\'/tenant/sw.js\'); }
window.TTPushConfig = ' . $config . ';
window.TTInstallConfig = ' . $installConfig . ';
</script>
<script src="/pwa/push-client.js"></script>
<script src="/pwa/install-prompt.js"></script>';
            },
        );
    }
}
