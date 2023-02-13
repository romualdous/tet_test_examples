<?php

namespace App\Exceptions;

class TransactionException extends ApiException
{
    /**
     * @param array $allowedTypes
     * @return static
     */
    public static function invalidType(array $allowedTypes): self
    {
        $types = implode(', ', $allowedTypes);

        return new self("Given type is invalid. Allowed types: {$types}");
    }

    /**
     * @return static
     */
    public static function invalidAmount(): self
    {
        return new self("Given amount must be a valid positive integer");
    }

    /**
     * @return static
     */
    public static function noWalletProvided(): self
    {
        return new self("No wallet has been provided for the transaction");
    }

    /**
     * @return static
     */
    public static function noTypeProvided(): self
    {
        return new self("No transaction type has been provided for the transaction");
    }

    /**
     * @return static
     */
    public static function noAmountProvided(): self
    {
        return new self("No amount has been provided for the transaction");
    }
}
