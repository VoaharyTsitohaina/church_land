<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesPropertiesByUser
{
    protected function scopeQuery(Builder $query): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('district_manager')) {
            $query->whereHas('church', fn ($q) => $q->where('district_id', $user->district_id));
        } elseif ($user->hasRole('federation_admin')) {
            $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $user->federation_id));
        }

        return $query;
    }

    protected function applyManualFilter(
        Builder $query,
        ?int $federationId = null,
        ?int $districtId = null,
        ?int $churchId = null
    ): Builder {
        if ($churchId) {
            $query->where('church_id', $churchId);
        } elseif ($districtId) {
            $query->whereHas('church', fn ($q) => $q->where('district_id', $districtId));
        } elseif ($federationId) {
            $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $federationId));
        }
 
        return $query;
    }

    protected function scopeQueryWithFilter(Builder $query): Builder
    {
        $query = $this->scopeQuery($query);
 
        return $this->applyManualFilter(
            $query,
            federationId: session('reports_filter.federation_id'),
            districtId: session('reports_filter.district_id'),
            churchId: session('reports_filter.church_id'),
        );
    }
    
}