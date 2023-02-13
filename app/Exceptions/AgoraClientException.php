<?php

namespace App\Exceptions;

class AgoraClientException extends ApiException
{
    /**
     * @return static
     */
    public static function participantsNotSet(): self
    {
        return new self('Participants (customer and listener) have not been set therefore channel cannot be created');
    }
}
