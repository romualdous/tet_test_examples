<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class UserHasEmailSet implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ! is_null(auth()->user()->email);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Currently signed-in user has no e-mail address set in system.';
    }
}
