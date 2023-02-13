<?php

namespace App\Jobs\Payments;

use App\Builders\Transaction\Builder as TransactionBuilder;
use App\DataTransferObjects\Contracts\HoldsPaymentData;
use App\Exceptions\TransactionException;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DepositCredits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var HoldsPaymentData
     */
    private HoldsPaymentData $payment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(HoldsPaymentData $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws TransactionException
     */
    public function handle()
    {
        /** @var User $user */
        $user = auth()->user();

        // 1. Create a Payment model in DB
        $payment = $user->payments()->create([
            'charge_id'      => $this->payment->chargeId(),
            'payment_intent' => $this->payment->paymentIntent(),
            'amount'         => $this->payment->amount(),
            'currency'       => $this->payment->currency(),
            'status'         => 'succeeded' // should be constant
        ]);
        // 2. Create a Transaction (automatically stores transaction in wallet)

        TransactionBuilder::make()
            ->amount($payment->amount)
            ->paymentId($payment->id)
            ->type(Transaction::TYPE_DEPOSIT)
            ->wallet($user->getWallet('eur'))
            ->create();
        // 3. Update user balance
    }
}
