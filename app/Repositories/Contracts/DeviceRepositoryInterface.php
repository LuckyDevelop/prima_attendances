<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface DeviceRepositoryInterface
{
    public function getByUser(int $userId): Collection;

    public function findForUser(int $id, int $userId): ?Device;

    public function upsert(User $user, array $data): Device;

    public function updateFcmToken(int $userId, string $deviceId, string $fcmToken): bool;

    public function deactivate(Device $device): bool;
}
