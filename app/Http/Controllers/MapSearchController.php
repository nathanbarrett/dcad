<?php

namespace App\Http\Controllers;

use App\Http\Requests\MapSearchRequest;
use App\Models\Property;
use App\Services\MapSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 *
 */
class MapSearchController extends Controller
{
    public function __construct(private MapSearchService $mapSearchService)
    {
        //
    }

    public function index(Request $request)
    {
        $days = (int) $request->get('days', 120);

        $properties = $this->mapSearchService
            ->searchProperties(deedTransferDayAge: $days)
            ->toArray();

        $availableZipCodes = $this->mapSearchService
            ->geocodedZipCodes()
            ->toArray();

        activity()->log('Map Search Page Viewed');

        return view('search.map', compact('properties', 'availableZipCodes'));
    }

    public function mapSearch(MapSearchRequest $request): JsonResponse
    {
        $zipCodes = $request->get('zip_codes', []);
        $days = (int) $request->get('deed_transfer_days_old', 120);

        $properties = $this->mapSearchService
            ->searchProperties(
                zipCodes: $zipCodes,
                deedTransferDayAge: $days
            )
            ->toArray();

        activity()
            ->withProperties([
                'zip_codes' => $zipCodes,
                'deed_transfer_days_old' => $days,
            ])
            ->log('Map Search Performed');

        return response()->json(compact('properties'));
    }
}
