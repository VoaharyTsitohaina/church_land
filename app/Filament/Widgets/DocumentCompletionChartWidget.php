<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use App\Filament\Resources\PropertyResource;
use Filament\Widgets\ChartWidget;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class DocumentCompletionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Complétude des documents';

    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    #[On('dashboard-filter-updated')]
    public function refreshChart(): void
    {
        // Force le re-render du widget
    }

    /**
     * Biens accessibles par l'utilisateur
     * + filtres du dashboard.
     */
    protected function scopedProperties(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Property::query();

        /*
         * Scope selon le rôle
         */
        if ($user->hasRole('district_manager')) {

            $query->whereHas(
                'church',
                fn ($q) => $q->where('district_id', $user->district_id)
            );

        } elseif ($user->hasRole('federation_admin')) {

            $query->whereHas(
                'church.district',
                fn ($q) => $q->where('federation_id', $user->federation_id)
            );
        }

        /*
         * Filtres du dashboard
         */
        $churchId = session('dashboard_filter.church_id');
        $districtId = session('dashboard_filter.district_id');
        $federationId = session('dashboard_filter.federation_id');

        if ($churchId) {

            $query->where('church_id', $churchId);

        } elseif ($districtId) {

            $query->whereHas(
                'church',
                fn ($q) => $q->where('district_id', $districtId)
            );

        } elseif ($federationId) {

            $query->whereHas(
                'church.district',
                fn ($q) => $q->where('federation_id', $federationId)
            );
        }

        return $query;
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $properties = $this->scopedProperties()->get();

        /*
         * Collections de médias attendues
         */
        $requiredCollections = [
            'titre_foncier',
            'plan',
            'acte',
            'photos',
        ];

        $enRegle = 0;
        $enCours = 0;
        $nonRenseigne = 0;

        foreach ($properties as $property) {

            /*
             * Récupère les collections de médias présentes
             */
            $presentCollections = [];

            foreach ($requiredCollections as $collection) {

                if ($property->getMedia($collection)->isNotEmpty()) {
                    $presentCollections[] = $collection;
                }
            }

            /*
             * Aucun document
             */
            if (empty($presentCollections)) {

                $nonRenseigne++;

                continue;
            }

            /*
             * Tous les documents sont présents
             */
            if (count($presentCollections) === count($requiredCollections)) {

                $enRegle++;

                continue;
            }

            /*
             * Au moins un document mais il en manque
             */
            $enCours++;
        }

        return [
            'datasets' => [
                [
                    'data' => [
                        $enRegle,
                        $enCours,
                        $nonRenseigne,
                    ],

                    'backgroundColor' => [
                        '#22c55e',
                        '#FBBF24',
                        '#ef4444',
                    ],
                ],
            ],

            'labels' => ['En règle', 'En cours', 'Non renseigné',],
        ];
    }

    protected function getOptions(): RawJs
    {
        $urls = [
            PropertyResource::getUrl('index', ['tableFilters' => ['docs_complete' => ['isActive' => true]]]),
            PropertyResource::getUrl('index', ['tableFilters' => ['docs_partial' => ['isActive' => true]]]),
            PropertyResource::getUrl('index', ['tableFilters' => ['docs_missing' => ['isActive' => true]]]),
        ];
 
        $urlsJs = '[' . implode(',', array_map(
            fn ($url) => "'" . addslashes($url) . "'",
            $urls
        )) . ']';
 
        return RawJs::make(<<<JS
        {
            onClick: (event, elements) => {
                const urls = {$urlsJs};
                if (elements.length > 0 && urls[elements[0].index]) {
                    window.location.href = urls[elements[0].index];
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            },
            plugins: {
                legend: {
                    onClick: (event, legendItem) => {
                        const urls = {$urlsJs};
                        if (urls[legendItem.index]) {
                            window.location.href = urls[legendItem.index];
                        }
                    }
                }
            }
        }
        JS);
    }
}