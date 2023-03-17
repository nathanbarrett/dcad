<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DallasZipCodes implements Rule
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
        return is_array($value) && count(array_diff($value, config('zip_codes.dallas'))) === 0;
    }

    public function message(): string
    {
        return 'Invalid Dallas zip codes.';
    }
}
