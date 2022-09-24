<?php

namespace App\Http\Controllers;

use App\Models\PropertyChange;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PropertyChangesController extends Controller
{
    public function index(Request $request): View
    {
        $propertyChangeDays = (int) $request->get('days', 30);
        $perPage = (int) $request->get('per_page', 50);
        $deedTransferredDays = (int) $request->get('deed_transferred_days');
        if ($perPage > 100) {
            $perPage = 100;
        }
        if ($perPage < 5) {
            $perPage = 5;
        }
        $zipCodes = $request->get('zip_codes', []);

        $paginator = PropertyChange::query()
            ->with('property.owners')
            ->select('property_changes.*')
            ->leftJoin('properties', 'property_changes.property_id', '=', 'properties.id')
            ->leftJoin('owner_property', 'owner_property.property_id', '=', 'properties.id')
            ->where('property_changes.created_at', '>=', now()->subDays($propertyChangeDays))
            ->when($zipCodes, function (Builder $query, $zipCodes) {
                return $query->whereIn('properties.zip_code', $zipCodes);
            })
            ->when($deedTransferredDays, function (Builder $query, $deedTransferredDays) {
                return $query->where('owner_property.deed_transferred_at', '>=', now()->startOfDay()->subDays($deedTransferredDays));
            })
            ->orderBy('owner_property.deed_transferred_at', 'desc')
            ->orderBy('property_changes.created_at', 'desc')
            ->paginate($perPage);

        return view('property.changes', compact('paginator'));
    }
}
