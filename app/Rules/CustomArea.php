<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CustomArea implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $coordinates) {
            if (
                !$this->isValidLatitude($coordinates['lat'] ?? 999)
                || !$this->isValidLongitude($coordinates['lng'] ?? 999)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'Invalid custom area.';
    }

    private function isValidLatitude(float $latitude): bool
    {
        return $latitude >= -90 && $latitude <= 90;
    }

    private function isValidLongitude(float $longitude): bool
    {
        return $longitude >= -180 && $longitude <= 180;
    }
}
