<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZipCodesController extends Controller
{
    public function all(): JsonResponse
    {
        return response()->json([
            'zip_codes' => config('zip_codes.all')
        ]);
    }

    public function dallas(): JsonResponse
    {
        return response()->json([
            'zip_codes' => config('zip_codes.dallas')
        ]);
    }
}
