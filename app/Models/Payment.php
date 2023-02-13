<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';

    private static array $availableStatuses = [
        self::STATUS_SUCCEEDED,
        self::STATUS_FAILED
    ];

    protected $fillable = [
        'payer_id',
        'charge_id',
        'payment_intent',
        'amount',
        'currency',
        'status',

    ];

    /**
     * @return BelongsTo
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Whether or not the charge is successful.
     *
     * @return bool
     */
    public function paid(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }
}
