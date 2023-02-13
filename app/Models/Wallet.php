<?php

namespace App\Models;

use App\Builders\Transaction\Builder as TransactionBuilder;
use App\Contracts\PaymentStubContract;
use App\Exceptions\TransactionException;
use App\Exceptions\WalletException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Wallet
 *
 * @property int $id
 * @property int $user_id
 * @property string $currency
 * @property int $balance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Transaction[] $transactions
 * @property-read int|null $transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet query()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereUserId($value)
 * @mixin \Eloquent
 * @method static \Database\Factories\WalletFactory factory(...$parameters)
 */
class Wallet extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'currency',
        'balance',
    ];

    /**
     * @param User $user
     * @param string $currency
     * @return $this|null
     */
    public static function instance(User $user, string $currency = 'eur'): ?self
    {
        $wallet = self::firstWhere(['user_id' => $user->id, 'currency' => $currency]);

        return $wallet ?? null;
    }

    /**
     * @param PaymentStubContract $payment
     * @return bool
     * @throws TransactionException
     */
    public function deposit(PaymentStubContract $payment): bool
    {
        $depositAmount = $payment->amount();

        if (! $depositAmount) {
            return false;
        }

        $this->update(['balance' => $this->balance + $depositAmount]);

        TransactionBuilder::make()
            ->wallet($this)
            ->type('deposit')
            ->amount($depositAmount)
            ->create();

        return true;
    }

    /**
     * @param int $amount
     * @return bool
     * @throws WalletException
     * @throws TransactionException
     */
    public function withdraw(int $amount): bool
    {
        if (! $this->hasSufficientFunds($amount)) {
            throw WalletException::insufficientFunds();
        }

        $this->update(['balance' => $this->balance - $amount]);

        TransactionBuilder::make()
            ->wallet($this)
            ->type('withdraw')
            ->amount($amount)
            ->create();

        return true;
    }

    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderByDesc('updated_at');
    }

    /**
     * @param int $plannedAmount Credit balance that is planned to be deducted.
     * @return bool
     */
    public function hasSufficientFunds(int $plannedAmount): bool
    {
        return $plannedAmount <= $this->balance;
    }
}
