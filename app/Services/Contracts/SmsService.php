<?php

namespace App\Services\Contracts;

interface SmsService
{
    /**
     * Send SMS message to provided phone number.
     *
     * @param string $recipient
     * @param string|int $message
     * @return mixed
     */
    public function send(string $recipient, string|int $message): mixed;
}
