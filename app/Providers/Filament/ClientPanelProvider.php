<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Support\Branding;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ClientPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('client')
            ->path('client')
            ->login()
            ->profile(EditProfile::class, isSimple: false)
            ->brandName(fn (): string => Branding::name().' · Clientes')
            ->brandLogo(fn (): ?string => Branding::logoUrl())
            ->brandLogoHeight('2rem')
            ->favicon(fn (): ?string => Branding::faviconUrl())
            ->colors([
                // Color de marca configurable; por defecto el azul del portal
                // (--color-brand-600 en resources/css/app.css).
                'primary' => Color::hex(Branding::color()),
            ])
            ->font('Inter')
            // El portal propio (/panel/*) solo tiene versión clara. Con el modo
            // oscuro activo, Soporte y Facturación seguían la preferencia del
            // sistema y se veían negras junto al resto del portal en blanco.
            ->darkMode(false)
            // Combina el panel con las vistas propias del portal.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.client.theme')->render(),
            )
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\\Filament\\Client\\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\\Filament\\Client\\Pages')
            ->pages([
                \App\Filament\Client\Pages\Dashboard::class,
            ])
            // Una sola barra lateral: las secciones que ahora son vistas propias
            // se enlazan desde aquí, para que Filament (Soporte y Facturación)
            // muestre exactamente la misma navegación que <x-portal-shell>.
            ->navigationItems([
                NavigationItem::make('Inicio')
                    ->url('/panel')->icon('heroicon-o-home')->sort(0),
                NavigationItem::make('Renovaciones')
                    ->url('/panel/renovaciones')->icon('heroicon-o-arrow-path')->sort(1),
                NavigationItem::make('Mis servicios')
                    ->url('/panel/servicios')->icon('heroicon-o-server-stack')->sort(2),
                NavigationItem::make('Dominios')
                    ->url('/panel/dominios')->icon('heroicon-o-globe-alt')->sort(3),
                NavigationItem::make('Pagos')
                    ->url('/panel/pagos')->icon('heroicon-o-credit-card')->sort(4),
                NavigationItem::make('Licencias')
                    ->url('/panel/licencias')->icon('heroicon-o-key')->sort(6),
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
            ->widgets([
                \App\Filament\Client\Widgets\ClientStatsWidget::class,
                Widgets\AccountWidget::class,
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
            ]);
    }
}
