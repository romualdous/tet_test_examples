<?php

namespace App\Models;

use App\Events\Calls\CallRequested;
use App\Exceptions\ConversationException;
use App\Services\Agora\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * App\Models\Conversation
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation query()
 * @mixin \Eloquent
 * @property int $id
 * @property int $caller_id
 * @property int|null $listener_id
 * @property int|null $topic_id
 * @property string $channel
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property int|null $duration
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereCallerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereListenerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereTopicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Conversation whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Rating[] $ratings
 * @property-read int|null $ratings_count
 * @method static \Database\Factories\ConversationFactory factory(...$parameters)
 */
class Conversation extends Model
{
    use HasFactory;

    /**
     * Constant representing call being initiated.
     */
    const STATUS_REQUESTED = 'requested';

    /**
     * Constant representing call getting cancelled.
     */
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Constant representing call getting answered.
     */
    const STATUS_ONGOING = 'on-going';

    /**
     * Constant representing call being done.
     */
    const STATUS_FINISHED = 'finished';

    /**
     * @return string[]
     */
    public static array $availableStatuses = [
        self::STATUS_REQUESTED,
        self::STATUS_ONGOING,
        self::STATUS_CANCELLED,
        self::STATUS_FINISHED
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'caller_id',
        'listener_id',
        'topic_id',
        'channel',
        'token',
        'started_at',
        'finished_at',
        'duration',
        'status',
        'check_time',
        'duration_billable'
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'started_at',
        'finished_at',
    ];

    /**
     * @return HasMany
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function listener()
    {
        return $this->belongsTo(User::class, 'listener_id');
    }

    public function caller()
    {
        return $this->belongsTo(User::class, 'caller_id');
    }


    /**
     * @param Authenticatable|User $caller
     * @param Authenticatable|User $listener
     * @param Topic|null $topic
     * @return static
     * @throws ConversationException
     */
    public static function start(Authenticatable|User $caller, Authenticatable|User $listener, $topic = null): self
    {
        (new self)->validate($caller, $listener);

        $channel = app(Client::class)
            ->setCustomer($caller)
            ->setListener($listener)
            ->createChannel();

        $call = self::create([
            'caller_id'   => $caller->id,
            'listener_id' => $listener->id,
            'topic_id'    => $topic,
            'channel'     => $channel->name(),
            'token'       => $channel->token(),
            'duration'    => 0,
            'status'      => self::STATUS_REQUESTED
        ]);

        event(new CallRequested($call));

        return $call;
    }

    /**
     * @param User $customer
     * @param User $listener
     * @return bool
     */
    public function hasValidParticipants(User $customer, User $listener): bool
    {
        return in_array($customer->type, [User::TYPE_BOTH, User::TYPE_CUSTOMER])
            && in_array($listener->type, [User::TYPE_BOTH, User::TYPE_LISTENER]);
    }

    /**
     * @param Authenticatable $user
     * @return bool
     */
    public function hasParticipant(Authenticatable $user): bool
    {
        return in_array($user->id, $this->participants()->pluck('id')->toArray());
    }

    /**
     * @return bool
     */
    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    /**
     * @return Collection
     */
    public function participants(): Collection
    {
        return User::findMany([$this->caller_id, $this->listener_id]);
    }

    /**
     * Channel name from Agora.
     *
     * @return string
     */
    public function channel(): string
    {
        return $this->channel;
    }

    /**
     * Channel token from Agora.
     * Only useful for initial joining to the call,
     * later on it gets regenerated for each participant.
     *
     * @return string
     */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * @param string $status
     * @throws ConversationException
     */
    public function setStatusAttribute(string $status): void
    {
        if (!in_array(strtolower($status), self::$availableStatuses)) {
            throw ConversationException::invalidStatusProvided(self::$availableStatuses);
        }

        $this->attributes['status'] = $status;
    }

    /**
     * Mark conversation as being on-going.
     * It means - listener picked up the call
     * and is currently talking with the customer.
     *
     * @return bool
     */
    public function markAsOngoing(): bool
    {
        return $this->update([
            'started_at' => now(),
            'status'     => self::STATUS_ONGOING
        ]);
    }

    /**
     * Mark conversation as cancelled.
     * It means that one of the participants
     * pressed cancel on active call.
     *
     * @return bool
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    /**
     * Mark conversation as done & dusted.
     * It means - phone call is done and both
     * participants ended the call.
     *
     * @return bool
     */
    public function markAsFinished(): bool
    {
        $conversation = tap($this)->update([
            'finished_at' => now(),
            'status'      => self::STATUS_FINISHED,
        ]);

        return $conversation->setDuration();
    }
    /**
     * Mark conversation as done & dusted.
     * Get check_time from $time and put it in finish_time.
     */
    public function checkTimeToFinish($time)
    {
        $conversation = tap($this)->update([
            'finished_at' => $time,
            'status'      => self::STATUS_FINISHED,
        ]);

        return $conversation->setDuration();
    }

    /**
     * Set call duration after call is done.
     *
     * @return bool
     * @throws ConversationException
     */
    public function setDuration(): bool
    {
        if (!$this->timestampsSet()) {
            throw ConversationException::timestampsAreNotSet();
        }

        $durationInMinutes = $this->started_at->diffInMinutes($this->finished_at, false);

        if ($durationInMinutes < 0) {
            throw ConversationException::timestampsHaveBeenManipulated();
        }
        $getWeightValue = Topic::where('id', $this->topic_id)->first();
        $durationWithWeight = $durationInMinutes * $getWeightValue->weight;

        return $this->update([
            'duration' => $durationWithWeight,
            'duration_billable' => $durationWithWeight
        ]);
    }

    /**
     * @return bool
     */
    public function isCancellable(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    /**
     * @return bool
     */
    public function isAcceptable(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    /**
     * @param Authenticatable|User $customer
     * @param Authenticatable|User $listener
     * @return bool
     */
    public static function hasActiveCallBetween(Authenticatable|User $customer, Authenticatable|User $listener): bool
    {
        $conversation = self::firstWhere([
            'caller_id'   => $customer->id,
            'listener_id' => $listener->id
        ]);

        return $conversation
            && in_array($conversation->status, [self::STATUS_REQUESTED, self::STATUS_ONGOING]);
    }

    /**
     * Perform checks before starting conversation.
     *
     * @param User $caller
     * @param User $listener
     * @throws ConversationException
     */
    private function validate(User $caller, User $listener): void
    {
        if (!$this->hasValidParticipants(customer: $caller, listener: $listener)) {
            throw ConversationException::invalidParticipants();
        }

        if ($this->attemptsToCallHimself($caller, $listener)) {
            throw ConversationException::cannotCallYourself();
        }

        if (self::hasActiveCallBetween($caller, $listener)) {
            throw ConversationException::callCurrentlyHappening();
        }

        if (!$caller->hasCredits()) {
            throw ConversationException::notEnoughCredits();
        }

        if (!$listener->isAvailableForCall()) {
            throw ConversationException::notAvailableForCall($listener);
       }
    }

    /**
     * @return bool
     */
    private function timestampsSet(): bool
    {
        return $this->started_at && $this->finished_at;
    }

    /**
     * @param User $caller
     * @param User $listener
     * @return bool
     */
    private function attemptsToCallHimself(User $caller, User $listener): bool
    {
        return $caller->id === $listener->id;
    }

    /**
     * @param User $customer
     * @param User $listener
     * @return bool
     * @deprecated Will have to change this logic due to multiple FCM instances used
     */
    private function hasDeviceTokensSet(User $customer, User $listener): bool
    {
        return $customer->device_token_customer && $listener->device_token_listener;
    }
    public function conversations_finished()
    {
        return Conversation::where('status', '=', 'finished')->count();
    }
}
