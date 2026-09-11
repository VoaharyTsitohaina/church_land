<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Widgets\Widget;
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
            ->favicon(asset('sgfeNoBackground.png'))
            ->brandLogo(fn() => view('filament.brand-logo'))
            ->brandLogoHeight('auto')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->colors([
                'primary' => Color::hex('#315585'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ->plugins([
                FilamentShieldPlugin::make(),
                ActivitylogPlugin::make()
                    ->translateLogKey(fn (string $key) => match ($key) {
                        'type' => 'Type',
                        'church' => 'Église',
                        'reference' => 'Référence',
                        'name' => 'Nom',
                        'area' => 'Superficie',
                        'land_title_number' => 'Titre foncier',
                        'cadastral_number' => 'Numéro cadastral',
                        'legal_status' => 'Statut juridique',
                        'acquisition_mode' => "Mode d'acquisition",
                        'acquisition_date' => "Date d'acquisition",
                        'estimated_value' => 'Valeur estimée',
                        'current_use' => 'Utilisation actuelle',
                        'observations' => 'Observations',
                        'history' => 'Historique',
                        default => $key,
                    })
                    ->translateSubject(fn (string $subject) => match ($subject) {
                        'Property' => 'Bien',
                        'Church' => 'Église',
                        'District' => 'District',
                        'Federation' => 'Fédération',
                        'User' => 'Utilisateur',
                        'PropertyType' => 'Type de bien',
                        default => $subject,
                    }),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
