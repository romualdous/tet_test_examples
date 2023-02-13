<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ListenerOnlineCheck
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
        $user = $request->user();
        if(is_null($user)) {
            return redirect()->back();
        }
        $d=strtotime("now");
        $d2 = date("Y-m-d H:i:s", $d);
        $user->last_activity_date = $d2;
        return $next($request);
    }
}
