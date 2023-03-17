<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\NotificationSubscriptionType;
use App\Rules\CustomArea;
use App\Rules\DallasZipCodes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Enum;

class CreateNotificationSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(NotificationSubscriptionType::class)],
            'zip_codes' => ['required_without:custom_areas', new DallasZipCodes()],
            'custom_areas' => ['required_without:zip_codes', 'array'],
            'custom_areas.*' => ['required', new CustomArea()],
        ];
    }

    public function getFilters(): Collection
    {
        $filters = collect();

        $this->has('zip_codes') ?
            $filters->put('zip_codes', $this->input('zip_codes')) :
            $filters->put('custom_areas', $this->input('custom_areas'));

        return $filters;
    }
}
