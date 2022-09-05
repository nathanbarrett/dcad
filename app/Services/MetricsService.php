<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PropertyChange;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    public function dailyPropertyChanges(int $daySpan, array $zipCodes = []): Collection
    {
        $results = DB::table('property_changes')
            ->selectRaw('
                count(property_changes.id) AS "total_changes",
                sum(if(property_changes.type = "owner_update", 1, 0)) as "ownership_updates",
                sum(if(property_changes.type = "owner_percentage_update", 1, 0)) as "percentage_updates",
                date(property_changes.created_at) AS "date"
            ')
            ->leftJoin("properties", 'properties.id', '=', 'property_changes.property_id')
            ->where('property_changes.created_at', '>=', now()->subDays($daySpan))
            ->when(!empty($zipCodes), function (Builder $query) use ($zipCodes) {
                return $query->whereIn('properties.zip_code', $zipCodes);
            })
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        return $results;
    }
}
