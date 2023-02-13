<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Rating
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $reviewer_id
 * @property int $recipient_id
 * @property int|null $feels_better
 * @property int|null $would_talk_again
 * @property int|null $rating
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $recipient
 * @property-read \App\Models\User $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder|Rating newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating query()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereFeelsBetter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereRecipientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereWouldTalkAgain($value)
 * @mixin \Eloquent
 * @method static \Database\Factories\RatingFactory factory(...$parameters)
 */
class Rating extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'conversation_id',
        'reviewer_id',
        'recipient_id',
        'feels_better',
        'would_talk_again',
        'rating',
        'comment'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'rating' => 'integer'
    ];

    /**
     * User who made rating about recipient.
     *
     * @return BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * User who received rating from reviewer.
     *
     * @return BelongsTo
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
