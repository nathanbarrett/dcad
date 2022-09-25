<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class MapSearchService
{
    public function geocodedZipCodes(): Collection
    {
        return DB::table('properties')
            ->selectRaw('distinct(properties.zip_code) as "zip_code"')
            ->whereNotNull('properties.lat')
            ->groupBy('properties.zip_code')
            ->get()
            ->pluck('zip_code');
    }

    public function searchProperties(array $zipCodes = [], int $deedTransferDayAge = 90): EloquentCollection
    {
        return Property::query()
            ->with(['ownerProperties' => function ($query) {
                $query->with(['owner'])
                    ->orderBy('deed_transferred_at', 'desc')
                    ->orderBy('created_at', 'desc');
            }])
            ->whereNotNull('lat')
            ->whereHas('ownerProperties', function ($query) use ($deedTransferDayAge) {
                $query->where('deed_transferred_at', '>=', now()->startOfDay()->subDays($deedTransferDayAge));
            })
            ->when(!empty($zipCodes), function ($query) use ($zipCodes) {
                return $query->whereIn('properties.zip_code', $zipCodes);
            })
            ->get();
    }
}
