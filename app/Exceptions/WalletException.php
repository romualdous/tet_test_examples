<?php

namespace App\Exceptions;

class WalletException extends ApiException
{
    /**
     * @param string $currency
     * @return static
     */
    public static function alreadyExists(string $currency): self
    {
        return new self("Wallet for '{$currency}' currency already exists.");
    }

    /**
     * @return static
     */
    public static function insufficientFunds(): self
    {
        return new self("Wallet has insufficient funds.");
    }
}
