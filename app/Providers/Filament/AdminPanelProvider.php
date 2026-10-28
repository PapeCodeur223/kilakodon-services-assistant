<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RequireAdminMultiFactor;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
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
            ->path('admin')
            ->login()
            ->profile(isSimple: false)
            // MFA par application ; obligatoire pour les admins via RequireAdminMultiFactor (TRD §11).
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
            ], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(RequireAdminMultiFactor::class)
            ->brandName('Kilakodon Hub')
            ->brandLogo(fn () => asset('images/brand/logo-kilakodon-2x.png'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('favicon.ico'))
            // Palette Kilakodon (UX §5.8) : primaire bleu de marque, danger rouge fonctionnel.
            ->colors([
                'primary' => Color::generateV3Palette('#282A89'),
                'danger' => Color::generateV3Palette('#D4170A'),
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info' => Color::Indigo,
            ])
            ->font('Inter Variable', provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(false)
            ->navigationGroups([
                NavigationGroup::make('Commercial'),
                NavigationGroup::make('Contenus'),
                NavigationGroup::make('Assistant'),
                NavigationGroup::make('Diagnostic'),
                NavigationGroup::make('Réglages'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
