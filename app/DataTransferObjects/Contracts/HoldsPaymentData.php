<?php

namespace App\DataTransferObjects\Contracts;

interface HoldsPaymentData
{
    /**
     * Retrieve customer ID who paid.
     *
     * @return string
     */
    public function customer(): string;

    /**
     * Retrieve payment intent for payment.
     *
     * @return string
     */
    public function paymentIntent(): string;

    /**
     * Retrieve the amount of money paid
     * by lowest denomination.
     *
     * @return int
     */
    public function amount(): int;

    /**
     * Retrieve currency used to pay with.
     *
     * @return string
     */
    public function currency(): string;

    /**
     * Retrieve charge ID.
     *
     * @return string
     */
    public function chargeId(): string;
}
