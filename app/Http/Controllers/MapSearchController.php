<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

/**
 *
 */
class MapSearchController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->get('days', 90);

        $properties = Property::query()
            ->select('properties.*')
            ->with(['ownerProperties' => function ($query) {
                $query->with(['owner'])
                    ->orderBy('deed_transferred_at', 'desc')
                    ->orderBy('created_at', 'desc');
            }])
            ->whereNotNull('lat')
            ->whereHas('ownerProperties', function ($query) use ($days) {
                $query->where('deed_transferred_at', '>=', now()->startOfDay()->subDays($days));
            })
            ->get()
            ->toArray();

        return view('search.map', compact('properties'));
    }
}
