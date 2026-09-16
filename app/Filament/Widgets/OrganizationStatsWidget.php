<?php

namespace App\Filament\Widgets;

use App\Models\Church;
use App\Models\District;
use App\Models\Federation;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class OrganizationStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $heading = 'Structure organisationnelle';

    #[On('dashboard-filter-updated')]
    public function refreshStats(): void
    {
        // vide : force Livewire à re-render le widget (donc relire la session)
    }

    // Résout federation_id/district_id à partir du filtre le plus précis choisi
    // (ex: si seule une église est sélectionnée, on retrouve son district et sa fédération)
    protected function effectiveFilterIds(): array
    {
        $churchId = session('dashboard_filter.church_id');
        $districtId = session('dashboard_filter.district_id');
        $federationId = session('dashboard_filter.federation_id');

        if ($churchId) {
            $church = Church::with('district')->find($churchId);
            $districtId = $districtId ?? $church?->district_id;
            $federationId = $federationId ?? $church?->district?->federation_id;
        } elseif ($districtId) {
            $district = District::find($districtId);
            $federationId = $federationId ?? $district?->federation_id;
        }

        return [
            'federation_id' => $federationId,
            'district_id' => $districtId,
            'church_id' => $churchId,
        ];
    }

    // --- Requêtes filtrées, une par entité (rôle + filtre manuel admin) ---

    protected function federationsQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Federation::query();

        if ($user->hasRole('district_manager')) {
            $query->whereHas('districts', fn ($q) => $q->where('id', $user->district_id));
        } elseif ($user->hasRole('federation_admin')) {
            $query->where('id', $user->federation_id);
        }

        if ($id = $this->effectiveFilterIds()['federation_id']) {
            $query->where('id', $id);
        }

        return $query;
    }

    protected function districtsQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = District::query();

        if ($user->hasRole('district_manager')) {
            $query->where('id', $user->district_id);
        } elseif ($user->hasRole('federation_admin')) {
            $query->where('federation_id', $user->federation_id);
        }

        $filters = $this->effectiveFilterIds();

        if ($filters['district_id']) {
            $query->where('id', $filters['district_id']);
        } elseif ($filters['federation_id']) {
            $query->where('federation_id', $filters['federation_id']);
        }

        return $query;
    }

    protected function churchesQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Church::query();

        if ($user->hasRole('district_manager')) {
            $query->where('district_id', $user->district_id);
        } elseif ($user->hasRole('federation_admin')) {
            $query->whereHas('district', fn ($q) => $q->where('federation_id', $user->federation_id));
        }

        if ($churchId = session('dashboard_filter.church_id')) {
            $query->where('id', $churchId);
        } elseif ($districtId = session('dashboard_filter.district_id')) {
            $query->where('district_id', $districtId);
        } elseif ($federationId = session('dashboard_filter.federation_id')) {
            $query->whereHas('district', fn ($q) => $q->where('federation_id', $federationId));
        }

        return $query;
    }

    protected function propertiesQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Property::query();

        if ($user->hasRole('district_manager')) {
            $query->whereHas('church', fn ($q) => $q->where('district_id', $user->district_id));
        } elseif ($user->hasRole('federation_admin')) {
            $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $user->federation_id));
        }

        if ($churchId = session('dashboard_filter.church_id')) {
            $query->where('church_id', $churchId);
        } elseif ($districtId = session('dashboard_filter.district_id')) {
            $query->whereHas('church', fn ($q) => $q->where('district_id', $districtId));
        } elseif ($federationId = session('dashboard_filter.federation_id')) {
            $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $federationId));
        }

        return $query;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Biens', $this->propertiesQuery()->count())
                ->description('Patrimoine enregistré')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('primary'),

            Stat::make('Églises', $this->churchesQuery()->count())
                ->description('Églises enregistrées')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),

            Stat::make('Districts', $this->districtsQuery()->count())
                ->description('Districts administratifs')
                ->descriptionIcon('heroicon-m-map')
                ->color('warning'),

            Stat::make('Fédérations', $this->federationsQuery()->count())
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