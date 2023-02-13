<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class CheckRequestOrigins
{
    public function handle(Request $request, Closure $next)
    {
        //Waiting for header called ORIGIN.
        $getOriginHeader = $request->header('ORIGIN');
        $getEnvHeaders = explode(',',env("ORIGIN"));
        Config::set('session.secure', !Str::contains($request->fullUrl(), 'localhost'));
        // Seaching if url contains one from allowed origin
        if (in_array($getOriginHeader, $getEnvHeaders) or $getOriginHeader == null) {
            $getCurrentRequest = $next($request);
            $getCurrentRequest->header('Access-Control-Allow-Credentials','true');
            if ($getOriginHeader != null)
            {
                $getCurrentRequest->header('Access-Control-Allow-Origin',$getOriginHeader);
            }
            else
            {
                $getCurrentRequest->headers->remove('Access-Control-Allow-Origin');
            }
            return $getCurrentRequest;
        }

        return response()->json([
            'data' => 'Wrong origin'
        ], 401);
    }
}
