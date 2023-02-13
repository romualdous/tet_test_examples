<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Transaction
 *
 * @property int $id
 * @property int $wallet_id
 * @property int $amount
 * @property string $type
 * @property string|null $payment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereWalletId($value)
 * @mixin \Eloquent
 * @method static \Database\Factories\TransactionFactory factory(...$parameters)
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'wallet_id',
        'amount',
        'minutes',
        'payment_id',
        'type',
        'user_id'
    ];

    const TYPE_DEPOSIT = 'deposit';

    const TYPE_WITHDRAW = 'withdraw';

    const TYPE_TRIAL = 'trial';

    public static array $allowedTypes = [
        self::TYPE_DEPOSIT,
        self::TYPE_WITHDRAW,
        self::TYPE_TRIAL
    ];

    /**
     * @param string $value
     * @throws Exception
     */
    public function setTypeAttribute(string $value): void
    {
        $types = implode(', ', self::$allowedTypes);

        if (!in_array($value, self::$allowedTypes)) {
            throw new Exception("Invalid transaction type. Only allowed types: {$types}");
        }

        $this->attributes['type'] = $value;
    }

    /**
     * @return BelongsTo
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return bool
     */
    public function hasAssociatedPayment(): bool
    {
        return !is_null($this->payment_id);
    }

    /**
     * @return Payment|null
     */
    public function payment(): ?Payment
    {
        return Payment::where('payment_id', $this->payment_id)->firstOrFail();
    }
}
