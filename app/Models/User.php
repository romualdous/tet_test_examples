<?php

namespace App\Models;


use App\Exceptions\ConversationException;
use App\Exceptions\UserException;
use App\Exceptions\WalletException;
use App\Notifications\ResetAdminPassword;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use stdClass;


/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|\Laravel\Sanctum\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int|null $age
 * @property string|null $phone_number
 * @property string|null $gender
 * @property string $type
 * @property string|null $verification_code
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereVerificationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User onlyAdmins()
 * @method static \Illuminate\Database\Eloquent\Builder|User onlyNormal()
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutTokens()
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $photo
 * @property string|null $bio
 * @property-read string $full_name
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoto($value)
 * @property string|null $stripe_id
 * @property string|null $card_brand
 * @property string|null $card_last_four
 * @property string|null $trial_ends_at
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCardBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCardLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTrialEndsAt($value)
 * @property float|null $rating
 * @property string|null $device_token
 * @property-read Collection|\App\Models\Rating[] $givenRatings
 * @property-read int|null $given_ratings_count
 * @property-read Collection|\App\Models\Rating[] $receivedRatings
 * @property-read int|null $received_ratings_count
 * @property-read Collection|\App\Models\Wallet[] $wallets
 * @property-read int|null $wallets_count
 * @method static \Illuminate\Database\Eloquent\Builder|User customers()
 * @method static \Illuminate\Database\Eloquent\Builder|User listeners()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeviceToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRating($value)
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @property string $status
 * @method static \Illuminate\Database\Eloquent\Builder|User online()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStatus($value)
 * @property string $date_of_birth
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFullName($value)
 * @property-read Collection|\App\Models\Device[] $devices
 * @property-read int|null $devices_count
 * @method static \Illuminate\Database\Query\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|User withoutTrashed()
 */
class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasApiTokens, InteractsWithMedia, SoftDeletes, Billable, SoftDeletes;

    const TYPE_CUSTOMER = 'customer';
    const TYPE_LISTENER = 'listener';
    const TYPE_BOTH = 'both';

    const STATUS_ONLINE = 'online';
    const STATUS_ON_CALL = 'on-call';
    const STATUS_OFFLINE = 'offline';

    public static array $availableTypes = [
        self::TYPE_CUSTOMER,
        self::TYPE_LISTENER,
        self::TYPE_BOTH
    ];

    public static array $availableStatuses = [
        self::STATUS_ONLINE,
        self::STATUS_ON_CALL,
        self::STATUS_OFFLINE
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name',
        'email',
        'photo',
        'phone_number',
        'verification_code',
        'device_token_customer',
        'device_token_listener',
        'type',
        'status',
        'rating',
        'date_of_birth',
        'gender',
        'bio',
        'deleted_at',
        'balance',
        'profile_url',
        'language',
        'profile_picture',
        'consent_to_agreement',
        'oldenough'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'verification_code',
        'deleted_at',
        'card_brand',
        'trial_ends_at',
        'card_last_four',
        'pivot'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'age'               => 'integer',
        'rating'            => 'float',
        'balance'            => 'float'
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'date_of_birth'
    ];

    protected static function booted()
    {
        static::deleting(function ($user) {

            $oldPhone = $user->phone_number;

            $user->phone_number = "DELETED_" . $user->id . "_" . substr($oldPhone, -4);

            $user->save();
        });

        static::created(function ($user) {

            $topics = Topic::all();

            $user->topics()->saveMany($topics);
        });
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Payment::class, 'payer_id', 'payment_id');
    }
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function conversationsAsListener(): HasMany
    {
        return $this->hasMany(Conversation::class, 'listener_id');
    }

    public function conversationsAsCaller(): HasMany
    {
        return $this->hasMany(Conversation::class, 'caller_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function spoken_languages(): HasMany
    {
        return $this->hasMany(LanguageUser::class);
    }

    /**
     * The topics that belong to the user.
     */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class);
    }

    /**
     * Ratings given to conversation participant.
     *
     * @return HasMany
     */
    public function givenRatings(): HasMany
    {
        return $this->hasMany(Rating::class, 'reviewer_id');
    }

    /**
     * Ratings received from conversation participant.
     *
     * @return HasMany
     */
    public function receivedRatings(): HasMany
    {
        return $this->hasMany(Rating::class, 'recipient_id');
    }

    /**
     * @return HasMany
     */
    public function logins(): HasMany
    {
        return $this->hasMany(Login::class);
    }

    /**
     * Prepends file path to picture name.
     *
     * @param  string  $value
     * @return string
     */
    public function getPhotoAttribute($value)
    {
        if ($value === null) return $value;
        return config('filesystems.profile_picture_path') . $value;
    }

    /**
     * @return Device
     */
    public function getActiveDevice(): Device
    {
        return $this->devices()->whereNull('deleted_at')->first();
    }


    /**
     * Generate a token name taking into account that its name
     * consists of device ID that user is creating it for.
     */
    public function generateTokenName(string $deviceID): string
    {
        return "user_{$this->id}_device_{$deviceID}";
    }

    /**
     * Recalculate user's average rating after rating is received.
     */
    public function recalculateRating(): void
    {

        $this->update([

            'rating' => $this->fresh()->receivedRatings()->avg('rating')
        ]);
    }

    /**
     * @param Conversation $conversation
     * @return bool
     */
    public function haveBeenRatedIn(Conversation $conversation): bool
    {
        return $conversation->ratings()->where('recipient_id', $this->id)->exists();
    }

    /**
     * Retrieve wallet for specific currency.
     *
     * @param string $currency
     * @return Wallet|null
     */
    public function getWallet(string $currency): ?Wallet
    {
        $wallet = $this->wallets()->firstWhere('currency', $currency);

        return $wallet ?? null;
    }

    /**
     * Create unique wallet for given currency.
     *
     * @param string $currency
     * @param int $amount
     * @return Wallet
     * @throws WalletException
     */
    public function addWallet(string $currency, int $amount = 0): Wallet
    {
        if ($this->wallets()->where('currency', $currency)->exists()) {
            throw WalletException::alreadyExists($currency);
        }

        return $this->wallets()->create([
            'currency' => $currency,
            'balance'  => $amount
        ]);
    }

    /**
     * @param string $codeFromRequest
     * @return bool
     */
    public function isVerificationCodeCorrect(string $codeFromRequest): bool
    {
        return Hash::check($codeFromRequest, $this->verification_code);
    }

    /**
     * @return bool
     */
    public function resetVerificationCode(): bool
    {
        return $this->update([
            'verification_code' => null
        ]);
    }

    /**
     * @param string $value
     * @throws UserException
     */
    public function setTypeAttribute(string $value): void
    {
        if (!in_array($value, self::$availableTypes)) {
            throw UserException::givenTypeInvalid(allowedTypes: self::$availableTypes);
        }

        $this->attributes['type'] = $value;
    }

    /**
     * @param string $value
     * @throws UserException
     */
    public function setStatusAttribute(string $value): void
    {

        if (!in_array($value, self::$availableStatuses)) {
            throw UserException::givenStatusInvalid(allowedStatuses: self::$availableStatuses);
        }


        $this->attributes['status'] = $value;
    }
    /**
     * @param string|null $value
     * @throws UserException
     */
    public function setEmailAttribute(?string $value): void
    {
        // Allow for nullable.
        if (is_null($value)) {
            $this->attributes['email'] = null;

            return;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw UserException::invalidEmailFormat();
        }

        $this->attributes['email'] = $value;
    }

    /**
     * @param string $value
     * @throws UserException
     */
    public function setGenderAttribute(string $value): void
    {
        if (!in_array($value, ['male', 'female'])) {
            throw UserException::givenGenderInvalid(allowedGenders: ['male', 'female']);
        }

        $this->attributes['gender'] = $value;
    }

    /**
     * Clear all user tokens (without administrative tokens).
     */
    public function deleteTokens(): void
    {
        $this->tokens()->where('abilities', '!=', 'admin-access')->delete();
    }

    /**
     * Check for given ability (outside of middleware context).
     *
     * @param string $ability
     * @return bool
     */
    public function hasAbility(string $ability): bool
    {
        if (!$this->tokens()->count()) {
            return false;
        }

        return (bool) $this->tokensWithAbilities()
            ->filter(
                fn (PersonalAccessToken $token) => in_array($ability, array_values($token->abilities))
            )->count();
    }

    /**
     * Get tokens who have any kind of ability except for "*".
     *
     * @return Collection
     */
    public function tokensWithAbilities(): Collection
    {
        return $this->tokens()->where('abilities', '!=', '["*"]')->get();
    }

    /**
     * Scope only administrators.
     *
     * @param $query
     * @return mixed
     */
    public function scopeOnlyAdmins($query)
    {
        return $query->whereHas('tokens', function ($query) {
            $query->where('abilities', '["admin-access"]');
        });
    }

    /**
     * Scope only normal users.
     *
     * @param $query
     * @return mixed
     */
    public function scopeOnlyNormal($query)
    {
        return $query->whereHas('tokens', function ($query) {
            $query->where('abilities', '["*"]');
        });
    }

    /**
     * @param $query
     * @return mixed
     */
    public static function scopeCustomers($query)
    {
        return $query->whereIn('type', [User::TYPE_BOTH, User::TYPE_CUSTOMER]);
    }

    /**
     * @param $query
     * @return mixed
     */
    public static function scopeListeners($query)
    {
        return $query->whereIn('type', [User::TYPE_BOTH, User::TYPE_LISTENER]);
    }

    /**
     * @param $query
     * @return mixed
     */
    public static function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeWithoutTokens($query)
    {
        return $query->whereDoesntHave('tokens');
    }

    /**
     * Users that speak at least one language from array.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $languages
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSpeaks($query, $languages)
    {
        return $query->whereHas('spoken_languages', function (Builder $query) use ($languages) {
            $query->whereIn('language', $languages);
        });
    }

    /**
     * Users that are subsribed to the topic.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $topic_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopic($query, $topic_id)
    {

        return $query->whereHas('topics', function (Builder $query) use ($topic_id) {
            $query->where('topic_id', $topic_id);
        });
    }
    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetAdminPassword($token));
    }

    /**
     * @return bool
     */
    public function resetProfileImage(): bool
    {
        $this->clearMediaCollection('photo');

        $this->update([
            'photo' => $this->createDefaultProfilePhoto()
        ]);

        return true;
    }

    /**
     * Register 'photo' collection for profile.
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('photo')
            ->singleFile();
    }

    /**
     * @return mixed
     */
    private function createDefaultProfilePhoto(): string
    {
        return "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->email))) . "&s=64";
    }

    /**
     * Determine is user is active on system (can be on call currently).
     *
     * @return bool
     * @throws Exception
     */
    public function isOnline(): bool
    {
        return in_array($this->status, [self::STATUS_ONLINE, self::STATUS_ON_CALL]);
    }

    /**
     * @return bool
     */
    public function isAvailableForCall(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    /**
     * @return bool
     */
    public function isBothApplicationUser(): bool
    {
        return $this->type === self::TYPE_BOTH;
    }

    /**
     * @return bool
     */
    public function isCustomer(): bool
    {
        return $this->type === self::TYPE_CUSTOMER;
    }

    /**
     * @return bool
     */
    public function isListener(): bool
    {
        return $this->type === self::TYPE_LISTENER;
    }

    /**
     * Get the correct user type that should be set.
     *
     * @param string $phoneNumber
     * @param string $typeProvided
     * @return string
     */
    public static function getCorrectType(string $phoneNumber, string $typeProvided): string
    {
        $user = User::firstWhere('phone_number', $phoneNumber);

        if (!$user) {
            return $typeProvided;
        }

        // When user isn't using both app's and we want to set a new type for user that is not
        // current user type, we set it to 'both'.
        if ($user->isBothApplicationUser() || $typeProvided !== $user->type) {
            return self::TYPE_BOTH;
        }

        // Completely new users get assigned the type
        // they get from the appropriate app.
        return $typeProvided;
    }

    /**
     * @param User $listener
     * @param Topic|null $topic
     * @return Conversation
     * @throws ConversationException
     */
    public function startConversationWith(User $listener, Topic $topic = null): Conversation
    {
        return Conversation::start($this, $listener, $topic);
    }

    /**
     * @return bool
     */
    public function hasCredits(): bool
    {
        return true;
    }

    public function getTransactions($startdate,$enddate)
    {
        $all = [];

// Transactions

        $transactions = DB::table('transactions');
        $transactions->join('payments', 'transactions.payment_id', '=', 'payments.id');
        $transactions->select('transactions.created_at', 'transactions.type', 'transactions.amount', 'transactions.minutes');
        $transactions->where('payments.payer_id', $this->id);
        if(!is_null($startdate)) $transactions->where('transactions.created_at', '>=', $startdate);
        if(!is_null($enddate)) $transactions->where('transactions.created_at', '<=', $enddate);
        $get_transactions = $transactions->get();

        foreach ($get_transactions as $transaction) {
            $deposit = [];

            $deposit['date'] = $transaction->created_at;
            $deposit['type'] = $transaction->type;
            $deposit['amount'] = $transaction->amount;
            $deposit['minutes'] = $transaction->minutes;

            $all[] = $deposit;
        }
// Conversations
        $caller_array = [
            ['caller_id', '=', $this->id],
            ['status', '=', 'finished'],
            ['duration', '>', 0],
        ];
        $listener_array = [
            ['listener_id', '=', $this->id],
            ['status', '=', 'finished'],
            ['duration', '>', 0],
        ];
        if(!is_null($startdate)) {
            $caller_array[] = ['finished_at', '>=', $startdate];
            $listener_array[] = ['finished_at', '>=', $startdate];
        }
        if(!is_null($enddate)) {
            $caller_array[] = ['finished_at', '<=', $enddate];
            $listener_array[] = ['finished_at', '<=', $enddate];
            }

        $conversations = DB::table('conversations');
        $conversations->select('finished_at', 'caller_id', 'listener_id', 'duration','duration_billable','topic_id');
        $conversations->where($caller_array)->orWhere($listener_array);
        $get_conversations = $conversations->get();

        foreach ($get_conversations as $conversation) {
            $getWeightValue = Topic::where('id', $conversation->topic_id)->first();
            $deposit = [];
            $deposit['date'] = $conversation->finished_at;
            $deposit['type'] = $conversation->caller_id == $this->id ? 'Spend' : 'Earn';
            $deposit['amount'] = null;
            $deposit['minutes'] = $conversation->duration;
            $deposit['duration_billable'] = $conversation->duration_billable;
            $deposit['weight'] = $getWeightValue->weight ?? 0;

            $all[] = $deposit;
        }


        $col = collect($all);

        return $col->sortByDesc('date')->values()->all();

    }
    // Collects user id,then getting datas from conversation with statements -> count.
    public function user_dashboard_succ_calls($user)
    {
        return Conversation::where([['status', '=', 'finished'],['caller_id', $user]])->orWhere([['status', '=', 'finished'],['listener_id', $user]])->count();
    }
    // Collects user id,then getting datas from conversation with statements -> sum.
    public function user_dashboard_total_time($user)
    {
        return Conversation::where([['status', '=', 'finished'],['caller_id', $user]])->orWhere([['status', '=', 'finished'],['listener_id', $user]])->sum('duration');
    }

    /// <summary>
    ///     Method recalculate balance of user. If no user_id provided,then it's mean i need to use current accessed user from request. If otherwise,it means admin sended user_id,and i need to work with it.
    /// </summary>
    /// <returns>array</returns>

    public function recalculateBalance()
    {
        $storeUserID = $this->id;
        $trialTime = 30;

        // Storing final results of all operation in Transaction and Conversation.
        $totalTransactions = $this->sumTransactionsDeposit($storeUserID) - $this->sumTransactionsWithdraw($storeUserID);
        $totalConversations = $this->sumConversationAsListener($storeUserID) - $this->sumConversationAsCustomer($storeUserID);

        // Calculating new balance.
        $recalculatedBalance = $trialTime + $totalTransactions + $totalConversations;
        $currentUser = User::where('id', $storeUserID)->first();
        $currentUser->balance = $recalculatedBalance;
        $currentUser->save();
        return $recalculatedBalance;
    }
    public function sumTransactionsDeposit($user_id) {
        $paymentsDeposit = DB::table('transactions')
            ->where('user_id', $user_id)
            ->where('type','=', 'deposit')
            ->sum('minutes');

        return $paymentsDeposit;
    }
    public function sumTransactionsWithdraw($user_id) {
        $paymentsWithdraw = DB::table('transactions')
            ->where('user_id', $user_id)
            ->where('type', '=', 'withdraw')
            ->sum('minutes');

        return $paymentsWithdraw;
    }
    public function sumConversationAsListener($user_id) {
        $conversationsAsListener = DB::table('conversations')
            ->where([['listener_id', $user_id],['duration', '!=', null]])
            ->sum('duration_billable');

        return $conversationsAsListener;
    }
    public function sumConversationAsCustomer($user_id) {
        $conversationsAsCustomer = DB::table('conversations')
            ->where([['caller_id', $user_id],['duration', '!=', null]])
            ->sum('duration_billable');

        return $conversationsAsCustomer;
    }
}
