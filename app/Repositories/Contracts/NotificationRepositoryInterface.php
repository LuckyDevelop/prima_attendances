<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    public function paginateByUser(int $userId, array $filters): LengthAwarePaginator;

    public function findForUser(int $id, int $userId): ?Notification;

    public function markAsRead(Notification $notification): Notification;

    public function markAllAsRead(int $userId): int;

    public function getUnreadCount(int $userId): int;
}
