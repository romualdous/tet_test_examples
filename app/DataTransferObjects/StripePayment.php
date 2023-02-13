<?php

namespace App\DataTransferObjects;

use App\DataTransferObjects\Contracts\HoldsPaymentData;
use Illuminate\Support\Arr;

class StripePayment implements HoldsPaymentData
{
    private array $payload;
    private array $charge;

    /**
     * StripePayment constructor.
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->charge = Arr::get($this->payload, 'charges.data')[0];
    }

    /**
     * @inheritDoc
     */
    public function customer(): string
    {
        return Arr::get($this->charge, 'customer');
    }

    /**
     * @inheritDoc
     */
    public function paymentIntent(): string
    {
        return Arr::get($this->charge, 'payment_intent');
    }

    /**
     * @inheritDoc
     */
    public function amount(): int
    {
        return (int) Arr::get($this->charge, 'amount_captured');
    }

    /**
     * @inheritDoc
     */
    public function currency(): string
    {
        return Arr::get($this->charge, 'currency');
    }

    /**
     * @return string
     */
    public function chargeId(): string
    {
        return Arr::get($this->charge, 'id');
    }
}
