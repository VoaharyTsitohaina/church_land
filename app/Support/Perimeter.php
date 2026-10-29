<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Perimeter
{
    /** 'district' | 'federation' | 'all' */
    public static function type(?User $user = null): string
    {
        /** @var User $user */
        $user ??= Auth::user();

        if ($user->district_id)   return 'district';
        if ($user->federation_id) return 'federation';

        return 'all';
    }

    public static function properties(Builder $query): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return match (self::type($user)) {
            'district'   => $query->whereHas('church', fn ($q) => $q->where('district_id', $user->district_id)),
            'federation' => $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $user->federation_id)),
            default      => $query,
        };
    }

    public static function churches(Builder $query): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return match (self::type($user)) {
            'district'   => $query->where('district_id', $user->district_id),
            'federation' => $query->whereHas('district', fn ($q) => $q->where('federation_id', $user->federation_id)),
            default      => $query,
        };
    }

    public static function districts(Builder $query): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return match (self::type($user)) {
            'district'   => $query->where('id', $user->district_id),
            'federation' => $query->where('federation_id', $user->federation_id),
            default      => $query,
        };
    }

    public static function federations(Builder $query): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return match (self::type($user)) {
            'district'   => $query->whereHas('districts', fn ($q) => $q->where('id', $user->district_id)),
            'federation' => $query->where('id', $user->federation_id),
            default      => $query,
        };
    }
}