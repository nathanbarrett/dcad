<?php

namespace App\Http\Requests;

use App\Services\MapSearchService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /* @var MapSearchService $mapSearchService */
        $mapSearchService = app()->make(MapSearchService::class);
        return [
            'deed_transfer_days_old' => ['integer', 'min:1'],
            'zip_codes' => ['array'],
            'zip_codes.*' => ['string', Rule::in($mapSearchService->geocodedZipCodes()->toArray())],
        ];
    }
}
