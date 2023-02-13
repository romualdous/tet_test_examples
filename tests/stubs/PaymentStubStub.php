<?php

namespace Tests\stubs;

use App\Contracts\PaymentStubContract;

class PaymentStubStub implements PaymentStubContract
{
    private int $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return int
     */
    public function amount(): int
    {
        return $this->amount;
    }
}
