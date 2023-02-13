<?php

namespace App\Services;

use App\Exceptions\Services\Text2ReachServiceException;
use App\Services\Contracts\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class Text2ReachService implements SmsService
{
    /**
     * @var string
     */
    private string $url = 'https://my.text2reach.com/api/1.1/sms/bulk/?';

    /**
     * @var array|string[]
     */
    private array $errorCodes = [
        10 => 'General system error',
        11 => 'Wrong API key for the request',
        12 => 'Wrong message Id',
        14 => 'Wrong source address',
        15 => 'Wrong destination status',
        16 => 'Wrong "type", must be "txt" or "bin"',
        17 => 'Wrong message length (empty)',
        18 => 'Wrong message length (too long)',
        19 => 'Wrong "schedule" value',
        20 => 'Wrong "expires" value',
        21 => 'Phone in blacklist',
        22 => 'No route destination',
        34 => 'Message failed',
        35 => 'Client undefined'
    ];

    /**
     * @inheritDoc
     * @throws Text2ReachServiceException
     */
    public function send(string $recipient, int|string $message): bool
    {

        $url = $this->generateRequestUrl($recipient, $message);

        $response = Http::get($url)->body();

        if ($this->isErrorResponse($response)) {
            throw new Text2ReachServiceException(
                $this->getError($response)
            );
        }

        Redis::set(request()->ip(), time());

        return true;
    }

    /**
     * @param string $recipient
     * @param int|string $message
     * @return string
     */
    private function generateRequestUrl(string $recipient, int|string $message): string
    {
        $smsServiceConfig = config('services.sms');

        return $this->url . http_build_query([
            'api_key' => $smsServiceConfig['api_key'],
            'phone'   => $recipient,
            'from'    => $smsServiceConfig['sender'],
            'message' => $message
        ]);
    }

    /**
     * @param string $response
     * @return bool
     */
    public function isErrorResponse(string $response): bool
    {
        return array_key_exists(
            str_replace('-', '', $response),
            $this->errorCodes
        );
    }

    /**
     * @param string $response
     * @return string
     */
    private function getError(string $response): string
    {
        return $this->errorCodes[str_replace('-', '', $response)];
    }
}
