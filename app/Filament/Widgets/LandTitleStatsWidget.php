<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ScopesPropertiesByUser;
use App\Filament\Resources\PropertyResource;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class LandTitleStatsWidget extends BaseWidget
{
    use ScopesPropertiesByUser;

    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 3;
    protected ?string $heading = 'Informations foncières';

    // Recalcule les stats quand le filtre du dashboard change
    #[On('dashboard-filter-updated')]
    public function refreshStats(): void
    {
        // Livewire re-render automatiquement getStats() au prochain cycle
    }

    protected function scopedProperties(): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->scopeQuery(Property::query());

        return $this->applyManualFilter(
            $query,
            federationId: session('dashboard_filter.federation_id'),
            districtId: session('dashboard_filter.district_id'),
            churchId: session('dashboard_filter.church_id'),
        );
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Avec titre foncier', (clone $this->scopedProperties())->whereNotNull('land_title_number')->count())
                ->description('Biens sécurisés')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success'),

            Stat::make('Sans titre foncier', (clone $this->scopedProperties())->whereNull('land_title_number')->count())
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
                number_format((clone $this->scopedProperties())->sum('estimated_value'), 0, ',', ' ') . ' Ar'
            )
                ->description('Valeur totale du patrimoine')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make(
                'Surface totale',
                number_format((clone $this->scopedProperties())->sum('area'), 2, ',', ' ') . ' m²'
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