<?php

namespace App\Contracts;

interface PaymentStubContract
{
    /**
     * Get amount from payment.
     *
     * @return int
     */
    public function amount(): int;
}
