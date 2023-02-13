<?php

namespace App\Builders\Transaction;

use App\Exceptions\TransactionException;
use App\Models\Transaction;
use App\Models\Wallet;

class Builder
{
    /**
     * @var string|null
     */
    private ?string $type = null;

    /**
     * @var int|null
     */
    private ?int $amount = null;

    /**
     * @var Wallet|null
     */
    private ?Wallet $wallet = null;

    /**
     * @var string|null
     */
    private ?string $paymentId = null;

    /**
     * Builder constructor.
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * @param string $type
     * @return $this
     * @throws TransactionException
     */
    public function type(string $type): self
    {
        if (! in_array($type, $allowedTypes = Transaction::$allowedTypes)) {
            throw TransactionException::invalidType($allowedTypes);
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     * @throws TransactionException
     */
    public function amount(int $amount): self
    {
        if ($amount < 1) {
            throw TransactionException::invalidAmount();
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function paymentId(string $id): self
    {
        $this->paymentId = $id;

        return $this;
    }

    /**
     * @param Wallet $wallet
     * @return $this
     */
    public function wallet(Wallet $wallet): self
    {
        $this->wallet = $wallet;

        return $this;
    }

    /**
     * @return mixed
     * @throws TransactionException
     */
    public function create(): Transaction
    {
        if (! $this->wallet) {
            throw TransactionException::noWalletProvided();
        }

        if (! $this->type) {
            throw TransactionException::noTypeProvided();
        }

        if (! $this->amount) {
            throw TransactionException::noAmountProvided();
        }

        return $this->wallet->transactions()->create([
            'type'       => $this->type,
            'amount'     => $this->amount,
            'payment_id' => $this->paymentId,
        ]);
    }
}
