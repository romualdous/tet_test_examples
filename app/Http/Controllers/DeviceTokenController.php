<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Refresh/set device token for current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => 'string|nullable',
            'type'         => 'required|in:customer,listener'
        ]);

        $request->user()->update([
            "device_token_{$request->get('type')}" => $request->get('device_token')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device token has been set',
            'data'    => []
        ]);
    }
}
