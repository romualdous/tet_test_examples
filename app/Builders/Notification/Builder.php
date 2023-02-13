<?php

namespace App\Builders\Notification;

use App\Exceptions\ConversationException;
use App\Exceptions\PushNotificationException;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;

class Builder
{
    /**
     * @var string
     */
    private string $channel;

    /**
     * @var array|Collection
     */
    private array|Collection $data;

    /**
     * @var string
     */
    private string $type;

    /**
     * @var User[]
     */
    private array $recipients;

    /**
     * Set Firebase CM instance where to
     * send the notification.
     *
     * @param string $name
     * @return $this
     * @throws PushNotificationException
     */
    public function channel(string $name): self
    {
        if (! in_array($name, $possibleChannels = ['customer', 'listener'])) {
            throw PushNotificationException::invalidChannelSpecified($possibleChannels);
        }

        $this->channel = $name;

        return $this;
    }

    /**
     * Set notification payload.
     *
     * @param Collection|array $data
     * @return $this
     */
    public function data(Collection|array $data): self
    {
        $this->data = $data instanceof Collection
            ? $data->toArray()
            : $data;

        return $this;
    }

    /**
     * Set the notification type for the incoming message.
     *
     * @param string $type
     * @return $this
     */
    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param User ...$users
     * @return $this
     * @throws ConversationException
     */
    public function recipients(User ...$users): self
    {
        $invalidUsers = collect($users)->first(fn (User $user) => ! ($user->device_token_customer && $user->device_token_listener));

        if (! is_null($invalidUsers)) {
            throw ConversationException::deviceTokensNotSet($invalidUsers);
        }

        $this->recipients = $users;

        return $this;
    }

    /**
     * Send message to FCM instance.
     *
     * @throws Exception
     */
    public function send(): void
    {
        $url = config('firebase.url');
        $apiKey = config('firebase.api_key');

        $fields = [
            'to'           => $this->recipients,
            'notification' => [
                'title' => $this->notification->title,
                'body'  => $this->notification->body
            ],
            'data'         => $this->data,
            'priority'     => $this->priority,
        ];

        $headers = [
            'Content-Type:application/json',
            'Authorization: key=' . $apiKey
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);

        if (! $result) {
            throw new Exception("Failed to send push notification! Message:" . curl_error($ch));
        }

        curl_close($ch);
    }
}
