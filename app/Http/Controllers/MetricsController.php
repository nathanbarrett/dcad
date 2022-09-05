<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    public function __construct(
        private MetricsService $metricsService,
    ) {
    }

    public function index(Request $request): View
    {
        $days = (int) $request->get('days', 30);
        $zipCodes = $request->get('zip_codes', ["75230", "75220"]);
        $dailyCounts = $this->metricsService->dailyPropertyChanges($days, $zipCodes)->toArray();

        return view('metrics.index', compact('dailyCounts', 'days', 'zipCodes'));
    }

    public function getDailyChangesDetected(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $zipCodes = $request->get('zip_codes', ["75230", "75220"]);
        $dailyCounts = $this->metricsService->dailyPropertyChanges($days, $zipCodes)->toArray();

        return response()->json(compact('dailyCounts', 'days', 'zipCodes'));
    }
}
