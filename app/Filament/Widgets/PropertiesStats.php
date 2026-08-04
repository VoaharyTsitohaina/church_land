<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PropertyResource;
use App\Models\Church;
use App\Models\District;
use App\Models\Federation;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PropertiesStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make('Biens', Property::count())
                ->description('Patrimoine enregistré')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('primary'),

            Stat::make('Églises', Church::count())
                ->description('Églises enregistrées')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),

            Stat::make('Districts', District::count())
                ->description('Districts administratifs')
                ->descriptionIcon('heroicon-m-map')
                ->color('warning'),

            Stat::make('Fédérations', Federation::count())
                ->description('Fédérations')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),

            Stat::make(
                'Avec titre foncier',
                Property::whereNotNull('land_title_number')->count()
            )
                ->description('Biens sécurisés')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success'),

            Stat::make(
                'Sans titre foncier',
                Property::whereNull('land_title_number')->count()
            )
                ->description('À régulariser')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->url(
                PropertyResource::getUrl('index', [
                    'tableFilters' => [
                        'sans_titre' => [
                            'isActive' => true,
                        ],
                    ],
                ])),
            Stat::make(
                'Valeur estimée',
                number_format(
                    Property::sum('estimated_value'),
                    0,
                    ',',
                    ' '
                ) . ' Ar'
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
}