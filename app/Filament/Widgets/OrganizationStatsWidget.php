<?php

namespace App\Filament\Widgets;

use App\Models\Church;
use App\Models\District;
use App\Models\Federation;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;
use App\Filament\Concerns\ScopesPropertiesByUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrganizationStatsWidget extends BaseWidget
{
    use ScopesPropertiesByUser;

    // protected static ?string $pollingInterval = null;
    protected static ?int $sort = 1;
    protected ?string $heading = 'Structure organisationnelle';

    #[On('dashboard-filter-updated')]
    public function refreshStats(): void
    {
        // Livewire re-render automatiquement getStats() au prochain cycle
    }

    protected function scopedProperties(): Builder
    {
        $query = $this->scopeQuery(Property::query());

        return $this->applyManualFilter(
            $query,
            federationId: session('dashboard_filter.federation_id'),
            districtId: session('dashboard_filter.district_id'),
            churchId: session('dashboard_filter.church_id'),
        );
    }

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
 
        if ($id = session('dashboard_filter.federation_id')) {
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
 
        if ($districtId = session('dashboard_filter.district_id')) {
            $query->where('id', $districtId);
        } elseif ($federationId = session('dashboard_filter.federation_id')) {
            $query->where('federation_id', $federationId);
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

    protected function getStats(): array
    {
        return [
            Stat::make('Biens', (clone $this->scopedProperties())->count())
                ->description('Patrimoine enregistré')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('primary'),

            Stat::make('Églises', (clone $this->churchesQuery())->count())
                ->description('Églises enregistrées')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),

            Stat::make('Districts', (clone $this->districtsQuery())->count())
                ->description('Districts')
                ->descriptionIcon('heroicon-m-map')
                ->color('warning'),

            Stat::make('Fédérations', (clone $this->federationsQuery())->count())
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