<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidPhoneNumber implements Rule
{
    /**
     * @var array|string[]
     */
    private array $allowedCountryCallingCodes = [
        '+371', '+372', '+370'
    ];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return str_starts_with($value, '+');

        // Uncomment to restrict phone numbers to specific countries:
        // && in_array(str_split($value, 4)[0], $this->allowedCountryCallingCodes);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'Provided :attribute is not valid.';
    }
}
