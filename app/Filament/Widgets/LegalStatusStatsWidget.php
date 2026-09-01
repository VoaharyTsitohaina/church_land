<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;
use App\Filament\Concerns\ScopesPropertiesByUser;

class LegalStatusStatsWidget extends BaseWidget
{
    use ScopesPropertiesByUser;
    // protected static ?string $pollingInterval = null;
    protected static ?int $sort = 2;
    protected ?string $heading = "État de complétude des dossiers";

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

    protected function requiredFields(): array
    {
        return [
            'area',
            'land_title_number',
            'cadastral_number',
            'legal_status',
            'acquisition_mode',
            'acquisition_date',
        ];
    }

    protected function getStats(): array
    {
        $fields = $this->requiredFields();

        $enRegle = (clone $this->scopedProperties());
        foreach ($fields as $field) {
            $enRegle->whereNotNull($field);
        }

        $nonRenseigne = (clone $this->scopedProperties());
        foreach ($fields as $field) {
            $nonRenseigne->whereNull($field);
        }

        $enRegleCount = $enRegle->count();
        $nonRenseigneCount = $nonRenseigne->count();
        $enCoursCount = (clone $this->scopedProperties())->count() - $enRegleCount - $nonRenseigneCount;

        return [
            Stat::make('En règle', $enRegleCount)
                ->description('Tous les champs obligatoires renseignés')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('En cours', $enCoursCount)
                ->description('Dossier partiellement complété')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Non renseigné', $nonRenseigneCount)
                ->description('Aucune information foncière saisie')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}