<?php

namespace App\Filament\Widgets;

use App\Models\Church;
use App\Models\District;
use App\Models\Federation;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrganizationStatsWidget extends BaseWidget
{
    // protected static ?string $pollingInterval = null;
    protected static ?int $sort = 1;
    protected ?string $heading = 'Structure organisationnelle';

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
                ->description('Districts')
                ->descriptionIcon('heroicon-m-map')
                ->color('warning'),

            Stat::make('Fédérations', Federation::count())
                ->description('Fédérations/Missions')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}