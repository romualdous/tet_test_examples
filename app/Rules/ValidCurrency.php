<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidCurrency implements Rule
{
    /**
     * @var array
     */
    private array $allowedCurrencies;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //TODO: @Yevgeniy should we handle a list of currencies from the dashboard?
        $this->allowedCurrencies = config('cashier.allowed_currencies') ?? [];
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return in_array($value, $this->allowedCurrencies);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        $allowedCurrencies = implode(', ', $this->allowedCurrencies);

        $message = 'Given currency is not allowed.';

        if (count($this->allowedCurrencies)) {
            $message .= " Allowed currencies: $allowedCurrencies.";
        }

        return $message;
    }
}
