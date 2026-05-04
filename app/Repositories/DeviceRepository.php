<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Device;
use App\Models\User;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DeviceRepository implements DeviceRepositoryInterface
{
    public function getByUser(int $userId): Collection
    {
        return Device::where('user_id', $userId)
            ->orderByDesc('last_login')
            ->get();
    }

    public function findForUser(int $id, int $userId): ?Device
    {
        return Device::where('id', $id)->where('user_id', $userId)->first();
    }

    public function upsert(User $user, array $data): Device
    {
        return Device::updateOrCreate(
            [
                'user_id'   => $user->id,
                'device_id' => $data['device_id'],
            ],
            [
                'device_name' => $data['device_name'],
                'platform'    => $data['platform'],
                'fcm_token'   => $data['fcm_token'] ?? null,
                'is_active'   => true,
                'last_login'  => now(),
            ]
        );
    }

    public function updateFcmToken(int $userId, string $deviceId, string $fcmToken): bool
    {
        return Device::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->update(['fcm_token' => $fcmToken]) > 0;
    }

    public function deactivate(Device $device): bool
    {
        return $device->update(['is_active' => false, 'fcm_token' => null]);
    }
}
