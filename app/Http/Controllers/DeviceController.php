<?php

namespace App\Http\Controllers;

use App\Events\DeviceCreated;
use App\Events\DeviceUserUpdated;
use App\Http\Requests\StoreDeviceRequest;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDeviceRequest $request
     * @return JsonResponse
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $existingDevice = Device::withTrashed()->firstWhere('device_id', $request->get('device_id'));

        if (! $existingDevice) {
            $device = auth()->user()->devices()->create($request->validated());

            event(new DeviceCreated($device, auth()->user()));
            return response()->json([
                'success' => true,
                'message' => 'Device created successfully',
                'data'    => []
            ]);
        }

        if ($existingDevice->trashed()) {
            $existingDevice->restore();
        }

        if ($existingDevice->user_id !== auth()->id()) {
            $existingDevice->update(['user_id' => auth()->id()]);

            event(new DeviceUserUpdated($existingDevice->fresh()));
        }

        return response()->json([
            'success' => true,
            'message' => 'Device restored and user ID assigned',
            'data'    => []
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Device $device
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(Device $device): JsonResponse
    {
        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device has been marked as deleted',
            'data'    => []
        ]);
    }
}
