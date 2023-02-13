<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\Topic
 *
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder|Topic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Topic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Topic query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $photo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Topic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topic whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topic wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topic whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topic whereUpdatedAt($value)
 * @method static \Database\Factories\TopicFactory factory(...$parameters)
 */
class Topic extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'description',
        'photo',
        'weight'
    ];
    protected $hidden = [

        'pivot'
    ];

    /**
     * The users that belong to the topic.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }


    /**
     * Online users that belong to the topic.
     */
    public function onlineUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->select(['users.id', 'rating', 'full_name'])
            ->addSelect([
                'comment' => Rating::select('comment')
                    ->whereColumn('recipient_id', 'users.id')
                    ->latest()
                    ->take(1)
            ])
            ->listeners()
            ->online();
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

    public function topics()
    {
        return $this->hasMany(Conversation::class);
    }
    public function topics_finished()
    {
        return $this->hasMany(Conversation::class)->where('status', '=', 'finished');
    }
}
