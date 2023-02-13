<?php

namespace App\Services\Agora;

use App\Exceptions\AgoraClientException;
use App\Models\User;
use Illuminate\Support\Str;

class Client
{
    /**
     * @var User|null
     */
    private ?User $customer = null;

    /**
     * @var User|null
     */
    private ?User $listener = null;

    /**
     * @var string
     */
    private string $configFileName = 'agora';

    /**
     * @param string $channelName
     * @return string
     */
    public function createToken(string $channelName): string
    {
        $settings = config($this->configFileName);

        // TODO if not set, show as exceptions
        $appId = $settings['app_id'];
        $certificate = $settings['certificate'];
        $uid = $settings['uid'];
        $role = RtcTokenBuilder::RoleAttendee;
        $privilegeTtl = now()->timestamp + 10*60; // current timestamp + 10 minutes

        return RtcTokenBuilder::buildTokenWithUid($appId, $certificate, $channelName, $uid, $role, $privilegeTtl);
    }

    /**
     * @param User $customer
     * @return $this
     */
    public function setCustomer(User $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @param User $listener
     * @return $this
     */
    public function setListener(User $listener): self
    {
        $this->listener = $listener;

        return $this;
    }

    /**
     * @return Channel
     * @throws AgoraClientException
     */
    public function createChannel(): Channel
    {
        if (! ($this->customer && $this->listener)) {
            throw AgoraClientException::participantsNotSet();
        }

        $token = $this->createToken($name = Str::random(30));

        return new Channel($name, $token);
    }
}
