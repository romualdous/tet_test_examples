<?php

namespace Tests\Unit;

use App\Models\Transaction;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function type_can_be_set()
    {
        $transaction = new Transaction;

        $transaction->type = 'deposit';
        $this->assertEquals('deposit', $transaction->type);

        $transaction->type = 'withdraw';
        $this->assertEquals('withdraw', $transaction->type);

        $this->expectException(Exception::class);
        $transaction->type = 'invalid_type';
    }
}
