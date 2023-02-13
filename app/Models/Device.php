<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Device
 *
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\DeviceFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Device newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Device newQuery()
 * @method static \Illuminate\Database\Query\Builder|Device onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Device query()
 * @method static \Illuminate\Database\Query\Builder|Device withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Device withoutTrashed()
 * @mixin \Eloquent
 */
class Device extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'device_id',
        'type',
        'user_id',
        'user_agent',
        'deleted_at',
    ];

    /**
     * Eager loaded relationships.
     */
    protected $with = ['user'];

    /**
     * Device belongs to single user.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Is the device without owner.
     *
     * @return bool
     */
    public function stale(): bool
    {
        return ! $this->user_id;
    }
}
