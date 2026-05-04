<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function paginateByUser(int $userId, array $filters): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $userId)
            ->orderByDesc('created_at');

        if (isset($filters['is_read'])) {
            $query->where('is_read', (bool) $filters['is_read']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function findForUser(int $id, int $userId): ?Notification
    {
        return Notification::where('id', $id)->where('user_id', $userId)->first();
    }

    public function markAsRead(Notification $notification): Notification
    {
        $notification->update(['is_read' => true]);

        return $notification;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->where('is_read', false)->count();
    }
}
