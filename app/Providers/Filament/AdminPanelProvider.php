<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\ShopStatsOverview;
use App\Http\Middleware\RequireStaffTwoFactor;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->id('admin')
            ->path('kms-login') // Backend-Adresse laut Kundenwunsch
            ->login(Login::class)
            ->brandName('Armory Falter')
            ->brandLogo(asset('images/brand/armory-falter-logo.svg'))
            ->darkModeBrandLogo(asset('images/brand/armory-falter-logo-light.svg'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/brand/favicon.svg'))
            ->colors([
                'primary' => Color::hex('#9d4640'),
                'gray' => Color::Zinc,
            ])
            ->font('Poppins')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->navigationGroups(['Verkauf', 'Katalog', 'Kunden & Prüfung', 'Service', 'Marketing', 'Inhalte', 'System'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                ShopStatsOverview::class,
                LatestOrders::class,
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
                RequireStaffTwoFactor::class,
            ]);
    }
}
