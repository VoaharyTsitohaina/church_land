<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PropertyResource;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LandTitleStatsWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = null;
    protected static ?int $sort = 3;
    protected ?string $heading = 'Informations foncières';

    protected function getStats(): array
    {
        return [
            Stat::make('Avec titre foncier', Property::whereNotNull('land_title_number')->count())
                ->description('Biens sécurisés')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success'),

            Stat::make('Sans titre foncier', Property::whereNull('land_title_number')->count())
                ->description('À régulariser')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->url(PropertyResource::getUrl('index', [
                    'tableFilters' => [
                        'sans_titre' => ['isActive' => true],
                    ],
                ])),

            Stat::make(
                'Valeur estimée',
                number_format(Property::sum('estimated_value'), 0, ',', ' ') . ' Ar'
            )
                ->description('Valeur totale du patrimoine')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make(
                'Surface totale',
                number_format(Property::sum('area'), 2, ',', ' ') . ' m²'
            )
                ->description('Superficie cumulée')
                ->descriptionIcon('heroicon-m-square-3-stack-3d')
                ->color('primary'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}