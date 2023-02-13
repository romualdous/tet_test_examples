<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ThrottleSms
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $lastRequest = Redis::get(request()->ip());

        $tajm = Carbon::createFromTimestamp($lastRequest);

        $diff = $tajm->diffInSeconds(Carbon::now());

        if ($diff < 30) {
            throw new ThrottleRequestsException("To many requests");
        }

        return $next($request);
    }
}
