<?php

namespace App\Services\Agora;

class Channel
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $token;

    /**
     * Channel constructor.
     * @param string $name
     * @param string $token
     */
    public function __construct(string $name, string $token)
    {
        $this->name = $name;
        $this->token = $token;
    }

    /**
     * Receive created channel name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Receive created channel token to
     * use in the front-end.
     *
     * @return string
     */
    public function token(): string
    {
        return $this->token;
    }
}
