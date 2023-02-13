<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class VerificationException extends ApiException
{
    /**
     * @return static
     */
    public static function incorrectVerificationCode(): self
    {
        return new static('Incorrect verification code');
    }

    /**
     * @return static
     */
    public static function verificationCodeIsNotSet(): self
    {
        return new static('Verification code has not been set');
    }

    /**
     * @return static
     */
    public static function verificationCodeIsAlreadySet()
    {
        return new static('Verification code has already been set');
    }

    /**
     * @return static
     */
    public static function alreadyVerified()
    {
        return new static('Verification process already completed');
    }
}
