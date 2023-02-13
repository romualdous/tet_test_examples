<?php

namespace App\Exceptions;

class PushNotificationException extends ApiException
{
    /**
     * @return static
     */
    public static function deviceTokenNotSet(): self
    {
        return new self("Data message cannot be sent to listener, because listener device_token haven't been set");
    }

    /**
     * @param array $possibleChannels
     * @return static
     */
    public static function invalidChannelSpecified(array $possibleChannels): self
    {
        $possibleChannels = implode(', ', $possibleChannels);

        return new self(
            "Invalid FCM instance provided. Possible instances: {$possibleChannels}"
        );
    }
}
