<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class AuthenticationException extends ApiException
{
    public static function typeInRequestDoesNotMatchUserType(): static
    {
        return new self("User type given in request doesn't match the user type in database");
    }

    public static function registrationThroughListenerAppIsNotAllowed(): static
    {
        return new self("User cannot register for the first time through listener application");
    }

    public static function deviceNotRegistered(): static
    {
        return new self("User hasn't registered device successfully. Make sure to send device data with POST api/devices at the time of registration before verification");
    }
}
