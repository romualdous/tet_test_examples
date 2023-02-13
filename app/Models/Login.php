<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Login extends Model
{
    use HasFactory;

    /**
     * Disable "updated_at" timestamp as not important.
     */
    const UPDATED_AT = null;

    /**
     * @var string[]
     */
    protected $fillable = [
        'ip_address',
        'user_agent'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
