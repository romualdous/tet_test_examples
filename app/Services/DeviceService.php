<?php

namespace App\Services;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceService
{
    /**
     * Invalidate specific device ID's
     * independent from the user that's using them.
     *
     * @param array $exceptFor Devices ID's for devices to keep active
     * @return bool
     */
    public function invalidateAllDevices(array $exceptFor = []): bool
    {
        if (! $exceptFor) {
            // TODO use cursor for large datasets
            return Device::query()->delete();
        }

        return Device::withTrashed()
            ->whereNotIn('device_id', $exceptFor)
            ->delete();
    }

    /**
     * Invalidate all or specific user devices.
     *
     * @param User $user
     * @param array $exceptFor Devices ID's for devices to keep active
     * @return bool
     */
    public function invalidateUserDevices(User $user, array $exceptFor = []): bool
    {
        if (! $exceptFor) {
            return $user->devices()->delete();
        }

        $query = $user->devices()->withTrashed()->whereNotIn('device_id', $exceptFor);

        $this->invalidateAssociatedTokens($user, $query->pluck('device_id')->toArray());

        return $query->delete();
    }

    public function invalidateAssociatedTokens(User $user, array $deviceIds): void
    {
        $user->tokens->filter(function (PersonalAccessToken $token) use ($user, $deviceIds) {
            $deviceIDinToken = Arr::last(explode('_', $token->name));

            return Str::startsWith($token->name, "user_{$user->id}")
                && in_array($deviceIDinToken, $deviceIds);
        })->each(fn (PersonalAccessToken $token) => $token->delete());
    }
}
